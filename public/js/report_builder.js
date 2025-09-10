document.addEventListener('DOMContentLoaded', function () {
    // Element references
    const availableColumnsEl = document.getElementById('available-columns');
    const selectedColumnsEl = document.getElementById('selected-columns');
    const addColBtn = document.getElementById('add-col');
    const addAllColsBtn = document.getElementById('add-all-cols');
    const removeColBtn = document.getElementById('remove-col');
    const removeAllColsBtn = document.getElementById('remove-all-cols');

    const filterContainer = document.getElementById('filterContainer');
    const addFilterBtn = document.getElementById('addFilterBtn');
    const generateReportBtn = document.getElementById('generateReportBtn');
    const exportXlsxBtn = document.getElementById('exportXlsxBtn');
    const resultsContainer = document.getElementById('resultsContainer');
    const resultsPlaceholder = document.getElementById('resultsPlaceholder');
    const resultsTable = document.getElementById('resultsTable');
    const paginationContainer = document.getElementById('paginationContainer');
    const filterTemplate = document.getElementById('filterTemplate');

    let reportColumns = []; // To store the dictionary columns
    let fullReportData = []; // To store the complete dataset for pagination/export
    let currentPage = 1;
    const rowsPerPage = 15;

    // --- Initialization ---

    new Sortable(selectedColumnsEl, {
        animation: 150,
        ghostClass: 'selected'
    });

    function populateSelectors(columns) {
        availableColumnsEl.innerHTML = '';
        columns.forEach(col => {
            const item = document.createElement('div');
            item.className = 'list-item';
            item.dataset.key = col.key;
            item.textContent = col.friendly_name;
            availableColumnsEl.appendChild(item);
        });
        // Add first filter row automatically
        addFilterRow();
    }

    function addFilterRow() {
        const newFilter = filterTemplate.content.cloneNode(true);
        const columnDropdown = newFilter.querySelector('.filter-column');

        reportColumns.forEach(col => {
            const option = document.createElement('option');
            option.value = col.key;
            option.textContent = col.friendly_name;
            columnDropdown.appendChild(option);
        });

        // Add date picker for date fields
        const valueInput = newFilter.querySelector('.filter-value');
        columnDropdown.addEventListener('change', () => {
            const selectedKey = columnDropdown.value;
            const selectedCol = reportColumns.find(c => c.key === selectedKey);
            if (selectedCol && selectedCol.type === 'date') {
                valueInput.type = 'date';
            } else {
                valueInput.type = 'text';
            }
        });

        filterContainer.appendChild(newFilter);
    }

    // Fetch dictionary and populate initial UI
    fetch('../src/ajax/get_reporting_dictionary.php')
        .then(response => response.json())
        .then(data => {
            reportColumns = data;
            populateSelectors(reportColumns);
        })
        .catch(error => {
            console.error('Error fetching reporting dictionary:', error);
            alert('No se pudo cargar la configuración de reportes.');
        });


    // --- Event Listeners ---

    function moveItems(from, to, items) {
        items.forEach(item => {
            item.classList.remove('selected');
            to.appendChild(item);
        });
    }

    addColBtn.addEventListener('click', () => {
        moveItems(availableColumnsEl, selectedColumnsEl, availableColumnsEl.querySelectorAll('.list-item.selected'));
    });
    addAllColsBtn.addEventListener('click', () => {
        moveItems(availableColumnsEl, selectedColumnsEl, availableColumnsEl.querySelectorAll('.list-item'));
    });
    removeColBtn.addEventListener('click', () => {
        moveItems(selectedColumnsEl, availableColumnsEl, selectedColumnsEl.querySelectorAll('.list-item.selected'));
    });
    removeAllColsBtn.addEventListener('click', () => {
        moveItems(selectedColumnsEl, availableColumnsEl, selectedColumnsEl.querySelectorAll('.list-item'));
    });

    document.getElementById('dual-list-container').addEventListener('click', function(e) {
        if (e.target.classList.contains('list-item')) {
            e.target.classList.toggle('selected');
        }
    });

    addFilterBtn.addEventListener('click', addFilterRow);

    filterContainer.addEventListener('click', function (e) {
        if (e.target.closest('.remove-filter-btn')) {
            e.target.closest('.filter-row').remove();
        }
    });

    generateReportBtn.addEventListener('click', function () {
        // 1. Collect selected columns
        const selectedColumns = Array.from(selectedColumnsEl.querySelectorAll('.list-item')).map(item => item.dataset.key);
        if (selectedColumns.length === 0) {
            alert('Por favor, seleccione al menos una columna.');
            return;
        }

        // 2. Collect filters
        const filters = [];
        document.querySelectorAll('.filter-row').forEach(row => {
            const column = row.querySelector('.filter-column').value;
            const operator = row.querySelector('.filter-operator').value;
            const value = row.querySelector('.filter-value').value;
            if (column && value) { // Only add filter if value is not empty
                filters.push({ column, operator, value });
            }
        });

        // 3. Send to backend
        generateReportBtn.disabled = true;
        generateReportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
        resultsPlaceholder.textContent = 'Cargando...';
        resultsTable.style.display = 'none';
        exportXlsxBtn.style.display = 'none';


        fetch('../src/ajax/get_dynamic_report.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ columns: selectedColumns, filters: filters })
        })
        .then(response => response.json())
        .then(result => {
            if (result.error) {
                throw new Error(result.error);
            }
            renderResults(result.data, true); // This is a new report
        })
        .catch(error => {
            console.error('Error generating report:', error);
            alert(`Error al generar el reporte: ${error.message}`);
            resultsPlaceholder.textContent = 'Ocurrió un error al generar el reporte.';
        })
        .finally(() => {
            generateReportBtn.disabled = false;
            generateReportBtn.innerHTML = '<i class="fas fa-cogs"></i> Generar Reporte';
        });
    });

    exportXlsxBtn.addEventListener('click', function() {
        if (fullReportData.length > 0) {
            exportToXlsx(fullReportData);
        }
    });

    paginationContainer.addEventListener('click', function(e) {
        e.preventDefault();
        if (e.target.tagName === 'A') {
            const page = parseInt(e.target.dataset.page, 10);
            if (page) {
                currentPage = page;
                renderResults(fullReportData, false); // Not a new report
            }
        }
    });


    // --- Rendering & Export ---

    function renderResults(data, isNewReport = false) {
        if (isNewReport) {
            fullReportData = data; // Store full dataset
            currentPage = 1; // Reset to first page
        }

        const tbody = resultsTable.querySelector('tbody');
        const thead = resultsTable.querySelector('thead');

        // Clear previous results
        thead.innerHTML = '';
        tbody.innerHTML = '';
        paginationContainer.innerHTML = '';
        paginationContainer.style.display = 'none';


        if (data.length === 0) {
            resultsPlaceholder.textContent = 'La consulta no devolvió resultados.';
            resultsPlaceholder.style.display = '';
            resultsTable.style.display = 'none';
            exportXlsxBtn.style.display = 'none';
            return;
        }

        resultsPlaceholder.style.display = 'none';
        resultsTable.style.display = '';
        exportXlsxBtn.style.display = '';

        // Create header
        const headers = Object.keys(data[0]);
        const headerRow = document.createElement('tr');
        headers.forEach(headerText => {
            const th = document.createElement('th');
            th.textContent = headerText;
            headerRow.appendChild(th);
        });
        thead.appendChild(headerRow);

        // Paginate and create body
        const startIndex = (currentPage - 1) * rowsPerPage;
        const endIndex = startIndex + rowsPerPage;
        const paginatedData = data.slice(startIndex, endIndex);

        paginatedData.forEach(row => {
            const tr = document.createElement('tr');
            headers.forEach(header => {
                const td = document.createElement('td');
                td.textContent = row[header];
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });

        renderPagination(data.length, currentPage);
    }

    function renderPagination(totalRows, page) {
        const pageCount = Math.ceil(totalRows / rowsPerPage);
        if (pageCount <= 1) return;

        paginationContainer.style.display = 'block';
        const ul = paginationContainer.querySelector('ul');
        ul.innerHTML = '';

        for (let i = 1; i <= pageCount; i++) {
            const li = document.createElement('li');
            li.className = `page-item ${i === page ? 'active' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.dataset.page = i;
            a.textContent = i;
            li.appendChild(a);
            ul.appendChild(li);
        }
    }

    function exportToXlsx(data) {
        // Create a new workbook
        const wb = XLSX.utils.book_new();

        // Convert the array of objects to a worksheet
        const ws = XLSX.utils.json_to_sheet(data);

        // Add the worksheet to the workbook
        XLSX.utils.book_append_sheet(wb, ws, 'Reporte');

        // Generate the .xlsx file and trigger a download
        XLSX.writeFile(wb, 'ReporteDinamico.xlsx');
    }
});

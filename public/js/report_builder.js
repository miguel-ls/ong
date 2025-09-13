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

    // Template UI Elements
    const templateSelect = document.getElementById('template-select');
    const loadTemplateBtn = document.getElementById('load-template-btn');
    const deleteTemplateBtn = document.getElementById('delete-template-btn');
    const templateNameInput = document.getElementById('template-name-input');
    const saveTemplateBtn = document.getElementById('save-template-btn');

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
        // 1. Create worksheet from data
        const ws = XLSX.utils.json_to_sheet(data);

        // 2. Set worksheet properties
        ws['!autofilter'] = { ref: ws['!ref'] };
        ws['!view'] = { state: 'frozen', ySplit: 1 };

        // 3. Define styles with a blue color scheme
        const borderStyle = { style: 'thin', color: { rgb: "FF000000" } };
        const headerStyle = {
            font: { bold: true, color: { rgb: "FFFFFFFF" } },
            fill: { fgColor: { rgb: "FF4F81BD" } }, // Medium Blue
            border: {
                top: borderStyle,
                bottom: borderStyle,
                left: borderStyle,
                right: borderStyle
            }
        };
        const evenRowStyle = {
            border: {
                top: borderStyle,
                bottom: borderStyle,
                left: borderStyle,
                right: borderStyle
            }
        };
        const oddRowStyle = {
            fill: { fgColor: { rgb: "FFDCE6F1" } }, // Light Blue
            border: {
                top: borderStyle,
                bottom: borderStyle,
                left: borderStyle,
                right: borderStyle
            }
        };

        // 4. Apply styles and calculate widths
        const range = XLSX.utils.decode_range(ws['!ref']);
        let colWidths = [];

        for (let C = range.s.c; C <= range.e.c; ++C) {
            let max_width = 0;
            for (let R = range.s.r; R <= range.e.r; ++R) {
                const cell_ref = XLSX.utils.encode_cell({ c: C, r: R });
                if (!ws[cell_ref]) continue; // Skip empty cells

                // Apply style
                if (R === 0) { // Header row
                    ws[cell_ref].s = headerStyle;
                } else { // Data rows
                    ws[cell_ref].s = (R % 2 !== 0) ? oddRowStyle : evenRowStyle;
                }

                // Calculate width
                if(ws[cell_ref] && ws[cell_ref].v) {
                    const cell_text_length = String(ws[cell_ref].v).length;
                    max_width = Math.max(max_width, cell_text_length);
                }
            }
            const header_cell_ref = XLSX.utils.encode_cell({ c: C, r: 0 });
            const header_text_length = ws[header_cell_ref] ? String(ws[header_cell_ref].v).length : 0;
            max_width = Math.max(max_width, header_text_length);

            colWidths[C] = { wch: max_width + 2 };
        }
        ws['!cols'] = colWidths;

        // 5. Create workbook and export
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Reporte");
        XLSX.writeFile(wb, "ReporteDinamico.xlsx", { bookSST: true });
    }

    // --- Template Management ---

    function loadTemplates(selectId = null) {
        fetch('../src/ajax/plantillas_reporte_process.php?action=obtener_todas')
            .then(response => response.json())
            .then(result => {
                if (!result.success) throw new Error(result.error);

                templateSelect.innerHTML = '<option value="" selected>-- Seleccione una plantilla --</option>'; // Reset
                result.templates.forEach(template => {
                    const option = document.createElement('option');
                    option.value = template.id;
                    option.textContent = template.nombre_plantilla;
                    templateSelect.appendChild(option);
                });

                if (selectId) {
                    templateSelect.value = selectId;
                }
            })
            .catch(error => {
                console.error('Error loading templates:', error);
                alert('No se pudieron cargar las plantillas.');
            });
    }

    saveTemplateBtn.addEventListener('click', function() {
        const name = templateNameInput.value.trim();
        if (!name) {
            alert('Por favor, ingrese un nombre para la plantilla.');
            return;
        }

        const selectedColumns = Array.from(selectedColumnsEl.querySelectorAll('.list-item')).map(item => item.dataset.key);

        fetch('../src/ajax/plantillas_reporte_process.php?action=guardar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name, columns: selectedColumns })
        })
        .then(response => response.json())
        .then(result => {
            if (!result.success) throw new Error(result.error);
            alert('Plantilla guardada con éxito.');
            templateNameInput.value = '';
            loadTemplates(result.new_id); // Refresh list and select the new one
        })
        .catch(error => {
            console.error('Error saving template:', error);
            alert(`Error al guardar la plantilla: ${error.message}`);
        });
    });

    loadTemplateBtn.addEventListener('click', function() {
        const templateId = templateSelect.value;
        if (!templateId) {
            alert('Por favor, seleccione una plantilla para cargar.');
            return;
        }

        fetch(`../src/ajax/plantillas_reporte_process.php?action=obtener_una&id=${templateId}`)
            .then(response => response.json())
            .then(result => {
                if (!result.success) throw new Error(result.error);

                // 1. Reset current selection by moving all selected columns back to available
                const allSelectedItems = selectedColumnsEl.querySelectorAll('.list-item');
                if (allSelectedItems.length > 0) {
                    moveItems(selectedColumnsEl, availableColumnsEl, allSelectedItems);
                }

                // 2. Load new columns from template
                const columnsToSelect = result.columns || [];

                columnsToSelect.forEach(columnKey => {
                    const columnElement = availableColumnsEl.querySelector(`.list-item[data-key="${columnKey}"]`);
                    if (columnElement) {
                        moveItems(availableColumnsEl, selectedColumnsEl, [columnElement]);
                    }
                });

                // Also copy name to input field for easy updating
                templateNameInput.value = templateSelect.options[templateSelect.selectedIndex].text;
            })
            .catch(error => {
                console.error('Error loading template:', error);
                alert(`Error al cargar la plantilla: ${error.message}`);
            });
    });

    deleteTemplateBtn.addEventListener('click', function() {
        const templateId = templateSelect.value;
        if (!templateId) {
            alert('Por favor, seleccione una plantilla para eliminar.');
            return;
        }

        if (!confirm('¿Está seguro de que desea eliminar la plantilla seleccionada? Esta acción no se puede deshacer.')) {
            return;
        }

        fetch('../src/ajax/plantillas_reporte_process.php?action=eliminar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: templateId })
        })
        .then(response => response.json())
        .then(result => {
            if (!result.success) throw new Error(result.error);
            alert(result.message);
            loadTemplates(); // Refresh list
        })
        .catch(error => {
            console.error('Error deleting template:', error);
            alert(`Error al eliminar la plantilla: ${error.message}`);
        });
    });

    // Initial load of templates
    loadTemplates();

});

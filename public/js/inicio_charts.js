document.addEventListener('DOMContentLoaded', function () {
    const filtroAnio = document.getElementById('filtroAnio');
    const filtroMes = document.getElementById('filtroMes');
    const filtroCentroCosto = document.getElementById('filtroCentroCosto');

    const ctxBar = document.getElementById('barChart').getContext('2d');
    const ctxPie1 = document.getElementById('pieChart1').getContext('2d');
    const ctxPie2 = document.getElementById('pieChart2').getContext('2d');

    let barChart, pieChart1, pieChart2;

    // --- Inicialización de Filtros ---

    function poblarFiltroAnio() {
        fetch('../src/ajax/get_years.php')
            .then(response => response.json())
            .then(data => {
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.anio;
                    option.textContent = item.anio;
                    filtroAnio.appendChild(option);
                });
                // Una vez poblado, actualizamos todos los gráficos
                actualizarGraficos();
            });
    }

    function poblarFiltroCentroCosto() {
        fetch('../src/ajax/get_centros_costos.php')
            .then(response => response.json())
            .then(data => {
                const optionTodos = document.createElement('option');
                optionTodos.value = '';
                optionTodos.textContent = 'Todos';
                filtroCentroCosto.appendChild(optionTodos);

                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.nombre;
                    filtroCentroCosto.appendChild(option);
                });
            });
    }

    function setMesActual() {
        const mesActual = new Date().getMonth() + 1;
        filtroMes.value = mesActual;
    }

    // --- Lógica de Gráficos ---

    function actualizarGraficos() {
        const anio = filtroAnio.value;
        const mes = filtroMes.value;
        const idCentroCosto = filtroCentroCosto.value;

        actualizarBarChart(anio);
        actualizarPieChart1(anio, mes);
        if (idCentroCosto) {
            actualizarPieChart2(anio, mes, idCentroCosto);
        } else {
            // Si no hay centro de costo seleccionado, limpiar el gráfico 2 o mostrar un mensaje
            if (pieChart2) pieChart2.destroy();
        }
    }

    function actualizarBarChart(anio) {
        fetch(`../src/ajax/get_reporte_bar_chart.php?anio=${anio}`)
            .then(response => response.json())
            .then(data => {
                if (barChart) barChart.destroy();

                const labels = [...new Set(data.map(item => getMonthName(item.mes)))];
                const datasets = [];
                const centrosCosto = [...new Set(data.map(item => item.nombre_centro_costo))];

                centrosCosto.forEach(cc => {
                    const dataPorCC = labels.map(label => {
                        const mesNum = getMonthNumber(label);
                        const item = data.find(d => d.mes == mesNum && d.nombre_centro_costo === cc);
                        return item ? parseFloat(item.total_soles) : 0;
                    });
                    datasets.push({
                        label: cc,
                        data: dataPorCC,
                        backgroundColor: getRandomColor(),
                    });
                });

                barChart = new Chart(ctxBar, {
                    type: 'bar',
                    data: { labels, datasets },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            });
    }

    function actualizarPieChart1(anio, mes) {
        fetch(`../src/ajax/get_reporte_pie_chart1.php?anio=${anio}&mes=${mes}`)
            .then(response => response.json())
            .then(data => {
                if (pieChart1) pieChart1.destroy();

                const labels = data.map(item => item.nombre_centro_costo);
                const importes = data.map(item => parseFloat(item.total_soles));

                pieChart1 = new Chart(ctxPie1, {
                    type: 'pie',
                    data: {
                        labels,
                        datasets: [{
                            data: importes,
                            backgroundColor: labels.map(() => getRandomColor()),
                        }]
                    },
                    options: {
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        let value = context.raw || 0;
                                        let total = context.chart.getDatasetMeta(0).total;
                                        let percentage = ((value / total) * 100).toFixed(2);
                                        return `${label}: S/ ${value.toFixed(2)} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            });
    }

    function actualizarPieChart2(anio, mes, idCentroCosto) {
        fetch(`../src/ajax/get_reporte_pie_chart2.php?anio=${anio}&mes=${mes}&id_centro_costo=${idCentroCosto}`)
            .then(response => response.json())
            .then(data => {
                if (pieChart2) pieChart2.destroy();

                const labels = data.map(item => item.nombre_tipo_documento);
                const importes = data.map(item => parseFloat(item.total_soles));

                pieChart2 = new Chart(ctxPie2, {
                    type: 'pie',
                    data: {
                        labels,
                        datasets: [{
                            data: importes,
                            backgroundColor: labels.map(() => getRandomColor()),
                        }]
                    },
                     options: {
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        let value = context.raw || 0;
                                        let total = context.chart.getDatasetMeta(0).total;
                                        let percentage = ((value / total) * 100).toFixed(2);
                                        return `${label}: S/ ${value.toFixed(2)} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            });
    }

    // --- Helpers ---
    function getRandomColor() {
        const r = Math.floor(Math.random() * 255);
        const g = Math.floor(Math.random() * 255);
        const b = Math.floor(Math.random() * 255);
        return `rgba(${r}, ${g}, ${b}, 0.7)`;
    }

    function getMonthName(monthNumber) {
        const monthNames = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
        return monthNames[monthNumber - 1];
    }

    function getMonthNumber(monthName) {
        const monthNames = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
        return monthNames.indexOf(monthName) + 1;
    }


    // --- Event Listeners e Inicialización ---
    filtroAnio.addEventListener('change', actualizarGraficos);
    filtroMes.addEventListener('change', actualizarGraficos);
    filtroCentroCosto.addEventListener('change', actualizarGraficos);

    poblarFiltroAnio();
    poblarFiltroCentroCosto();
    setMesActual();
});

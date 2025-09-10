<header class="mb-4">
    <h1>Generador de Reportes Dinámicos</h1>
</header>

<section class="report-builder">
    <!-- Fila para Selectores y Filtros -->
    <div class="row">
        <!-- Selector de Columnas -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">1. Seleccione y Ordene las Columnas</h5>
                </div>
                <div class="card-body">
                    <style>
                        .dual-list-box { display: flex; justify-content: space-between; }
                        .dual-list-box .list-box { width: 47%; border: 1px solid #ccc; padding: 5px; height: 250px; overflow-y: auto; }
                        .dual-list-box .list-box .list-item { padding: 5px; border-bottom: 1px solid #eee; cursor: grab; user-select: none; }
                        .dual-list-box .list-box .list-item:last-child { border-bottom: none; }
                        .dual-list-box .list-box .list-item.selected { background-color: #dcedff; }
                        .dual-list-box .actions { width: 10%; display: flex; flex-direction: column; justify-content: center; align-items: center; }
                        .dual-list-box .actions button { margin-bottom: 10px; }
                    </style>
                    <div class="dual-list-box">
                        <div class="available-cols">
                            <strong>Disponibles</strong>
                            <div id="available-columns" class="list-box"></div>
                        </div>
                        <div class="actions">
                            <button id="add-col" class="btn btn-sm btn-outline-secondary">&gt;</button>
                            <button id="add-all-cols" class="btn btn-sm btn-outline-secondary">&gt;&gt;</button>
                            <button id="remove-col" class="btn btn-sm btn-outline-secondary">&lt;</button>
                            <button id="remove-all-cols" class="btn btn-sm btn-outline-secondary">&lt;&lt;</button>
                        </div>
                        <div class="selected-cols">
                            <strong>Seleccionadas (arrastre para ordenar)</strong>
                            <div id="selected-columns" class="list-box"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Constructor de Filtros -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">2. Construya los Filtros</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Añada condiciones para filtrar los datos. Todos los filtros se aplican con un "Y" lógico.</p>
                    <div id="filterContainer">
                        <!-- Los filtros se añadirán aquí dinámicamente -->
                    </div>
                    <button id="addFilterBtn" class="btn btn-sm btn-outline-primary mt-2">
                        <i class="fas fa-plus"></i> Añadir Filtro
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Botón de Generar Reporte -->
    <div class="text-center my-4">
        <button id="generateReportBtn" class="btn btn-primary btn-lg">
            <i class="fas fa-cogs"></i> Generar Reporte
        </button>
        <button id="exportXlsxBtn" class="btn btn-success btn-lg" style="display: none;">
            <i class="fas fa-file-excel"></i> Exportar a Excel
        </button>
    </div>


    <!-- Grilla de Resultados -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Resultados</h5>
        </div>
        <div class="card-body">
            <div id="resultsContainer" class="table-responsive">
                <p id="resultsPlaceholder">Los resultados de su consulta aparecerán aquí. Por favor, genere un reporte.</p>
                <table id="resultsTable" class="table table-striped table-bordered" style="display: none;">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
             <!-- Paginación -->
            <nav id="paginationContainer" aria-label="Page navigation" style="display: none;">
                <ul class="pagination justify-content-center">
                    <!-- Los controles de paginación se añadirán aquí -->
                </ul>
            </nav>
        </div>
    </div>
</section>

<!-- Template para un nuevo filtro -->
<template id="filterTemplate">
    <div class="filter-row input-group mb-2">
        <select class="form-select filter-column"></select>
        <select class="form-select filter-operator" style="max-width: 80px;">
            <option value="=">=</option>
            <option value="!=">!=</option>
            <option value=">">&gt;</option>
            <option value="<">&lt;</option>
            <option value=">=">&gt;=</option>
            <option value="<=">&lt;=</option>
            <option value="LIKE">Contiene</option>
            <option value="NOT LIKE">No Contiene</option>
        </select>
        <input type="text" class="form-control filter-value" placeholder="Valor">
        <button class="btn btn-outline-danger remove-filter-btn" type="button">
            <i class="fas fa-times"></i>
        </button>
    </div>
</template>

<script src="js/report_builder.js"></script>

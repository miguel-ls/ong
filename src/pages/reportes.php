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
                        .list-box { width: 100%; border: 1px solid #ccc; padding: 5px; height: 250px; overflow-y: auto; }
                        .list-item { padding: 5px; border-bottom: 1px solid #eee; cursor: grab; user-select: none; }
                        .list-item:last-child { border-bottom: none; }
                        .list-item.selected { background-color: #dcedff; }
                        .actions-col button { margin-bottom: 10px; width: 50px; }
                    </style>
                    <div class="row">
                        <div class="col-md-5">
                            <strong>Disponibles</strong>
                            <div id="available-columns" class="list-box"></div>
                        </div>
                        <div class="col-md-2 d-flex flex-column justify-content-center align-items-center actions-col">
                            <button id="add-col" class="btn btn-sm btn-outline-secondary" title="Añadir">&gt;</button>
                            <button id="add-all-cols" class="btn btn-sm btn-outline-secondary" title="Añadir Todos">&gt;&gt;</button>
                            <button id="remove-col" class="btn btn-sm btn-outline-secondary" title="Quitar">&lt;</button>
                            <button id="remove-all-cols" class="btn btn-sm btn-outline-secondary" title="Quitar Todos">&lt;&lt;</button>
                        </div>
                        <div class="col-md-5">
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

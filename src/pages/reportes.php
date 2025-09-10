<header class="mb-4">
    <h1>Generador de Reportes Dinámicos</h1>
</header>

<section class="report-builder">
    <!-- Fila para Selectores y Filtros -->
    <div class="row">
        <!-- Selector de Columnas -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">1. Seleccione las Columnas</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Elija los campos que desea incluir en el reporte.</p>
                    <select id="columnSelector" class="form-control" multiple style="height: 200px;"></select>
                </div>
            </div>
        </div>

        <!-- Constructor de Filtros -->
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
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
        <button id="exportCsvBtn" class="btn btn-success btn-lg" style="display: none;">
            <i class="fas fa-file-csv"></i> Exportar a CSV
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

<header class="mb-4">
    <h1>Dashboard</h1>
</header>

<!-- Filtros -->
<section class="card mb-4">
    <div class="card-header">
        Filtros
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label for="filtroAnio" class="form-label">Año</label>
                <select id="filtroAnio" class="form-select"></select>
            </div>
            <div class="col-md-4">
                <label for="filtroMes" class="form-label">Mes</label>
                <select id="filtroMes" class="form-select">
                    <option value="1">Enero</option>
                    <option value="2">Febrero</option>
                    <option value="3">Marzo</option>
                    <option value="4">Abril</option>
                    <option value="5">Mayo</option>
                    <option value="6">Junio</option>
                    <option value="7">Julio</option>
                    <option value="8">Agosto</option>
                    <option value="9">Septiembre</option>
                    <option value="10">Octubre</option>
                    <option value="11">Noviembre</option>
                    <option value="12">Diciembre</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="filtroCentroCosto" class="form-label">Centro de Costo</label>
                <select id="filtroCentroCosto" class="form-select"></select>
            </div>
        </div>
    </div>
</section>

<!-- Gráfico de Barras -->
<div class="row justify-content-center">
    <div class="col-lg-8">
        <section class="card mb-2">
            <div class="card-header">
                Total de Documentos en Soles por Mes y Centro de Costo
            </div>
            <div class="card-body">
                <canvas id="barChart"></canvas>
            </div>
        </section>
    </div>
</div>

<!-- Gráficos Circulares -->
<section class="row justify-content-center">
    <div class="col-lg-4 mb-2">
        <div class="card h-100">
            <div class="card-header">
                Importes en Soles por Centro de Costo (Mes Actual)
            </div>
            <div class="card-body">
                <canvas id="pieChart1"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-2">
        <div class="card h-100">
            <div class="card-header">
                Importes por Tipo de Documento (Mes y C.Costo Actual)
            </div>
            <div class="card-body">
                <canvas id="pieChart2"></canvas>
            </div>
        </div>
    </div>
</section>

<script src="js/inicio_charts.js"></script>

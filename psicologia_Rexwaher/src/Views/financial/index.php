<?php require __DIR__ . '/../layouts/header.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-3 mb-4 border-bottom">
        <div>
            <h1 class="h2 fw-bold text-primary">Mis Finanzas</h1>
            <p class="text-muted">Resumen de tus ingresos netos (después de comisiones).</p>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-download me-1"></i> Descargar Informe
            </button>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-4 mb-4">
        <!-- Mes Actual -->
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Este Mes (<?= date('M') ?>)</h6>
                    <div class="d-flex align-items-center">
                        <h2 class="mb-0 fw-bold text-dark"><?= number_format($stats['month_current'], 2) ?> €</h2>
                        <span class="badge ms-3 <?= $monthGrowth >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                            <i class="bi <?= $monthGrowth >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' ?>"></i> 
                            <?= number_format(abs($monthGrowth), 1) ?>%
                        </span>
                    </div>
                    <small class="text-muted mt-2 d-block">vs <?= number_format($stats['month_last_year'], 2) ?> € año anterior</small>
                </div>
            </div>
        </div>

        <!-- Año Actual -->
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Acumulado Año <?= $currentYear ?></h6>
                    <div class="d-flex align-items-center">
                        <h2 class="mb-0 fw-bold text-dark"><?= number_format($stats['year_current'], 2) ?> €</h2>
                        <span class="badge ms-3 <?= $yearGrowth >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                            <i class="bi <?= $yearGrowth >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' ?>"></i> 
                            <?= number_format(abs($yearGrowth), 1) ?>%
                        </span>
                    </div>
                    <small class="text-muted mt-2 d-block">vs <?= number_format($stats['year_last'], 2) ?> € año anterior</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico Comparativo -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Evolución de Ingresos</h5>
            <div class="small text-muted">
                <span class="me-3"><i class="bi bi-circle-fill text-primary"></i> <?= $currentYear ?></span>
                <span><i class="bi bi-circle-fill text-secondary"></i> <?= $lastYear ?></span>
            </div>
        </div>
        <div class="card-body">
            <canvas id="financeChart" height="100"></canvas>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('financeChart').getContext('2d');
const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
        datasets: [
            {
                label: '<?= $currentYear ?>',
                data: <?= json_encode(array_values($stats['monthly_data_current'])) ?>,
                borderColor: '#0d6efd', // Primary Blue
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true
            },
            {
                label: '<?= $lastYear ?>',
                data: <?= json_encode(array_values($stats['monthly_data_last'])) ?>,
                borderColor: '#adb5bd', // Grey
                borderWidth: 2,
                borderDash: [5, 5],
                tension: 0.4,
                fill: false
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + ' €';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { borderDash: [2, 4] }
            },
            x: {
                grid: { display: false }
            }
        }
    }
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
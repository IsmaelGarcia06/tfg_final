<?php require __DIR__ . '/../layouts/header.php'; ?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Panel de Comisiones y Liquidaciones</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#newCommissionTypeModal">
            <i class="bi bi-plus-circle"></i> Nuevo Tipo de Comisión
        </button>
        <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">Imprimir Informe</button>
    </div>
</div>

<!-- Filtros -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('/admin/commissions') ?>" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Profesional</label>
                <select name="professional_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($professionals as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= (isset($_GET['professional_id']) && $_GET['professional_id'] == $p['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Desde</label>
                <input type="date" name="start_date" class="form-control" value="<?= $_GET['start_date'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Hasta</label>
                <input type="date" name="end_date" class="form-control" value="<?= $_GET['end_date'] ?? '' ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<!-- Tarjetas Resumen -->
<?php
    // Evitar errores si $monthlyStats está vacío
    $monthlyStats = $monthlyStats ?? [];
    $totalRevenue = array_sum(array_column($monthlyStats, 'revenue'));
    $totalAdmin = array_sum(array_column($monthlyStats, 'admin_profit'));
    $totalProf = array_sum(array_column($monthlyStats, 'prof_cost'));
?>
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-header">Facturación Total</div>
            <div class="card-body">
                <h4 class="card-title"><?= number_format($totalRevenue, 2) ?> €</h4>
                <p class="card-text small">Ingresos brutos en el periodo.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-header">Ganancia Neta (Admin)</div>
            <div class="card-body">
                <h4 class="card-title"><?= number_format($totalAdmin, 2) ?> €</h4>
                <p class="card-text small">Comisiones retenidas.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-secondary mb-3">
            <div class="card-header">Total a Pagar (Profesionales)</div>
            <div class="card-body">
                <h4 class="card-title"><?= number_format($totalProf, 2) ?> €</h4>
                <p class="card-text small">Monto a liquidar.</p>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Liquidación (A Pagar) -->
<div class="card shadow-sm mb-4 border-warning">
    <div class="card-header bg-warning text-dark fw-bold">
        <i class="bi bi-cash-stack"></i> Liquidación a Pagar por Profesional
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Profesional</th>
                    <th class="text-end">Facturado</th>
                    <th class="text-end">Comisión Clínica</th>
                    <th class="text-end">A Pagar (Neto)</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($profStats)): ?>
                    <?php foreach ($profStats as $name => $stat): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($name) ?></td>
                        <td class="text-end"><?= number_format($stat['revenue'], 2) ?> €</td>
                        <td class="text-end text-success"><?= number_format($stat['admin_profit'], 2) ?> €</td>
                        <td class="text-end fw-bold text-dark bg-light"><?= number_format($stat['prof_cost'], 2) ?> €</td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">No hay datos en este periodo.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Gráfico Mensual -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">Evolución Mensual</h5>
    </div>
    <div class="card-body">
        <canvas id="profitChart" width="400" height="100"></canvas>
    </div>
</div>

<!-- Modal Nuevo Tipo de Comisión -->
<div class="modal fade" id="newCommissionTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Tipo de Comisión</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="commissionTypeForm" onsubmit="createCommissionType(event)">
                    <div class="mb-3">
                        <label class="form-label">Nombre de la Comisión</label>
                        <input type="text" class="form-control" id="cName" placeholder="Ej: Standard, Premium, Beca..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Porcentaje Clínica (%)</label>
                        <input type="number" step="0.01" class="form-control" id="cPercentage" placeholder="Ej: 30" required>
                        <div class="form-text">Es el porcentaje del precio de la sesión que se queda la clínica.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" id="cDescription" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Crear Tipo de Comisión</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
<?php if(!empty($chartLabels)): ?>
const ctx = document.getElementById('profitChart').getContext('2d');
const profitChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [
            {
                label: 'Ganancia Admin',
                data: <?= json_encode($chartAdminData) ?>,
                backgroundColor: '#198754',
            },
            {
                label: 'Pago a Profesional',
                data: <?= json_encode($chartProfData) ?>,
                backgroundColor: '#6c757d',
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            x: { stacked: true },
            y: {
                stacked: true,
                ticks: { callback: function(value) { return value + ' €'; } }
            }
        }
    }
});
<?php endif; ?>

async function createCommissionType(e) {
    e.preventDefault();
    const data = {
        name: document.getElementById('cName').value,
        percentage: document.getElementById('cPercentage').value,
        description: document.getElementById('cDescription').value
    };

    try {
        const res = await fetch(BASE_URL + '/api/admin/commissions', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        if (res.ok) {
            alert('Tipo de comisión creado. Ahora puedes asignarlo a los profesionales en su perfil.');
            location.reload();
        } else {
            const err = await res.json();
            alert('Error: ' + (err.message || 'No se pudo crear la comisión'));
        }
    } catch (err) {
        console.error(err);
        alert('Error de conexión');
    }
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
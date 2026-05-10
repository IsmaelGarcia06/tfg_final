<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Tarifas de: <?= htmlspecialchars($user['name']) ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="/practicas2026/admin/users" class="btn btn-sm btn-outline-secondary me-2">Volver</a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal">
            <i class="bi bi-calendar-plus"></i> Asignar Nueva Tarifa
        </button>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">Historial de Comisiones</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tarifa</th>
                            <th>Porcentaje</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history)): ?>
                            <tr><td colspan="5" class="text-center text-muted">Sin historial de tarifas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($history as $h): 
                                $isActive = $h['end_date'] === null || $h['end_date'] >= date('Y-m-d');
                            ?>
                            <tr class="<?= $isActive ? 'table-success' : '' ?>">
                                <td><?= htmlspecialchars($h['tariff_name']) ?></td>
                                <td class="fw-bold"><?= $h['percentage'] ?>%</td>
                                <td><?= date('d/m/Y', strtotime($h['start_date'])) ?></td>
                                <td><?= $h['end_date'] ? date('d/m/Y', strtotime($h['end_date'])) : '<span class="text-muted">Actualidad</span>' ?></td>
                                <td>
                                    <?php if ($isActive): ?>
                                        <span class="badge bg-success">Activa</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Histórica</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Asignar -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Asignar Tarifa a Profesional</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning small">
                    <i class="bi bi-exclamation-triangle"></i> Al asignar una nueva tarifa, la tarifa actual se cerrará automáticamente el día anterior a la fecha de inicio seleccionada.
                </div>
                <form id="assignForm" onsubmit="assignTariff(event)">
                    <input type="hidden" id="userId" value="<?= $user['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Seleccionar Tarifa</label>
                        <select class="form-select" id="tariffId" required>
                            <?php foreach ($allTariffs as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?> (<?= $t['percentage'] ?>%)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha de Inicio</label>
                        <input type="date" class="form-control" id="startDate" required value="<?= date('Y-m-d') ?>">
                        <div class="form-text">A partir de esta fecha se aplicará el nuevo porcentaje.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Asignar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
async function assignTariff(e) {
    e.preventDefault();
    const data = {
        user_id: document.getElementById('userId').value,
        tariff_id: document.getElementById('tariffId').value,
        start_date: document.getElementById('startDate').value
    };

    const res = await fetch('/practicas2026/api/admin/tariffs/assign', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });

    if (res.ok) {
        location.reload();
    } else {
        alert('Error al asignar tarifa');
    }
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

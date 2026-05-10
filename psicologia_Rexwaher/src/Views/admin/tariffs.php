<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Configuración de Tarifas</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tariffModal">
        <i class="bi bi-plus-lg"></i> Nueva Tarifa
    </button>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">Tipos de Tarifas Disponibles</div>
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Porcentaje (%)</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tariffs as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['name']) ?></td>
                        <td class="fw-bold text-success"><?= $t['percentage'] ?>%</td>
                        <td class="text-muted small"><?= htmlspecialchars($t['description']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-4">
        <div class="alert alert-info">
            <h5><i class="bi bi-info-circle"></i> ¿Cómo funciona?</h5>
            <p>Aquí defines los "tipos" de acuerdos económicos (ej. Estándar, Socio, Especial).</p>
            <p>Luego, en la sección de <strong>Usuarios</strong>, asignas estas tarifas a cada profesional indicando desde qué fecha aplican.</p>
        </div>
    </div>
</div>

<!-- Modal Nueva Tarifa -->
<div class="modal fade" id="tariffModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Tarifa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="tariffForm" onsubmit="createTariff(event)">
                    <div class="mb-3">
                        <label class="form-label">Nombre (Ej: Estándar 2024)</label>
                        <input type="text" class="form-control" id="tName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Porcentaje Comisión (%)</label>
                        <input type="number" step="0.01" class="form-control" id="tPerc" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" id="tDesc"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
async function createTariff(e) {
    e.preventDefault();
    const data = {
        name: document.getElementById('tName').value,
        percentage: document.getElementById('tPerc').value,
        description: document.getElementById('tDesc').value
    };

    const res = await fetch('/practicas2026/api/admin/tariffs', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });

    if (res.ok) {
        location.reload();
    } else {
        alert('Error al crear tarifa');
    }
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

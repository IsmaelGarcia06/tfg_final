<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Catálogo de Servicios</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#serviceModal">
        <i class="bi bi-plus-lg"></i> Nuevo Servicio
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Precio</th>
                    <th>Sesiones</th>
                    <th>Duración</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $s): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($s['name']) ?></td>
                    <td>
                        <?php if ($s['type'] === 'pack'): ?>
                            <span class="badge bg-info text-dark">Bono</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Sesión</span>
                        <?php endif; ?>
                    </td>
                    <td><?= number_format($s['price'], 2) ?> €</td>
                    <td><?= $s['session_count'] ?></td>
                    <td><?= $s['duration_minutes'] ?> min</td>
                    <td><span class="text-success">Activo</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Nuevo Servicio -->
<div class="modal fade" id="serviceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Servicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="serviceForm" onsubmit="createService(event)">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Servicio</label>
                        <input type="text" class="form-control" id="sName" placeholder="Ej: Sesión Individual" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Precio (€)</label>
                            <input type="number" step="0.01" class="form-control" id="sPrice" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" id="sType" onchange="togglePackFields()">
                                <option value="session">Sesión Única</option>
                                <option value="pack">Bono (Pack)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nº Sesiones</label>
                            <input type="number" class="form-control" id="sCount" value="1" min="1" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Duración (min)</label>
                            <input type="number" class="form-control" id="sDuration" value="60">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Guardar Servicio</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function togglePackFields() {
    const type = document.getElementById('sType').value;
    const countInput = document.getElementById('sCount');
    if (type === 'pack') {
        countInput.readOnly = false;
        countInput.value = 5;
    } else {
        countInput.readOnly = true;
        countInput.value = 1;
    }
}

async function createService(e) {
    e.preventDefault();
    const data = {
        name: document.getElementById('sName').value,
        price: document.getElementById('sPrice').value,
        type: document.getElementById('sType').value,
        session_count: document.getElementById('sCount').value,
        duration: document.getElementById('sDuration').value
    };

    const res = await fetch('/practicas2026/api/admin/services', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });

    if (res.ok) {
        location.reload();
    } else {
        alert('Error al crear servicio');
    }
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

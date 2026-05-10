<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestión de Usuarios</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
        <i class="bi bi-person-plus"></i> Nuevo Usuario
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="badge bg-secondary"><?= $u['role'] ?></span></td>
                    <td><?= $u['active'] ? '<span class="text-success">Activo</span>' : '<span class="text-danger">Inactivo</span>' ?></td>
                    <td>
                        <?php if ($u['role'] === 'professional'): ?>
                        <a href="<?= url('/admin/users/' . $u['id'] . '/commissions') ?>" class="btn btn-sm btn-outline-success" title="Gestionar Comisiones">
                            <i class="bi bi-cash-coin"></i> Comisiones
                        </a>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Nuevo Usuario -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="userForm" onsubmit="createUser(event)">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="uName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="uEmail" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="uPass" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <select class="form-select" id="uRole">
                            <option value="professional">Profesional</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Crear</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
async function createUser(e) {
    e.preventDefault();
    const data = {
        name: document.getElementById('uName').value,
        email: document.getElementById('uEmail').value,
        password: document.getElementById('uPass').value,
        role: document.getElementById('uRole').value
    };

    const res = await fetch(BASE_URL + '/api/admin/users', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });

    if (res.ok) {
        location.reload();
    } else {
        alert('Error al crear usuario');
    }
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
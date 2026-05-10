<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-3 mb-4 border-bottom">
        <div>
            <h1 class="h2 fw-bold text-primary">Mi Cuenta</h1>
            <p class="text-muted">Gestiona tus datos personales, seguridad y conexiones.</p>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <?php if ($_GET['msg'] == '2fa_enabled'): ?>
                <i class="bi bi-check-circle-fill me-2"></i> 2FA activado correctamente.
            <?php elseif ($_GET['msg'] == 'google_connected'): ?>
                <i class="bi bi-check-circle-fill me-2"></i> Cuenta de Google Calendar conectada correctamente.
            <?php elseif ($_GET['msg'] == 'google_disconnected'): ?>
                <i class="bi bi-info-circle-fill me-2"></i> Cuenta de Google Calendar desconectada.
            <?php elseif ($_GET['msg'] == 'already_connected'): ?>
                <i class="bi bi-exclamation-triangle-fill me-2"></i> Ya tienes una cuenta de Google conectada.
            <?php else: ?>
                <i class="bi bi-info-circle-fill me-2"></i> Acción realizada.
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Columna Izquierda: Resumen -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4 text-center">
                <div class="card-body py-5">
                    <div class="mb-3">
                        <div class="avatar-circle bg-primary text-white d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px; font-size: 2rem;">
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        </div>
                    </div>
                    <h4 class="card-title fw-bold"><?= htmlspecialchars($user['name']) ?></h4>
                    <p class="text-muted mb-1"><?= htmlspecialchars($user['email']) ?></p>
                    <span class="badge bg-light text-dark border"><?= strtoupper($user['role']) ?></span>
                </div>
            </div>

            <!-- Integraciones -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-plug-fill me-2 text-primary"></i> Integraciones</h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-google fs-3 text-danger me-3"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Google Calendar</h6>
                                <?php if (!empty($user['google_access_token'])): ?>
                                    <small class="text-success"><i class="bi bi-check-circle-fill"></i> Conectado</small>
                                <?php else: ?>
                                    <small class="text-muted">No conectado</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <?php if (empty($user['google_access_token'])): ?>
                                <a href="<?= url('/auth/google') ?>" class="btn btn-sm btn-outline-danger">Conectar</a>
                            <?php else: ?>
                                <form action="<?= url('/auth/google/disconnect') ?>" method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que quieres desconectar tu cuenta de Google Calendar? Dejarás de sincronizar tus citas.');">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Desconectar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Formularios -->
        <div class="col-lg-8">
            
            <!-- 1. Datos Personales -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-person-lines-fill me-2 text-primary"></i> Datos Personales</h5>
                </div>
                <div class="card-body p-4">
                    <form onsubmit="updateProfile(event)">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">Nombre Completo</label>
                                <input type="text" class="form-control" id="pName" value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">Email</label>
                                <input type="email" class="form-control" id="pEmail" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>
                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 2. Seguridad (Password) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-key-fill me-2 text-primary"></i> Contraseña</h5>
                </div>
                <div class="card-body p-4">
                    <form id="passForm" onsubmit="changePassword(event)">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">Contraseña Actual</label>
                                <input type="password" class="form-control" id="currPass" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">Nueva Contraseña</label>
                                <input type="password" class="form-control" id="newPass" required minlength="12">
                            </div>
                        </div>
                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-warning text-white">Actualizar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 3. Doble Factor (2FA) -->
            <div class="card border-0 shadow-sm mb-4 <?= !empty($user['two_factor_enabled']) ? 'border-start border-success border-4' : 'border-start border-warning border-4' ?>">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="fw-bold mb-1">Doble Factor (2FA)</h5>
                            <p class="text-muted mb-0 small">Protege tu cuenta con un código extra.</p>
                        </div>
                        <div>
                            <?php if (!empty($user['two_factor_enabled'])): ?>
                                <form action="<?= url('/auth/2fa/disable') ?>" method="POST" onsubmit="return confirm('¿Desactivar 2FA?');">
                                    <button class="btn btn-outline-danger">Desactivar</button>
                                </form>
                            <?php else: ?>
                                <a href="<?= url('/auth/2fa/setup') ?>" class="btn btn-success">Activar Ahora</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
async function updateProfile(e) {
    e.preventDefault();
    const data = {
        name: document.getElementById('pName').value,
        email: document.getElementById('pEmail').value
    };
    
    const res = await fetch(BASE_URL + '/profile/update', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    
    if (res.ok) {
        alert('Perfil actualizado');
        location.reload();
    } else {
        alert('Error al actualizar');
    }
}

async function changePassword(e) {
    e.preventDefault();
    const data = {
        current_password: document.getElementById('currPass').value,
        new_password: document.getElementById('newPass').value
    };

    const res = await fetch(BASE_URL + '/profile/password', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    
    const json = await res.json();
    if (res.ok) {
        alert('Contraseña cambiada');
        document.getElementById('passForm').reset();
    } else {
        alert('Error: ' + json.message);
    }
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
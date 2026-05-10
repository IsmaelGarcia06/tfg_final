<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Configuración</h1>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-<?= $_GET['msg'] == '2fa_enabled' ? 'success' : 'warning' ?> alert-dismissible fade show" role="alert">
        <?php if ($_GET['msg'] == '2fa_enabled'): ?>
            <i class="bi bi-check-circle-fill me-2"></i> 2FA activado correctamente.
        <?php else: ?>
            <i class="bi bi-exclamation-triangle-fill me-2"></i> 2FA desactivado.
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Columna Izquierda: Perfil y Seguridad -->
    <div class="col-md-6">

        <!-- Tarjeta Perfil -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary"><i class="bi bi-person-badge me-2"></i> Datos de Perfil</h5>
            </div>
            <div class="card-body">
                <form id="profileForm" onsubmit="updateProfile(event)">
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="profileName" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="profileEmail" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <input type="text" class="form-control bg-light" value="<?= ucfirst($user['role']) ?>" readonly>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tarjeta Contraseña -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-danger"><i class="bi bi-shield-lock me-2"></i> Cambiar Contraseña</h5>
            </div>
            <div class="card-body">
                <form id="passwordForm" onsubmit="changePassword(event)">
                    <div class="mb-3">
                        <label class="form-label">Contraseña Actual</label>
                        <input type="password" class="form-control" id="currentPass" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" id="newPass" required minlength="12">
                        <div class="form-text">Mínimo 12 caracteres.</div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-outline-danger">Actualizar Clave</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Columna Derecha: 2FA e Integraciones -->
    <div class="col-md-6">

        <!-- Tarjeta 2FA -->
        <div class="card shadow-sm mb-4 border-start border-4 <?= !empty($user['two_factor_enabled']) ? 'border-success' : 'border-warning' ?>">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-dark"><i class="bi bi-qr-code me-2"></i> Doble Factor (2FA)</h5>
                <?php if (!empty($user['two_factor_enabled'])): ?>
                    <span class="badge bg-success">ACTIVADO</span>
                <?php else: ?>
                    <span class="badge bg-secondary">INACTIVO</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <p class="text-muted">Añade una capa extra de seguridad a tu cuenta requiriendo un código temporal desde tu móvil.</p>

                <?php if (!empty($user['two_factor_enabled'])): ?>
                    <div class="alert alert-success d-flex align-items-center">
                        <i class="bi bi-shield-check fs-4 me-3"></i>
                        <div>Tu cuenta está protegida.</div>
                    </div>
                    <form action="/psicologia_Rexwaher/auth/2fa/disable" method="POST" onsubmit="return confirm('¿Desactivar 2FA? Tu cuenta será menos segura.');">
                        <button type="submit" class="btn btn-danger w-100">Desactivar 2FA</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-light border d-flex align-items-center">
                        <i class="bi bi-phone fs-4 me-3 text-muted"></i>
                        <div>Usa Google Authenticator o Authy.</div>
                    </div>
                    <a href="/psicologia_Rexwaher/auth/2fa/setup" class="btn btn-success w-100">Configurar Ahora</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tarjeta Integraciones -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-info text-dark"><i class="bi bi-grid-3x3-gap me-2"></i> Integraciones</h5>
            </div>
            <div class="list-group list-group-flush">
                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-google fs-4 me-3 text-danger"></i>
                        <div>
                            <h6 class="mb-0">Google Calendar</h6>
                            <small class="text-muted">Sincronizar citas</small>
                        </div>
                    </div>
                    <?php if (!empty($user['google_calendar_id']) && $user['google_calendar_id'] !== 'primary'): ?>
                        <button class="btn btn-sm btn-success disabled"><i class="bi bi-check2"></i> Conectado</button>
                    <?php else: ?>
                        <a href="/psicologia_Rexwaher/auth/google" class="btn btn-sm btn-outline-primary">Conectar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
async function updateProfile(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button');
    const original = btn.innerText;
    btn.disabled = true; btn.innerText = 'Guardando...';

    const data = {
        name: document.getElementById('profileName').value,
        email: document.getElementById('profileEmail').value
    };

    try {
        const res = await fetch('/psicologia_Rexwaher/config/profile', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const json = await res.json();
        
        if (res.ok) {
            alert('Perfil actualizado');
            location.reload();
        } else {
            alert('Error: ' + json.message);
        }
    } catch (err) { console.error(err); }
    finally { btn.disabled = false; btn.innerText = original; }
}

async function changePassword(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button');
    const original = btn.innerText;
    btn.disabled = true; btn.innerText = 'Procesando...';

    const data = {
        current_password: document.getElementById('currentPass').value,
        new_password: document.getElementById('newPass').value
    };

    try {
        const res = await fetch('/psicologia_Rexwaher/config/password', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const json = await res.json();
        
        if (res.ok) {
            alert('Contraseña cambiada');
            document.getElementById('passwordForm').reset();
        } else {
            alert('Error: ' + json.message);
        }
    } catch (err) { console.error(err); }
    finally { btn.disabled = false; btn.innerText = original; }
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

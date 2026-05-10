<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2 text-danger"><i class="bi bi-cpu"></i> Panel de Administración IT</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button class="btn btn-outline-danger me-2" onclick="testError()">
            <i class="bi bi-bug"></i> Simular Error
        </button>
        <button class="btn btn-outline-secondary" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Refrescar
        </button>
    </div>
</div>

<div class="row">
    <!-- Configuración -->
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">Configuración de Alertas</div>
            <div class="card-body">
                <form onsubmit="saveSettings(event)">
                    <div class="mb-3">
                        <label class="form-label">Email para Notificaciones</label>
                        <input type="email" class="form-control" id="alertEmail" value="<?= htmlspecialchars($alertEmail) ?>" required>
                        <div class="form-text">Recibirá un correo cada vez que ocurra un error crítico (500).</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Guardar Configuración</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Visor de Logs -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-terminal"></i> Logs del Sistema (system.log)</span>
                <button class="btn btn-sm btn-outline-light" onclick="clearLogs()">Limpiar</button>
            </div>
            <div class="card-body bg-light p-0">
                <pre class="m-0 p-3" style="max-height: 500px; overflow-y: auto; font-size: 0.85rem; color: #333;"><?php 
                    if (empty($logs)) {
                        echo "No hay logs registrados.";
                    } else {
                        foreach ($logs as $line) {
                            // Colorear errores
                            if (strpos($line, '[ERROR]') !== false) {
                                echo "<span class='text-danger fw-bold'>" . htmlspecialchars($line) . "</span>";
                            } elseif (strpos($line, '[ALERT]') !== false) {
                                echo "<span class='text-primary'>" . htmlspecialchars($line) . "</span>";
                            } else {
                                echo htmlspecialchars($line);
                            }
                        }
                    }
                ?></pre>
            </div>
        </div>
    </div>
</div>

<script>
async function saveSettings(e) {
    e.preventDefault();
    const email = document.getElementById('alertEmail').value;
    
    const res = await fetch('/psicologia_Rexwaher/api/it/settings', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ email: email })
    });
    
    if (res.ok) alert('Guardado');
    else alert('Error al guardar');
}

async function clearLogs() {
    if(!confirm('¿Borrar todos los logs?')) return;
    await fetch('/psicologia_Rexwaher/api/it/logs/clear', {method: 'POST'});
    location.reload();
}

async function testError() {
    await fetch('/psicologia_Rexwaher/api/it/test-error', {method: 'POST'});
    location.reload();
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

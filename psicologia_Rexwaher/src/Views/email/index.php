<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Email Marketing</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button class="btn btn-primary" onclick="runAutomation()">
            <i class="bi bi-gear-wide-connected"></i> Control General de Automatizaciones
        </button>
    </div>
</div>

<div class="row">
    <!-- Editor de Plantillas -->
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">Plantillas de Correo</div>
            <div class="list-group list-group-flush">
                <?php foreach ($templates as $t): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div style="cursor: pointer; flex-grow: 1;" onclick="loadTemplate(<?= htmlspecialchars(json_encode($t)) ?>)">
                        <h6 class="mb-1"><?= htmlspecialchars($t['name']) ?></h6>
                        <small class="text-muted"><?= $t['code'] ?></small>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" 
                               onchange="toggleTemplate(<?= $t['id'] ?>, this.checked)" 
                               <?= $t['active'] ? 'checked' : '' ?>>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="card-body border-top">
                <form id="templateForm" onsubmit="saveTemplate(event)" style="display:none;">
                    <input type="hidden" id="tplId">
                    <div class="mb-3">
                        <label class="form-label">Asunto</label>
                        <input type="text" class="form-control" id="tplSubject" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cuerpo del Mensaje</label>
                        <textarea class="form-control" id="tplBody" rows="6" required></textarea>
                        <div class="form-text text-info" id="tplVars"></div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Guardar Cambios</button>
                </form>
                <div id="noSelection" class="text-center text-muted py-4">
                    Selecciona una plantilla para editar
                </div>
            </div>
        </div>
    </div>

    <!-- Cola de Envíos -->
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white">Cola de Envíos Recientes</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="font-size: 0.9rem;">
                    <thead>
                        <tr>
                            <th>Destinatario</th>
                            <th>Asunto</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($queue)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Cola vacía</td></tr>
                        <?php else: ?>
                            <?php foreach ($queue as $q): ?>
                            <tr>
                                <td><?= htmlspecialchars($q['recipient_email']) ?></td>
                                <td><?= htmlspecialchars(substr($q['subject'], 0, 20)) ?>...</td>
                                <td>
                                    <?php if ($q['status'] === 'sent'): ?>
                                        <span class="badge bg-success">Enviado</span>
                                    <?php elseif ($q['status'] === 'failed'): ?>
                                        <span class="badge bg-danger">Falló</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m H:i', strtotime($q['scheduled_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function loadTemplate(tpl) {
    document.getElementById('noSelection').style.display = 'none';
    document.getElementById('templateForm').style.display = 'block';
    
    document.getElementById('tplId').value = tpl.id;
    document.getElementById('tplSubject').value = tpl.subject;
    document.getElementById('tplBody').value = tpl.body;
    document.getElementById('tplVars').innerText = 'Variables disponibles: ' + (tpl.variables_help || 'Ninguna');
}

async function saveTemplate(e) {
    e.preventDefault();
    const data = {
        id: document.getElementById('tplId').value,
        subject: document.getElementById('tplSubject').value,
        body: document.getElementById('tplBody').value
    };

    const res = await fetch('/psicologia_Rexwaher/api/email/templates', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });

    if (res.ok) {
        alert('Plantilla guardada');
        location.reload();
    } else {
        alert('Error al guardar');
    }
}

async function toggleTemplate(id, isActive) {
    try {
        const res = await fetch('/psicologia_Rexwaher/api/email/templates/toggle', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id: id, active: isActive })
        });
        
        if (!res.ok) {
            alert('Error al cambiar estado');
            location.reload(); // Revertir UI
        }
    } catch (e) {
        console.error(e);
        alert('Error de conexión');
    }
}

async function runAutomation() {
    // Aquí podrías abrir un modal con más opciones si quisieras
    if (!confirm('Esto buscará citas para mañana y generará los correos usando las plantillas activas. ¿Continuar?')) return;

    const res = await fetch('/psicologia_Rexwaher/api/email/run', { method: 'POST' });
    const json = await res.json();
    
    alert(json.message);
    location.reload();
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

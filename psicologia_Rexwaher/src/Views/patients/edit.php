<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Expediente: <span id="headerName"><?= htmlspecialchars($patient['name'] ?? 'Sin Nombre') ?></span></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if (!empty($patient['drive_folder_url'])): ?>
            <a href="<?= htmlspecialchars($patient['drive_folder_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                <i class="bi bi-google"></i> Repositorio Externo
            </a>
        <?php endif; ?>
        <a href="<?= url('/patients') ?>" class="btn btn-sm btn-outline-secondary me-2">Volver</a>
    </div>
</div>

<ul class="nav nav-tabs mb-4" id="patientTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#info">Datos del Caso</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#members">Miembros</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#history" onclick="loadAppointments()">Citas</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#notes" onclick="loadNotes()">Notas</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#docs" onclick="loadDocuments()">Documentos</button></li>
</ul>

<div class="tab-content">
    
    <!-- TAB 1: Datos del Caso -->
    <div class="tab-pane fade show active" id="info">
        <div class="card shadow-sm">
            <div class="card-body">
                <form onsubmit="updateCase(event)">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre del Caso</label>
                            <input type="text" class="form-control" id="pName" value="<?= htmlspecialchars($patient['name'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($patient['type'] ?? 'individual') ?>" disabled>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" id="pStatus">
                                <?php $status = $patient['status'] ?? 'open'; ?>
                                <option value="open" <?= $status=='open'?'selected':'' ?>>Abierto</option>
                                <option value="closed" <?= $status=='closed'?'selected':'' ?>>Cerrado</option>
                                <option value="reopened" <?= $status=='reopened'?'selected':'' ?>>Reabierto</option>
                                <option value="dropout" <?= $status=='dropout'?'selected':'' ?>>Abandono</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Fecha Entrada</label>
                            <input type="date" class="form-control" value="<?= htmlspecialchars($patient['entry_date'] ?? '') ?>" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha Cierre</label>
                            <input type="date" class="form-control" id="pClosure" value="<?= htmlspecialchars($patient['closure_date'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Derivado Por</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($patient['referred_by'] ?? '') ?>" disabled>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-google"></i> Enlace a Google Drive (Repositorio Externo)</label>
                        <input type="url" class="form-control" id="pDrive" value="<?= htmlspecialchars($patient['drive_folder_url'] ?? '') ?>" placeholder="https://drive.google.com/drive/folders/...">
                    </div>

                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB 2: Miembros -->
    <div class="tab-pane fade" id="members">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Lista de Miembros</h5>
                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                    <i class="bi bi-person-plus"></i> Añadir Miembro
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre Completo</th>
                            <th>DNI / NIF</th>
                            <th>Fecha Nac.</th>
                            <th>Ocupación</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($patient['members'])): ?>
                            <?php foreach ($patient['members'] as $m): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($m['name'] ?? '') ?> <?= htmlspecialchars($m['surname'] ?? '') ?></td>
                                <td><?= htmlspecialchars($m['dni'] ?? '-') ?></td>
                                <td><?= !empty($m['birth_date']) ? date('d/m/Y', strtotime($m['birth_date'])) : '-' ?></td>
                                <td><?= htmlspecialchars($m['occupation'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($m['email'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($m['phone'] ?? '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted">No hay miembros registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: Citas -->
    <div class="tab-pane fade" id="history">
        <div id="upcomingAppointments">Cargando...</div>
        <hr>
        <div id="pastAppointments"></div>
    </div>

    <!-- TAB 4: Notas -->
    <div class="tab-pane fade" id="notes">
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <textarea id="noteContent" class="form-control mb-2" rows="4" placeholder="Nueva nota..."></textarea>
                        <input type="file" id="noteFile" class="form-control form-control-sm mb-2">
                        <button onclick="saveNote()" class="btn btn-primary w-100">Guardar</button>
                    </div>
                </div>
            </div>
            <div class="col-md-8" id="notesList"></div>
        </div>
    </div>

    <!-- TAB 5: Documentos -->
    <div class="tab-pane fade" id="docs">
        <div class="card">
            <div class="card-header">
                <input type="file" id="docUpload" style="display:none" onchange="uploadDocument(this)">
                <button class="btn btn-sm btn-primary float-end" onclick="document.getElementById('docUpload').click()">Subir</button>
            </div>
            <div class="card-body" id="docsList"></div>
        </div>
    </div>
</div>

<!-- Modal Añadir Miembro -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Añadir Nuevo Miembro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addMemberForm" onsubmit="addMember(event)">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="mName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Apellidos</label>
                        <input type="text" class="form-control" id="mSurname">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">DNI / NIF</label>
                        <input type="text" class="form-control" id="mDni">
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Fecha Nacimiento</label>
                            <input type="date" class="form-control" id="mBirth">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Ocupación</label>
                            <input type="text" class="form-control" id="mOcc">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="mEmail">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="mPhone">
                    </div>
                    <button type="submit" class="btn btn-success w-100">Guardar Miembro</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const PATIENT_ID = <?= $patient['id'] ?>;

async function updateCase(e) {
    e.preventDefault();
    const data = {
        name: document.getElementById('pName').value,
        status: document.getElementById('pStatus').value,
        closure_date: document.getElementById('pClosure').value,
        drive_folder_url: document.getElementById('pDrive').value
    };

    const res = await fetch(BASE_URL + `/api/patients/${PATIENT_ID}`, {
        method: 'PUT',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });

    if (res.ok) {
        alert('Guardado');
        location.reload();
    }
}

async function addMember(e) {
    e.preventDefault();
    const data = {
        name: document.getElementById('mName').value,
        surname: document.getElementById('mSurname').value,
        dni: document.getElementById('mDni').value,
        birth_date: document.getElementById('mBirth').value,
        occupation: document.getElementById('mOcc').value,
        email: document.getElementById('mEmail').value,
        phone: document.getElementById('mPhone').value
    };

    const res = await fetch(BASE_URL + `/api/patients/${PATIENT_ID}/members`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });

    if (res.ok) {
        alert('Miembro añadido');
        location.reload();
    } else {
        const err = await res.json();
        alert('Error: ' + (err.message || 'Error desconocido'));
    }
}
</script>

<script>
async function loadAppointments() {
    const res = await fetch(BASE_URL + `/api/patients/${PATIENT_ID}/appointments`);
    let data = await res.json();
    if (!Array.isArray(data)) data = [];
    const upcoming = data.filter(a => new Date(a.start_time) >= new Date());
    const past = data.filter(a => new Date(a.start_time) < new Date());

    document.getElementById('upcomingAppointments').innerHTML = renderApptTable(upcoming, 'Próximas');
    document.getElementById('pastAppointments').innerHTML = renderApptTable(past, 'Pasadas');
}

function renderApptTable(list, title) {
    if (list.length === 0) return `<h6>${title}</h6><p class="text-muted">Sin registros</p>`;
    
    const statusMap = {
        'scheduled': 'Programada',
        'completed': 'Realizada',
        'cancelled': 'Cancelada',
        'no_show': 'No presentado'
    };

    return `<h6>${title}</h6><table class="table table-sm">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Cuota</th>
            </tr>
        </thead>
        <tbody>
        ${list.map(a => `
            <tr>
                <td>${new Date(a.start_time).toLocaleDateString()}</td>
                <td>${statusMap[a.status] || a.status}</td>
                <td>${a.fee_amount ? parseFloat(a.fee_amount).toFixed(2) + ' €' : '0.00 €'}</td>
            </tr>
        `).join('')}
        </tbody>
    </table>`;
}

async function loadNotes() {
    const res = await fetch(BASE_URL + `/api/patients/${PATIENT_ID}/notes`);
    let notes = await res.json();
    if (!Array.isArray(notes)) notes = [];
    document.getElementById('notesList').innerHTML = notes.map(n => `
        <div class="card mb-2"><div class="card-body p-2">
            <small class="text-muted">${new Date(n.created_at).toLocaleDateString()}</small>
            <p class="mb-0">${n.content}</p>
        </div></div>
    `).join('');
}

async function saveNote() {
    const content = document.getElementById('noteContent').value;
    if(!content) return;

    const formData = new FormData();
    formData.append('content', content);
    formData.append('patient_id', PATIENT_ID);
    
    await fetch(BASE_URL + '/api/notes', {method:'POST', body:formData});
    document.getElementById('noteContent').value = '';
    loadNotes();
}

async function loadDocuments() {
    const res = await fetch(BASE_URL + `/api/patients/${PATIENT_ID}/documents`);
    let docs = await res.json();
    if (!Array.isArray(docs)) docs = [];
    document.getElementById('docsList').innerHTML = docs.map(d => `
        <div class="d-flex justify-content-between border-bottom p-2">
            <span>${d.file_name}</span>
            <a href="${BASE_URL}/uploads/${d.file_path}" target="_blank">Ver</a>
        </div>
    `).join('');
}

async function uploadDocument(input) {
    if(!input.files[0]) return;
    const formData = new FormData();
    formData.append('file', input.files[0]);
    formData.append('patient_id', PATIENT_ID);
    await fetch(BASE_URL + '/api/documents', {method:'POST', body:formData});
    loadDocuments();
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
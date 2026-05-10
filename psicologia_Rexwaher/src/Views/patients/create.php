<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Nuevo Expediente</h1>
    <a href="/psicologia_Rexwaher/patients" class="btn btn-sm btn-outline-secondary">Volver</a>
</div>

<div class="row">
    <div class="col-md-10 offset-md-1">
        <form id="createCaseForm" onsubmit="saveCase(event)">

            <!-- Datos del Caso -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">Datos del Expediente</div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre del Caso *</label>
                            <input type="text" class="form-control" id="caseName" placeholder="Ej: Familia García o Juan Pérez" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" id="caseType">
                                <option value="individual">Individual</option>
                                <option value="couple">Pareja</option>
                                <option value="family">Familia</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Entrada</label>
                            <input type="date" class="form-control" id="entryDate" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Derivado Por</label>
                        <input type="text" class="form-control" id="referredBy">
                    </div>
                </div>
            </div>

            <!-- Miembros -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span>Miembros del Caso</span>
                    <button type="button" class="btn btn-sm btn-success" onclick="addMemberRow()">
                        <i class="bi bi-plus-lg"></i> Añadir Miembro
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre *</th>
                                <th>Apellidos</th>
                                <th>Fecha Nac.</th>
                                <th>Ocupación</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="membersBody">
                            <!-- Filas dinámicas -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Crear Expediente</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    addMemberRow(); // Añadir primera fila por defecto
});

function addMemberRow() {
    const tbody = document.getElementById('membersBody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" class="form-control form-control-sm m-name" required></td>
        <td><input type="text" class="form-control form-control-sm m-surname"></td>
        <td><input type="date" class="form-control form-control-sm m-birth"></td>
        <td><input type="text" class="form-control form-control-sm m-occ"></td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="this.closest('tr').remove()">
                <i class="bi bi-x-lg"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
}

async function saveCase(e) {
    e.preventDefault();
    
    // Recopilar miembros
    const members = [];
    document.querySelectorAll('#membersBody tr').forEach(tr => {
        members.push({
            name: tr.querySelector('.m-name').value,
            surname: tr.querySelector('.m-surname').value,
            birth_date: tr.querySelector('.m-birth').value,
            occupation: tr.querySelector('.m-occ').value
        });
    });

    if (members.length === 0) {
        alert('Debe añadir al menos un miembro.');
        return;
    }

    const data = {
        name: document.getElementById('caseName').value,
        type: document.getElementById('caseType').value,
        entry_date: document.getElementById('entryDate').value,
        referred_by: document.getElementById('referredBy').value,
        members: members
    };

    try {
        const res = await fetch('/psicologia_Rexwaher/api/patients', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const json = await res.json();

        if (res.ok) {
            window.location.href = `/psicologia_Rexwaher/patients/${json.id}/edit`;
        } else {
            alert('Error: ' + (json.message || 'No se pudo guardar'));
        }
    } catch (err) {
        console.error(err);
        alert('Error de conexión');
    }
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

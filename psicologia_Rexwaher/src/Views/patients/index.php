<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Expedientes</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= url('/patients/create') ?>" class="btn btn-primary">
            <i class="bi bi-folder-plus"></i> Nuevo Expediente
        </a>
    </div>
</div>

<!-- Filtros y Toggle -->
<div class="row mb-4">
    <div class="col-md-12 d-flex justify-content-between align-items-center">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-success active" id="btnActive" onclick="toggleView('active')">
                <i class="bi bi-person-check"></i> Activos
            </button>
            <button type="button" class="btn btn-secondary" id="btnInactive" onclick="toggleView('inactive')">
                <i class="bi bi-archive"></i> Inactivos
            </button>
        </div>
        <input type="text" id="searchInput" class="form-control" style="max-width: 300px;" placeholder="Buscar expediente..." onkeyup="searchTable()">
    </div>
</div>

<!-- Tabla Única -->
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light" id="tableHeader">
                <!-- El encabezado se genera con JS -->
            </thead>
            <tbody id="patientsTableBody">
                <tr><td colspan="5" class="text-center py-3">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let allData = { active: [], inactive: [] };
let currentView = 'active';

document.addEventListener('DOMContentLoaded', loadPatients);

async function loadPatients() {
    try {
        const response = await fetch(BASE_URL + '/api/patients');
        let data = await response.json();
        
        // Manejar estructura {active: [...], inactive: [...]} o un array simple por compatibilidad
        if (data && data.active && data.inactive) {
            allData = data;
        } else if (Array.isArray(data)) {
            // Si es un array simple, simulamos que son todos activos
            allData = { active: data, inactive: [] };
        } else {
             allData = { active: [], inactive: [] };
        }

        toggleView('active'); // Mostrar activos por defecto
    } catch (error) {
        console.error('Error:', error);
    }
}

function toggleView(view) {
    currentView = view;
    
    // Actualizar botones
    document.getElementById('btnActive').classList.toggle('active', view === 'active');
    document.getElementById('btnInactive').classList.toggle('active', view === 'inactive');

    // Actualizar encabezado de tabla
    const header = document.getElementById('tableHeader');
    if (view === 'active') {
        header.innerHTML = `
            <tr>
                <th>Nombre del Caso</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Última Sesión</th>
                <th class="text-end">Acciones</th>
            </tr>
        `;
    } else {
        header.innerHTML = `
            <tr>
                <th>Nombre del Caso</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Fecha Cierre</th>
                <th class="text-end">Acciones</th>
            </tr>
        `;
    }

    renderTable();
}

function renderTable() {
    const tbody = document.getElementById('patientsTableBody');
    const list = allData[currentView] || [];
    tbody.innerHTML = '';

    if (list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No hay registros</td></tr>';
        return;
    }

    list.forEach(p => {
        const typeBadge = {
            'individual': '<span class="badge bg-primary">Individual</span>',
            'couple': '<span class="badge bg-info text-dark">Pareja</span>',
            'family': '<span class="badge bg-warning text-dark">Familia</span>'
        }[p.type] || '<span class="badge bg-secondary">Otro</span>';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="fw-bold">${p.name}</td>
            <td>${typeBadge}</td>
            <td><span class="badge bg-${currentView === 'active' ? 'success' : 'secondary'}">${p.status}</span></td>
            <td>${currentView === 'active' ? (p.last_session_at ? formatDate(p.last_session_at) : '-') : (p.closure_date ? formatDate(p.closure_date) : '-')}</td>
            <td class="text-end">
                <a href="${BASE_URL}/patients/${p.id}/edit" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Ver Ficha</a>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function formatDate(dateString) {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('es-ES');
}

function searchTable() {
    const term = document.getElementById('searchInput').value.toLowerCase();
    const list = (allData[currentView] || []).filter(p => p.name.toLowerCase().includes(term));
    
    const tbody = document.getElementById('patientsTableBody');
    tbody.innerHTML = '';

    if (list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No se encontraron resultados</td></tr>';
        return;
    }

    list.forEach(p => {
        const typeBadge = {
            'individual': '<span class="badge bg-primary">Individual</span>',
            'couple': '<span class="badge bg-info text-dark">Pareja</span>',
            'family': '<span class="badge bg-warning text-dark">Familia</span>'
        }[p.type] || '<span class="badge bg-secondary">Otro</span>';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="fw-bold">${p.name}</td>
            <td>${typeBadge}</td>
            <td><span class="badge bg-${currentView === 'active' ? 'success' : 'secondary'}">${p.status}</span></td>
            <td>${currentView === 'active' ? (p.last_session_at ? formatDate(p.last_session_at) : '-') : (p.closure_date ? formatDate(p.closure_date) : '-')}</td>
            <td class="text-end">
                <a href="${BASE_URL}/patients/${p.id}/edit" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Ver Ficha</a>
            </td>
        `;
        tbody.appendChild(tr);
    });
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Seguridad y Bloqueos</h1>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="bi bi-shield-lock"></i> Intentos de Acceso Fallidos</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>IP</th>
                    <th>Usuario Intentado</th>
                    <th>Intentos</th>
                    <th>Último Intento</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($blocked)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">No hay bloqueos activos.</td></tr>
                <?php else: ?>
                    <?php foreach ($blocked as $b): ?>
                    <tr>
                        <td><?= htmlspecialchars($b['ip_address']) ?></td>
                        <td><?= htmlspecialchars($b['username']) ?></td>
                        <td><span class="badge bg-warning text-dark"><?= $b['attempts'] ?></span></td>
                        <td><?= $b['last_attempt'] ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-success" onclick="unblockIp('<?= $b['ip_address'] ?>')">
                                <i class="bi bi-unlock"></i> Desbloquear
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
async function unblockIp(ip) {
    if (!confirm('¿Seguro que quieres desbloquear esta IP?')) return;

    const res = await fetch('/psicologia_Rexwaher/api/admin/security/unblock', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ ip: ip })
    });

    if (res.ok) {
        location.reload();
    } else {
        alert('Error al desbloquear');
    }
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

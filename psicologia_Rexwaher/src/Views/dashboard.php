<?php require __DIR__ . '/layouts/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Panel de Control</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary">Compartir</button>
            <button type="button" class="btn btn-sm btn-outline-secondary">Exportar</button>
        </div>
    </div>
</div>

<!-- Tarjetas de Resumen (Ejemplo) -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-header">Próximas Citas</div>
            <div class="card-body">
                <h5 class="card-title display-6"><?= count($upcomingAppointments) ?></h5>
                <p class="card-text">Agendadas próximamente.</p>
            </div>
        </div>
    </div>
    <?php if ($role !== 'professional'): ?>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-header">Ingresos del Mes</div>
            <div class="card-body">
                <h5 class="card-title display-6">0 €</h5>
                <p class="card-text">Calculado automáticamente.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row">
    <!-- Columna Izquierda: Próximas Citas -->
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary"><i class="bi bi-calendar-event"></i> Próximas Citas</h5>
            </div>
            <div class="list-group list-group-flush">
                <?php if (empty($upcomingAppointments)): ?>
                    <div class="list-group-item text-center text-muted py-4">No hay citas próximas programadas.</div>
                <?php else: ?>
                    <?php foreach ($upcomingAppointments as $appt): 
                        $start = new DateTime($appt['start_time']);
                        $isToday = $start->format('Y-m-d') === date('Y-m-d');
                        $badgeColor = $isToday ? 'bg-danger' : 'bg-primary';
                        $dayLabel = $isToday ? 'HOY' : $start->format('d/m');
                    ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge <?= $badgeColor ?> me-2"><?= $dayLabel ?></span>
                            <strong><?= $start->format('H:i') ?></strong> - <?= htmlspecialchars($appt['patient_name']) ?>
                        </div>
                        <span class="badge bg-light text-dark border"><?= $appt['status'] ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white text-center">
                <a href="<?= url('/calendar') ?>" class="text-decoration-none small">Ver calendario completo &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Columna Derecha: Últimos Pacientes -->
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-success"><i class="bi bi-people"></i> Últimos Pacientes Atendidos</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size: 0.9rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Paciente</th>
                            <th>Última Sesión</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentPatients)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-3">Aún no se han registrado sesiones completadas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentPatients as $p): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($p['name']) ?></td>
                                <td><?= date('d/m/Y', strtotime($p['last_session_at'])) ?></td>
                                <td>
                                    <a href="<?= url('/patients/' . $p['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary py-0" style="font-size: 0.8rem;">Ver Ficha</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white text-center">
                <a href="<?= url('/patients') ?>" class="text-decoration-none small">Ver todos los pacientes &rarr;</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layouts/footer.php'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>
<?php use Src\Services\Csrf; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">Establecer Nueva Contraseña</div>
                    <div class="card-body">
                        <form action="<?= BASE_URL ?>/password/reset" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= Csrf::getToken() ?>">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">

                            <div class="mb-3">
                                <label class="form-label">Nueva Contraseña</label>
                                <input type="password" name="password" class="form-control" required minlength="6">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirmar Contraseña</label>
                                <input type="password" name="password_confirm" class="form-control" required minlength="6">
                            </div>

                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?= $error ?></div>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-primary w-100">Restablecer Contraseña</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
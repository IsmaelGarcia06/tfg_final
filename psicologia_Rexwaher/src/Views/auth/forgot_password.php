<?php require __DIR__ . '/../layouts/header.php'; ?>
<?php use Src\Services\Csrf; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">Recuperar Contraseña</div>
                    <div class="card-body">
                        <p>Ingresa tu email para recibir un enlace de recuperación.</p>

                        <form action="<?= BASE_URL ?>/password/email" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= Csrf::getToken() ?>">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Enviar Enlace</button>
                        </form>

                        <hr>
                        <p class="text-center">
                            <a href="<?= BASE_URL ?>/login">Volver al Login</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
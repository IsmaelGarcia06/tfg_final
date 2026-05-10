<?php require __DIR__ . '/../layouts/header.php'; ?>
<?php use Src\Services\Csrf; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">Configurar Doble Factor (2FA)</div>
                    <div class="card-body text-center">
                        <p>Escanea este código QR con Google Authenticator o Authy:</p>
                        <img src="<?= $qrUrl ?>" alt="QR Code" class="img-fluid mb-3 border p-2">
                        <p class="text-muted small">Secreto: <?= $_SESSION['2fa_temp_secret'] ?></p>

                        <hr>

                        <form action="<?= BASE_URL ?>/auth/2fa/enable" method="POST">
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?= Csrf::getToken() ?>">

                            <div class="mb-3">
                                <label class="form-label">Ingresa el código de 6 dígitos</label>
                                <input type="text" name="code" class="form-control text-center fs-4" maxlength="6" required autocomplete="off">
                            </div>
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?= $error ?></div>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-success w-100">Activar 2FA</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
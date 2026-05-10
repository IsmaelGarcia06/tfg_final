<?php use Src\Services\Csrf; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Eliminar la restricción de CSP para que todo funcione de nuevo -->
    <meta name="csrf-token" content="<?= Csrf::getToken() ?>">
    <title>CRM Clínica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- FullCalendar -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <!-- Idioma Español para FullCalendar -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales/es.global.min.js'></script>
    
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; box-shadow: 0 0 10px rgba(0,0,0,0.1); z-index: 1000; }
        .nav-link { color: #333; font-weight: 500; padding: 0.8rem 1rem; }
        .nav-link:hover, .nav-link.active { background-color: #e9ecef; color: #0d6efd; border-radius: 5px; }
        
        /* Estilos personalizados para botones de FullCalendar */
        .fc .fc-button-primary {
            background-color: #fff;
            color: #333;
            border-color: #dee2e6;
        }
        .fc .fc-button-primary:hover {
            background-color: #e9ecef;
            color: #333;
            border-color: #dee2e6;
        }
        .fc .fc-button-primary:not(:disabled).fc-button-active,
        .fc .fc-button-primary:not(:disabled):active {
            background-color: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }

        /* Fix específico para que los iconos (flechas) se vean bien en Bootstrap + FullCalendar */
        .fc-icon {
            display: inline-block !important;
            font-size: 1.5em !important;
            line-height: 1 !important;
            vertical-align: middle !important;
        }
    </style>
    
    <script>
        const BASE_URL = "<?= BASE_URL ?>";
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            // Deshabilitar momentaneamente inyección de CSRF token
            if (['POST', 'PUT', 'DELETE', 'PATCH'].includes((options.method || 'GET').toUpperCase())) {
                options.headers = options.headers || {};
                options.headers['X-CSRF-Token'] = document.querySelector('meta[name="csrf-token"]').content;
            }
            return originalFetch(url, options);
        };
    </script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary d-md-none">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">CRM Clínica</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mobileMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mobileMenu">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?= url('/dashboard') ?>">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('/patients') ?>">Pacientes</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('/calendar') ?>">Calendario</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('/profile') ?>">Mi Cuenta</a></li>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('/admin/users') ?>">Administración</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Desktop -->
        <nav class="col-md-3 col-lg-2 d-none d-md-block bg-white sidebar py-3">
            <div class="d-flex align-items-center mb-4 px-3">
                <i class="bi bi-heart-pulse-fill text-primary fs-4 me-2"></i>
                <span class="fs-4 fw-bold">Clínica</span>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('/dashboard') ?>">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('/patients') ?>">
                        <i class="bi bi-people me-2"></i> Pacientes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('/calendar') ?>">
                        <i class="bi bi-calendar-week me-2"></i> Calendario
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('/financial') ?>">
                        <i class="bi bi-cash-coin me-2"></i> Economía
                    </a>
                </li>

                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <li class="nav-item mt-3">
                    <span class="text-muted small px-3 text-uppercase fw-bold">Administración</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('/admin/users') ?>">
                        <i class="bi bi-people-fill me-2"></i> Usuarios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('/admin/services') ?>">
                        <i class="bi bi-tags-fill me-2"></i> Servicios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('/admin/commissions') ?>">
                        <i class="bi bi-graph-up-arrow me-2"></i> Comisiones
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('/admin/email') ?>">
                        <i class="bi bi-envelope-paper me-2"></i> Email Marketing
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item mt-3">
                    <a class="nav-link" href="<?= url('/profile') ?>">
                        <i class="bi bi-person-circle me-2"></i> Mi Cuenta
                    </a>
                </li>
            </ul>
            
            <div class="mt-auto px-3 pt-4 border-top">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <small class="text-muted d-block">Usuario</small>
                        <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></strong>
                    </div>
                    <form action="<?= url('/logout') ?>" method="POST">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right"></i></button>
                    </form>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
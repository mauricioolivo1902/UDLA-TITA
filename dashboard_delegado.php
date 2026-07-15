<?php
// dashboard_delegado.php
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('delegado');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php $titulo_pagina = 'Delegado Académico'; require __DIR__ . '/includes/partials/head.php'; ?>
</head>
<body class="dashboard-body bg-ice-blue">
    
    <?php require __DIR__ . '/includes/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="greeting">
                <h1 class="text-accent">Hola, Delegado Académico<php></h1>
                <p class="text-muted">¿Qué deseas gestionar hoy?</p>
            </div>
        </header>

        <section class="modules-grid">
            
            <a href="reporte_titulacion.php" class="module-card">
                <div class="icon-wrapper bg-blue-light">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#262f57" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <h3>Reporte para Titulación</h3>
                <p class="text-muted">Visualiza y exporta a Excel el listado de estudiantes listos para titularse.</p>
            </a>

            <a href="registro_senescyt.php" class="module-card">
                <div class="icon-wrapper bg-yellow-light">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#92400e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                    </svg>
                </div>
                <h3>Registro Senescyt</h3>
                <p class="text-muted">Aprueba estudiantes, ingresa actas y números de registro.</p>
            </a>

        </section>
    </main>
</body>
</html>
<?php
// inicio_admin.php
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php $titulo_pagina = 'Inicio Canciller'; require __DIR__ . '/includes/partials/head.php'; ?>
    <style>
        .main-content { padding: 30px; }


        /* GRID EXACTO DE 4 COLUMNAS */
        .modules-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        /* Responsividad para pantallas más pequeñas */
        @media (max-width: 1200px) {
            .modules-grid-4 { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .modules-grid-4 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="dashboard-body bg-ice-blue">
    
    <?php require __DIR__ . '/includes/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="greeting">
                <h1 class="text-accent">Bienvenido, Canciller</h1>
                <p class="text-muted">¿Qué deseas revisar hoy?</p>
            </div>
        </header>

        <section class="modules-grid-4">
            
            <a href="dashboard_admin.php" class="module-card">
                <div class="icon-wrapper" style="background-color: #e0e7ff;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#3730a3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                </div>
                <h3>Métricas y Gráficas</h3>
                <p class="text-muted">Análisis general, población estudiantil y estadísticas en tiempo real.</p>
            </a>

            <a href="modulos_admin.php" class="module-card">
                <div class="icon-wrapper" style="background-color: #fef3c7;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#92400e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                </div>
                <h3>Gestión de Módulos</h3>
                <p class="text-muted">Crea, renombra o elimina los módulos académicos (ej. 2026-1, 2026-2).</p>
            </a>

        </section>
    </main>
</body>
</html>
<?php
// dashboard.php
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('admisiones');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php $titulo_pagina = 'Dashboard Admisiones'; require __DIR__ . '/includes/partials/head.php'; ?>
</head>
<body class="dashboard-body bg-ice-blue">
    
    <?php require __DIR__ . '/includes/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div class="greeting">
                <h1 class="text-accent">Hola, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
                <p class="text-muted">¿Qué deseas gestionar hoy?</p>
            </div>
        </header>

        <section class="modules-grid">
            
            <a href="crear_estudiante.php" class="module-card">
                <div class="icon-wrapper bg-blue-light">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#262f57" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>
                </div>
                <h3>Crear Estudiante</h3>
                <p class="text-muted">Registrar nuevo ingreso y generar su carpeta en el sistema.</p>
            </a>

            <a href="revisar_documentos.php" class="module-card">
                <div class="icon-wrapper bg-yellow-light">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#92400e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><polyline points="9 15 11 17 15 13"></polyline></svg>
                </div>
                <h3>Revisar Documentos</h3>
                <p class="text-muted">Aprobar o negar los documentos subidos por los estudiantes.</p>
            </a>

            <a href="visualizar_listas.php" class="module-card">
                <div class="icon-wrapper bg-purple-light">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#262f57" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                </div>
                <h3>Visualizar Listas</h3>
                <p class="text-muted">Búsqueda avanzada, ver estados y dashboard por estudiante.</p>
            </a>

            <a href="exportar_listas.php" class="module-card">
                <div class="icon-wrapper bg-blue-light">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#262f57" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                </div>
                <h3>Exportar Listas</h3>
                <p class="text-muted">Descargar reportes y matriz de datos en formato Excel.</p>
            </a>

        </section>
    </main>
</body>
</html>
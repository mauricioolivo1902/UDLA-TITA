<?php
// dashboard_contabilidad.php
// Inicio de Contabilidad: acceso al reporte general y contador de pagos
// pendientes de consolidar.
require_once __DIR__ . '/includes/bootstrap.php';
exigir_rol('contabilidad');

try {
    $cantidad_pendientes = (new PagoRepository(Database::conexion()))->contarPorEstado('En Revisión');
} catch (PDOException $e) {
    $cantidad_pendientes = 0;
    $error_db = 'Error al contar pagos pendientes: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php $titulo_pagina = 'Panel de Contabilidad'; require __DIR__ . '/includes/partials/head.php'; ?>
    <style>
        .finance-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
        }
        .finance-icon {
            background-color: var(--success-text);
            color: white;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
        }
        .icon-warning {
            background-color: #f59e0b;
            box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.3);
        }
        /* Contenedor tipo grid para las tarjetas */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        @media (min-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body class="dashboard-body bg-ice-blue">
    
    <?php require __DIR__ . '/includes/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1 class="text-accent">Módulo Financiero</h1>
                <p class="text-muted">Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name']); ?>. Control de cobros y saldos.</p>
            </div>
        </header>

        <?php if(isset($error_db)): ?>
            <div style="background-color: var(--danger-red); color: var(--danger-text); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error_db); ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Widget 1: Reporte General -->
            <section class="form-card card-white finance-card" style="margin-bottom: 0;">
                <div class="finance-icon">
                    <svg width="35" height="35" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="18" x2="12" y2="12"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                </div>
                
                <h2 class="text-accent" style="margin-bottom: 10px;">Reporte General</h2>
                <p class="text-muted" style="margin-bottom: 25px; font-size: 15px;">
                    Descarga el Excel consolidado de todos los estudiantes, organizado por carrera y orden alfabético.
                </p>

                <form action="exportar_contabilidad.php" method="GET">
                    <button type="submit" class="btn-success" style="background-color: var(--success-text); color: white; padding: 14px 28px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 15px; display: inline-flex; align-items: center; gap: 10px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg> 
                        Exportar a Excel
                    </button>
                </form>
            </section>

            <!-- Widget 2: Acceso directo a Consolidar -->
            <section class="form-card card-white finance-card" style="margin-bottom: 0;">
                <div class="finance-icon icon-warning">
                    <svg width="35" height="35" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 11 12 14 22 4"></polyline>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                </div>
                
                <h2 class="text-accent" style="margin-bottom: 10px;">Pagos por Consolidar</h2>
                <p class="text-muted" style="margin-bottom: 25px; font-size: 15px;">
                    Accede a la bandeja de revisión para aprobar o rechazar los pagos subidos por los estudiantes.
                </p>
                
                <div style="margin-bottom: 20px; font-weight: 600; font-size: 16px; color: #d97706; background-color: #fef3c7; padding: 8px 16px; border-radius: 20px;">
                    <?php echo $cantidad_pendientes; ?> pago(s) pendiente(s)
                </div>

                <a href="pagos_por_consolidar.php" class="btn-primary" style="padding: 14px 28px; font-size: 15px;">
                    Ir al panel de revisión
                </a>
            </section>
        </div>
        
    </main>

</body>
</html>
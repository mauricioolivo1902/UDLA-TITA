<?php
// includes/partials/sidebar.php
// Menú lateral según el rol de la sesión.
//
// Para añadir una página al menú de un rol basta con agregarla al arreglo
// $menus_por_rol: ninguna vista necesita cambios (principio abierto/cerrado).
//
// Variables opcionales antes de incluir:
//   $pagina_activa — archivo a resaltar; por defecto, la página actual.

$menus_por_rol = [
    'admisiones' => [
        'dashboard.php'           => 'Inicio',
        'crear_estudiante.php'    => 'Crear Estudiante',
        'revisar_documentos.php'  => 'Revisar Documentos',
        'visualizar_listas.php'   => 'Visualizar Listas',
        'exportar_listas.php'     => 'Exportar Listas',
    ],
    'contabilidad' => [
        'dashboard_contabilidad.php' => 'Inicio',
        'pagos_por_consolidar.php'   => 'Pagos por Consolidar',
    ],
    'delegado' => [
        'dashboard_delegado.php'  => 'Inicio',
        'reporte_titulacion.php'  => 'Reporte para Titulación',
        'registro_senescyt.php'   => 'Registro Senescyt',
    ],
    'admin' => [
        'inicio_admin.php'    => 'Inicio',
        'dashboard_admin.php' => 'Dashboard',
        'modulos_admin.php'   => 'Módulos',
    ],
    'estudiante' => [
        'dashboard_estudiante.php' => 'Mi Progreso',
        'subir_documento.php'      => 'Subir Documentos',
        'subir_pago.php'           => 'Subir Pago',
    ],
];

$insignias_por_rol = [
    'admisiones'   => ['texto' => 'Admisiones',   'estilo' => ''],
    'contabilidad' => ['texto' => 'Contabilidad', 'estilo' => 'background-color: #f1f5f9; color: var(--text-dark);'],
    'delegado'     => ['texto' => 'Delegado',     'estilo' => 'background-color: #ede9fe; color: #6d28d9;'],
    'admin'        => ['texto' => 'Canciller',    'estilo' => ''],
    'estudiante'   => ['texto' => 'Estudiante',   'estilo' => 'background-color: #e0f2fe; color: #0369a1;'],
];

$rol_sidebar   = $_SESSION['rol'] ?? '';
$menu_sidebar  = $menus_por_rol[$rol_sidebar] ?? [];
$insignia      = $insignias_por_rol[$rol_sidebar] ?? ['texto' => 'Usuario', 'estilo' => ''];
$pagina_activa = $pagina_activa ?? basename($_SERVER['SCRIPT_NAME']);
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <h2 class="text-accent">NEO</h2>
        <span class="badge-role" style="<?php echo $insignia['estilo']; ?>"><?php echo e($insignia['texto']); ?></span>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($menu_sidebar as $archivo => $etiqueta): ?>
            <a href="<?php echo $archivo; ?>" class="nav-item <?php echo $archivo === $pagina_activa ? 'active' : ''; ?>"><?php echo e($etiqueta); ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-item text-danger">Cerrar Sesión</a>
    </div>
</aside>

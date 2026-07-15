<?php
// includes/partials/head.php
// Cabecera HTML común a todas las páginas.
// Variable requerida antes de incluir:
//   $titulo_pagina — texto que acompaña a "NEO -" en la pestaña del navegador.
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NEO - <?php echo e($titulo_pagina ?? 'Sistema'); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<?php // La versión con filemtime obliga al navegador a recargar el CSS cuando cambia (evita cachés viejas) ?>
<link rel="stylesheet" href="css/style.css?v=<?php echo filemtime(dirname(__DIR__, 2) . '/css/style.css'); ?>">

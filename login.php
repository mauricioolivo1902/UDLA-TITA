<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Si ya está logueado, lo enviamos a su panel correspondiente
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: " . pagina_inicio_por_rol($_SESSION['rol'] ?? ''));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEO - Inicio de Sesión</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime(__DIR__ . '/css/style.css'); ?>">
</head>
<body class="bg-ice-blue">
    <main class="login-wrapper">
        <section class="login-card">
            <header class="login-header">
                <h1 class="text-accent">Proyecto NEO</h1>
                <p class="text-muted">Acceso al Sistema</p>
            </header>

            <?php
            // Mostrar mensaje de error si las credenciales fallan
            if (isset($_SESSION['login_error'])) {
                echo '<div style="background-color: var(--danger-red); color: var(--danger-text); padding: 12px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-size: 14px; font-weight: 500;">' . $_SESSION['login_error'] . '</div>';
                unset($_SESSION['login_error']); // Limpiar el error después de mostrarlo
            }
            ?>
            
            <form action="auth.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="usuario">Usuario / Correo</label>
                    <input type="text" id="usuario" name="usuario" placeholder="correo@rtv.edu.ec" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn-primary">Iniciar Sesión</button>
            </form>
        </section>
    </main>
</body>
</html>
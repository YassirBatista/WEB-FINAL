<?php
// NEXUS STELLAR SHIPYARDS — Sistema de Acceso (Solo Admin)
require_once '../includes/db_connect.php';

session_start();

// Si ya está logueado, redirigir
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['rol'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Todos los campos son obligatorios';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ? AND rol = 'admin' AND activo = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['rol'] = $user['rol'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['faccion_id'] = $user['faccion_id'];

            header('Location: ../admin/dashboard.php');
            exit;
        } else {
            $error = 'Credenciales inválidas';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEXUS STELLAR SHIPYARDS — Acceso al Sistema</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
</head>
<body class="scanlines">
<div class="bg-space" style="background-image:url('../assets/images/backgrounds/space_station.jpg')"></div>

<div class="login-container">
    <div class="login-box">
        <div class="login-logo">
            <div class="brand">NEXUS</div>
            <div class="subbrand">Stellar Shipyards</div>
        </div>

        <?php if ($error): ?>
            <div class="login-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label class="form-label">ID de Acceso</label>
                <input type="text" name="username" class="form-input" placeholder="Ingrese su ID" required 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Código de Seguridad</label>
                <input type="password" name="password" class="form-input" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>

        <div style="margin-top:20px; text-align:center; font-size:11px; color:var(--tech-white-dim)">
            <p>Demo: <strong>admin</strong> / <strong>password</strong></p>
        </div>
    </div>
</div>
</body>
</html>
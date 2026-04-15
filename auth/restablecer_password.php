<?php
session_start();
include("../config/conexion.php");

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    exit("Token inválido.");
}

$sql = "SELECT pr.*, u.nombre
        FROM password_resets pr
        INNER JOIN usuarios u ON pr.usuario_id = u.id
        WHERE pr.token = ? AND pr.usado = 0
        LIMIT 1";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();
$reset = $res->fetch_assoc();

if (!$reset) {
    exit("El token no es válido o ya fue usado.");
}

if (strtotime($reset['expiracion']) < time()) {
    exit("El token ya expiró.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nueva contraseña</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
<style>
.contenedor-recuperar{
    max-width:500px;
    margin:60px auto;
    background:#fff;
    padding:30px;
    border-radius:14px;
    box-shadow:0 4px 14px rgba(0,0,0,.08);
}
</style>
</head>
<body>

<header class="topbar">
    <a href="../index.php" class="logo-area">
        <img src="../assets/img/Logo.jpeg" class="logo-img" alt="Logo">
        <div class="logo-text">Sistema de Reservas</div>
    </a>
</header>

<div class="contenedor-recuperar">
    <h2 class="titulo-azul">Nueva contraseña</h2>

    <?php if (isset($_SESSION['error_password'])) { ?>
        <div class="mensaje-error">
            <?php
            echo htmlspecialchars($_SESSION['error_password']);
            unset($_SESSION['error_password']);
            ?>
        </div>
    <?php } ?>

    <form action="guardar_nueva_password.php" method="POST">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

        <label>Nueva contraseña</label>
        <input type="password" name="password" required>

        <label>Confirmar contraseña</label>
        <input type="password" name="password2" required>

        <button type="submit" class="btn">Guardar nueva contraseña</button>
    </form>
</div>

</body>
</html>
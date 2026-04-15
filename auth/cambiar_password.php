<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'coordinador') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cambiar contraseña</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
<style>
.box{
    max-width:500px;
    margin:80px auto;
    background:#fff;
    padding:30px;
    border-radius:14px;
    box-shadow:0 4px 14px rgba(0,0,0,.08);
}
</style>
</head>
<body>

<div class="box">
    <h2 class="titulo-azul">Debes cambiar tu contraseña</h2>

    <?php if (isset($_SESSION['error_password'])) { ?>
        <div class="mensaje-error">
            <?php echo $_SESSION['error_password']; unset($_SESSION['error_password']); ?>
        </div>
    <?php } ?>

    <form method="POST" action="guardar_password_forzado.php">
        <label>Nueva contraseña</label>
        <input type="password" name="password" required>

        <label>Confirmar contraseña</label>
        <input type="password" name="password2" required>

        <button type="submit" class="btn">Guardar</button>
    </form>
</div>

</body>
</html>
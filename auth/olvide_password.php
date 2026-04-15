<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Recuperar contraseña</title>
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
    <h2 class="titulo-azul">Recuperar contraseña</h2>

    <?php if (isset($_SESSION['error_password'])) { ?>
        <div class="mensaje-error">
            <?php
            echo htmlspecialchars($_SESSION['error_password']);
            unset($_SESSION['error_password']);
            ?>
        </div>
    <?php } ?>

    <?php if (isset($_SESSION['ok_password'])) { ?>
        <div class="mensaje-ok">
            <?php
            echo htmlspecialchars($_SESSION['ok_password']);
            unset($_SESSION['ok_password']);
            ?>
        </div>
    <?php } ?>

    <form action="enviar_recuperacion.php" method="POST">
        <label>Correo electrónico</label>
        <input type="email" name="correo" required>

        <button type="submit" class="btn">Enviar recuperación</button>
        <a href="login.php" class="btn">Volver al login</a>
    </form>
</div>

</body>
</html>
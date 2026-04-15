<?php
session_start();

if (isset($_SESSION['rol'])) {
    if ($_SESSION['rol'] == 'directivo') {
        header("Location: directivo/dashboard.php");
        exit();
    } elseif ($_SESSION['rol'] == 'coordinador') {
        header("Location: coordinador/dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Sistema de Reservas</title>
<link rel="stylesheet" href="assets/css/estilos.css">
</head>

<body>

<header class="topbar">
    <a href="index.php" class="logo-area">
        <img src="assets/img/Logo.jpeg" class="logo-img" alt="Logo">
        <div class="logo-text">Centro universitario de valladolid</div>
    </a>
</header>

<main class="contenedor inicio">
</br>
</br>
</br>
</br>
</br>
    <h1>Bienvenido al sistema</h1>
    <p>Sistema de reservas</p>

    <div class="botones-inicio">
        <a href="auth/login.php" class="btn">Iniciar sesión</a>
    </div>
</main>

</body>
</html>
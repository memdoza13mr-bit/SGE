<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$password = trim($_POST['password'] ?? '');
$password2 = trim($_POST['password2'] ?? '');

if ($password === '' || $password2 === '') {
    $_SESSION['error_password'] = "Completa todos los campos.";
    header("Location: cambiar_password.php");
    exit();
}

if ($password !== $password2) {
    $_SESSION['error_password'] = "Las contraseñas no coinciden.";
    header("Location: cambiar_password.php");
    exit();
}

if (strlen($password) < 6) {
    $_SESSION['error_password'] = "Mínimo 6 caracteres.";
    header("Location: cambiar_password.php");
    exit();
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$sql = "UPDATE usuarios 
        SET password = ?, debe_cambiar_password = 0 
        WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("si", $password_hash, $_SESSION['usuario_id']);
$stmt->execute();

/* redirigir al sistema */
header("Location: ../coordinador/dashboard.php");
exit();
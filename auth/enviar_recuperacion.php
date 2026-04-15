<?php
session_start();
include("../config/conexion.php");

$correo = trim($_POST['correo'] ?? '');

if ($correo === '') {
    $_SESSION['error_password'] = "Debes escribir tu correo.";
    header("Location: olvide_password.php");
    exit();
}

$sqlUsuario = "SELECT id, nombre, correo, rol_id FROM usuarios WHERE correo = ? LIMIT 1";
$stmtUsuario = $conexion->prepare($sqlUsuario);
$stmtUsuario->bind_param("s", $correo);
$stmtUsuario->execute();
$resUsuario = $stmtUsuario->get_result();
$usuario = $resUsuario->fetch_assoc();

if (!$usuario) {
    $_SESSION['error_password'] = "No existe un usuario con ese correo.";
    header("Location: olvide_password.php");
    exit();
}

/* opcional: solo coordinadores */
if ((int)$usuario['rol_id'] !== 2) {
    $_SESSION['error_password'] = "Solo los coordinadores pueden usar esta recuperación.";
    header("Location: olvide_password.php");
    exit();
}

$usuario_id = (int)$usuario['id'];
$token = bin2hex(random_bytes(32));
$expiracion = date('Y-m-d H:i:s', strtotime('+30 minutes'));

/* invalidar tokens anteriores */
$sqlInvalidar = "UPDATE password_resets SET usado = 1 WHERE usuario_id = ? AND usado = 0";
$stmtInvalidar = $conexion->prepare($sqlInvalidar);
$stmtInvalidar->bind_param("i", $usuario_id);
$stmtInvalidar->execute();

/* guardar token nuevo */
$sqlInsert = "INSERT INTO password_resets (usuario_id, correo, token, expiracion, usado)
              VALUES (?, ?, ?, ?, 0)";
$stmtInsert = $conexion->prepare($sqlInsert);
$stmtInsert->bind_param("isss", $usuario_id, $correo, $token, $expiracion);
$stmtInsert->execute();

/*
    Como normalmente en localhost no hay correo real configurado,
    por ahora mostramos el enlace directamente.
    Más adelante, si quieres, lo conectamos con PHPMailer.
*/
$enlace = "http://localhost/proyecto2.0/auth/restablecer_password.php?token=" . urlencode($token);

$_SESSION['ok_password'] = "Recuperación generada. Abre este enlace: " . $enlace;
header("Location: olvide_password.php");
exit();
?>
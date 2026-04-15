<?php
session_start();
include("../config/conexion.php");

$token = trim($_POST['token'] ?? '');
$password = trim($_POST['password'] ?? '');
$password2 = trim($_POST['password2'] ?? '');

if ($token === '' || $password === '' || $password2 === '') {
    $_SESSION['error_password'] = "Debes completar todos los campos.";
    header("Location: restablecer_password.php?token=" . urlencode($token));
    exit();
}

if ($password !== $password2) {
    $_SESSION['error_password'] = "Las contraseñas no coinciden.";
    header("Location: restablecer_password.php?token=" . urlencode($token));
    exit();
}

if (strlen($password) < 6) {
    $_SESSION['error_password'] = "La contraseña debe tener al menos 6 caracteres.";
    header("Location: restablecer_password.php?token=" . urlencode($token));
    exit();
}

$sql = "SELECT * FROM password_resets WHERE token = ? AND usado = 0 LIMIT 1";
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

$usuario_id = (int)$reset['usuario_id'];
$password_hash = password_hash($password, PASSWORD_DEFAULT);

$conexion->begin_transaction();

try {
    $sqlUpdate = "UPDATE usuarios SET password = ? WHERE id = ?";
    $stmtUpdate = $conexion->prepare($sqlUpdate);
    $stmtUpdate->bind_param("si", $password_hash, $usuario_id);
    $stmtUpdate->execute();

    $sqlUsado = "UPDATE password_resets SET usado = 1 WHERE id = ?";
    $stmtUsado = $conexion->prepare($sqlUsado);
    $stmtUsado->bind_param("i", $reset['id']);
    $stmtUsado->execute();

    $conexion->commit();

    $_SESSION['ok_password'] = "Tu contraseña se cambió correctamente. Ya puedes iniciar sesión.";
    header("Location: login.php");
    exit();

} catch (Exception $e) {
    $conexion->rollback();
    $_SESSION['error_password'] = "No se pudo actualizar la contraseña.";
    header("Location: restablecer_password.php?token=" . urlencode($token));
    exit();
}
?>
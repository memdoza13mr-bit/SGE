<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'coordinador') {
    header("Location: ../auth/login.php");
    exit();
}

$coordinador_id = (int)$_SESSION['usuario_id'];
$solicitud_id = (int)($_POST['solicitud_id'] ?? 0);
$password = trim($_POST['password'] ?? '');
$password2 = trim($_POST['password2'] ?? '');

if ($solicitud_id <= 0) {
    $_SESSION['error_password'] = "Solicitud inválida.";
    header("Location: cambiar_password_autorizado.php");
    exit();
}

if ($password === '' || $password2 === '') {
    $_SESSION['error_password'] = "Debes completar todos los campos.";
    header("Location: cambiar_password_autorizado.php");
    exit();
}

if ($password !== $password2) {
    $_SESSION['error_password'] = "Las contraseñas no coinciden.";
    header("Location: cambiar_password_autorizado.php");
    exit();
}

if (strlen($password) < 6) {
    $_SESSION['error_password'] = "La contraseña debe tener al menos 6 caracteres.";
    header("Location: cambiar_password_autorizado.php");
    exit();
}

$sql = "SELECT *
        FROM solicitudes_cambio_password
        WHERE id = ? AND coordinador_id = ? AND estado = 'autorizada'
        LIMIT 1";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    $_SESSION['error_password'] = "No se pudo validar la solicitud.";
    header("Location: cambiar_password_autorizado.php");
    exit();
}

$stmt->bind_param("ii", $solicitud_id, $coordinador_id);
$stmt->execute();
$solicitud = $stmt->get_result()->fetch_assoc();

if (!$solicitud) {
    $_SESSION['error_password'] = "La solicitud no es válida o ya no está autorizada.";
    header("Location: cambiar_password_autorizado.php");
    exit();
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$conexion->begin_transaction();

try {
    $sqlUpdateUser = "UPDATE usuarios
                      SET password = ?
                      WHERE id = ?";
    $stmtUpdateUser = $conexion->prepare($sqlUpdateUser);

    if (!$stmtUpdateUser) {
        throw new Exception("No se pudo preparar la actualización del usuario.");
    }

    $stmtUpdateUser->bind_param("si", $password_hash, $coordinador_id);

    if (!$stmtUpdateUser->execute()) {
        throw new Exception("No se pudo actualizar la contraseña del usuario.");
    }

    $sqlCerrar = "UPDATE solicitudes_cambio_password
                  SET estado = 'rechazada',
                      respuesta_director = CONCAT(IFNULL(respuesta_director,''), '\n\nCambio aplicado correctamente.')
                  WHERE id = ?";
    $stmtCerrar = $conexion->prepare($sqlCerrar);

    if (!$stmtCerrar) {
        throw new Exception("No se pudo preparar el cierre de la solicitud.");
    }

    $stmtCerrar->bind_param("i", $solicitud_id);

    if (!$stmtCerrar->execute()) {
        throw new Exception("No se pudo actualizar la solicitud.");
    }

    $conexion->commit();

    $_SESSION['ok_password'] = "Tu contraseña se cambió correctamente.";
    header("Location: dashboard.php");
    exit();

} catch (Exception $e) {
    $conexion->rollback();
    $_SESSION['error_password'] = "No se pudo actualizar la contraseña.";
    header("Location: cambiar_password_autorizado.php");
    exit();
}
?>
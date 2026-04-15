<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'coordinador') {
    header("Location: ../auth/login.php");
    exit();
}

$coordinador_id = (int)$_SESSION['usuario_id'];
$motivo = trim($_POST['motivo'] ?? '');

if ($motivo === '') {
    $_SESSION['error_password'] = "Debes escribir el motivo de la solicitud.";
    header("Location: solicitar_cambio_password.php");
    exit();
}

/* evitar múltiples solicitudes pendientes */
$sqlPendiente = "SELECT id
                 FROM solicitudes_cambio_password
                 WHERE coordinador_id = ? AND estado = 'pendiente'
                 LIMIT 1";
$stmtPendiente = $conexion->prepare($sqlPendiente);

if (!$stmtPendiente) {
    $_SESSION['error_password'] = "No se pudo validar si ya existe una solicitud pendiente.";
    header("Location: solicitar_cambio_password.php");
    exit();
}

$stmtPendiente->bind_param("i", $coordinador_id);
$stmtPendiente->execute();
$resPendiente = $stmtPendiente->get_result();

if ($resPendiente->num_rows > 0) {
    $_SESSION['error_password'] = "Ya tienes una solicitud pendiente.";
    header("Location: solicitar_cambio_password.php");
    exit();
}

$sql = "INSERT INTO solicitudes_cambio_password (coordinador_id, motivo, estado)
        VALUES (?, ?, 'pendiente')";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    $_SESSION['error_password'] = "No se pudo preparar la solicitud.";
    header("Location: solicitar_cambio_password.php");
    exit();
}

$stmt->bind_param("is", $coordinador_id, $motivo);

if ($stmt->execute()) {
    $_SESSION['ok_password'] = "Tu solicitud fue enviada correctamente.";
} else {
    $_SESSION['error_password'] = "No se pudo guardar la solicitud.";
}

header("Location: solicitar_cambio_password.php");
exit();
?>
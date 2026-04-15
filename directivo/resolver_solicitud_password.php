<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

$id = (int)($_POST['id'] ?? 0);
$accion = trim($_POST['accion'] ?? '');
$respuesta_director = trim($_POST['respuesta_director'] ?? '');

if ($id <= 0) {
    $_SESSION['error_password'] = "ID de solicitud inválido.";
    header("Location: solicitudes_password.php");
    exit();
}

if (!in_array($accion, ['autorizar', 'rechazar'], true)) {
    $_SESSION['error_password'] = "Acción no válida.";
    header("Location: solicitudes_password.php");
    exit();
}

if ($accion === 'rechazar' && $respuesta_director === '') {
    $_SESSION['error_password'] = "Debes escribir una respuesta del director para rechazar la solicitud.";
    header("Location: solicitudes_password.php");
    exit();
}

/* buscar solicitud */
$sqlBuscar = "SELECT *
              FROM solicitudes_cambio_password
              WHERE id = ?
              LIMIT 1";
$stmtBuscar = $conexion->prepare($sqlBuscar);

if (!$stmtBuscar) {
    $_SESSION['error_password'] = "No se pudo validar la solicitud.";
    header("Location: solicitudes_password.php");
    exit();
}

$stmtBuscar->bind_param("i", $id);
$stmtBuscar->execute();
$solicitud = $stmtBuscar->get_result()->fetch_assoc();

if (!$solicitud) {
    $_SESSION['error_password'] = "Solicitud no encontrada.";
    header("Location: solicitudes_password.php");
    exit();
}

if (($solicitud['estado'] ?? '') !== 'pendiente') {
    $_SESSION['error_password'] = "Solo se pueden resolver solicitudes en estado pendiente.";
    header("Location: solicitudes_password.php");
    exit();
}

$nuevoEstado = ($accion === 'autorizar') ? 'autorizada' : 'rechazada';

$sqlUpdate = "UPDATE solicitudes_cambio_password
              SET estado = ?, respuesta_director = ?, fecha_respuesta = NOW()
              WHERE id = ?
              LIMIT 1";
$stmtUpdate = $conexion->prepare($sqlUpdate);

if (!$stmtUpdate) {
    $_SESSION['error_password'] = "No se pudo preparar la resolución de la solicitud.";
    header("Location: solicitudes_password.php");
    exit();
}

$stmtUpdate->bind_param("ssi", $nuevoEstado, $respuesta_director, $id);

if ($stmtUpdate->execute() && $stmtUpdate->affected_rows > 0) {
    if ($nuevoEstado === 'autorizada') {
        $_SESSION['ok_password'] = "La solicitud fue autorizada correctamente.";
    } else {
        $_SESSION['ok_password'] = "La solicitud fue rechazada correctamente.";
    }
} else {
    $_SESSION['error_password'] = "No se pudo resolver la solicitud.";
}

header("Location: solicitudes_password.php");
exit();
?>
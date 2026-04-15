<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

$id = (int)($_POST['id'] ?? 0);
$observaciones = trim($_POST['observaciones_directivo'] ?? '');

if ($id <= 0) {
    $_SESSION['error_reserva'] = "ID de reserva inválido.";
    header("Location: reservas.php");
    exit();
}

if ($observaciones === '') {
    $_SESSION['error_reserva'] = "Debes escribir observaciones para regresar la reserva a edición.";
    header("Location: regresar_reserva.php?id=" . $id);
    exit();
}

/* validar que exista la reserva */
$sqlBuscar = "SELECT id, estado
              FROM reservas
              WHERE id = ?
              LIMIT 1";
$stmtBuscar = $conexion->prepare($sqlBuscar);

if (!$stmtBuscar) {
    $_SESSION['error_reserva'] = "No se pudo validar la reserva.";
    header("Location: reservas.php");
    exit();
}

$stmtBuscar->bind_param("i", $id);
$stmtBuscar->execute();
$reserva = $stmtBuscar->get_result()->fetch_assoc();

if (!$reserva) {
    $_SESSION['error_reserva'] = "Reserva no encontrada.";
    header("Location: reservas.php");
    exit();
}

if (($reserva['estado'] ?? '') !== 'pendiente') {
    $_SESSION['error_reserva'] = "Solo se pueden regresar a edición reservas en estado pendiente.";
    header("Location: ver_reserva.php?id=" . $id);
    exit();
}

/* actualizar reserva */
$sql = "UPDATE reservas
        SET estado = 'correccion',
            observaciones_directivo = ?
        WHERE id = ?
        LIMIT 1";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    $_SESSION['error_reserva'] = "No se pudo preparar la actualización de la reserva.";
    header("Location: regresar_reserva.php?id=" . $id);
    exit();
}

$stmt->bind_param("si", $observaciones, $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $_SESSION['ok_reserva'] = "La reserva fue regresada a edición correctamente.";
} else {
    $_SESSION['error_reserva'] = "No se pudo regresar la reserva a edición.";
}

header("Location: ver_reserva.php?id=" . $id);
exit();
?>
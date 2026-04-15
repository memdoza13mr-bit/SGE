<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['error_reserva'] = "ID de reserva inválido.";
    header("Location: reservas.php");
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

if (($reserva['estado'] ?? '') !== 'denegada') {
    $_SESSION['error_reserva'] = "Solo se pueden restaurar reservas en estado denegada.";
    header("Location: reservas.php");
    exit();
}

/* restaurar a pendiente */
$sql = "UPDATE reservas
        SET estado = 'pendiente',
            observaciones_directivo = NULL
        WHERE id = ?
        LIMIT 1";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    $_SESSION['error_reserva'] = "No se pudo preparar la restauración de la reserva.";
    header("Location: reservas.php");
    exit();
}

$stmt->bind_param("i", $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $_SESSION['ok_reserva'] = "La reserva fue restaurada a pendiente correctamente.";
} else {
    $_SESSION['error_reserva'] = "No se pudo restaurar la reserva.";
}

header("Location: reservas.php");
exit();
?>
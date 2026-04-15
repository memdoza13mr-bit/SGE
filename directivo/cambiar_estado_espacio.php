<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$estado = trim($_GET['estado'] ?? '');

$permitidos = ['disponible', 'mantenimiento', 'inactivo'];

if ($id <= 0) {
    $_SESSION['error_espacio'] = "ID de espacio inválido.";
    header("Location: espacios.php");
    exit();
}

if (!in_array($estado, $permitidos, true)) {
    $_SESSION['error_espacio'] = "Estado no válido.";
    header("Location: espacios.php");
    exit();
}

/* verificar que el espacio exista */
$sqlBuscar = "SELECT id, nombre, estatus
              FROM espacios
              WHERE id = ?
              LIMIT 1";
$stmtBuscar = $conexion->prepare($sqlBuscar);

if (!$stmtBuscar) {
    $_SESSION['error_espacio'] = "No se pudo validar el espacio.";
    header("Location: espacios.php");
    exit();
}

$stmtBuscar->bind_param("i", $id);
$stmtBuscar->execute();
$espacio = $stmtBuscar->get_result()->fetch_assoc();

if (!$espacio) {
    $_SESSION['error_espacio'] = "Espacio no encontrado.";
    header("Location: espacios.php");
    exit();
}

if (($espacio['estatus'] ?? '') === $estado) {
    $_SESSION['ok_espacio'] = "El espacio ya estaba en estado " . $estado . ".";
    header("Location: espacios.php");
    exit();
}

/* actualizar estado */
$sql = "UPDATE espacios
        SET estatus = ?
        WHERE id = ?
        LIMIT 1";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    $_SESSION['error_espacio'] = "No se pudo preparar el cambio de estado.";
    header("Location: espacios.php");
    exit();
}

$stmt->bind_param("si", $estado, $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $_SESSION['ok_espacio'] = "Estado del espacio actualizado a: " . $estado;
} else {
    $_SESSION['error_espacio'] = "No se pudo actualizar el estado del espacio.";
}

header("Location: espacios.php");
exit();
?>
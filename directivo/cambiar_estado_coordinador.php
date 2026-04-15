<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$estado = trim($_GET['estado'] ?? '');

if ($id <= 0) {
    $_SESSION['error_usuario'] = "ID de coordinador inválido.";
    header("Location: coordinadores.php");
    exit();
}

if (!in_array($estado, ['activo', 'inactivo'], true)) {
    $_SESSION['error_usuario'] = "Estado no válido.";
    header("Location: coordinadores.php");
    exit();
}

/* verificar que exista y que sí sea coordinador */
$sqlBuscar = "SELECT id, nombre, apellido_paterno, estatus
              FROM usuarios
              WHERE id = ? AND rol_id = 2
              LIMIT 1";

$stmtBuscar = $conexion->prepare($sqlBuscar);

if (!$stmtBuscar) {
    $_SESSION['error_usuario'] = "No se pudo validar el coordinador.";
    header("Location: coordinadores.php");
    exit();
}

$stmtBuscar->bind_param("i", $id);
$stmtBuscar->execute();
$coordinador = $stmtBuscar->get_result()->fetch_assoc();

if (!$coordinador) {
    $_SESSION['error_usuario'] = "Coordinador no encontrado.";
    header("Location: coordinadores.php");
    exit();
}

if (($coordinador['estatus'] ?? '') === $estado) {
    $nombreCompleto = trim(($coordinador['nombre'] ?? '') . ' ' . ($coordinador['apellido_paterno'] ?? ''));
    $_SESSION['ok_usuario'] = "El coordinador " . $nombreCompleto . " ya estaba en estado " . $estado . ".";
    header("Location: coordinadores.php");
    exit();
}

/* actualizar estado */
$sqlUpdate = "UPDATE usuarios
              SET estatus = ?
              WHERE id = ? AND rol_id = 2
              LIMIT 1";

$stmtUpdate = $conexion->prepare($sqlUpdate);

if (!$stmtUpdate) {
    $_SESSION['error_usuario'] = "No se pudo preparar el cambio de estado.";
    header("Location: coordinadores.php");
    exit();
}

$stmtUpdate->bind_param("si", $estado, $id);

if ($stmtUpdate->execute() && $stmtUpdate->affected_rows > 0) {
    $nombreCompleto = trim(($coordinador['nombre'] ?? '') . ' ' . ($coordinador['apellido_paterno'] ?? ''));
    $_SESSION['ok_usuario'] = "El coordinador " . $nombreCompleto . " ahora está " . $estado . ".";
} else {
    $_SESSION['error_usuario'] = "No se pudo cambiar el estado del coordinador.";
}

header("Location: coordinadores.php");
exit();
?>
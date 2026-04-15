<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

$id = (int)($_POST['id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$ubicacion = trim($_POST['ubicacion'] ?? '');

$capacidad = ($_POST['capacidad'] ?? '') !== '' ? (int)$_POST['capacidad'] : null;
$minimo_alumnos = ($_POST['minimo_alumnos'] ?? '') !== '' ? (int)$_POST['minimo_alumnos'] : null;
$maximo_alumnos = ($_POST['maximo_alumnos'] ?? '') !== '' ? (int)$_POST['maximo_alumnos'] : null;

if ($id <= 0) {
    $_SESSION['error_espacio'] = "ID de espacio inválido.";
    header("Location: espacios.php");
    exit();
}

if ($nombre === '') {
    $_SESSION['error_espacio'] = "El nombre del espacio es obligatorio.";
    header("Location: editar_espacio.php?id=" . $id);
    exit();
}

if ($capacidad !== null && $capacidad < 0) {
    $_SESSION['error_espacio'] = "La capacidad no puede ser negativa.";
    header("Location: editar_espacio.php?id=" . $id);
    exit();
}

if ($minimo_alumnos !== null && $minimo_alumnos < 1) {
    $_SESSION['error_espacio'] = "El mínimo de alumnos debe ser mayor a 0.";
    header("Location: editar_espacio.php?id=" . $id);
    exit();
}

if ($maximo_alumnos !== null && $maximo_alumnos < 1) {
    $_SESSION['error_espacio'] = "El máximo de alumnos debe ser mayor a 0.";
    header("Location: editar_espacio.php?id=" . $id);
    exit();
}

if ($minimo_alumnos !== null && $maximo_alumnos !== null && $minimo_alumnos > $maximo_alumnos) {
    $_SESSION['error_espacio'] = "El mínimo de alumnos no puede ser mayor que el máximo.";
    header("Location: editar_espacio.php?id=" . $id);
    exit();
}

/* verificar que el espacio exista */
$sqlExiste = "SELECT id FROM espacios WHERE id = ? LIMIT 1";
$stmtExiste = $conexion->prepare($sqlExiste);

if (!$stmtExiste) {
    $_SESSION['error_espacio'] = "No se pudo validar el espacio.";
    header("Location: espacios.php");
    exit();
}

$stmtExiste->bind_param("i", $id);
$stmtExiste->execute();
$espacioExiste = $stmtExiste->get_result()->fetch_assoc();

if (!$espacioExiste) {
    $_SESSION['error_espacio'] = "Espacio no encontrado.";
    header("Location: espacios.php");
    exit();
}

$sql = "UPDATE espacios
        SET nombre = ?, descripcion = ?, ubicacion = ?, capacidad = ?, minimo_alumnos = ?, maximo_alumnos = ?
        WHERE id = ?";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    $_SESSION['error_espacio'] = "No se pudo preparar la actualización del espacio.";
    header("Location: editar_espacio.php?id=" . $id);
    exit();
}

$stmt->bind_param(
    "sssiiii",
    $nombre,
    $descripcion,
    $ubicacion,
    $capacidad,
    $minimo_alumnos,
    $maximo_alumnos,
    $id
);

if ($stmt->execute()) {
    $_SESSION['ok_espacio'] = "Espacio actualizado correctamente.";
} else {
    $_SESSION['error_espacio'] = "No se pudo actualizar el espacio.";
}

header("Location: espacios.php");
exit();
?>
<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$ubicacion = trim($_POST['ubicacion'] ?? '');

$capacidad = ($_POST['capacidad'] ?? '') !== '' ? (int)$_POST['capacidad'] : null;
$minimo_alumnos = ($_POST['minimo_alumnos'] ?? '') !== '' ? (int)$_POST['minimo_alumnos'] : null;
$maximo_alumnos = ($_POST['maximo_alumnos'] ?? '') !== '' ? (int)$_POST['maximo_alumnos'] : null;

if ($nombre === '') {
    $_SESSION['error_espacio'] = "El nombre del espacio es obligatorio.";
    header("Location: espacios.php");
    exit();
}

if ($capacidad !== null && $capacidad < 0) {
    $_SESSION['error_espacio'] = "La capacidad no puede ser negativa.";
    header("Location: espacios.php");
    exit();
}

if ($minimo_alumnos !== null && $minimo_alumnos < 1) {
    $_SESSION['error_espacio'] = "El mínimo de alumnos debe ser mayor a 0.";
    header("Location: espacios.php");
    exit();
}

if ($maximo_alumnos !== null && $maximo_alumnos < 1) {
    $_SESSION['error_espacio'] = "El máximo de alumnos debe ser mayor a 0.";
    header("Location: espacios.php");
    exit();
}

if ($minimo_alumnos !== null && $maximo_alumnos !== null && $minimo_alumnos > $maximo_alumnos) {
    $_SESSION['error_espacio'] = "El mínimo de alumnos no puede ser mayor que el máximo.";
    header("Location: espacios.php");
    exit();
}

$sql = "INSERT INTO espacios (nombre, descripcion, ubicacion, capacidad, minimo_alumnos, maximo_alumnos)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    $_SESSION['error_espacio'] = "No se pudo preparar el guardado del espacio.";
    header("Location: espacios.php");
    exit();
}

$stmt->bind_param(
    "sssiii",
    $nombre,
    $descripcion,
    $ubicacion,
    $capacidad,
    $minimo_alumnos,
    $maximo_alumnos
);

if ($stmt->execute()) {
    $_SESSION['ok_espacio'] = "Espacio guardado correctamente.";
} else {
    $_SESSION['error_espacio'] = "No se pudo guardar el espacio.";
}

header("Location: espacios.php");
exit();
?>
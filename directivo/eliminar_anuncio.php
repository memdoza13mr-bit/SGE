<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: anuncios.php");
    exit();
}

/* validar que exista */
$sqlBuscar = "SELECT id FROM anuncios WHERE id = ? LIMIT 1";
$stmtBuscar = $conexion->prepare($sqlBuscar);

if (!$stmtBuscar) {
    header("Location: anuncios.php");
    exit();
}

$stmtBuscar->bind_param("i", $id);
$stmtBuscar->execute();
$anuncio = $stmtBuscar->get_result()->fetch_assoc();

if (!$anuncio) {
    header("Location: anuncios.php");
    exit();
}

/* eliminar */
$sql = "DELETE FROM anuncios WHERE id = ? LIMIT 1";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    header("Location: anuncios.php");
    exit();
}

$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: anuncios.php");
exit();
?>
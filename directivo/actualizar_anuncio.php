<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

$id = (int)($_POST['id'] ?? 0);
$titulo = trim($_POST['titulo'] ?? '');
$contenido = trim($_POST['contenido'] ?? '');
$estatus = trim($_POST['estatus'] ?? 'activo');

$permitidos = ['activo', 'inactivo'];

if ($id <= 0) {
    $_SESSION['error_anuncio'] = "ID de anuncio inválido.";
    header("Location: anuncios.php");
    exit();
}

if ($titulo === '' || $contenido === '') {
    $_SESSION['error_anuncio'] = "El título y el contenido son obligatorios.";
    header("Location: anuncios.php");
    exit();
}

if (!in_array($estatus, $permitidos, true)) {
    $_SESSION['error_anuncio'] = "El estado del anuncio no es válido.";
    header("Location: anuncios.php");
    exit();
}

/* validar que exista */
$sqlBuscar = "SELECT id FROM anuncios WHERE id = ? LIMIT 1";
$stmtBuscar = $conexion->prepare($sqlBuscar);

if (!$stmtBuscar) {
    $_SESSION['error_anuncio'] = "No se pudo validar el anuncio.";
    header("Location: anuncios.php");
    exit();
}

$stmtBuscar->bind_param("i", $id);
$stmtBuscar->execute();
$anuncio = $stmtBuscar->get_result()->fetch_assoc();

if (!$anuncio) {
    $_SESSION['error_anuncio'] = "Anuncio no encontrado.";
    header("Location: anuncios.php");
    exit();
}

/* actualizar */
$sql = "UPDATE anuncios
        SET titulo = ?, contenido = ?, estatus = ?
        WHERE id = ?
        LIMIT 1";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    $_SESSION['error_anuncio'] = "No se pudo preparar la actualización del anuncio.";
    header("Location: anuncios.php");
    exit();
}

$stmt->bind_param("sssi", $titulo, $contenido, $estatus, $id);

if ($stmt->execute()) {
    $_SESSION['ok_anuncio'] = "Anuncio actualizado correctamente.";
} else {
    $_SESSION['error_anuncio'] = "No se pudo actualizar el anuncio.";
}

header("Location: anuncios.php");
exit();
?>
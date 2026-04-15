<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

$id = (int)($_POST['id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
$apellido_materno = trim($_POST['apellido_materno'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$licenciaturas = $_POST['licenciaturas'] ?? [];

if ($id <= 0) {
    $_SESSION['error_usuario'] = "ID inválido.";
    header("Location: coordinadores.php");
    exit();
}

if ($nombre === '' || $apellido_paterno === '' || $correo === '') {
    $_SESSION['error_usuario'] = "Debes llenar los campos obligatorios.";
    header("Location: editar_coordinador.php?id=" . $id);
    exit();
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_usuario'] = "El correo no es válido.";
    header("Location: editar_coordinador.php?id=" . $id);
    exit();
}

if (!is_array($licenciaturas) || count($licenciaturas) === 0) {
    $_SESSION['error_usuario'] = "Debes seleccionar al menos una licenciatura.";
    header("Location: editar_coordinador.php?id=" . $id);
    exit();
}

/* limpiar y evitar duplicados */
$licenciaturasLimpias = [];
foreach ($licenciaturas as $lic) {
    $lic = trim($lic);
    if ($lic !== '') {
        $licenciaturasLimpias[] = $lic;
    }
}
$licenciaturasLimpias = array_values(array_unique($licenciaturasLimpias));

if (count($licenciaturasLimpias) === 0) {
    $_SESSION['error_usuario'] = "Debes seleccionar al menos una licenciatura válida.";
    header("Location: editar_coordinador.php?id=" . $id);
    exit();
}

/* verificar que exista el coordinador */
$sqlExisteCoord = "SELECT id
                   FROM usuarios
                   WHERE id = ? AND rol_id = 2
                   LIMIT 1";
$stmtExisteCoord = $conexion->prepare($sqlExisteCoord);

if (!$stmtExisteCoord) {
    $_SESSION['error_usuario'] = "No se pudo validar el coordinador.";
    header("Location: coordinadores.php");
    exit();
}

$stmtExisteCoord->bind_param("i", $id);
$stmtExisteCoord->execute();
$coordExiste = $stmtExisteCoord->get_result()->fetch_assoc();

if (!$coordExiste) {
    $_SESSION['error_usuario'] = "Coordinador no encontrado.";
    header("Location: coordinadores.php");
    exit();
}

/* verificar correo duplicado en otro usuario */
$sqlCorreo = "SELECT id
              FROM usuarios
              WHERE correo = ? AND id <> ?
              LIMIT 1";
$stmtCorreo = $conexion->prepare($sqlCorreo);

if (!$stmtCorreo) {
    $_SESSION['error_usuario'] = "No se pudo validar el correo.";
    header("Location: editar_coordinador.php?id=" . $id);
    exit();
}

$stmtCorreo->bind_param("si", $correo, $id);
$stmtCorreo->execute();
$correoDuplicado = $stmtCorreo->get_result();

if ($correoDuplicado->num_rows > 0) {
    $_SESSION['error_usuario'] = "Ese correo ya está registrado por otro usuario.";
    header("Location: editar_coordinador.php?id=" . $id);
    exit();
}

$conexion->begin_transaction();

try {
    /* actualizar datos básicos */
    $sqlUpdate = "UPDATE usuarios
                  SET nombre = ?, apellido_paterno = ?, apellido_materno = ?, correo = ?
                  WHERE id = ? AND rol_id = 2";
    $stmtUpdate = $conexion->prepare($sqlUpdate);

    if (!$stmtUpdate) {
        throw new Exception("No se pudo preparar la actualización del coordinador.");
    }

    $stmtUpdate->bind_param(
        "ssssi",
        $nombre,
        $apellido_paterno,
        $apellido_materno,
        $correo,
        $id
    );

    if (!$stmtUpdate->execute()) {
        throw new Exception("No se pudo actualizar el coordinador.");
    }

    /* borrar licenciaturas actuales */
    $sqlDeleteLic = "DELETE FROM coordinador_licenciaturas WHERE coordinador_id = ?";
    $stmtDeleteLic = $conexion->prepare($sqlDeleteLic);

    if (!$stmtDeleteLic) {
        throw new Exception("No se pudo preparar la actualización de licenciaturas.");
    }

    $stmtDeleteLic->bind_param("i", $id);

    if (!$stmtDeleteLic->execute()) {
        throw new Exception("No se pudieron limpiar las licenciaturas anteriores.");
    }

    /* insertar nuevas licenciaturas */
    $sqlInsertLic = "INSERT INTO coordinador_licenciaturas (coordinador_id, licenciatura)
                     VALUES (?, ?)";
    $stmtInsertLic = $conexion->prepare($sqlInsertLic);

    if (!$stmtInsertLic) {
        throw new Exception("No se pudo preparar el guardado de licenciaturas.");
    }

    foreach ($licenciaturasLimpias as $lic) {
        $stmtInsertLic->bind_param("is", $id, $lic);

        if (!$stmtInsertLic->execute()) {
            throw new Exception("No se pudo guardar una de las licenciaturas.");
        }
    }

    $conexion->commit();

    $_SESSION['ok_usuario'] = "Coordinador actualizado correctamente.";
    header("Location: editar_coordinador.php?id=" . $id);
    exit();

} catch (Exception $e) {
    $conexion->rollback();
    $_SESSION['error_usuario'] = $e->getMessage();
    header("Location: editar_coordinador.php?id=" . $id);
    exit();
}
?>
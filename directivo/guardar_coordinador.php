<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

$nombre = trim($_POST['nombre'] ?? '');
$apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
$apellido_materno = trim($_POST['apellido_materno'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$password = trim($_POST['password'] ?? '');
$licenciaturas = $_POST['licenciaturas'] ?? [];

if (
    $nombre === '' ||
    $apellido_paterno === '' ||
    $correo === '' ||
    $password === ''
) {
    $_SESSION['error_usuario'] = "Debes llenar los campos obligatorios.";
    header("Location: coordinadores.php");
    exit();
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_usuario'] = "El correo no es válido.";
    header("Location: coordinadores.php");
    exit();
}

if (!is_array($licenciaturas) || count($licenciaturas) === 0) {
    $_SESSION['error_usuario'] = "Debes seleccionar al menos una licenciatura.";
    header("Location: coordinadores.php");
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
    header("Location: coordinadores.php");
    exit();
}

/* verificar correo duplicado */
$sqlExiste = "SELECT id FROM usuarios WHERE correo = ? LIMIT 1";
$stmtExiste = $conexion->prepare($sqlExiste);

if (!$stmtExiste) {
    $_SESSION['error_usuario'] = "Error al validar el correo.";
    header("Location: coordinadores.php");
    exit();
}

$stmtExiste->bind_param("s", $correo);
$stmtExiste->execute();
$resExiste = $stmtExiste->get_result();

if ($resExiste->num_rows > 0) {
    $_SESSION['error_usuario'] = "Ese correo ya está registrado.";
    header("Location: coordinadores.php");
    exit();
}

/* contraseña segura */
$password_hash = password_hash($password, PASSWORD_DEFAULT);

/*
    Se asume:
    1 = directivo
    2 = coordinador
*/
$rol_id = 2;

/* transacción */
$conexion->begin_transaction();

try {
    /* guardar coordinador */
    $sql = "INSERT INTO usuarios
            (
                nombre,
                apellido_paterno,
                apellido_materno,
                correo,
                password,
                rol_id,
                estatus,
                debe_cambiar_password
            )
            VALUES (?, ?, ?, ?, ?, ?, 'activo', 1)";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception("No se pudo preparar la inserción del coordinador.");
    }

    $stmt->bind_param(
        "sssssi",
        $nombre,
        $apellido_paterno,
        $apellido_materno,
        $correo,
        $password_hash,
        $rol_id
    );

    if (!$stmt->execute()) {
        throw new Exception("No se pudo guardar el coordinador.");
    }

    $coordinador_id = $stmt->insert_id;

    /* guardar licenciaturas */
    $sqlLic = "INSERT INTO coordinador_licenciaturas (coordinador_id, licenciatura)
               VALUES (?, ?)";
    $stmtLic = $conexion->prepare($sqlLic);

    if (!$stmtLic) {
        throw new Exception("No se pudo preparar la inserción de licenciaturas.");
    }

    foreach ($licenciaturasLimpias as $lic) {
        $stmtLic->bind_param("is", $coordinador_id, $lic);

        if (!$stmtLic->execute()) {
            throw new Exception("No se pudo guardar una de las licenciaturas.");
        }
    }

    $conexion->commit();

    $_SESSION['ok_usuario'] = "Coordinador registrado correctamente.";
    header("Location: coordinadores.php");
    exit();

} catch (Exception $e) {
    $conexion->rollback();
    $_SESSION['error_usuario'] = $e->getMessage();
    header("Location: coordinadores.php");
    exit();
}
?>
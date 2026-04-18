<?php
session_start();
include("../config/conexion.php");

/* =========================
   INICIALIZAR CONTROL
========================= */
if (!isset($_SESSION['intentos_fallidos'])) {
    $_SESSION['intentos_fallidos'] = 0;
}

if (!isset($_SESSION['bloqueado_hasta'])) {
    $_SESSION['bloqueado_hasta'] = 0;
}

/* =========================
   VALIDAR BLOQUEO
========================= */
if ($_SESSION['bloqueado_hasta'] > time()) {
    header("Location: login.php");
    exit();
}

/* =========================
   VALIDAR MÉTODO
========================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

/* =========================
   CAPTURA DE DATOS
========================= */
$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';
$captcha_usuario = trim($_POST['captcha'] ?? '');

/* =========================
   VALIDAR CAPTCHA
========================= */
if (!isset($_SESSION['captcha_resultado']) || $captcha_usuario === '') {
    header("Location: login.php?error=captcha");
    exit();
}

if ((int)$captcha_usuario !== (int)$_SESSION['captcha_resultado']) {
    $_SESSION['intentos_fallidos']++;

    if ($_SESSION['intentos_fallidos'] >= 5) {
        $_SESSION['bloqueado_hasta'] = time() + (60 * 5);
        $_SESSION['intentos_fallidos'] = 0;
    }

    unset($_SESSION['captcha_resultado']);
    header("Location: login.php?error=captcha");
    exit();
}

/* El captcha ya no se necesita después de validarlo */
unset($_SESSION['captcha_resultado']);

/* =========================
   VALIDAR CAMPOS
========================= */
if ($correo === '' || $password === '') {
    header("Location: login.php?error=1");
    exit();
}

/* =========================
   BUSCAR USUARIO
========================= */
$sql = "SELECT 
            id,
            nombre,
            apellido_paterno,
            apellido_materno,
            correo,
            password,
            rol_id,
            estatus,
            debe_cambiar_password
        FROM usuarios
        WHERE correo = ?
          AND estatus = 'activo'
        LIMIT 1";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error al preparar la consulta de inicio de sesión.");
}

$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

/* =========================
   VALIDAR USUARIO Y PASSWORD
========================= */
if (!$usuario || !password_verify($password, $usuario['password'])) {
    $_SESSION['intentos_fallidos']++;

    if ($_SESSION['intentos_fallidos'] >= 5) {
        $_SESSION['bloqueado_hasta'] = time() + (60 * 5);
        $_SESSION['intentos_fallidos'] = 0;
    }

    header("Location: login.php?error=1");
    exit();
}

/* =========================
   LOGIN CORRECTO
========================= */
$_SESSION['intentos_fallidos'] = 0;
$_SESSION['bloqueado_hasta'] = 0;

/* =========================
   DATOS DE SESIÓN
========================= */
$_SESSION['usuario_id'] = (int)$usuario['id'];

$nombre = trim($usuario['nombre'] ?? '');
$apellido_paterno = trim($usuario['apellido_paterno'] ?? '');
$apellido_materno = trim($usuario['apellido_materno'] ?? '');

/* Guardar nombre base */
$_SESSION['nombre'] = $nombre;

/* =========================
   DETECTAR ROL
========================= */
if ((int)$usuario['rol_id'] === 1) {
    $_SESSION['rol'] = 'directivo';
} else {
    $_SESSION['rol'] = 'coordinador';
}

/* =========================
   LIMPIAR PREFIJOS DEL NOMBRE
========================= */
$nombre = preg_replace('/^(dr\.?\s*|coordinador\s+)/i', '', $nombre);
$nombre = trim($nombre);

/* =========================
   ARMAR NOMBRE COMPLETO
========================= */
$partesNombre = array_filter([$nombre, $apellido_paterno, $apellido_materno]);
$nombreBase = trim(implode(' ', $partesNombre));

if ($_SESSION['rol'] === 'directivo') {
    $prefijo = 'Dr. ';
} elseif ($_SESSION['rol'] === 'coordinador') {
    $prefijo = '';
} else {
    $prefijo = '';
}

$_SESSION['nombre_completo'] = trim($prefijo . $nombreBase);

/* =========================
   CAMBIO OBLIGATORIO DE CONTRASEÑA
========================= */
if ((int)$usuario['rol_id'] === 2 && (int)$usuario['debe_cambiar_password'] === 1) {
    header("Location: cambiar_password.php");
    exit();
}

/* =========================
   REDIRECCIÓN FINAL
========================= */
if ($_SESSION['rol'] === 'directivo') {
    header("Location: ../directivo/dashboard.php");
} else {
    header("Location: ../coordinador/dashboard.php");
}

exit();
?>
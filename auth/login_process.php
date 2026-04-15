<?php
session_start();
include("../config/conexion.php");

/* VALIDAR CAPTCHA */
$captcha_usuario = trim($_POST['captcha'] ?? '');

if (!isset($_SESSION['captcha_resultado']) || $captcha_usuario == '') {
    header("Location: login.php?error=captcha");
    exit();
}

if ((int)$captcha_usuario !== (int)$_SESSION['captcha_resultado']) {
    $_SESSION['intentos_fallidos']++;
    header("Location: login.php?error=captcha");
    exit();
}

/* BLOQUEO POR INTENTOS */
if ($_SESSION['bloqueado_hasta'] > time()) {
    header("Location: login.php");
    exit();
}

/* DATOS */
$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';

if ($correo === '' || $password === '') {
    header("Location: login.php?error=1");
    exit();
}

/* BUSCAR USUARIO */
$sql = "SELECT * FROM usuarios WHERE correo = ? AND estatus = 'activo' LIMIT 1";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error en la consulta");
}

$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

/* VALIDAR PASSWORD */
if (!$usuario || !password_verify($password, $usuario['password'])) {
    $_SESSION['intentos_fallidos']++;

    /* BLOQUEAR SI LLEGA A 5 */
    if ($_SESSION['intentos_fallidos'] >= 5) {
        $_SESSION['bloqueado_hasta'] = time() + (60 * 5); // 5 minutos
        $_SESSION['intentos_fallidos'] = 0;
    }

    header("Location: login.php?error=1");
    exit();
}

/* LOGIN CORRECTO → RESETEAR INTENTOS */
$_SESSION['intentos_fallidos'] = 0;
$_SESSION['bloqueado_hasta'] = 0;

/* GUARDAR SESIÓN */
$_SESSION['usuario_id'] = $usuario['id'];
$_SESSION['nombre'] = $usuario['nombre'];

/* DETECTAR ROL */
if ((int)$usuario['rol_id'] === 1) {
    $_SESSION['rol'] = 'directivo';
} else {
    $_SESSION['rol'] = 'coordinador';
}

/*  CAMBIO OBLIGATORIO DE CONTRASEÑA */
if ((int)$usuario['rol_id'] === 2 && (int)$usuario['debe_cambiar_password'] === 1) {
    header("Location: cambiar_password.php");
    exit();
}

/* REDIRECCIÓN NORMAL */
if ($_SESSION['rol'] === 'directivo') {
    header("Location: ../directivo/dashboard.php");
} else {
    header("Location: ../coordinador/dashboard.php");
}

exit();
?>
<?php
include("../config/conexion.php");

$nombre = trim($_POST['nombre']);
$apellido_paterno = trim($_POST['apellido_paterno']);
$apellido_materno = trim($_POST['apellido_materno']);
$correo = trim($_POST['correo']);
$telefono = trim($_POST['telefono']);
$password = $_POST['password'];
$confirmar_password = $_POST['confirmar_password'];

if ($password !== $confirmar_password) {
    die("Las contraseñas no coinciden");
}

$verificar = $conexion->prepare("SELECT id FROM usuarios WHERE correo = ?");
$verificar->bind_param("s", $correo);
$verificar->execute();
$resultado = $verificar->get_result();

if ($resultado->num_rows > 0) {
    die("Ese correo ya está registrado");
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);
$rol_id = 2; // coordinador

$sql = "INSERT INTO usuarios (
    nombre, apellido_paterno, apellido_materno, correo, telefono, password, rol_id
) VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conexion->prepare($sql);
$stmt->bind_param(
    "ssssssi",
    $nombre,
    $apellido_paterno,
    $apellido_materno,
    $correo,
    $telefono,
    $password_hash,
    $rol_id
);

if ($stmt->execute()) {
    header("Location: login.php");
    exit();
} else {
    echo "Error al registrar";
}
?>
<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    exit("ID de espacio inválido.");
}

$sql = "SELECT * FROM espacios WHERE id = ? LIMIT 1";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    exit("Error al consultar el espacio.");
}

$stmt->bind_param("i", $id);
$stmt->execute();
$espacio = $stmt->get_result()->fetch_assoc();

if (!$espacio) {
    exit("Espacio no encontrado.");
}

$titulo_pagina = 'Panel Directivo';
$menu_activo = 'espacios';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Espacio</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.card{
    background:#fff;
    border-radius:16px;
    padding:24px;
    box-shadow:0 4px 14px rgba(0,0,0,.06);
    margin-bottom:24px;
    max-width:900px;
}

.card h2{
    color:#1d4ed8;
    margin-bottom:18px;
    font-size:28px;
}

.acciones-form{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:10px;
}

@media (max-width: 900px){
    .main{
        padding:20px 14px;
    }
}
</style>
</head>
<body>

<?php include("../includes/menu_directivo.php"); ?>

<main class="main">
    <div class="card">
        <h2>Editar espacio</h2>

        <form action="actualizar_espacio.php" method="POST">
            <input type="hidden" name="id" value="<?php echo (int)$espacio['id']; ?>">

            <label>Nombre</label>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($espacio['nombre']); ?>" required>

            <label>Descripción</label>
            <textarea name="descripcion"><?php echo htmlspecialchars($espacio['descripcion'] ?? ''); ?></textarea>

            <label>Ubicación</label>
            <input type="text" name="ubicacion" value="<?php echo htmlspecialchars($espacio['ubicacion'] ?? ''); ?>">

            <label>Capacidad</label>
            <input type="number" name="capacidad" min="0" value="<?php echo htmlspecialchars($espacio['capacidad'] ?? ''); ?>">

            <label>Mínimo de alumnos permitido</label>
            <input type="number" name="minimo_alumnos" min="1" value="<?php echo htmlspecialchars($espacio['minimo_alumnos'] ?? ''); ?>">

            <label>Máximo de alumnos permitido</label>
            <input type="number" name="maximo_alumnos" min="1" value="<?php echo htmlspecialchars($espacio['maximo_alumnos'] ?? ''); ?>">

            <div class="acciones-form">
                <button type="submit" class="btn">Guardar cambios</button>
                <a href="espacios.php" class="btn">Volver</a>
            </div>
        </form>
    </div>
</main>

<?php include("../includes/footer_directivo.php"); ?>

</body>
</html>
<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT * FROM anuncios WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$anuncio = $stmt->get_result()->fetch_assoc();

if (!$anuncio) {
    echo "Anuncio no encontrado.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Anuncio</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body>

<header class="topbar">
    <a href="dashboard.php" class="logo-area">
        <img src="../assets/img/Logo.jpeg" class="logo-img" alt="Logo">
        <div class="logo-text">Menu principal</div>
    </a>
</header>

<div class="contenedor">
    <h2 class="titulo-azul">Editar anuncio</h2>

    <form action="actualizar_anuncio.php" method="POST">
        <input type="hidden" name="id" value="<?php echo (int)$anuncio['id']; ?>">

        <label>Título</label>
        <input type="text" name="titulo" value="<?php echo htmlspecialchars($anuncio['titulo'] ?? ''); ?>" required>

        <label>Contenido</label>
        <textarea name="contenido" required><?php echo htmlspecialchars($anuncio['contenido'] ?? ''); ?></textarea>

        <label>Estado</label>
        <select name="estatus">
            <option value="activo" <?php echo (($anuncio['estatus'] ?? '') === 'activo') ? 'selected' : ''; ?>>Activo</option>
            <option value="inactivo" <?php echo (($anuncio['estatus'] ?? '') === 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
        </select>

        <button type="submit" class="btn">Guardar cambios</button>
        <a href="anuncios.php" class="btn">Volver</a>
    </form>
</div>

</body>
</html>
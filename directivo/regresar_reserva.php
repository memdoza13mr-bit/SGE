<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    exit("Reserva inválida.");
}

$sql = "SELECT r.*, u.nombre, u.apellido_paterno
        FROM reservas r
        INNER JOIN usuarios u ON r.coordinador_id = u.id
        WHERE r.id = ?
        LIMIT 1";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    exit("Error al consultar la reserva.");
}

$stmt->bind_param("i", $id);
$stmt->execute();
$reserva = $stmt->get_result()->fetch_assoc();

if (!$reserva) {
    exit("Reserva no encontrada.");
}

$titulo_pagina = 'Panel Directivo';
$menu_activo = 'reservas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Regresar reserva para edición</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.card{
    max-width:900px;
    background:#fff;
    padding:30px;
    border-radius:14px;
    box-shadow:0 4px 14px rgba(0,0,0,.06);
}

.card h2{
    margin-bottom:18px;
}

.card p{
    margin:8px 0;
    line-height:1.6;
}

.acciones-form{
    margin-top:15px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

@media (max-width: 900px){
    .main{
        padding:20px 14px;
    }

    .card{
        padding:22px;
    }
}
</style>
</head>
<body>

<?php include("../includes/menu_directivo.php"); ?>

<main class="main">
    <div class="card">
        <h2 class="titulo-azul">Regresar reserva para edición</h2>

        <p><strong>Reserva:</strong>
            <?php echo htmlspecialchars($reserva['nombre_actividad'] ?? ('#' . (int)$reserva['id'])); ?>
        </p>

        <p><strong>Coordinador:</strong>
            <?php echo htmlspecialchars(($reserva['nombre'] ?? '') . ' ' . ($reserva['apellido_paterno'] ?? '')); ?>
        </p>

        <form action="guardar_regreso_reserva.php" method="POST">
            <input type="hidden" name="id" value="<?php echo (int)$reserva['id']; ?>">

            <label>Observaciones del directivo</label>
            <textarea name="observaciones_directivo" required placeholder="Escribe aquí qué debe corregir el coordinador..."></textarea>

            <div class="acciones-form">
                <button type="submit" class="btn">Regresar para edición</button>
                <a href="reservas.php?id=<?php echo (int)$reserva['id']; ?>" class="btn">Volver</a>
            </div>
        </form>
    </div>
</main>

<?php include("../includes/footer_directivo.php"); ?>

</body>
</html>
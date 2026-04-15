<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'coordinador') {
    header("Location: ../auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$coordinador_id = (int)$_SESSION['usuario_id'];

if ($id <= 0) {
    exit("Reserva inválida.");
}

$sqlReserva = "SELECT *
               FROM reservas
               WHERE id = ? AND coordinador_id = ?";
$stmtReserva = $conexion->prepare($sqlReserva);

if (!$stmtReserva) {
    exit("Error al consultar la reserva.");
}

$stmtReserva->bind_param("ii", $id, $coordinador_id);
$stmtReserva->execute();
$reserva = $stmtReserva->get_result()->fetch_assoc();

if (!$reserva) {
    exit("Reserva no encontrada.");
}

$sqlDetalles = "SELECT rd.*, e.nombre AS espacio
                FROM reserva_detalles rd
                INNER JOIN espacios e ON rd.espacio_id = e.id
                WHERE rd.reserva_id = ?
                ORDER BY rd.fecha, rd.hora_inicio";
$stmtDetalles = $conexion->prepare($sqlDetalles);

if (!$stmtDetalles) {
    exit("Error al consultar los detalles.");
}

$stmtDetalles->bind_param("i", $id);
$stmtDetalles->execute();
$detalles = $stmtDetalles->get_result();

function formatearHora12($hora) {
    if (!$hora) {
        return '';
    }
    $formato = date("h:i a", strtotime($hora));
    return str_replace(["am", "pm"], ["a. m.", "p. m."], $formato);
}

function formatearFecha($fecha) {
    if (!$fecha || $fecha === '0000-00-00') {
        return 'No especificada';
    }
    return date("d/m/Y", strtotime($fecha));
}

$titulo_pagina = 'Sistema de Reservas';
$menu_activo = 'mis_reservas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ver reserva</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.page-card{
    background:#fff;
    border-radius:18px;
    padding:24px;
    box-shadow:0 8px 24px rgba(15,23,42,.08);
    margin-bottom:24px;
}

.page-card h2,
.page-card h3{
    color:#1d4ed8;
    margin-bottom:18px;
}

.detalle-card{
    border:1px solid #e5e7eb;
    background:#f9fafb;
    border-radius:12px;
    padding:16px;
    margin-top:14px;
}

.detalle-card p{
    margin:8px 0;
    line-height:1.5;
}

.estado{
    display:inline-block;
    padding:6px 12px;
    border-radius:20px;
    font-size:14px;
    font-weight:bold;
}

.estado.pendiente{
    background:#fef3c7;
    color:#92400e;
}

.estado.autorizada{
    background:#dcfce7;
    color:#166534;
}

.estado.denegada{
    background:#fee2e2;
    color:#991b1b;
}

.estado.correccion{
    background:#dbeafe;
    color:#1d4ed8;
}

.acciones{
    margin-top:18px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.bloque-observacion{
    margin-top:12px;
    background:#fff7ed;
    border:1px solid #fdba74;
    color:#9a3412;
    padding:12px;
    border-radius:10px;
}

.vacio{
    color:#6b7280;
}

.info-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
}

.info-grid .full{
    grid-column:1 / -1;
}

@media (max-width: 900px){
    .info-grid{
        grid-template-columns:1fr;
    }

    .info-grid .full{
        grid-column:auto;
    }
}
</style>
</head>
<body>

<?php include("../includes/menu_coordinador.php"); ?>

<main class="main">
    <div class="page-card">
        <h2>Reserva #<?php echo (int)$reserva['id']; ?></h2>

        <div class="info-grid">
            <div>
                <p><strong>Tipo de actividad:</strong><br>
                <?php echo htmlspecialchars($reserva['tipo_actividad'] ?? 'No especificado'); ?></p>
            </div>

            <div>
                <p><strong>Nombre de la actividad:</strong><br>
                <?php echo htmlspecialchars($reserva['nombre_actividad'] ?? 'No especificado'); ?></p>
            </div>

            <div>
                <p><strong>Folio del coordinador:</strong><br>
                <?php echo htmlspecialchars($reserva['folio_coordinador'] ?? 'No especificado'); ?></p>
            </div>

            <div>
                <p><strong>Fecha de entrega del formato:</strong><br>
                <?php echo htmlspecialchars(formatearFecha($reserva['fecha_entrega_formato'] ?? '')); ?></p>
            </div>

            <div class="full">
                <p><strong>Objetivo:</strong><br>
                <?php echo nl2br(htmlspecialchars($reserva['objetivo'] ?? '')); ?></p>
            </div>

            <div>
                <p><strong>Asignatura:</strong><br>
                <?php echo htmlspecialchars($reserva['asignatura'] ?? 'No especificada'); ?></p>
            </div>

            <div>
                <p><strong>Docente responsable:</strong><br>
                <?php echo htmlspecialchars($reserva['docente_responsable'] ?? 'No especificado'); ?></p>
            </div>

            <div class="full">
                <p><strong>Coordinador responsable:</strong><br>
                <?php echo htmlspecialchars($reserva['coordinador_responsable'] ?? 'No especificado'); ?></p>
            </div>

            <div class="full">
                <p><strong>Estado:</strong><br>
                    <span class="estado <?php echo htmlspecialchars($reserva['estado'] ?? 'pendiente'); ?>">
                        <?php echo ucfirst(htmlspecialchars($reserva['estado'] ?? 'pendiente')); ?>
                    </span>
                </p>
            </div>
        </div>

        <?php if (($reserva['estado'] ?? '') === 'correccion') { ?>
            <div class="bloque-observacion">
                <strong>Observaciones del directivo:</strong><br>
                <?php echo nl2br(htmlspecialchars($reserva['observaciones_directivo'] ?? '')); ?>
            </div>
        <?php } ?>

        <div class="acciones">
            <a class="btn" href="mis_reservas.php">Volver</a>
            <a class="btn" href="../imprimir_reserva.php?id=<?php echo (int)$reserva['id']; ?>">Imprimir</a>

            <?php if (($reserva['estado'] ?? '') === 'correccion') { ?>
                <a class="btn" href="editar_reserva.php?id=<?php echo (int)$reserva['id']; ?>">Editar</a>
            <?php } ?>
        </div>
    </div>

    <div class="page-card">
        <h3>Espacios y horarios</h3>

        <?php if ($detalles->num_rows > 0) { ?>
            <?php while($d = $detalles->fetch_assoc()) { ?>
                <div class="detalle-card">
                    <p><strong>Espacio:</strong>
                    <?php echo htmlspecialchars($d['espacio'] ?? ''); ?></p>

                    <p><strong>Fecha:</strong>
                    <?php echo htmlspecialchars(formatearFecha($d['fecha'] ?? '')); ?></p>

                    <p><strong>Horario:</strong>
                    <?php echo htmlspecialchars(formatearHora12($d['hora_inicio'] ?? '')); ?>
                    -
                    <?php echo htmlspecialchars(formatearHora12($d['hora_fin'] ?? '')); ?></p>

                    <p><strong>Alumnos:</strong>
                    <?php echo htmlspecialchars($d['cantidad_alumnos'] ?? ''); ?></p>

                    <p><strong>Licenciaturas:</strong>
                    <?php echo htmlspecialchars($d['licenciaturas'] ?? 'No especificadas'); ?></p>

                    <p><strong>Cuatrimestres / Grupo:</strong>
                    <?php echo htmlspecialchars($d['cuatrimestres'] ?? 'No especificados'); ?></p>

                    <p><strong>Descripción:</strong><br>
                    <?php echo nl2br(htmlspecialchars($d['descripcion_actividad'] ?? '')); ?></p>

                    <?php if (!empty($d['requerimientos'])) { ?>
                        <p><strong>Requerimientos:</strong><br>
                        <?php echo nl2br(htmlspecialchars($d['requerimientos'])); ?></p>
                    <?php } ?>

                    <?php if ((float)($d['costo_alumno'] ?? 0) > 0) { ?>
                        <p><strong>Costo por alumno:</strong>
                        <?php echo number_format((float)$d['costo_alumno'], 2); ?></p>
                    <?php } ?>

                    <?php if (!empty($d['promocion_redes'])) { ?>
                        <p><strong>Promoción en redes:</strong>
                        <?php echo htmlspecialchars($d['promocion_redes']); ?></p>
                    <?php } ?>

                    <?php if (!empty($d['observaciones'])) { ?>
                        <p><strong>Observaciones:</strong><br>
                        <?php echo nl2br(htmlspecialchars($d['observaciones'])); ?></p>
                    <?php } ?>

                    <?php if (!empty($d['material_requerido'])) { ?>
                        <p><strong>Material requerido:</strong><br>
                        <?php echo nl2br(htmlspecialchars($d['material_requerido'])); ?></p>
                    <?php } ?>
                </div>
            <?php } ?>
        <?php } else { ?>
            <p class="vacio">No hay detalles registrados para esta reserva.</p>
        <?php } ?>
    </div>
</main>

<?php include("../includes/footer_coordinador.php"); ?>

</body>
</html>
<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sqlReserva = "SELECT r.*, u.nombre, u.apellido_paterno
               FROM reservas r
               INNER JOIN usuarios u ON r.coordinador_id = u.id
               WHERE r.id = ?";

$stmtReserva = $conexion->prepare($sqlReserva);

if (!$stmtReserva) {
    exit("Error al consultar la reserva.");
}

$stmtReserva->bind_param("i", $id);
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
    exit("Error al consultar los detalles de la reserva.");
}

$stmtDetalles->bind_param("i", $id);
$stmtDetalles->execute();
$detalles = $stmtDetalles->get_result();

function valorTexto($valor, $default = 'No especificado') {
    if (!isset($valor) || $valor === null || trim((string)$valor) === '') {
        return $default;
    }
    return htmlspecialchars((string)$valor);
}

function valorMultilinea($valor, $default = 'No especificado') {
    if (!isset($valor) || $valor === null || trim((string)$valor) === '') {
        return $default;
    }
    return nl2br(htmlspecialchars((string)$valor));
}

function formatearHora12($hora) {
    if (!$hora) {
        return 'No especificada';
    }

    $formato = date("h:i a", strtotime($hora));
    $formato = str_replace("am", "a. m.", $formato);
    $formato = str_replace("pm", "p. m.", $formato);

    return $formato;
}

function formatearFecha($fecha) {
    if (!$fecha || $fecha === '0000-00-00') {
        return 'No especificada';
    }
    return date("d/m/Y", strtotime($fecha));
}

$titulo_pagina = 'Panel Directivo';
$menu_activo = 'reservas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Detalle de Reserva</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.card{
    background:#fff;
    border-radius:16px;
    padding:24px;
    box-shadow:0 4px 14px rgba(0,0,0,.06);
    margin-bottom:24px;
}

.card h2,
.card h3{
    color:#1d4ed8;
    margin-bottom:18px;
}

.detalle-card{
    border:1px solid #e5e7eb;
    border-left:6px solid #ef1c1c;
    border-radius:12px;
    background:#fafafa;
    padding:18px;
    margin-bottom:18px;
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
    margin-top:20px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.btn-verde{
    display:inline-block;
    background:#16a34a;
    color:#fff;
    text-decoration:none;
    border:none;
    padding:10px 16px;
    border-radius:8px;
    font-weight:bold;
}

.btn-verde:hover{
    background:#15803d;
}

.btn-gris{
    display:inline-block;
    background:#6b7280;
    color:#fff;
    text-decoration:none;
    border:none;
    padding:10px 16px;
    border-radius:8px;
    font-weight:bold;
}

.btn-gris:hover{
    background:#4b5563;
}

.vacio{
    color:#6b7280;
}
</style>
</head>
<body>

<?php include("../includes/menu_directivo.php"); ?>

<main class="main">
    <div class="card">
        <h2>Reserva #<?php echo (int)$reserva['id']; ?></h2>

        <div class="detalle-card">
            <p><strong>Coordinador:</strong>
            <?php echo htmlspecialchars(($reserva['nombre'] ?? '') . ' ' . ($reserva['apellido_paterno'] ?? '')); ?></p>

            <p><strong>Tipo de actividad:</strong>
            <?php echo valorTexto($reserva['tipo_actividad'] ?? '', 'No especificado'); ?></p>

            <p><strong>Nombre de la actividad:</strong>
            <?php echo valorTexto($reserva['nombre_actividad'] ?? '', 'No especificado'); ?></p>

            <p><strong>Folio del coordinador:</strong>
            <?php echo valorTexto($reserva['folio_coordinador'] ?? '', 'No especificado'); ?></p>

            <p><strong>Fecha de entrega del formato:</strong>
            <?php echo formatearFecha($reserva['fecha_entrega_formato'] ?? ''); ?></p>

            <p><strong>Objetivo:</strong><br>
            <?php echo valorMultilinea($reserva['objetivo'] ?? '', 'No especificado'); ?></p>

            <p><strong>Asignatura vinculada:</strong>
            <?php echo valorTexto($reserva['asignatura'] ?? '', 'No especificada'); ?></p>

            <p><strong>Docente responsable:</strong>
            <?php echo valorTexto($reserva['docente_responsable'] ?? '', 'No especificado'); ?></p>

            <p><strong>Coordinador responsable:</strong>
            <?php echo valorTexto($reserva['coordinador_responsable'] ?? '', 'No especificado'); ?></p>

            <p><strong>Observaciones del directivo:</strong><br>
            <?php echo valorMultilinea($reserva['observaciones_directivo'] ?? '', 'Sin observaciones'); ?></p>

            <p><strong>Estado general:</strong>
                <span class="estado <?php echo htmlspecialchars($reserva['estado'] ?? 'pendiente'); ?>">
                    <?php echo ucfirst(htmlspecialchars($reserva['estado'] ?? 'pendiente')); ?>
                </span>
            </p>
        </div>

        <div class="acciones">
            <a class="btn" href="reservas.php">Volver</a>
            <a class="btn" href="../imprimir_reserva.php?id=<?php echo (int)$reserva['id']; ?>" target="_blank">Imprimir formato</a>

            <?php if (($reserva['estado'] ?? '') === 'pendiente') { ?>
                <a class="btn-verde"
                   href="autorizar_reserva.php?id=<?php echo (int)$reserva['id']; ?>"
                   onclick="return confirm('¿Seguro que deseas autorizar esta reserva?');">
                   Autorizar
                </a>

                <a class="btn-gris"
                   href="denegar_reserva.php?id=<?php echo (int)$reserva['id']; ?>"
                   onclick="return confirm('¿Seguro que deseas denegar esta reserva? Esta acción no se puede restablecer.');">
                   Denegar
                </a>

                <a class="btn" href="regresar_reserva.php?id=<?php echo (int)$reserva['id']; ?>">Regresar para edición</a>
            <?php } ?>
        </div>
    </div>

    <div class="card">
        <h3>Espacios y horarios solicitados</h3>

        <?php if ($detalles->num_rows > 0) { ?>
            <?php while($d = $detalles->fetch_assoc()) { ?>
                <div class="detalle-card">
                    <p><strong>Espacio / Lugar:</strong>
                    <?php echo valorTexto($d['espacio'] ?? '', 'No especificado'); ?></p>

                    <p><strong>Fecha:</strong>
                    <?php echo formatearFecha($d['fecha'] ?? ''); ?></p>

                    <p><strong>Hora inicio:</strong>
                    <?php echo formatearHora12($d['hora_inicio'] ?? ''); ?></p>

                    <p><strong>Hora fin:</strong>
                    <?php echo formatearHora12($d['hora_fin'] ?? ''); ?></p>

                    <p><strong>Licenciaturas:</strong>
                    <?php echo valorTexto($d['licenciaturas'] ?? '', 'No especificadas'); ?></p>

                    <p><strong>Cuatrimestres / Grupo:</strong>
                    <?php echo valorTexto($d['cuatrimestres'] ?? '', 'No especificados'); ?></p>

                    <p><strong>Cantidad de alumnos:</strong>
                    <?php echo valorTexto($d['cantidad_alumnos'] ?? '', 'No especificada'); ?></p>

                    <p><strong>Descripción de actividad:</strong><br>
                    <?php echo valorMultilinea($d['descripcion_actividad'] ?? '', 'No especificada'); ?></p>

                    <p><strong>Requerimientos:</strong><br>
                    <?php echo valorMultilinea($d['requerimientos'] ?? '', 'No especificados'); ?></p>

                    <p><strong>Costo por alumno:</strong>
                    <?php echo number_format((float)($d['costo_alumno'] ?? 0), 2); ?></p>

                    <p><strong>Promoción en redes:</strong>
                    <?php echo valorTexto($d['promocion_redes'] ?? '', 'No'); ?></p>

                    <p><strong>Observaciones:</strong><br>
                    <?php echo valorMultilinea($d['observaciones'] ?? '', 'Sin observaciones'); ?></p>

                    <p><strong>Material requerido:</strong><br>
                    <?php echo valorMultilinea($d['material_requerido'] ?? '', 'No especificado'); ?></p>
                </div>
            <?php } ?>
        <?php } else { ?>
            <p class="vacio">No hay detalles registrados para esta reserva.</p>
        <?php } ?>
    </div>
</main>

<?php include("../includes/footer_directivo.php"); ?>

</body>
</html>
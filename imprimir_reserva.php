<?php
session_start();
include("config/conexion.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    exit("Reserva inválida.");
}

$rutaVolver = ($_SESSION['rol'] === 'directivo')
    ? 'directivo/reservas.php'
    : 'coordinador/mis_reservas.php';

$sqlReserva = "SELECT r.*, u.nombre, u.apellido_paterno
               FROM reservas r
               INNER JOIN usuarios u ON r.coordinador_id = u.id
               WHERE r.id = ?";
$stmtReserva = $conexion->prepare($sqlReserva);
$stmtReserva->bind_param("i", $id);
$stmtReserva->execute();
$reserva = $stmtReserva->get_result()->fetch_assoc();

if (!$reserva) {
    exit("Reserva no encontrada.");
}

if ($_SESSION['rol'] === 'coordinador' && (int)$reserva['coordinador_id'] !== (int)$_SESSION['usuario_id']) {
    exit("No autorizado.");
}

$sqlDetalles = "SELECT rd.*, e.nombre AS espacio
                FROM reserva_detalles rd
                INNER JOIN espacios e ON rd.espacio_id = e.id
                WHERE rd.reserva_id = ?
                ORDER BY rd.fecha, rd.hora_inicio";
$stmtDetalles = $conexion->prepare($sqlDetalles);
$stmtDetalles->bind_param("i", $id);
$stmtDetalles->execute();
$detalles = $stmtDetalles->get_result();

function esc($v) {
    return htmlspecialchars((string)$v);
}

function hora12($hora) {
    if (!$hora) return '';
    $formato = date("g:i a", strtotime($hora));
    $formato = str_replace("am", "a. m.", $formato);
    $formato = str_replace("pm", "p. m.", $formato);
    return $formato;
}

function fechaNormal($fecha) {
    if (!$fecha || $fecha === '0000-00-00') return '';
    return date("d/m/Y", strtotime($fecha));
}

function fechaEntrega($fecha) {
    if (!$fecha || $fecha === '0000-00-00') return '';
    return date("d.m.y", strtotime($fecha));
}

function unirLicenciaturasCuatrimestres($licenciaturasTexto, $cuatrimestresTexto) {
    $licenciaturas = array_values(array_filter(array_map('trim', explode(',', (string)$licenciaturasTexto))));
    $cuatrimestres = array_values(array_filter(array_map('trim', explode(',', (string)$cuatrimestresTexto))));

    $resultado = [];
    $max = max(count($licenciaturas), count($cuatrimestres));

    for ($i = 0; $i < $max; $i++) {
        $lic = $licenciaturas[$i] ?? '';
        $cuat = $cuatrimestres[$i] ?? '';

        if ($lic !== '' && $cuat !== '') {
            $resultado[] = $lic . ' ' . $cuat;
        } elseif ($lic !== '') {
            $resultado[] = $lic;
        } elseif ($cuat !== '') {
            $resultado[] = $cuat;
        }
    }

    return $resultado;
}

$licCuatJuntos = [];
$detalleFilas = [];

$descripcionGeneral = '';
$observacionesGeneral = '';
$costoGeneral = '';
$promocionGeneral = '';
$cantidadGeneral = '';

while ($d = $detalles->fetch_assoc()) {
    $fechaFila = fechaNormal($d['fecha'] ?? '');
    $lugarFila = $d['espacio'] ?? '';
    $horaInicioFila = hora12($d['hora_inicio'] ?? '');
    $horaFinFila = hora12($d['hora_fin'] ?? '');

    $detalleFilas[] = [
        'fecha' => esc($fechaFila),
        'lugar' => esc($lugarFila),
        'hora_inicio' => esc($horaInicioFila),
        'hora_fin' => esc($horaFinFila),
        'descripcion' => '<div class="texto-largo">' . nl2br(esc($d['descripcion_actividad'] ?? 'No especificado')) . '</div>',
        'requerimientos' => '<div class="texto-largo">' . nl2br(esc($d['requerimientos'] ?? 'No especificado')) . '</div>',
    ];

    $pares = unirLicenciaturasCuatrimestres($d['licenciaturas'] ?? '', $d['cuatrimestres'] ?? '');
    foreach ($pares as $par) {
        if (!in_array($par, $licCuatJuntos, true)) {
            $licCuatJuntos[] = $par;
        }
    }

    if ($descripcionGeneral === '' && trim((string)($d['descripcion_actividad'] ?? '')) !== '') {
        $descripcionGeneral = '<div class="texto-largo">' . nl2br(esc($d['descripcion_actividad'])) . '</div>';
    }

    if ($observacionesGeneral === '' && trim((string)($d['observaciones'] ?? '')) !== '') {
        $observacionesGeneral = '<div class="texto-largo">' . nl2br(esc($d['observaciones'])) . '</div>';
    }

    if ($costoGeneral === '' && isset($d['costo_alumno']) && $d['costo_alumno'] !== '') {
        $costoGeneral = '$' . number_format((float)$d['costo_alumno'], 2);
    }

    if ($promocionGeneral === '' && trim((string)($d['promocion_redes'] ?? '')) !== '') {
        $promocionGeneral = esc($d['promocion_redes']);
    }

    if ($cantidadGeneral === '' && isset($d['cantidad_alumnos']) && $d['cantidad_alumnos'] !== '') {
        $cantidadGeneral = (int)$d['cantidad_alumnos'];
    }
}

if ($descripcionGeneral === '') $descripcionGeneral = '<div class="texto-largo">No especificado</div>';
if ($observacionesGeneral === '') $observacionesGeneral = '<div class="texto-largo">No especificado</div>';
if ($costoGeneral === '') $costoGeneral = '$0.00';
if ($promocionGeneral === '') $promocionGeneral = 'No';
if ($cantidadGeneral === '') $cantidadGeneral = 0;

$esReservaSimple = count($detalleFilas) === 1;
$primerDetalle = $detalleFilas[0] ?? null;

$autorizacionTexto = '';
if (($reserva['estado'] ?? '') === 'autorizada') {
    $autorizacionTexto = 'AUTORIZADA';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Imprimir reserva</title>
<style>
body{
    font-family: Arial, Helvetica, sans-serif;
    margin:0;
    background:#ffffff;
    color:#000;
}

.contenedor{
    width:980px;
    margin:10px auto;
}

.acciones{
    margin:10px 0 20px 0;
    display:flex;
    gap:10px;
}

.btn{
    background:#2563eb;
    color:#fff;
    padding:8px 14px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-weight:bold;
    text-decoration:none;
    display:inline-block;
}

.encabezado{
    width:100%;
    margin-bottom:8px;
}

.encabezado-superior{
    display:grid;
    grid-template-columns:42% 58%;
    align-items:center;
    gap:10px;
}

.logos{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:16px;
    min-height:90px;
}

.logo-box{
    width:115px;
    height:78px;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
}

.logo-box img{
    max-width:100%;
    max-height:100%;
    object-fit:contain;
    display:block;
}

.info-centro{
    text-align:center;
    font-size:13px;
    line-height:1.35;
    font-weight:bold;
}

.info-centro .normal{
    font-weight:normal;
}

.linea-roja{
    width:100%;
    height:8px;
    margin-top:8px;
    background:linear-gradient(to right, #c8102e 0%, #ef4444 50%, #c8102e 100%);
    border-top:1px solid #8b0000;
    border-bottom:1px solid #8b0000;
}

.titulo-formato{
    text-align:center;
    font-weight:bold;
    font-size:18px;
    margin:10px 0 8px 0;
}

.tabla{
    width:100%;
    border-collapse:collapse;
    table-layout:fixed;
}

.tabla td{
    border:1px solid #000;
    padding:6px 8px;
    font-size:13px;
    vertical-align:top;
}

.gris{
    background:#efefef;
    font-weight:bold;
}

.centro{
    text-align:center;
}

.firmas td{
    height:110px;
    vertical-align:bottom;
    text-align:center;
    font-weight:bold;
}

.firma-linea{
    display:block;
    margin-top:60px;
    border-top:1px solid #000;
    padding-top:6px;
}

.detalle-tabla-wrap{
    padding:0 !important;
}

.detalle-multiple{
    width:100%;
    border-collapse:collapse;
    table-layout:fixed;
}

.detalle-multiple th,
.detalle-multiple td{
    border:1px solid #000;
    padding:6px 8px;
    font-size:12px;
    text-align:left;
    vertical-align:top;
}

.detalle-multiple th{
    background:#efefef;
    font-weight:bold;
}

.requerimiento-fila td{
    font-size:12px;
    line-height:1.7;
}

.requerimiento-label{
    background:#f8f8f8;
    font-weight:bold;
    width:18%;
}

.texto-largo{
    line-height:1.7;
    white-space:normal;
    word-break:break-word;
}

@media print{
    .acciones{
        display:none;
    }

    .contenedor{
        width:100%;
        margin:0;
    }

    body{
        margin:0;
    }
}
</style>
</head>
<body>

<div class="contenedor">
    <div class="acciones">
        <button type="button" class="btn" onclick="window.print()">Imprimir</button>
        <a href="<?php echo $rutaVolver; ?>" class="btn" onclick="history.back(); return false;">Volver</a>
    </div>

    <div class="encabezado">
        <div class="encabezado-superior">
            <div class="logos">
                <div class="logo-box">
                    <img src="assets/img/SEP.jpeg" alt="SEP">
                </div>
                <div class="logo-box">
                    <img src="assets/img/Logo.jpeg" alt="CUV">
                </div>
                <div class="logo-box">
                    <img src="assets/img/DGP.jpeg" alt="DGP">
                </div>
            </div>

            <div class="info-centro">
                CENTRO UNIVERSITARIO DE VALLADOLID<br>
                <span class="normal">Calle 49 No. 149-A Col. San Francisco</span><br>
                <span class="normal">Valladolid, Yucatán, México</span><br>
                <span class="normal">Clave SEP 31PSU0034W</span><br>
                <span class="normal">Tel. 985 856 2179</span><br>
                WWW.CUVMX
            </div>
        </div>

        <div class="linea-roja"></div>
    </div>

    <div class="titulo-formato">FORMATO DE ACTIVIDADES O EVENTOS</div>

    <table class="tabla">
        <tr>
            <td class="gris" style="width:55%;">FECHA DE ENTREGA DE FORMATO: <?php echo esc(fechaEntrega($reserva['fecha_entrega_formato'] ?? '')); ?></td>
            <td class="gris" style="width:45%;">No. FOLIO: <?php echo esc($reserva['folio_coordinador'] ?? ''); ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <strong>Indicaciones:</strong><br>
                1. Entregar el formato impreso en la dirección de licenciaturas.<br>
                2. La autorización de las actividades será otorgada por el director.<br>
                3. Este formato se deberá enviar impreso con al menos 10 días de anticipación.
            </td>
        </tr>
        <tr>
            <td colspan="2" class="gris centro">DATOS GENERALES</td>
        </tr>
    </table>

    <table class="tabla">
        <tr>
            <td class="gris" style="width:28%;">Tipo de la actividad</td>
            <td colspan="3"><?php echo esc($reserva['tipo_actividad'] ?? ''); ?></td>
        </tr>
        <tr>
            <td class="gris">Nombre de la actividad</td>
            <td colspan="3"><?php echo esc($reserva['nombre_actividad'] ?? ''); ?></td>
        </tr>
        <tr>
            <td class="gris">Objetivo</td>
            <td colspan="3"><?php echo nl2br(esc($reserva['objetivo'] ?? '')); ?></td>
        </tr>
        <tr>
            <td class="gris">Licenciatura / Cuatrimestre-Grupo</td>
            <td><?php echo implode("<br>", array_map('esc', $licCuatJuntos)); ?></td>
            <td class="gris">Cantidad de alumnos participantes</td>
            <td><?php echo esc($cantidadGeneral); ?> alumnos</td>
        </tr>
        <tr>
            <td class="gris">Asignatura vinculada</td>
            <td colspan="3"><?php echo esc($reserva['asignatura'] ?? ''); ?></td>
        </tr>

        <?php if ($esReservaSimple && $primerDetalle) { ?>
            <tr>
                <td class="gris">Fecha</td>
                <td><?php echo $primerDetalle['fecha']; ?></td>
                <td class="gris">Hora de inicio</td>
                <td><?php echo $primerDetalle['hora_inicio']; ?></td>
            </tr>
            <tr>
                <td class="gris">Lugar</td>
                <td><?php echo $primerDetalle['lugar']; ?></td>
                <td class="gris">Hora final</td>
                <td><?php echo $primerDetalle['hora_fin']; ?></td>
            </tr>
            <tr>
                <td class="gris">Descripción de la Actividad<br><small>(Incluir procedimientos, reseña de ponentes, detalles de principio a fin)</small></td>
                <td colspan="3"><?php echo $primerDetalle['descripcion']; ?></td>
            </tr>
            <tr>
                <td class="gris">Requerimientos</td>
                <td colspan="3"><?php echo $primerDetalle['requerimientos']; ?></td>
            </tr>
        <?php } else { ?>
            <tr>
                <td colspan="4" class="detalle-tabla-wrap">
                    <table class="detalle-multiple">
                        <thead>
                            <tr>
                                <th style="width:18%;">Fecha</th>
                                <th style="width:30%;">Lugar</th>
                                <th style="width:22%;">Hora de inicio</th>
                                <th style="width:30%;">Hora final</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalleFilas as $fila) { ?>
                                <tr>
                                    <td><?php echo $fila['fecha']; ?></td>
                                    <td><?php echo $fila['lugar']; ?></td>
                                    <td><?php echo $fila['hora_inicio']; ?></td>
                                    <td><?php echo $fila['hora_fin']; ?></td>
                                </tr>
                                <tr class="requerimiento-fila">
                                    <td class="requerimiento-label">Requerimientos</td>
                                    <td colspan="3"><?php echo $fila['requerimientos']; ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="gris">Descripción de la Actividad<br><small>(Incluir procedimientos, reseña de ponentes, detalles de principio a fin)</small></td>
                <td colspan="3"><?php echo $descripcionGeneral; ?></td>
            </tr>
        <?php } ?>

        <tr>
            <td class="gris">Costo para el alumno</td>
            <td><?php echo $costoGeneral; ?></td>
            <td class="gris">Promoción en Redes Sociales</td>
            <td><?php echo $promocionGeneral; ?></td>
        </tr>
        <tr>
            <td class="gris">Observaciones, anexos, jueces, invitados etc.</td>
            <td colspan="3"><?php echo $observacionesGeneral; ?></td>
        </tr>
    </table>

    <br>

    <table class="tabla firmas">
        <tr>
            <td style="width:33.33%;">
                <?php echo esc($reserva['docente_responsable'] ?? ''); ?>
                <span class="firma-linea">Nombre y firma del docente responsable</span>
            </td>

            <td style="width:33.33%;">
                <?php echo esc($reserva['coordinador_responsable'] ?? ''); ?>
                <span class="firma-linea">Nombre y firma del coordinador responsable</span>
            </td>

            <td style="width:33.33%;">
                <?php echo $autorizacionTexto; ?>
                <span class="firma-linea">Firma de Autorización</span>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
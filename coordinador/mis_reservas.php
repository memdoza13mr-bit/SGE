<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'coordinador') {
    header("Location: ../auth/login.php");
    exit();
}

function formatearHora12($hora) {
    if (!$hora) {
        return '';
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

$coordinador_id = (int)$_SESSION['usuario_id'];

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) {
    $pagina = 1;
}

$limite = 10;
$offset = ($pagina - 1) * $limite;

/* contar total */
$sqlTotal = "SELECT COUNT(*) AS total
             FROM reservas
             WHERE coordinador_id = ?";
$stmtTotal = $conexion->prepare($sqlTotal);

if (!$stmtTotal) {
    exit("Error al contar las reservas.");
}

$stmtTotal->bind_param("i", $coordinador_id);
$stmtTotal->execute();
$resTotal = $stmtTotal->get_result();
$totalFilas = (int)$resTotal->fetch_assoc()['total'];
$totalPaginas = max(1, (int)ceil($totalFilas / $limite));

/* traer reservas con primer detalle */
$sql = "SELECT 
            r.id,
            r.tipo_actividad,
            r.nombre_actividad,
            r.estado,
            r.observaciones_directivo,
            d.espacio,
            d.fecha,
            d.hora_inicio,
            d.hora_fin
        FROM reservas r
        LEFT JOIN (
            SELECT 
                rd1.reserva_id,
                e.nombre AS espacio,
                rd1.fecha,
                rd1.hora_inicio,
                rd1.hora_fin
            FROM reserva_detalles rd1
            INNER JOIN espacios e ON rd1.espacio_id = e.id
            WHERE rd1.id IN (
                SELECT MIN(rd2.id)
                FROM reserva_detalles rd2
                GROUP BY rd2.reserva_id
            )
        ) d ON d.reserva_id = r.id
        WHERE r.coordinador_id = ?
        ORDER BY r.id DESC
        LIMIT ? OFFSET ?";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    exit("Error al consultar las reservas.");
}

$stmt->bind_param("iii", $coordinador_id, $limite, $offset);
$stmt->execute();
$reservas = $stmt->get_result();

$titulo_pagina = 'Sistema de Reservas';
$menu_activo = 'mis_reservas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mis Reservas</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.contenedor-tabla{
    background:#fff;
    border-radius:18px;
    padding:24px;
    box-shadow:0 8px 24px rgba(37, 97, 239, 0.08);
}

.tabla-wrap{
    overflow-x:auto;
}

.tabla-reservas{
    width:100%;
    border-collapse:collapse;
    min-width:900px;
}

.tabla-reservas thead th{
    text-align:left;
    background:#eff6ff;
    color:#1e3a8a;
    padding:14px 12px;
    font-size:14px;
    border-bottom:1px solid #dbeafe;
}

.tabla-reservas tbody td{
    padding:14px 12px;
    border-bottom:1px solid #e5e7eb;
    color:#1f2937;
    font-size:14px;
    vertical-align:middle;
}

.tabla-reservas tbody tr:hover{
    background:#f8fafc;
}

.estado{
    display:inline-block;
    padding:6px 12px;
    border-radius:999px;
    font-size:13px;
    font-weight:700;
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
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.btn-accion{
    display:inline-block;
    text-decoration:none;
    border:none;
    padding:9px 12px;
    border-radius:8px;
    font-weight:700;
    font-size:13px;
    cursor:pointer;
}

.btn-detalle{
    background:#808080;
    color:#fff;
}

.btn-detalle:hover{
    background:#1d4ed8;
}

.btn-editar{
    background:#2563eb;
    color:#fff;
}

.btn-editar:hover{
    background:#15803d;
}

.bloque-observacion{
    margin-top:8px;
    background:#fff7ed;
    border:1px solid #fdba74;
    color:#9a3412;
    padding:10px;
    border-radius:10px;
    font-size:13px;
}

.paginacion{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-top:24px;
    margin-bottom:24px;
}

.paginacion-info{
    font-weight:bold;
    color:#374151;
}

.paginacion .btn,
.paginacion .btn-disabled{
    padding:10px 16px;
    border-radius:8px;
    text-decoration:none;
    font-weight:bold;
}

.paginacion .btn-disabled{
    background:#d1d5db;
    color:#6b7280;
    cursor:not-allowed;
}

.vacio{
    color:#6b7280;
}

@media (max-width: 1000px){
    .main{
        padding:20px 14px;
    }
}
</style>
</head>
<body>

<?php include("../includes/menu_coordinador.php"); ?>

<main class="main">
    <?php if (isset($_SESSION['ok_reserva'])) { ?>
        <div class="mensaje-ok">
            <?php
            echo htmlspecialchars($_SESSION['ok_reserva']);
            unset($_SESSION['ok_reserva']);
            ?>
        </div>
    <?php } ?>

    <?php if (isset($_SESSION['error_reserva'])) { ?>
        <div class="mensaje-error">
            <?php
            echo htmlspecialchars($_SESSION['error_reserva']);
            unset($_SESSION['error_reserva']);
            ?>
        </div>
    <?php } ?>

    <div class="contenedor-tabla">
        <h2 class="titulo-azul">Reservas realizadas</h2>

        <?php if ($reservas && $reservas->num_rows > 0) { ?>
            <div class="tabla-wrap">
                <table class="tabla-reservas">
                    <thead>
                        <tr>
                            <th>Actividad</th>
                            <th>Área solicitada</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($r = $reservas->fetch_assoc()) { ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($r['nombre_actividad'] ?? 'Sin nombre'); ?></strong><br>
                                    <span style="color:#64748b;">
                                        <?php echo htmlspecialchars($r['tipo_actividad'] ?? 'No especificado'); ?>
                                    </span>

                                    <?php if (($r['estado'] ?? '') === 'correccion') { ?>
                                        <div class="bloque-observacion">
                                            <strong>Observaciones del directivo:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($r['observaciones_directivo'] ?? '')); ?>
                                        </div>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($r['espacio'] ?? 'No especificada'); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars(formatearFecha($r['fecha'] ?? '')); ?>
                                </td>
                                <td>
                                    <?php
                                    $horaTexto = '';
                                    if (!empty($r['hora_inicio']) && !empty($r['hora_fin'])) {
                                        $horaTexto = formatearHora12($r['hora_inicio']) . ' - ' . formatearHora12($r['hora_fin']);
                                    } else {
                                        $horaTexto = 'No especificada';
                                    }
                                    echo htmlspecialchars($horaTexto);
                                    ?>
                                </td>
                                <td>
                                    <span class="estado <?php echo htmlspecialchars($r['estado'] ?? 'pendiente'); ?>">
                                        <?php echo ucfirst(htmlspecialchars($r['estado'] ?? 'pendiente')); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="acciones">
                                        <a class="btn-accion btn-detalle" href="../imprimir_reserva.php?id=<?php echo (int)$r['id']; ?>">
                                            Ver
                                        </a>

                                        <?php if (($r['estado'] ?? '') === 'correccion') { ?>
                                            <a class="btn-accion btn-editar" href="editar_reserva.php?id=<?php echo (int)$r['id']; ?>">
                                                Editar
                                            </a>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <p class="vacio">No tienes reservas registradas todavía.</p>
        <?php } ?>

        <div class="paginacion">
            <?php if ($pagina > 1) { ?>
                <a class="btn" href="mis_reservas.php?pagina=<?php echo $pagina - 1; ?>">⬅ Anterior</a>
            <?php } else { ?>
                <span class="btn-disabled">⬅ Anterior</span>
            <?php } ?>

            <span class="paginacion-info">
                Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?>
            </span>

            <?php if ($pagina < $totalPaginas) { ?>
                <a class="btn" href="mis_reservas.php?pagina=<?php echo $pagina + 1; ?>">Siguiente ➡</a>
            <?php } else { ?>
                <span class="btn-disabled">Siguiente ➡</span>
            <?php } ?>
        </div>

        <a class="btn" href="dashboard.php">Volver al panel</a>
    </div>
</main>

<?php include("../includes/footer_coordinador.php"); ?>

</body>
</html>
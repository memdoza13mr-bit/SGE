<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
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

$filtro = isset($_GET['estado']) ? trim($_GET['estado']) : '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

if ($pagina < 1) {
    $pagina = 1;
}

$limite = 10;
$offset = ($pagina - 1) * $limite;

$estadosPermitidos = ['pendiente', 'autorizada', 'denegada', 'correccion'];

$where = "";
$params = [];
$types = "";

if ($filtro !== '' && in_array($filtro, $estadosPermitidos, true)) {
    $where = " WHERE r.estado = ? ";
    $params[] = $filtro;
    $types .= "s";
}

/* contar total */
$sqlTotal = "SELECT COUNT(*) AS total
             FROM reservas r
             INNER JOIN usuarios u ON r.coordinador_id = u.id
             $where";

$stmtTotal = $conexion->prepare($sqlTotal);

if (!$stmtTotal) {
    exit("Error al contar las reservas.");
}

if ($types !== "") {
    $stmtTotal->bind_param($types, ...$params);
}

$stmtTotal->execute();
$resTotal = $stmtTotal->get_result();
$totalFilas = (int)$resTotal->fetch_assoc()['total'];
$totalPaginas = max(1, (int)ceil($totalFilas / $limite));

/* traer reservas paginadas + primer detalle */
$sql = "SELECT 
            r.id,
            r.estado,
            r.coordinador_id,
            u.nombre,
            u.apellido_paterno,
            d.espacio,
            d.fecha,
            d.hora_inicio,
            d.hora_fin
        FROM reservas r
        INNER JOIN usuarios u ON r.coordinador_id = u.id
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
        $where
        ORDER BY r.id DESC
        LIMIT ? OFFSET ?";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    exit("Error al consultar las reservas.");
}

if ($types !== "") {
    $types2 = $types . "ii";
    $params2 = array_merge($params, [$limite, $offset]);
    $stmt->bind_param($types2, ...$params2);
} else {
    $stmt->bind_param("ii", $limite, $offset);
}

$stmt->execute();
$reservas = $stmt->get_result();

$titulo_pagina = 'Panel Directivo';
$menu_activo = 'reservas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reservas del Directivo</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.card{
    background:#fff;
    border-radius:18px;
    padding:24px;
    box-shadow:0 8px 24px rgba(15,23,42,.08);
    margin-bottom:24px;
}

.titulo-seccion{
    color:#1e3a8a;
    margin-bottom:18px;
    font-size:28px;
    font-weight:800;
}

.filtros{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:22px;
}

.filtros a{
    text-decoration:none;
    padding:10px 14px;
    border-radius:999px;
    font-weight:600;
    border:1px solid #dbe2ea;
    color:#334155;
    background:#f8fafc;
}

.filtros a.activo{
    background:#1e3a8a;
    color:#fff;
    border-color:#1e3a8a;
}

.tabla-wrap{
    overflow-x:auto;
}

.tabla-reservas{
    width:100%;
    border-collapse:collapse;
    min-width:850px;
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

.btn-autorizar{
    background:#16a34a;
    color:#fff;
}

.btn-autorizar:hover{
    background:#15803d;
}

.btn-denegar{
    background:#d91515;
    color:#fff;
}

.btn-denegar:hover{
    background:#334155;
}

.btn-restaurar{
    background:#f59e0b;
    color:#fff;
}

.btn-restaurar:hover{
    background:#d97706;
}

.btn-editar{
    background:#1d4ed8;
    color:#fff;
}

.btn-editar:hover{
    background:#6d28d9;
}

.vacio{
    color:#6b7280;
}

.paginacion{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-top:24px;
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

    <div class="card">
        <h3 class="titulo-seccion">Solicitudes de reserva</h3>

        <div class="filtros">
            <a href="reservas.php" class="<?php echo ($filtro === '') ? 'activo' : ''; ?>">Todas</a>
            <a href="reservas.php?estado=pendiente" class="<?php echo ($filtro === 'pendiente') ? 'activo' : ''; ?>">Pendientes</a>
            <a href="reservas.php?estado=autorizada" class="<?php echo ($filtro === 'autorizada') ? 'activo' : ''; ?>">Autorizadas</a>
            <a href="reservas.php?estado=denegada" class="<?php echo ($filtro === 'denegada') ? 'activo' : ''; ?>">Denegadas</a>
            <a href="reservas.php?estado=correccion" class="<?php echo ($filtro === 'correccion') ? 'activo' : ''; ?>">Corrección</a>
        </div>

        <?php if ($reservas && $reservas->num_rows > 0) { ?>
            <div class="tabla-wrap">
                <table class="tabla-reservas">
                    <thead>
                        <tr>
                            <th>Coordinador</th>
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
                                    <?php echo htmlspecialchars(($r['nombre'] ?? '') . ' ' . ($r['apellido_paterno'] ?? '')); ?>
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
                                        <a class="btn-accion btn-detalle" href="../imprimir_reserva.php?id=<?php echo (int)$r['id']; ?>">Ver</a>

                                        <?php if (($r['estado'] ?? '') === 'pendiente') { ?>
                                            <a class="btn-accion btn-autorizar"
                                               href="autorizar_reserva.php?id=<?php echo (int)$r['id']; ?>"
                                               onclick="return confirm('¿Seguro que deseas autorizar esta reserva?');">
                                               Autorizar
                                            </a>

                                            <a class="btn-accion btn-denegar"
                                               href="denegar_reserva.php?id=<?php echo (int)$r['id']; ?>"
                                               onclick="return confirm('¿Seguro que deseas denegar esta reserva? Esta acción no se puede restablecer.');">
                                               Denegar
                                            </a>

                                            <a class="btn-accion btn-editar" href="regresar_reserva.php?id=<?php echo (int)$r['id']; ?>">
                                               Regresar a edición
                                            </a>
                                        <?php } ?>

                                        <?php if (($r['estado'] ?? '') === 'denegada') { ?>
                                            <a class="btn-accion btn-restaurar"
                                               href="restaurar_reserva.php?id=<?php echo (int)$r['id']; ?>"
                                               onclick="return confirm('¿Seguro que deseas restaurar esta reserva a pendiente?');">
                                               Restaurar
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
            <p class="vacio">No hay reservas registradas para este filtro.</p>
        <?php } ?>

        <div class="paginacion">
            <?php if ($pagina > 1) { ?>
                <a class="btn" href="reservas.php?estado=<?php echo urlencode($filtro); ?>&pagina=<?php echo $pagina - 1; ?>">⬅ Anterior</a>
            <?php } else { ?>
                <span class="btn-disabled">⬅ Anterior</span>
            <?php } ?>

            <span class="paginacion-info">
                Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?>
            </span>

            <?php if ($pagina < $totalPaginas) { ?>
                <a class="btn" href="reservas.php?estado=<?php echo urlencode($filtro); ?>&pagina=<?php echo $pagina + 1; ?>">Siguiente ➡</a>
            <?php } else { ?>
                <span class="btn-disabled">Siguiente ➡</span>
            <?php } ?>
        </div>
    </div>
</main>

<?php include("../includes/footer_directivo.php"); ?>

</body>
</html>
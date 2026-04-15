<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

/* conteos */
$totalEspacios = 0;
$totalPendientes = 0;
$totalAutorizadas = 0;
$totalDenegadas = 0;
$totalSolicitudesPasswordPendientes = 0;

$q1 = $conexion->query("SELECT COUNT(*) AS total FROM espacios");
if ($q1) {
    $totalEspacios = (int)$q1->fetch_assoc()['total'];
}

$q2 = $conexion->query("SELECT COUNT(*) AS total FROM reservas WHERE estado = 'pendiente'");
if ($q2) {
    $totalPendientes = (int)$q2->fetch_assoc()['total'];
}

$q3 = $conexion->query("SELECT COUNT(*) AS total FROM reservas WHERE estado = 'autorizada'");
if ($q3) {
    $totalAutorizadas = (int)$q3->fetch_assoc()['total'];
}

$q4 = $conexion->query("SELECT COUNT(*) AS total FROM reservas WHERE estado = 'denegada'");
if ($q4) {
    $totalDenegadas = (int)$q4->fetch_assoc()['total'];
}

$q5 = $conexion->query("SELECT COUNT(*) AS total FROM solicitudes_cambio_password WHERE estado = 'pendiente'");
if ($q5) {
    $totalSolicitudesPasswordPendientes = (int)$q5->fetch_assoc()['total'];
}

/* últimas reservas */
$sqlReservas = "SELECT r.id, r.objetivo, r.asignatura, r.estado, u.nombre, u.apellido_paterno
                FROM reservas r
                INNER JOIN usuarios u ON r.coordinador_id = u.id
                ORDER BY r.id DESC
                LIMIT 5";
$ultimasReservas = $conexion->query($sqlReservas);

/* últimos anuncios */
$sqlAnuncios = "SELECT id, titulo, contenido, fecha_publicacion
                FROM anuncios
                ORDER BY id DESC
                LIMIT 5";
$ultimosAnuncios = $conexion->query($sqlAnuncios);

/* últimas solicitudes de cambio de contraseña */
$sqlSolicitudesPassword = "SELECT s.*, u.nombre, u.apellido_paterno, u.correo
                           FROM solicitudes_cambio_password s
                           INNER JOIN usuarios u ON s.coordinador_id = u.id
                           ORDER BY s.id DESC
                           LIMIT 5";
$ultimasSolicitudesPassword = $conexion->query($sqlSolicitudesPassword);

$titulo_pagina = 'Panel Directivo';
$menu_activo = 'inicio';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel Directivo</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.titulo-principal{
    color:#1d4ed8;
    font-size:34px;
    margin-bottom:22px;
}

.cards-resumen{
    display:grid;
    grid-template-columns:repeat(5, 1fr);
    gap:18px;
    margin-bottom:24px;
}

.card-mini{
    background:#fff;
    border-radius:14px;
    padding:22px;
    box-shadow:0 4px 14px rgba(0,0,0,.06);
    border-left:6px solid #ef1c1c;
}

.card-mini h3{
    margin:0 0 10px 0;
    color:#374151;
    font-size:16px;
}

.card-mini .numero{
    font-size:34px;
    font-weight:bold;
    color:#111827;
}

.grid-doble{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:24px;
    margin-bottom:24px;
}

.card{
    background:#fff;
    border-radius:16px;
    padding:24px;
    box-shadow:0 4px 14px rgba(0,0,0,.06);
}

.card h3{
    color:#1d4ed8;
    margin-bottom:18px;
    font-size:22px;
}

.item{
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:16px;
    margin-bottom:14px;
    background:#fafafa;
}

.item p{
    margin:6px 0;
    line-height:1.5;
}

.estado{
    display:inline-block;
    padding:5px 10px;
    border-radius:20px;
    font-size:13px;
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

.estado.cancelada{
    background:#e5e7eb;
    color:#374151;
}

.estado.rechazada{
    background:#fee2e2;
    color:#991b1b;
}

.estado.correccion{
    background:#dbeafe;
    color:#1d4ed8;
}

.acciones{
    margin-top:12px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.btn-detalle,
.btn-autorizar,
.btn-denegar,
.btn-editar{
    display:inline-block;
    color:#fff;
    text-decoration:none;
    border:none;
    padding:10px 14px;
    border-radius:8px;
    font-weight:bold;
}

.btn-detalle{
    background:#6b7280;
}

.btn-detalle:hover{
    background:#4b5563;
}

.btn-autorizar{
    background:#16a34a;
}

.btn-autorizar:hover{
    background:#15803d;
}

.btn-denegar{
    background:#ef1c1c;
}

.btn-denegar:hover{
    background:#c91818;
}

.btn-editar{
    background:#2563eb;
}

.btn-editar:hover{
    background:#1d4ed8;
}

.aviso-password{
    border-left:6px solid #1d4ed8;
    background:#eff6ff;
    border-radius:14px;
    padding:18px;
    margin-bottom:24px;
    box-shadow:0 4px 14px rgba(0,0,0,.05);
}

.aviso-password h3{
    color:#1d4ed8;
    margin:0 0 10px 0;
    font-size:22px;
}

.aviso-password p{
    margin:6px 0;
    color:#1f2937;
    line-height:1.5;
}

.vacio{
    color:#6b7280;
}

@media (max-width: 1300px){
    .cards-resumen{
        grid-template-columns:repeat(3, 1fr);
    }
}

@media (max-width: 1100px){
    .grid-doble{
        grid-template-columns:1fr;
    }
}

@media (max-width: 900px){
    .cards-resumen{
        grid-template-columns:repeat(2, 1fr);
    }

    .main{
        padding:20px 14px;
    }
}

@media (max-width: 600px){
    .cards-resumen{
        grid-template-columns:1fr;
    }
}
</style>
</head>
<body>

<?php include("../includes/menu_directivo.php"); ?>

<main class="main">
    <h1 class="titulo-principal">Resumen general</h1>

    <?php if ($totalSolicitudesPasswordPendientes > 0) { ?>
        <section class="aviso-password">
            <h3>Solicitudes de cambio de contraseña</h3>
            <p>
                Tienes <strong><?php echo $totalSolicitudesPasswordPendientes; ?></strong>
                solicitud(es) pendiente(s) de revisión.
            </p>
            <div class="acciones">
                <a class="btn-editar" href="solicitudes_password.php">Revisar solicitudes</a>
            </div>
        </section>
    <?php } ?>

    <section class="cards-resumen">
        <div class="card-mini">
            <h3>Espacios registrados</h3>
            <div class="numero"><?php echo $totalEspacios; ?></div>
        </div>

        <div class="card-mini">
            <h3>Reservas pendientes</h3>
            <div class="numero"><?php echo $totalPendientes; ?></div>
        </div>

        <div class="card-mini">
            <h3>Reservas autorizadas</h3>
            <div class="numero"><?php echo $totalAutorizadas; ?></div>
        </div>

        <div class="card-mini">
            <h3>Reservas denegadas</h3>
            <div class="numero"><?php echo $totalDenegadas; ?></div>
        </div>

        <div class="card-mini">
            <h3>Solicitudes password</h3>
            <div class="numero"><?php echo $totalSolicitudesPasswordPendientes; ?></div>
        </div>
    </section>

    <section class="grid-doble">
        <div class="card">
            <h3>Últimas reservas</h3>

            <?php if ($ultimasReservas && $ultimasReservas->num_rows > 0) { ?>
                <?php while($r = $ultimasReservas->fetch_assoc()) { ?>
                    <div class="item">
                        <p><strong>Coordinador:</strong> <?php echo htmlspecialchars($r['nombre'] . ' ' . $r['apellido_paterno']); ?></p>
                        <p><strong>Objetivo:</strong> <?php echo htmlspecialchars($r['objetivo'] ?? ''); ?></p>
                        <p><strong>Asignatura:</strong> <?php echo htmlspecialchars($r['asignatura'] ?? ''); ?></p>
                        <p><strong>Estado:</strong>
                            <span class="estado <?php echo htmlspecialchars($r['estado']); ?>">
                                <?php echo ucfirst(htmlspecialchars($r['estado'])); ?>
                            </span>
                        </p>

                       <div class="acciones">
                            <a class="btn-detalle" href="../imprimir_reserva.php?id=<?php echo (int)$r['id']; ?>">Ver</a>

                            <?php if (($r['estado'] ?? '') === 'pendiente') { ?>
                                <a class="btn-autorizar"
                                href="autorizar_reserva.php?id=<?php echo (int)$r['id']; ?>"
                                onclick="return confirm('¿Seguro que deseas autorizar esta reserva?');">
                                Autorizar
                                </a>

                                <a class="btn-denegar"
                                href="denegar_reserva.php?id=<?php echo (int)$r['id']; ?>"
                                onclick="return confirm('¿Seguro que deseas denegar esta reserva?');">
                                Denegar
                                </a>

                                <a class="btn-editar"
                                href="regresar_reserva.php?id=<?php echo (int)$r['id']; ?>">
                                Regresar a edición
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <p class="vacio">No hay reservas registradas.</p>
            <?php } ?>
        </div>

        <div class="card">
            <h3>Últimos anuncios</h3>

            <?php if ($ultimosAnuncios && $ultimosAnuncios->num_rows > 0) { ?>
                <?php while($a = $ultimosAnuncios->fetch_assoc()) { ?>
                    <div class="item">
                        <p><strong><?php echo htmlspecialchars($a['titulo']); ?></strong></p>
                        <p><?php echo nl2br(htmlspecialchars($a['contenido'])); ?></p>
                        <p><small><?php echo htmlspecialchars($a['fecha_publicacion']); ?></small></p>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <p class="vacio">No hay anuncios publicados.</p>
            <?php } ?>

            <div class="acciones">
                <a class="btn-detalle" href="anuncios.php">Administrar anuncios</a>
            </div>
        </div>
    </section>

    <section class="card">
        <h3>Últimas solicitudes de cambio de contraseña</h3>

        <?php if ($ultimasSolicitudesPassword && $ultimasSolicitudesPassword->num_rows > 0) { ?>
            <?php while($s = $ultimasSolicitudesPassword->fetch_assoc()) { ?>
                <div class="item">
                    <p><strong>Coordinador:</strong> <?php echo htmlspecialchars($s['nombre'] . ' ' . $s['apellido_paterno']); ?></p>
                    <p><strong>Correo:</strong> <?php echo htmlspecialchars($s['correo']); ?></p>
                    <p><strong>Motivo:</strong><br><?php echo nl2br(htmlspecialchars($s['motivo'])); ?></p>
                    <p><strong>Estado:</strong>
                        <span class="estado <?php echo htmlspecialchars($s['estado']); ?>">
                            <?php echo ucfirst(htmlspecialchars($s['estado'])); ?>
                        </span>
                    </p>
                </div>
            <?php } ?>
        <?php } else { ?>
            <p class="vacio">No hay solicitudes registradas.</p>
        <?php } ?>

        <div class="acciones">
            <a class="btn-detalle" href="solicitudes_password.php">Ver todas las solicitudes</a>
        </div>
    </section>
</main>

<?php include("../includes/footer_directivo.php"); ?>

</body>
</html>
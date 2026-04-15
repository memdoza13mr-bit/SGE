<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

$totalSolicitudesPasswordPendientes = 0;
$qPendientes = $conexion->query("SELECT COUNT(*) AS total FROM solicitudes_cambio_password WHERE estado = 'pendiente'");
if ($qPendientes) {
    $totalSolicitudesPasswordPendientes = (int)$qPendientes->fetch_assoc()['total'];
}

$sql = "SELECT s.*, u.nombre, u.apellido_paterno, u.correo
        FROM solicitudes_cambio_password s
        INNER JOIN usuarios u ON s.coordinador_id = u.id
        ORDER BY s.id DESC";
$res = $conexion->query($sql);

$titulo_pagina = 'Panel Directivo';
$menu_activo = 'solicitudes_password';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Solicitudes de cambio de contraseña</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.page-title{
    color:#1d4ed8;
    margin-bottom:20px;
    font-size:28px;
    font-weight:800;
}

.card{
    background:#fff;
    border-radius:16px;
    padding:24px;
    box-shadow:0 4px 14px rgba(0,0,0,.06);
    margin-bottom:18px;
}

.card p{
    margin:8px 0;
    line-height:1.6;
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

.estado.rechazada{
    background:#fee2e2;
    color:#991b1b;
}

.form-respuesta{
    margin-top:14px;
}

.form-respuesta textarea{
    margin-top:6px;
}

.acciones{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:12px;
}

.vacio{
    color:#6b7280;
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
    <h2 class="page-title">Solicitudes de cambio de contraseña</h2>

    <?php if (isset($_SESSION['ok_password'])) { ?>
        <div class="mensaje-ok">
            <?php
            echo htmlspecialchars($_SESSION['ok_password']);
            unset($_SESSION['ok_password']);
            ?>
        </div>
    <?php } ?>

    <?php if (isset($_SESSION['error_password'])) { ?>
        <div class="mensaje-error">
            <?php
            echo htmlspecialchars($_SESSION['error_password']);
            unset($_SESSION['error_password']);
            ?>
        </div>
    <?php } ?>

    <?php if ($res && $res->num_rows > 0) { ?>
        <?php while($s = $res->fetch_assoc()) { ?>
            <div class="card">
                <p><strong>Coordinador:</strong> <?php echo htmlspecialchars($s['nombre'] . ' ' . $s['apellido_paterno']); ?></p>
                <p><strong>Correo:</strong> <?php echo htmlspecialchars($s['correo']); ?></p>
                <p><strong>Motivo:</strong><br><?php echo nl2br(htmlspecialchars($s['motivo'])); ?></p>
                <p>
                    <strong>Estado:</strong>
                    <span class="estado <?php echo htmlspecialchars($s['estado']); ?>">
                        <?php echo ucfirst(htmlspecialchars($s['estado'])); ?>
                    </span>
                </p>

                <?php if (($s['estado'] ?? '') === 'pendiente') { ?>
                    <form action="resolver_solicitud_password.php" method="POST" class="form-respuesta">
                        <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">

                        <label>Respuesta del director</label>
                        <textarea name="respuesta_director"></textarea>

                        <div class="acciones">
                            <button type="submit" name="accion" value="autorizar" class="btn">Autorizar</button>
                            <button type="submit" name="accion" value="rechazar" class="btn">Rechazar</button>
                        </div>
                    </form>
                <?php } else { ?>
                    <p><strong>Respuesta del director:</strong><br>
                    <?php echo nl2br(htmlspecialchars($s['respuesta_director'] ?? 'Sin respuesta')); ?></p>
                <?php } ?>
            </div>
        <?php } ?>
    <?php } else { ?>
        <p class="vacio">No hay solicitudes registradas.</p>
    <?php } ?>
</main>

<?php include("../includes/footer_directivo.php"); ?>

</body>
</html>
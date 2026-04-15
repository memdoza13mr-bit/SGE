<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'coordinador') {
    header("Location: ../auth/login.php");
    exit();
}

$coordinador_id = (int)$_SESSION['usuario_id'];

$sql = "SELECT * 
        FROM solicitudes_cambio_password
        WHERE coordinador_id = ?
        ORDER BY id DESC
        LIMIT 1";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    exit("Error al consultar la solicitud.");
}

$stmt->bind_param("i", $coordinador_id);
$stmt->execute();
$ultimaSolicitud = $stmt->get_result()->fetch_assoc();

$titulo_pagina = 'Sistema de Reservas';
$menu_activo = 'solicitar_password';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Solicitar cambio de contraseña</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.page-card{
    max-width:900px;
    margin:0 auto;
    background:#fff;
    padding:30px;
    border-radius:18px;
    box-shadow:0 8px 24px rgba(15,23,42,.08);
}

.estado{
    display:inline-block;
    padding:6px 12px;
    border-radius:20px;
    font-size:14px;
    font-weight:bold;
    margin-top:6px;
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

.alerta{
    background:#eff6ff;
    border:1px solid #bfdbfe;
    color:#1d4ed8;
    padding:12px 14px;
    border-radius:10px;
    margin-bottom:18px;
}

.bloque-info{
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:18px;
    margin-bottom:20px;
}

.bloque-info p{
    margin-bottom:14px;
    line-height:1.6;
}

.acciones{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    margin-top:18px;
}

.separador{
    margin:22px 0;
    border:none;
    border-top:1px solid #e5e7eb;
}

@media (max-width: 700px){
    .page-card{
        padding:20px;
    }
}
</style>
</head>
<body>

<?php include("../includes/menu_coordinador.php"); ?>

<main class="main">
    <div class="page-card">
        <h2 class="titulo-azul">Solicitar cambio de contraseña</h2>

        <?php if (isset($_SESSION['error_password'])) { ?>
            <div class="mensaje-error">
                <?php
                echo htmlspecialchars($_SESSION['error_password']);
                unset($_SESSION['error_password']);
                ?>
            </div>
        <?php } ?>

        <?php if (isset($_SESSION['ok_password'])) { ?>
            <div class="mensaje-ok">
                <?php
                echo htmlspecialchars($_SESSION['ok_password']);
                unset($_SESSION['ok_password']);
                ?>
            </div>
        <?php } ?>

        <?php if ($ultimaSolicitud) { ?>
            <div class="alerta">
                Estás viendo el estado de tu última solicitud.
            </div>

            <div class="bloque-info">
                <p>
                    <strong>Estado:</strong><br>
                    <span class="estado <?php echo htmlspecialchars($ultimaSolicitud['estado']); ?>">
                        <?php echo ucfirst(htmlspecialchars($ultimaSolicitud['estado'])); ?>
                    </span>
                </p>

                <p>
                    <strong>Motivo:</strong><br>
                    <?php echo nl2br(htmlspecialchars($ultimaSolicitud['motivo'] ?? '')); ?>
                </p>

                <?php if (!empty($ultimaSolicitud['respuesta_director'])) { ?>
                    <p>
                        <strong>Respuesta del director:</strong><br>
                        <?php echo nl2br(htmlspecialchars($ultimaSolicitud['respuesta_director'])); ?>
                    </p>
                <?php } ?>

                <?php if (($ultimaSolicitud['estado'] ?? '') === 'autorizada') { ?>
                    <div class="acciones">
                        <a href="cambiar_password_autorizado.php?solicitud_id=<?php echo (int)$ultimaSolicitud['id']; ?>" class="btn">
                            Cambiar contraseña ahora
                        </a>
                    </div>
                <?php } ?>
            </div>

            <hr class="separador">
        <?php } ?>

        <?php if (!$ultimaSolicitud || ($ultimaSolicitud['estado'] ?? '') !== 'pendiente') { ?>
            <form action="guardar_solicitud_password.php" method="POST">
                <label>Motivo de la solicitud</label>
                <textarea name="motivo" required></textarea>

                <div class="acciones">
                    <button type="submit" class="btn">Enviar solicitud</button>
                    <a href="dashboard.php" class="btn">Volver</a>
                </div>
            </form>
        <?php } else { ?>
            <div class="mensaje-error">
                Ya tienes una solicitud pendiente. Debes esperar respuesta del director.
            </div>

            <div class="acciones">
                <a href="dashboard.php" class="btn">Volver</a>
            </div>
        <?php } ?>
    </div>
</main>

<?php include("../includes/footer_coordinador.php"); ?>

</body>
</html>
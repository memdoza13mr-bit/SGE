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
          AND estado = 'autorizada'
        ORDER BY id DESC
        LIMIT 1";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    exit("Error al consultar la solicitud.");
}

$stmt->bind_param("i", $coordinador_id);
$stmt->execute();
$solicitud = $stmt->get_result()->fetch_assoc();

if (!$solicitud) {
    exit("No tienes una solicitud autorizada para cambiar tu contraseña.");
}

$titulo_pagina = 'Sistema de Reservas';
$menu_activo = 'solicitar_password';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cambiar contraseña autorizada</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.page-card{
    max-width:760px;
    margin:0 auto;
    background:#fff;
    padding:32px;
    border-radius:18px;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
}

.page-card h2{
    margin:0 0 10px 0;
    text-align:center;
    color:#1d4ed8;
}

.subtexto{
    margin:0 0 22px 0;
    text-align:center;
    color:#64748b;
    font-size:15px;
    line-height:1.5;
}

.form-group{
    margin-bottom:18px;
}

.info-box{
    background:#eff6ff;
    color:#1e40af;
    padding:12px 14px;
    border-radius:10px;
    margin-bottom:18px;
    border:1px solid #bfdbfe;
    font-size:14px;
}

.btn-principal{
    width:100%;
    margin-top:10px;
    background:#1d4ed8;
    color:#fff;
    border:none;
    padding:13px 14px;
    border-radius:10px;
    font-weight:bold;
    font-size:15px;
    cursor:pointer;
    transition:.2s;
}

.btn-principal:hover{
    background:#1e40af;
}

.acciones{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    margin-top:18px;
}

@media (max-width: 700px){
    .page-card{
        padding:22px;
        border-radius:16px;
    }
}
</style>
</head>
<body>

<?php include("../includes/menu_coordinador.php"); ?>

<main class="main">
    <div class="page-card">
        <h2>Cambio de contraseña autorizado</h2>
        <p class="subtexto">
            Tu solicitud fue aprobada. Ahora puedes registrar una nueva contraseña para tu cuenta.
        </p>

        <div class="info-box">
            Solicitud autorizada #<?php echo (int)$solicitud['id']; ?>
        </div>

        <?php if (isset($_SESSION['error_password'])) { ?>
            <div class="mensaje-error">
                <?php
                echo htmlspecialchars($_SESSION['error_password']);
                unset($_SESSION['error_password']);
                ?>
            </div>
        <?php } ?>

        <form action="guardar_password_autorizado.php" method="POST">
            <input type="hidden" name="solicitud_id" value="<?php echo (int)$solicitud['id']; ?>">

            <div class="form-group">
                <label>Nueva contraseña</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label>Confirmar contraseña</label>
                <input type="password" name="password2" required>
            </div>

            <button type="submit" class="btn-principal">Guardar nueva contraseña</button>
        </form>

        <div class="acciones">
            <a href="dashboard.php" class="btn">Volver al panel</a>
        </div>
    </div>
</main>

<?php include("../includes/footer_coordinador.php"); ?>

</body>
</html>
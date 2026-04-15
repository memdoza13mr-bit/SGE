<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die("ID inválido.");
}

$sql = "SELECT * FROM usuarios WHERE id = ? AND rol_id = 2 LIMIT 1";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error al consultar el coordinador.");
}

$stmt->bind_param("i", $id);
$stmt->execute();
$coordinador = $stmt->get_result()->fetch_assoc();

if (!$coordinador) {
    die("Coordinador no encontrado.");
}

$sqlLic = "SELECT licenciatura
           FROM coordinador_licenciaturas
           WHERE coordinador_id = ?";
$stmtLic = $conexion->prepare($sqlLic);

if (!$stmtLic) {
    die("Error al consultar las licenciaturas asignadas.");
}

$stmtLic->bind_param("i", $id);
$stmtLic->execute();
$resLic = $stmtLic->get_result();

$licenciaturasAsignadas = [];
while ($fila = $resLic->fetch_assoc()) {
    $licenciaturasAsignadas[] = $fila['licenciatura'];
}

$sqlTodasLic = "SELECT nombre FROM licenciaturas WHERE estatus = 'activa' ORDER BY nombre ASC";
$resTodasLic = $conexion->query($sqlTodasLic);

$mensaje = $_SESSION['ok_usuario'] ?? '';
$error = $_SESSION['error_usuario'] ?? '';
unset($_SESSION['ok_usuario'], $_SESSION['error_usuario']);

$titulo_pagina = 'Panel Directivo';
$menu_activo = 'coordinadores';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Coordinador</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.card{
    background:#fff;
    border-radius:16px;
    padding:24px;
    box-shadow:0 4px 14px rgba(0,0,0,.06);
    margin-bottom:24px;
    max-width:1000px;
}

.card h3{
    color:#1d4ed8;
    margin-bottom:18px;
    font-size:26px;
}

.grid-form{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px;
    margin-bottom:20px;
}

.grid-full{
    grid-column:1 / -1;
}

.licenciaturas-checks{
    display:grid;
    grid-template-columns:repeat(2, minmax(260px, 1fr));
    gap:12px;
    margin-top:8px;
    margin-bottom:10px;
}

.check-item{
    display:flex;
    align-items:center;
    gap:10px;
    border:1px solid var(--borde);
    border-radius:10px;
    padding:12px 14px;
    background:#fafafa;
    cursor:pointer;
    transition:.2s;
    font-weight:500;
    margin-bottom:0;
}

.check-item:hover{
    background:#f3f4f6;
    border-color:#9ca3af;
}

.check-item input[type="checkbox"]{
    width:18px;
    height:18px;
    margin:0;
    accent-color:var(--azul-titulo);
    flex-shrink:0;
}

.check-item span{
    margin:0;
    font-weight:600;
}

.ayuda{
    font-size:12px;
    color:#666;
    margin-top:6px;
    margin-bottom:12px;
}

.acciones-form{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:10px;
}

@media (max-width: 900px){
    .main{
        padding:20px 14px;
    }

    .grid-form{
        grid-template-columns:1fr;
    }

    .grid-full{
        grid-column:auto;
    }
}

@media (max-width: 700px){
    .licenciaturas-checks{
        grid-template-columns:1fr;
    }
}
</style>
</head>
<body>

<?php include("../includes/menu_directivo.php"); ?>

<main class="main">
    <div class="card">
        <h3>Editar coordinador</h3>

        <?php if ($mensaje !== '') { ?>
            <div class="mensaje-ok"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php } ?>

        <?php if ($error !== '') { ?>
            <div class="mensaje-error"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <form action="actualizar_coordinador.php" method="POST">
            <input type="hidden" name="id" value="<?php echo (int)$coordinador['id']; ?>">

            <div class="grid-form">
                <div>
                    <label>Nombre</label>
                    <input type="text" name="nombre" required value="<?php echo htmlspecialchars($coordinador['nombre']); ?>">
                </div>

                <div>
                    <label>Apellido paterno</label>
                    <input type="text" name="apellido_paterno" required value="<?php echo htmlspecialchars($coordinador['apellido_paterno']); ?>">
                </div>

                <div>
                    <label>Apellido materno</label>
                    <input type="text" name="apellido_materno" value="<?php echo htmlspecialchars($coordinador['apellido_materno'] ?? ''); ?>">
                </div>

                <div>
                    <label>Correo</label>
                    <input type="email" name="correo" required value="<?php echo htmlspecialchars($coordinador['correo']); ?>">
                </div>

                <div class="grid-full">
                    <label>Licenciaturas a su cargo*</label>

                    <div class="licenciaturas-checks">
                        <?php if ($resTodasLic && $resTodasLic->num_rows > 0) { ?>
                            <?php while ($lic = $resTodasLic->fetch_assoc()) { ?>
                                <label class="check-item">
                                    <input
                                        type="checkbox"
                                        name="licenciaturas[]"
                                        value="<?php echo htmlspecialchars($lic['nombre']); ?>"
                                        <?php echo in_array($lic['nombre'], $licenciaturasAsignadas, true) ? 'checked' : ''; ?>
                                    >
                                    <span><?php echo htmlspecialchars($lic['nombre']); ?></span>
                                </label>
                            <?php } ?>
                        <?php } else { ?>
                            <p>No hay licenciaturas activas registradas.</p>
                        <?php } ?>
                    </div>

                    <div class="ayuda">Selecciona una o varias licenciaturas.</div>
                </div>
            </div>

            <div class="acciones-form">
                <button type="submit" class="btn">Guardar cambios</button>
                <a href="coordinadores.php" class="btn">Volver</a>
            </div>
        </form>
    </div>
</main>

<?php include("../includes/footer_directivo.php"); ?>

</body>
</html>
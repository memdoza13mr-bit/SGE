<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

/*
    Se asume:
    1 = directivo
    2 = coordinador
*/
$sql = "SELECT *
        FROM usuarios
        WHERE rol_id = 2
        ORDER BY id DESC";
$res = $conexion->query($sql);

$sqlLicenciaturas = "SELECT nombre 
                     FROM licenciaturas 
                     WHERE estatus = 'activa'
                     ORDER BY nombre ASC";
$resLicenciaturas = $conexion->query($sqlLicenciaturas);

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
<title>Coordinadores</title>
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

.coord-card{
    border:1px solid #e5e7eb;
    border-left:6px solid #ef1c1c;
    border-radius:12px;
    background:#fafafa;
    padding:18px;
    margin-bottom:18px;
}

.coord-card p{
    margin:8px 0;
    line-height:1.5;
}

.estado{
    display:inline-block;
    padding:5px 10px;
    border-radius:20px;
    font-size:13px;
    font-weight:bold;
}

.estado.activo{
    background:#dcfce7;
    color:#166534;
}

.estado.inactivo{
    background:#e5e7eb;
    color:#374151;
}

.acciones{
    margin-top:12px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.btn-azul{
    display:inline-block;
    background:#2563eb;
    color:#fff;
    text-decoration:none;
    padding:10px 14px;
    border-radius:8px;
    font-weight:bold;
}

.btn-azul:hover{
    background:#1d4ed8;
}

.btn-verde{
    display:inline-block;
    background:#16a34a;
    color:#fff;
    text-decoration:none;
    padding:10px 14px;
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
    padding:10px 14px;
    border-radius:8px;
    font-weight:bold;
}

.btn-gris:hover{
    background:#4b5563;
}

.licenciaturas-wrap{
    margin-top:10px;
}

.licenciaturas-titulo{
    font-weight:bold;
    margin-bottom:8px;
    color:#111827;
}

.licenciaturas-lista{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
}

.badge-lic{
    display:inline-block;
    background:#dbeafe;
    color:#1d4ed8;
    padding:6px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:bold;
}

.sin-lic{
    color:#6b7280;
    font-size:14px;
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

.vacio{
    color:#6b7280;
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
        <h3>Registrar coordinador</h3>

        <?php if ($mensaje !== '') { ?>
            <div class="mensaje-ok"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php } ?>

        <?php if ($error !== '') { ?>
            <div class="mensaje-error"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <form action="guardar_coordinador.php" method="POST">
            <div class="grid-form">
                <div>
                    <label>Nombre</label>
                    <input type="text" name="nombre" required>
                </div>

                <div>
                    <label>Apellido paterno</label>
                    <input type="text" name="apellido_paterno" required>
                </div>

                <div>
                    <label>Apellido materno</label>
                    <input type="text" name="apellido_materno">
                </div>

                <div>
                    <label>Correo</label>
                    <input type="email" name="correo" required>
                </div>

                <div class="grid-full">
                    <label>Contraseña temporal</label>
                    <input type="password" name="password" required>
                </div>

                <div class="grid-full">
                    <label>Licenciaturas a su cargo*</label>

                    <div class="licenciaturas-checks">
                        <?php if ($resLicenciaturas && $resLicenciaturas->num_rows > 0) { ?>
                            <?php while ($lic = $resLicenciaturas->fetch_assoc()) { ?>
                                <label class="check-item">
                                    <input type="checkbox" name="licenciaturas[]" value="<?php echo htmlspecialchars($lic['nombre']); ?>">
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

            <button type="submit" class="btn">Guardar coordinador</button>
        </form>
    </div>

    <div class="card">
        <h3>Coordinadores registrados</h3>

        <?php if ($res && $res->num_rows > 0) { ?>
            <?php while($u = $res->fetch_assoc()) { ?>

                <?php
                $sqlLic = "SELECT licenciatura
                           FROM coordinador_licenciaturas
                           WHERE coordinador_id = ?
                           ORDER BY licenciatura ASC";
                $stmtLic = $conexion->prepare($sqlLic);
                $stmtLic->bind_param("i", $u['id']);
                $stmtLic->execute();
                $resLic = $stmtLic->get_result();

                $licenciaturas = [];
                while ($filaLic = $resLic->fetch_assoc()) {
                    $licenciaturas[] = $filaLic['licenciatura'];
                }
                ?>

                <div class="coord-card">
                    <p><strong>Nombre:</strong>
                        <?php
                        echo htmlspecialchars(
                            trim(
                                ($u['nombre'] ?? '') . ' ' .
                                ($u['apellido_paterno'] ?? '') . ' ' .
                                ($u['apellido_materno'] ?? '')
                            )
                        );
                        ?>
                    </p>

                    <p><strong>Correo:</strong> <?php echo htmlspecialchars($u['correo'] ?? ''); ?></p>

                    <p><strong>Estado:</strong>
                        <span class="estado <?php echo htmlspecialchars($u['estatus'] ?? 'inactivo'); ?>">
                            <?php echo ucfirst(htmlspecialchars($u['estatus'] ?? 'inactivo')); ?>
                        </span>
                    </p>

                    <div class="licenciaturas-wrap">
                        <div class="licenciaturas-titulo">Licenciaturas asignadas:</div>

                        <?php if (count($licenciaturas) > 0) { ?>
                            <div class="licenciaturas-lista">
                                <?php foreach ($licenciaturas as $lic) { ?>
                                    <span class="badge-lic"><?php echo htmlspecialchars($lic); ?></span>
                                <?php } ?>
                            </div>
                        <?php } else { ?>
                            <div class="sin-lic">Sin licenciaturas asignadas.</div>
                        <?php } ?>
                    </div>

                    <div class="acciones">
                        <a class="btn-azul" href="editar_coordinador.php?id=<?php echo (int)$u['id']; ?>">Editar</a>

                        <?php if (($u['estatus'] ?? '') === 'activo') { ?>
                            <a class="btn-gris" href="cambiar_estado_coordinador.php?id=<?php echo (int)$u['id']; ?>&estado=inactivo">Desactivar</a>
                        <?php } else { ?>
                            <a class="btn-verde" href="cambiar_estado_coordinador.php?id=<?php echo (int)$u['id']; ?>&estado=activo">Activar</a>
                        <?php } ?>
                    </div>
                </div>

            <?php } ?>
        <?php } else { ?>
            <p class="vacio">No hay coordinadores registrados.</p>
        <?php } ?>
    </div>
</main>

<?php include("../includes/footer_directivo.php"); ?>

</body>
</html>
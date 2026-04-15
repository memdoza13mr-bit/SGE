<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

$mensaje = $_SESSION['ok_espacio'] ?? '';
unset($_SESSION['ok_espacio']);

$resultado = $conexion->query("SELECT * FROM espacios ORDER BY id DESC");

$titulo_pagina = 'Panel Directivo';
$menu_activo = 'espacios';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Administrar Espacios</title>
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

.espacio-item{
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:16px;
    margin-bottom:14px;
    background:#fafafa;
}

.acciones{
    margin-top:12px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.btn-azul,
.btn-gris,
.btn-verde{
    display:inline-block;
    color:#fff;
    text-decoration:none;
    border:none;
    padding:10px 14px;
    border-radius:8px;
    font-weight:bold;
}

.btn-azul{
    background:#2563eb;
}

.btn-azul:hover{
    background:#1d4ed8;
}

.btn-gris{
    background:#6b7280;
}

.btn-gris:hover{
    background:#4b5563;
}

.btn-verde{
    background:#16a34a;
}

.btn-verde:hover{
    background:#15803d;
}

.estado{
    display:inline-block;
    padding:5px 10px;
    border-radius:20px;
    font-size:13px;
    font-weight:bold;
}

.estado.disponible{
    background:#dcfce7;
    color:#166534;
}

.estado.mantenimiento{
    background:#fef3c7;
    color:#92400e;
}

.estado.inactivo{
    background:#e5e7eb;
    color:#374151;
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
    <div class="card">
        <h3>Agregar espacio</h3>

        <?php if ($mensaje !== '') { ?>
            <div class="mensaje-ok">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php } ?>

        <?php if (isset($_SESSION['error_espacio'])) { ?>
            <div class="mensaje-error">
                <?php
                echo htmlspecialchars($_SESSION['error_espacio']);
                unset($_SESSION['error_espacio']);
                ?>
            </div>
        <?php } ?>

        <form action="guardar_espacio.php" method="POST">
            <label>Nombre del espacio</label>
            <input type="text" name="nombre" required>

            <label>Descripción</label>
            <textarea name="descripcion"></textarea>

            <label>Ubicación</label>
            <input type="text" name="ubicacion">

            <label>Capacidad</label>
            <input type="number" name="capacidad" min="0">

            <label>Mínimo de alumnos permitido</label>
            <input type="number" name="minimo_alumnos" min="1">

            <label>Máximo de alumnos permitido</label>
            <input type="number" name="maximo_alumnos" min="1">

            <button class="btn" type="submit">Guardar espacio</button>
        </form>
    </div>

    <div class="card">
        <h3>Espacios registrados</h3>

        <?php if ($resultado && $resultado->num_rows > 0) { ?>
            <?php while($espacio = $resultado->fetch_assoc()) { ?>
                <div class="espacio-item">
                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($espacio['nombre']); ?></p>
                    <p><strong>Ubicación:</strong> <?php echo htmlspecialchars($espacio['ubicacion'] ?? 'No especificada'); ?></p>
                    <p><strong>Capacidad:</strong> <?php echo htmlspecialchars($espacio['capacidad'] ?? 'No especificada'); ?></p>
                    <p><strong>Mínimo de alumnos:</strong> <?php echo htmlspecialchars($espacio['minimo_alumnos'] ?? 'No especificado'); ?></p>
                    <p><strong>Máximo de alumnos:</strong> <?php echo htmlspecialchars($espacio['maximo_alumnos'] ?? 'No especificado'); ?></p>
                    <p><strong>Estado:</strong>
                        <span class="estado <?php echo htmlspecialchars($espacio['estatus']); ?>">
                            <?php echo ucfirst(htmlspecialchars($espacio['estatus'])); ?>
                        </span>
                    </p>

                    <div class="acciones">
                        <a class="btn-azul" href="editar_espacio.php?id=<?php echo (int)$espacio['id']; ?>">Editar</a>

                        <?php if (($espacio['estatus'] ?? '') !== 'inactivo') { ?>
                            <a class="btn-gris" href="cambiar_estado_espacio.php?id=<?php echo (int)$espacio['id']; ?>&estado=inactivo" onclick="return confirm('¿Desactivar este espacio?');">Desactivar</a>
                        <?php } ?>

                        <?php if (($espacio['estatus'] ?? '') !== 'disponible') { ?>
                            <a class="btn-verde" href="cambiar_estado_espacio.php?id=<?php echo (int)$espacio['id']; ?>&estado=disponible">Reactivar</a>
                        <?php } ?>

                        <?php if (($espacio['estatus'] ?? '') !== 'mantenimiento') { ?>
                            <a class="btn-gris" href="cambiar_estado_espacio.php?id=<?php echo (int)$espacio['id']; ?>&estado=mantenimiento">Mantenimiento</a>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <p class="vacio">No hay espacios registrados.</p>
        <?php } ?>
    </div>
</main>

<?php include("../includes/footer_directivo.php"); ?>

</body>
</html>
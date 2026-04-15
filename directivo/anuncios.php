<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

$directivo_id = (int)$_SESSION['usuario_id'];

/* guardar anuncio */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $contenido = trim($_POST['contenido'] ?? '');

    if ($titulo !== '' && $contenido !== '') {
        $sqlInsert = "INSERT INTO anuncios (directivo_id, titulo, contenido, estatus)
                      VALUES (?, ?, ?, 'activo')";
        $stmtInsert = $conexion->prepare($sqlInsert);

        if ($stmtInsert) {
            $stmtInsert->bind_param("iss", $directivo_id, $titulo, $contenido);
            if ($stmtInsert->execute()) {
                $_SESSION['ok_anuncio'] = "Anuncio publicado correctamente.";
            } else {
                $_SESSION['error_anuncio'] = "No se pudo publicar el anuncio.";
            }
        } else {
            $_SESSION['error_anuncio'] = "No se pudo preparar el guardado del anuncio.";
        }

        header("Location: anuncios.php");
        exit();
    } else {
        $_SESSION['error_anuncio'] = "Debes escribir título y contenido.";
        header("Location: anuncios.php");
        exit();
    }
}

/* listar anuncios */
$sqlAnuncios = "SELECT a.*, u.nombre, u.apellido_paterno
                FROM anuncios a
                INNER JOIN usuarios u ON a.directivo_id = u.id
                ORDER BY a.id DESC";

$anuncios = $conexion->query($sqlAnuncios);

$titulo_pagina = 'Panel Directivo';
$menu_activo = 'anuncios';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Anuncios del Directivo</title>
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

.anuncio{
    border-left:6px solid #ef1c1c;
    background:#fafafa;
    border-radius:12px;
    padding:16px;
    margin-bottom:16px;
    border:1px solid #e5e7eb;
}

.anuncio h4{
    margin:0 0 10px 0;
    color:#111827;
}

.anuncio p{
    margin:7px 0;
    line-height:1.6;
}

.meta{
    color:#6b7280;
    font-size:13px;
}

.acciones{
    margin-top:12px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.btn-gris{
    display:inline-block;
    background:#6b7280;
    color:#fff;
    text-decoration:none;
    border:none;
    padding:10px 16px;
    border-radius:8px;
    font-weight:bold;
}

.btn-gris:hover{
    background:#4b5563;
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
        <h3>Crear anuncio</h3>

        <?php if (isset($_SESSION['ok_anuncio'])) { ?>
            <div class="mensaje-ok">
                <?php
                echo htmlspecialchars($_SESSION['ok_anuncio']);
                unset($_SESSION['ok_anuncio']);
                ?>
            </div>
        <?php } ?>

        <?php if (isset($_SESSION['error_anuncio'])) { ?>
            <div class="mensaje-error">
                <?php
                echo htmlspecialchars($_SESSION['error_anuncio']);
                unset($_SESSION['error_anuncio']);
                ?>
            </div>
        <?php } ?>

        <form method="POST" action="">
            <label>Título</label>
            <input type="text" name="titulo" required>

            <label>Contenido</label>
            <textarea name="contenido" required></textarea>

            <button type="submit" class="btn">Publicar anuncio</button>
        </form>
    </div>

    <div class="card">
        <h3>Anuncios publicados</h3>

        <?php if ($anuncios && $anuncios->num_rows > 0) { ?>
            <?php while($a = $anuncios->fetch_assoc()) { ?>
                <div class="anuncio">
                    <h4><?php echo htmlspecialchars($a['titulo'] ?? ''); ?></h4>

                    <p><?php echo nl2br(htmlspecialchars($a['contenido'] ?? '')); ?></p>

                    <p class="meta">
                        Publicado por
                        <?php echo htmlspecialchars(($a['nombre'] ?? '') . ' ' . ($a['apellido_paterno'] ?? '')); ?>
                        |
                        <?php echo htmlspecialchars($a['fecha_publicacion'] ?? ''); ?>
                        |
                        Estado: <?php echo htmlspecialchars($a['estatus'] ?? 'activo'); ?>
                    </p>

                    <div class="acciones">
                        <a class="btn-gris" href="eliminar_anuncio.php?id=<?php echo (int)$a['id']; ?>" onclick="return confirm('¿Deseas eliminar este anuncio?');">Eliminar</a>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <p class="vacio">No hay anuncios publicados todavía.</p>
        <?php } ?>
    </div>
</main>

<?php include("../includes/footer_directivo.php"); ?>

</body>
</html>
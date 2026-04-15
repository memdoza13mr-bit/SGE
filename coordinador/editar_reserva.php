<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'coordinador') {
    header("Location: ../auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$coordinador_id = (int)$_SESSION['usuario_id'];

if ($id <= 0) {
    exit("Reserva inválida.");
}

$sqlReserva = "SELECT *
               FROM reservas
               WHERE id = ? AND coordinador_id = ?
               LIMIT 1";
$stmtReserva = $conexion->prepare($sqlReserva);

if (!$stmtReserva) {
    exit("Error al consultar la reserva.");
}

$stmtReserva->bind_param("ii", $id, $coordinador_id);
$stmtReserva->execute();
$reserva = $stmtReserva->get_result()->fetch_assoc();

if (!$reserva) {
    exit("Reserva no encontrada.");
}

if (($reserva['estado'] ?? '') !== 'correccion') {
    exit("Esta reserva no está disponible para edición.");
}

/* licenciaturas permitidas del coordinador */
$sqlLicCoord = "SELECT licenciatura
                FROM coordinador_licenciaturas
                WHERE coordinador_id = ?
                ORDER BY licenciatura ASC";
$stmtLicCoord = $conexion->prepare($sqlLicCoord);

if (!$stmtLicCoord) {
    exit("Error al consultar las licenciaturas.");
}

$stmtLicCoord->bind_param("i", $coordinador_id);
$stmtLicCoord->execute();
$resLicCoord = $stmtLicCoord->get_result();

$licenciaturasCoordinador = [];
while ($filaLic = $resLicCoord->fetch_assoc()) {
    $licenciaturasCoordinador[] = $filaLic['licenciatura'];
}

$opcionesCuatrimestres = [
    "1A","1B","2A","2B","3A","3B","4A","4B","5A","5B","6A","6B","7A","7B","8A","8B","9A","9B"
];

/* detalles */
$sqlDetalles = "SELECT rd.*, e.nombre AS espacio_nombre
                FROM reserva_detalles rd
                INNER JOIN espacios e ON rd.espacio_id = e.id
                WHERE rd.reserva_id = ?
                ORDER BY rd.fecha, rd.hora_inicio";
$stmtDetalles = $conexion->prepare($sqlDetalles);

if (!$stmtDetalles) {
    exit("Error al consultar los detalles de la reserva.");
}

$stmtDetalles->bind_param("i", $id);
$stmtDetalles->execute();
$detalles = $stmtDetalles->get_result();

$detallesArray = [];
$primerDetalle = null;

while ($d = $detalles->fetch_assoc()) {
    if ($primerDetalle === null) {
        $primerDetalle = $d;
    }
    $detallesArray[] = $d;
}

if (!$primerDetalle) {
    exit("La reserva no tiene detalles para editar.");
}

/* tomar la info general del primer detalle */
$lics = array_values(array_filter(array_map('trim', explode(',', (string)($primerDetalle['licenciaturas'] ?? '')))));
$cuats = array_values(array_filter(array_map('trim', explode(',', (string)($primerDetalle['cuatrimestres'] ?? '')))));

$paresGenerales = [];
$max = max(count($lics), count($cuats));
for ($i = 0; $i < $max; $i++) {
    $paresGenerales[] = [
        'licenciatura' => $lics[$i] ?? '',
        'cuatrimestre' => $cuats[$i] ?? ''
    ];
}

if (count($paresGenerales) === 0) {
    $paresGenerales[] = [
        'licenciatura' => '',
        'cuatrimestre' => ''
    ];
}

$fechaMin = date('Y-m-d', strtotime('+10 days'));

$titulo_pagina = 'Sistema de Reservas';
$menu_activo = 'mis_reservas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Reserva</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.page-card{
    background:#fff;
    border-radius:18px;
    padding:24px;
    box-shadow:0 8px 24px rgba(15,23,42,.08);
}

.detalle-item{
    border:1px solid #d8d8d8;
    border-radius:12px;
    padding:20px;
    margin-bottom:20px;
    background:#fff;
    box-shadow:0 2px 8px rgba(0,0,0,.05);
}

.ayuda{
    font-size:12px;
    color:#777;
    margin-top:-8px;
    margin-bottom:12px;
}

.seccion-espacios{
    margin-top:20px;
}

.botones-form{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    margin-top:20px;
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

.subtitulo-formato{
    margin-top:10px;
    margin-bottom:20px;
    font-size:14px;
    color:#6b7280;
    text-align:center;
}

.pares-lic-cuat{
    display:flex;
    flex-direction:column;
    gap:12px;
    margin-bottom:12px;
}

.par-lic-cuat{
    display:grid;
    grid-template-columns:1fr 1fr auto;
    gap:12px;
    align-items:end;
    border:1px solid #e5e7eb;
    border-radius:10px;
    padding:12px;
    background:#fafafa;
}

.btn-mini{
    background:#6b7280;
    color:#fff;
    border:none;
    padding:10px 12px;
    border-radius:8px;
    cursor:pointer;
    font-weight:bold;
}

.btn-mini:hover{
    background:#4b5563;
}

.titulo-bloque{
    color:#1d4ed8;
    font-size:18px;
    margin-bottom:15px;
    font-weight:700;
}

.mensaje-directivo{
    background:#fff7ed;
    border:1px solid #fdba74;
    color:#9a3412;
    padding:14px;
    border-radius:10px;
    margin-bottom:18px;
}

select,
input[type="text"],
input[type="date"],
input[type="time"],
input[type="number"],
textarea{
    width:100%;
}

@media (max-width: 900px){
    .grid-form{
        grid-template-columns:1fr;
    }

    .grid-full{
        grid-column:auto;
    }

    .par-lic-cuat{
        grid-template-columns:1fr;
    }
}
</style>
</head>
<body>

<?php include("../includes/menu_coordinador.php"); ?>

<main class="main">
    <div class="page-card">

        <?php if (isset($_SESSION['error_reserva'])) { ?>
            <div class="mensaje-error">
                <?php
                echo htmlspecialchars($_SESSION['error_reserva']);
                unset($_SESSION['error_reserva']);
                ?>
            </div>
        <?php } ?>

        <form action="actualizar_reserva.php" method="POST">
            <input type="hidden" name="id" value="<?php echo (int)$reserva['id']; ?>">

            <h2 class="titulo-azul">Editar reserva</h2>
            <p class="subtitulo-formato">Corrige los datos solicitados por el directivo y vuelve a enviarla.</p>

            <div class="mensaje-directivo">
                <strong>Observaciones del directivo:</strong><br>
                <?php echo nl2br(htmlspecialchars($reserva['observaciones_directivo'] ?? '')); ?>
            </div>

            <div class="titulo-bloque">Información general</div>

            <div class="grid-form">
                <div>
                    <label>Folio del coordinador</label>
                    <input type="text" name="folio_coordinador" required value="<?php echo htmlspecialchars($reserva['folio_coordinador'] ?? ''); ?>">
                </div>

                <div>
                    <label>Fecha de entrega del formato</label>
                    <input type="date" value="<?php echo htmlspecialchars($reserva['fecha_entrega_formato'] ?? ''); ?>" readonly>
                </div>

                <div>
                    <label>Tipo de actividad</label>
                    <select name="tipo_actividad" required>
                        <option value="">Seleccione</option>
                        <option value="Proyecto integrador" <?php echo (($reserva['tipo_actividad'] ?? '') === 'Proyecto integrador') ? 'selected' : ''; ?>>Proyecto integrador</option>
                        <option value="Actividades ludicas o recreativas" <?php echo (($reserva['tipo_actividad'] ?? '') === 'Actividades ludicas o recreativas') ? 'selected' : ''; ?>>Actividades ludicas o recreativas</option>
                        <option value="Conferencias" <?php echo (($reserva['tipo_actividad'] ?? '') === 'Conferencias') ? 'selected' : ''; ?>>Conferencias</option>
                        <option value="Talleres" <?php echo (($reserva['tipo_actividad'] ?? '') === 'Talleres') ? 'selected' : ''; ?>>Talleres</option>
                    </select>
                </div>

                <div>
                    <label>Nombre de la actividad</label>
                    <input type="text" name="nombre_actividad" required value="<?php echo htmlspecialchars($reserva['nombre_actividad'] ?? ''); ?>">
                </div>

                <div class="grid-full">
                    <label>Objetivo</label>
                    <textarea name="objetivo" required><?php echo htmlspecialchars($reserva['objetivo'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label>Asignatura vinculada</label>
                    <input type="text" name="asignatura" required value="<?php echo htmlspecialchars($reserva['asignatura'] ?? ''); ?>">
                </div>

                <div>
                    <label>Docente responsable</label>
                    <input type="text" name="docente_responsable" required value="<?php echo htmlspecialchars($reserva['docente_responsable'] ?? ''); ?>">
                </div>

                <div class="grid-full">
                    <label>Coordinador responsable</label>
                    <input type="text" name="coordinador_responsable" required value="<?php echo htmlspecialchars($reserva['coordinador_responsable'] ?? ''); ?>">
                </div>

                <div class="grid-full">
                    <label>Licenciaturas y cuatrimestres</label>

                    <div class="pares-lic-cuat" id="pares-lic-cuat-general">
                        <?php foreach ($paresGenerales as $indice => $par) { ?>
                            <div class="par-lic-cuat">
                                <div>
                                    <label>Licenciatura</label>
                                    <select name="lic_cuat[<?php echo $indice; ?>][licenciatura]" required>
                                        <option value="">Seleccione</option>
                                        <?php foreach ($licenciaturasCoordinador as $lic) { ?>
                                            <option value="<?php echo htmlspecialchars($lic); ?>" <?php echo ($par['licenciatura'] === $lic) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($lic); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div>
                                    <label>Cuatrimestre / Grupo</label>
                                    <select name="lic_cuat[<?php echo $indice; ?>][cuatrimestre]" required>
                                        <option value="">Seleccione</option>
                                        <?php foreach ($opcionesCuatrimestres as $opc) { ?>
                                            <option value="<?php echo $opc; ?>" <?php echo ($par['cuatrimestre'] === $opc) ? 'selected' : ''; ?>>
                                                <?php echo $opc; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div>
                                    <?php if ($indice > 0) { ?>
                                        <button type="button" class="btn-mini" onclick="this.parentElement.parentElement.remove()">Quitar</button>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="ayuda">Agrega una pareja licenciatura/cuatrimestre por cada grupo participante.</div>
                    <button type="button" class="btn" onclick="agregarParLicCuatGeneral()">Agregar licenciatura y cuatrimestre</button>
                </div>

                <div>
                    <label>Cantidad total de alumnos</label>
                    <input type="number" name="cantidad_alumnos_general" min="1" required value="<?php echo htmlspecialchars($primerDetalle['cantidad_alumnos'] ?? ''); ?>">
                </div>

                <div>
                    <label>Costo por alumno</label>
                    <input type="number" step="0.01" name="costo_alumno_general" value="<?php echo htmlspecialchars($primerDetalle['costo_alumno'] ?? ''); ?>">
                </div>

                <div>
                    <label>Promoción en redes</label>
                    <select name="promocion_redes_general">
                        <option value="no" <?php echo (($primerDetalle['promocion_redes'] ?? 'no') === 'no') ? 'selected' : ''; ?>>No</option>
                        <option value="si" <?php echo (($primerDetalle['promocion_redes'] ?? '') === 'si') ? 'selected' : ''; ?>>Sí</option>
                    </select>
                </div>

                <div class="grid-full">
                    <label>Descripción de actividad</label>
                    <textarea name="descripcion_actividad_general"><?php echo htmlspecialchars($primerDetalle['descripcion_actividad'] ?? ''); ?></textarea>
                </div>

                <div class="grid-full">
                    <label>Observaciones</label>
                    <textarea name="observaciones_general"><?php echo htmlspecialchars($primerDetalle['observaciones'] ?? ''); ?></textarea>
                </div>

                <div class="grid-full">
                    <label>Material requerido</label>
                    <textarea name="material_requerido_general"><?php echo htmlspecialchars($primerDetalle['material_requerido'] ?? ''); ?></textarea>
                </div>
            </div>

            <hr>

            <div class="seccion-espacios">
                <h2 class="titulo-azul">Espacios y horarios</h2>

                <div id="contenedor-detalles">
                    <?php foreach ($detallesArray as $indice => $d) { ?>
                        <div class="detalle-item" data-indice="<?php echo $indice; ?>">
                            <div class="titulo-bloque">Espacio <?php echo $indice + 1; ?></div>

                            <div class="grid-form">
                                <div>
                                    <label>Espacio</label>
                                    <select name="espacio_id[]" required>
                                        <option value="">Seleccione</option>
                                        <?php
                                        $espaciosLista = $conexion->query("SELECT * FROM espacios WHERE estatus='disponible' OR id = " . (int)$d['espacio_id'] . " ORDER BY nombre ASC");
                                        while($e = $espaciosLista->fetch_assoc()){
                                            $selected = ((int)$d['espacio_id'] === (int)$e['id']) ? 'selected' : '';
                                            echo '<option value="' . (int)$e['id'] . '" ' . $selected . '>' . htmlspecialchars($e['nombre']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div>
                                    <label>Fecha</label>
                                    <input type="date" name="fecha[]" value="<?php echo htmlspecialchars($d['fecha']); ?>" min="<?php echo $fechaMin; ?>" required>
                                </div>

                                <div>
                                    <label>Hora inicio</label>
                                    <input type="time" name="hora_inicio[]" value="<?php echo htmlspecialchars($d['hora_inicio']); ?>" required>
                                </div>

                                <div>
                                    <label>Hora fin</label>
                                    <input type="time" name="hora_fin[]" value="<?php echo htmlspecialchars($d['hora_fin']); ?>" required>
                                </div>

                                <div class="grid-full">
                                    <label>Requerimientos de este espacio</label>
                                    <textarea name="requerimientos[]"><?php echo htmlspecialchars($d['requerimientos'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <button type="button" class="btn" onclick="eliminarDetalle(this)">Eliminar espacio</button>
                        </div>
                    <?php } ?>
                </div>

                <div class="botones-form">
                    <button type="button" class="btn" onclick="agregarDetalle()">Agregar otro espacio</button>
                    <button type="submit" class="btn">Reenviar reserva</button>
                </div>
            </div>
        </form>
    </div>
</main>

<?php include("../includes/footer_coordinador.php"); ?>

<script>
let indiceDetalle = <?php echo count($detallesArray); ?>;
let indiceLicGeneral = <?php echo count($paresGenerales); ?>;

const opcionesLicenciaturas = `<?php
    $htmlLic = '<option value="">Seleccione</option>';
    foreach ($licenciaturasCoordinador as $lic) {
        $htmlLic .= '<option value="' . htmlspecialchars($lic, ENT_QUOTES) . '">' . htmlspecialchars($lic, ENT_QUOTES) . '</option>';
    }
    echo $htmlLic;
?>`;

const opcionesCuatrimestres = `
<option value="">Seleccione</option>
<option value="1A">1A</option>
<option value="1B">1B</option>
<option value="2A">2A</option>
<option value="2B">2B</option>
<option value="3A">3A</option>
<option value="3B">3B</option>
<option value="4A">4A</option>
<option value="4B">4B</option>
<option value="5A">5A</option>
<option value="5B">5B</option>
<option value="6A">6A</option>
<option value="6B">6B</option>
<option value="7A">7A</option>
<option value="7B">7B</option>
<option value="8A">8A</option>
<option value="8B">8B</option>
<option value="9A">9A</option>
<option value="9B">9B</option>
`;

function agregarParLicCuatGeneral(){
    const contenedor = document.getElementById("pares-lic-cuat-general");

    const nuevo = document.createElement("div");
    nuevo.classList.add("par-lic-cuat");

    nuevo.innerHTML = `
        <div>
            <label>Licenciatura</label>
            <select name="lic_cuat[${indiceLicGeneral}][licenciatura]" required>
                ${opcionesLicenciaturas}
            </select>
        </div>

        <div>
            <label>Cuatrimestre / Grupo</label>
            <select name="lic_cuat[${indiceLicGeneral}][cuatrimestre]" required>
                ${opcionesCuatrimestres}
            </select>
        </div>

        <div>
            <button type="button" class="btn-mini" onclick="this.parentElement.parentElement.remove()">Quitar</button>
        </div>
    `;

    contenedor.appendChild(nuevo);
    indiceLicGeneral++;
}

function agregarDetalle(){
    const contenedor = document.getElementById("contenedor-detalles");
    const indiceActual = indiceDetalle + 1;

    const bloque = document.createElement("div");
    bloque.classList.add("detalle-item");
    bloque.setAttribute("data-indice", indiceDetalle);

    const bloque = document.createElement("div");
    bloque.classList.add("detalle-item");
    bloque.setAttribute("data-indice", indiceDetalle);

    bloque.innerHTML = `
        <div class="titulo-bloque">Espacio ${indiceActual}</div>

        <div class="grid-form">
            <div>
                <label>Espacio</label>
                <select name="espacio_id[]" required>
                    <option value="">Seleccione</option>
                    <?php
                    $espacios_js = $conexion->query("SELECT * FROM espacios WHERE estatus='disponible' ORDER BY nombre ASC");
                    while($e2 = $espacios_js->fetch_assoc()){
                        echo "<option value='".$e2['id']."'>".htmlspecialchars($e2['nombre'], ENT_QUOTES)."</option>";
                    }
                    ?>
                </select>
            </div>

            <div>
                <label>Fecha</label>
                <input type="date" name="fecha[]" min="<?php echo $fechaMin; ?>" required>
            </div>

            <div>
                <label>Hora inicio</label>
                <input type="time" name="hora_inicio[]" required>
            </div>

            <div>
                <label>Hora fin</label>
                <input type="time" name="hora_fin[]" required>
            </div>

            <div class="grid-full">
                <label>Requerimientos de este espacio</label>
                <textarea name="requerimientos[]" placeholder="Escribe aquí los requerimientos específicos para este espacio..."></textarea>
            </div>
        </div>

        <button type="button" class="btn" onclick="eliminarDetalle(this)">Eliminar espacio</button>
    `;

    contenedor.appendChild(bloque);
    indiceDetalle++;
}

function eliminarDetalle(btn){
    const bloques = document.querySelectorAll(".detalle-item");
    if (bloques.length > 1) {
        btn.parentElement.remove();
    } else {
        alert("Debe existir al menos un espacio.");
    }
}
</script>

</body>
</html>
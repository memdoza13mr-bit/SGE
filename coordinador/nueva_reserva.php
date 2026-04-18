<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'coordinador') {
    header("Location: ../auth/login.php");
    exit();
}

$coordinador_id = (int)$_SESSION['usuario_id'];
$fechaMin = date('Y-m-d', strtotime('+10 days'));

/* =========================
   DATOS ANTERIORES DEL FORM
========================= */
$formData = $_SESSION['form_reserva'] ?? [];
unset($_SESSION['form_reserva']);

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function old($campo, $default = '') {
    global $formData;
    return isset($formData[$campo]) ? $formData[$campo] : $default;
}

function oldArray($campo, $indice, $default = '') {
    global $formData;
    return isset($formData[$campo][$indice]) ? $formData[$campo][$indice] : $default;
}

$fechaPre = $_GET['fecha'] ?? oldArray('fecha', 0, '');
$espacioPre = $_GET['espacio_id'] ?? oldArray('espacio_id', 0, '');
$horaInicioPre = $_GET['hora_inicio'] ?? oldArray('hora_inicio', 0, '');
$horaFinPre = $_GET['hora_fin'] ?? oldArray('hora_fin', 0, '');

/* LICENCIATURAS ASIGNADAS AL COORDINADOR */
$sqlLicCoord = "SELECT licenciatura
                FROM coordinador_licenciaturas
                WHERE coordinador_id = ?
                ORDER BY licenciatura ASC";
$stmtLicCoord = $conexion->prepare($sqlLicCoord);

if (!$stmtLicCoord) {
    exit("Error al cargar las licenciaturas.");
}

$stmtLicCoord->bind_param("i", $coordinador_id);
$stmtLicCoord->execute();
$resLicCoord = $stmtLicCoord->get_result();

$licenciaturasCoordinador = [];
while ($filaLic = $resLicCoord->fetch_assoc()) {
    $licenciaturasCoordinador[] = $filaLic['licenciatura'];
}

/* ESPACIOS */
$listaEspacios = [];
$espaciosQuery = $conexion->query("SELECT * FROM espacios WHERE estatus='disponible' ORDER BY nombre ASC");
while ($esp = $espaciosQuery->fetch_assoc()) {
    $listaEspacios[] = $esp;
}

/* ARMAR DETALLES DE ESPACIOS */
$detalles = [];

if (!empty($formData['espacio_id']) && is_array($formData['espacio_id'])) {
    $totalDetalles = count($formData['espacio_id']);
    for ($i = 0; $i < $totalDetalles; $i++) {
        $detalles[] = [
            'espacio_id' => $formData['espacio_id'][$i] ?? '',
            'fecha' => $formData['fecha'][$i] ?? '',
            'hora_inicio' => $formData['hora_inicio'][$i] ?? '',
            'hora_fin' => $formData['hora_fin'][$i] ?? '',
            'requerimientos' => $formData['requerimientos'][$i] ?? ''
        ];
    }
}

if (empty($detalles)) {
    $detalles[] = [
        'espacio_id' => $espacioPre,
        'fecha' => $fechaPre,
        'hora_inicio' => $horaInicioPre,
        'hora_fin' => $horaFinPre,
        'requerimientos' => ''
    ];
}

/* LICENCIATURAS / CUATRIMESTRES */
$licCuatData = old('lic_cuat', []);
if (!is_array($licCuatData) || empty($licCuatData)) {
    $licCuatData = [
        [
            'licenciatura' => '',
            'cuatrimestre' => ''
        ]
    ];
}

$titulo_pagina = 'Sistema de Reservas';
$menu_activo = 'nueva_reserva';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nueva Reserva</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.page-card{
    background:#fff;
    border-radius:18px;
    padding:24px;
    box-shadow:0 8px 24px rgba(37, 97, 239, 0.08);
}
.detalle-item{
    border:1px solid #d8d8d8;
    border-radius:10px;
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
.mensaje-aviso{
    background:#fef3c7;
    color:#92400e;
    padding:12px;
    border-radius:8px;
    margin-bottom:16px;
    font-weight:bold;
}
.titulo-bloque{
    color:#1d4ed8;
    font-size:18px;
    margin-bottom:15px;
    font-weight:700;
}
.preseleccion{
    background:#eff6ff;
    color:#1d4ed8;
    border:1px solid #bfdbfe;
    padding:12px;
    border-radius:10px;
    margin-bottom:16px;
}
.mensaje-error{
    background:#fee2e2;
    color:#991b1b;
    border:1px solid #fecaca;
    padding:12px;
    border-radius:10px;
    margin-bottom:20px;
    font-weight:600;
}
.mensaje-ok{
    background:#dcfce7;
    color:#166534;
    border:1px solid #bbf7d0;
    padding:12px;
    border-radius:10px;
    margin-bottom:20px;
    font-weight:600;
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
    <?php if (isset($_SESSION['error_reserva'])) { ?>
        <div class="mensaje-error">
            <?php
            echo e($_SESSION['error_reserva']);
            unset($_SESSION['error_reserva']);
            ?>
        </div>
    <?php } ?>

    <?php if (isset($_SESSION['ok_reserva'])) { ?>
        <div class="mensaje-ok">
            <?php
            echo e($_SESSION['ok_reserva']);
            unset($_SESSION['ok_reserva']);
            ?>
        </div>
    <?php } ?>

    <div class="page-card">
        <?php if ($fechaPre !== '' || $espacioPre !== '' || $horaInicioPre !== '' || $horaFinPre !== '') { ?>
            <div class="preseleccion">
                Se cargó un horario sugerido desde el calendario. Puedes ajustarlo antes de enviar la reserva.
            </div>
        <?php } ?>

        <?php if (count($licenciaturasCoordinador) === 0) { ?>
            <div class="mensaje-aviso">
                Tu usuario no tiene licenciaturas asignadas. Solicita al directivo que te asigne al menos una antes de hacer una reserva.
            </div>
        <?php } ?>

        <form action="guardar_reserva.php" method="POST">
            <h2 class="titulo-azul">Formato de Actividades o Eventos</h2>
            <p class="subtitulo-formato">Primero captura la información general y después agrega uno o varios espacios.</p>

            <div class="titulo-bloque">Información general</div>

            <div class="grid-form">
                <div>
                    <label>Folio del coordinador</label>
                    <input type="text" name="folio_coordinador" value="<?php echo e(old('folio_coordinador')); ?>" required>
                </div>

                <div>
                    <label>Fecha de entrega del formato</label>
                    <input type="date" value="<?php echo date('Y-m-d'); ?>" readonly>
                </div>

                <div>
                    <label>Tipo de actividad</label>
                    <select name="tipo_actividad" required>
                        <option value="">Seleccione</option>
                        <option value="Proyecto integrador" <?php echo old('tipo_actividad') === 'Proyecto integrador' ? 'selected' : ''; ?>>Proyecto integrador</option>
                        <option value="Actividades ludicas o recreativas" <?php echo old('tipo_actividad') === 'Actividades ludicas o recreativas' ? 'selected' : ''; ?>>Actividades ludicas o recreativas</option>
                        <option value="Conferencias" <?php echo old('tipo_actividad') === 'Conferencias' ? 'selected' : ''; ?>>Conferencias</option>
                        <option value="Talleres" <?php echo old('tipo_actividad') === 'Talleres' ? 'selected' : ''; ?>>Talleres</option>
                    </select>
                </div>

                <div>
                    <label>Nombre de la actividad</label>
                    <input type="text" name="nombre_actividad" value="<?php echo e(old('nombre_actividad')); ?>" required>
                </div>
            </div>

            <div class="seccion-espacios">
                <h2 class="titulo-azul">Espacios y horarios</h2>

                <div id="contenedor-detalles">
                    <?php foreach ($detalles as $i => $detalle) { ?>
                        <div class="detalle-item" data-indice="<?php echo $i; ?>">
                            <div class="titulo-bloque">Espacio <?php echo $i + 1; ?></div>

                            <div class="grid-form">
                                <div>
                                    <label>Espacio</label>
                                    <select name="espacio_id[]" required>
                                        <option value="">Seleccione</option>
                                        <?php foreach ($listaEspacios as $e) { ?>
                                            <option value="<?php echo $e['id']; ?>" <?php echo ((string)$detalle['espacio_id'] === (string)$e['id']) ? 'selected' : ''; ?>>
                                                <?php echo e($e['nombre']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div>
                                    <label>Fecha</label>
                                    <input
                                        type="date"
                                        name="fecha[]"
                                        value="<?php echo e($detalle['fecha']); ?>"
                                        min="<?php echo $fechaMin; ?>"
                                        required
                                    >
                                </div>

                                <div>
                                    <label>Hora inicio</label>
                                    <input type="time" name="hora_inicio[]" value="<?php echo e($detalle['hora_inicio']); ?>" required>
                                </div>

                                <div>
                                    <label>Hora fin</label>
                                    <input type="time" name="hora_fin[]" value="<?php echo e($detalle['hora_fin']); ?>" required>
                                </div>

                                <div class="grid-full">
                                    <label>Requerimientos de este espacio</label>
                                    <textarea name="requerimientos[]" placeholder="Escribe aquí los requerimientos específicos para este espacio..."><?php echo e($detalle['requerimientos']); ?></textarea>
                                </div>
                            </div>

                            <button type="button" class="btn" onclick="eliminarDetalle(this)">Eliminar espacio</button>
                        </div>
                    <?php } ?>
                </div>

                <div class="botones-form">
                    <button type="button" class="btn" onclick="agregarDetalle()" <?php echo count($licenciaturasCoordinador) === 0 ? 'disabled' : ''; ?>>Agregar otro espacio</button>
                </div>
            </div>

            <div class="grid-form">
                <div class="grid-full">
                    <label>Objetivo</label>
                    <textarea name="objetivo" required><?php echo e(old('objetivo')); ?></textarea>
                </div>

                <div>
                    <label>Asignatura vinculada</label>
                    <input type="text" name="asignatura" value="<?php echo e(old('asignatura')); ?>" required>
                </div>

                <div>
                    <label>Docente responsable</label>
                    <input type="text" name="docente_responsable" value="<?php echo e(old('docente_responsable')); ?>" required>
                </div>

                <div class="grid-full">
                    <label>Coordinador responsable</label>
                    <input type="text" name="coordinador_responsable" value="<?php echo e(old('coordinador_responsable')); ?>" required>
                </div>

                <div class="grid-full">
                    <label>Licenciaturas y cuatrimestres</label>

                    <div class="pares-lic-cuat" id="pares-lic-cuat-general">
                        <?php foreach ($licCuatData as $i => $par) { ?>
                            <div class="par-lic-cuat">
                                <div>
                                    <label>Licenciatura</label>
                                    <select name="lic_cuat[<?php echo $i; ?>][licenciatura]" required <?php echo count($licenciaturasCoordinador) === 0 ? 'disabled' : ''; ?>>
                                        <option value="">Seleccione</option>
                                        <?php foreach ($licenciaturasCoordinador as $lic) { ?>
                                            <option value="<?php echo e($lic); ?>" <?php echo (($par['licenciatura'] ?? '') === $lic) ? 'selected' : ''; ?>>
                                                <?php echo e($lic); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div>
                                    <label>Cuatrimestre / Grupo</label>
                                    <select name="lic_cuat[<?php echo $i; ?>][cuatrimestre]" required>
                                        <option value="">Seleccione</option>
                                        <?php
                                        $cuats = ['1A','1B','2A','2B','3A','3B','4A','4B','5A','5B','6A','6B','7A','7B','8A','8B','9A','9B'];
                                        foreach ($cuats as $cuat) {
                                        ?>
                                            <option value="<?php echo $cuat; ?>" <?php echo (($par['cuatrimestre'] ?? '') === $cuat) ? 'selected' : ''; ?>>
                                                <?php echo $cuat; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div>
                                    <?php if ($i > 0) { ?>
                                        <button type="button" class="btn-mini" onclick="this.parentElement.parentElement.remove()">Quitar</button>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="ayuda">Agrega una pareja licenciatura/cuatrimestre por cada grupo participante.</div>
                    <button type="button" class="btn" onclick="agregarParLicCuatGeneral()" <?php echo count($licenciaturasCoordinador) === 0 ? 'disabled' : ''; ?>>Agregar licenciatura y cuatrimestre</button>
                </div>

                <div>
                    <label>Cantidad total de alumnos</label>
                    <input type="number" name="cantidad_alumnos_general" min="1" value="<?php echo e(old('cantidad_alumnos_general')); ?>" required>
                </div>

                <div>
                    <label>Costo por alumno</label>
                    <input type="number" step="0.01" name="costo_alumno_general" value="<?php echo e(old('costo_alumno_general')); ?>">
                </div>

                <div>
                    <label>Promoción en redes</label>
                    <select name="promocion_redes_general">
                        <option value="no" <?php echo old('promocion_redes_general', 'no') === 'no' ? 'selected' : ''; ?>>No</option>
                        <option value="si" <?php echo old('promocion_redes_general') === 'si' ? 'selected' : ''; ?>>Sí</option>
                    </select>
                </div>

                <div class="grid-full">
                    <label>Descripción de actividad (Itinerario)</label>
                    <textarea name="descripcion_actividad_general"><?php echo e(old('descripcion_actividad_general')); ?></textarea>
                </div>

                <div class="grid-full">
                    <label>Observaciones</label>
                    <textarea name="observaciones_general"><?php echo e(old('observaciones_general')); ?></textarea>
                </div>
            </div>

            <div class="botones-form">
                <button type="submit" class="btn" <?php echo count($licenciaturasCoordinador) === 0 ? 'disabled' : ''; ?>>Enviar reserva</button>
            </div>
        </form>
    </div>
</main>

<?php include("../includes/footer_coordinador.php"); ?>

<script>
let indiceDetalle = <?php echo count($detalles); ?>;
let indiceLicGeneral = <?php echo count($licCuatData); ?>;

const opcionesEspacios = `
    <option value="">Seleccione</option>
    <?php foreach ($listaEspacios as $e2) { ?>
        <option value="<?php echo $e2['id']; ?>"><?php echo e($e2['nombre']); ?></option>
    <?php } ?>
`;

const opcionesLicenciaturas = `
    <option value="">Seleccione</option>
    <?php foreach ($licenciaturasCoordinador as $lic) { ?>
        <option value="<?php echo e($lic); ?>"><?php echo e($lic); ?></option>
    <?php } ?>
`;

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

    bloque.innerHTML = `
        <div class="titulo-bloque">Espacio ${indiceActual}</div>

        <div class="grid-form">
            <div>
                <label>Espacio</label>
                <select name="espacio_id[]" required>
                    ${opcionesEspacios}
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
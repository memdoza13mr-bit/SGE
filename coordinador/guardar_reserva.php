<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'coordinador') {
    header("Location: ../auth/login.php");
    exit();
}

function volverConError($mensaje, $postData = []) {
    $_SESSION['error_reserva'] = $mensaje;
    $_SESSION['form_reserva'] = $postData;
    header("Location: nueva_reserva.php");
    exit();
}

$coordinador_id = (int)($_SESSION['usuario_id'] ?? 0);

/* FECHA AUTOMÁTICA DEL SERVIDOR */
$fecha_entrega_formato = date('Y-m-d');

/* VALIDAR MÉTODO */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    volverConError("Método de envío no válido.");
}

/* CAPTURA Y NORMALIZACIÓN DE DATOS */
$folio_coordinador = trim($_POST['folio_coordinador'] ?? '');
$tipo_actividad = trim($_POST['tipo_actividad'] ?? '');
$nombre_actividad = trim($_POST['nombre_actividad'] ?? '');
$objetivo = trim($_POST['objetivo'] ?? '');
$asignatura = trim($_POST['asignatura'] ?? '');
$docente_responsable = trim($_POST['docente_responsable'] ?? '');
$coordinador_responsable = trim($_POST['coordinador_responsable'] ?? '');

$espacios = isset($_POST['espacio_id']) && is_array($_POST['espacio_id']) ? $_POST['espacio_id'] : [];
$fechas = isset($_POST['fecha']) && is_array($_POST['fecha']) ? $_POST['fecha'] : [];
$hora_inicio = isset($_POST['hora_inicio']) && is_array($_POST['hora_inicio']) ? $_POST['hora_inicio'] : [];
$hora_fin = isset($_POST['hora_fin']) && is_array($_POST['hora_fin']) ? $_POST['hora_fin'] : [];
$requerimientos = isset($_POST['requerimientos']) && is_array($_POST['requerimientos']) ? $_POST['requerimientos'] : [];

$lic_cuat = isset($_POST['lic_cuat']) && is_array($_POST['lic_cuat']) ? $_POST['lic_cuat'] : [];

/* DATOS GENERALES */
$cantidad_alumnos_general = (int)($_POST['cantidad_alumnos_general'] ?? 0);
$descripcion_general = trim($_POST['descripcion_actividad_general'] ?? '');
$costo_general = (isset($_POST['costo_alumno_general']) && $_POST['costo_alumno_general'] !== '')
    ? (float)$_POST['costo_alumno_general']
    : 0.00;
$promocion_general = trim($_POST['promocion_redes_general'] ?? 'no');
$obs_general = trim($_POST['observaciones_general'] ?? '');
$material_general = trim($_POST['material_requerido_general'] ?? '');

/* GUARDAR POST ORIGINAL PARA REPINTAR FORMULARIO SI HAY ERROR */
$formData = $_POST;

/* VALIDACIONES BÁSICAS */
if (
    $folio_coordinador === '' ||
    $tipo_actividad === '' ||
    $nombre_actividad === '' ||
    $objetivo === '' ||
    $asignatura === '' ||
    $docente_responsable === '' ||
    $coordinador_responsable === ''
) {
    volverConError("Debes capturar todos los datos generales del formato.", $formData);
}

if (!is_array($espacios) || count($espacios) === 0) {
    volverConError("Debes agregar al menos un espacio.", $formData);
}

if ($cantidad_alumnos_general <= 0) {
    volverConError("La cantidad total de alumnos debe ser mayor a 0.", $formData);
}

if (
    count($espacios) !== count($fechas) ||
    count($espacios) !== count($hora_inicio) ||
    count($espacios) !== count($hora_fin) ||
    count($espacios) !== count($requerimientos)
) {
    volverConError("Los datos de espacios, horarios o requerimientos están incompletos.", $formData);
}

/* LICENCIATURAS PERMITIDAS */
$sqlLicPermitidas = "SELECT licenciatura
                     FROM coordinador_licenciaturas
                     WHERE coordinador_id = ?";
$stmtLicPermitidas = $conexion->prepare($sqlLicPermitidas);

if (!$stmtLicPermitidas) {
    volverConError("No se pudieron validar las licenciaturas permitidas.", $formData);
}

$stmtLicPermitidas->bind_param("i", $coordinador_id);
$stmtLicPermitidas->execute();
$resLicPermitidas = $stmtLicPermitidas->get_result();

$licPermitidas = [];
while ($filaLic = $resLicPermitidas->fetch_assoc()) {
    $licPermitidas[] = $filaLic['licenciatura'];
}

if (count($licPermitidas) === 0) {
    volverConError("Tu usuario no tiene licenciaturas permitidas asignadas.", $formData);
}

$fechaMinima = date('Y-m-d', strtotime('+10 days'));

/* PREPARAR CONSULTAS */
$sqlConflicto = "SELECT rd.id, e.nombre AS espacio
                 FROM reserva_detalles rd
                 INNER JOIN espacios e ON rd.espacio_id = e.id
                 INNER JOIN reservas r ON rd.reserva_id = r.id
                 WHERE rd.espacio_id = ?
                   AND rd.fecha = ?
                   AND r.estado IN ('pendiente', 'autorizada')
                   AND NOT (? >= rd.hora_fin OR ? <= rd.hora_inicio)
                 LIMIT 1";

$stmtConflicto = $conexion->prepare($sqlConflicto);

if (!$stmtConflicto) {
    volverConError("No se pudo validar conflictos de horario.", $formData);
}

$sqlEspacio = "SELECT nombre, minimo_alumnos, maximo_alumnos
               FROM espacios
               WHERE id = ?
               LIMIT 1";

$stmtEspacio = $conexion->prepare($sqlEspacio);

if (!$stmtEspacio) {
    volverConError("No se pudo validar la información de los espacios.", $formData);
}

/* VALIDAR ESPACIOS */
for ($i = 0; $i < count($espacios); $i++) {
    $espacio_id = (int)($espacios[$i] ?? 0);
    $fecha = trim($fechas[$i] ?? '');
    $inicio = trim($hora_inicio[$i] ?? '');
    $fin = trim($hora_fin[$i] ?? '');
    $cant = $cantidad_alumnos_general;

    if ($espacio_id <= 0 || $fecha === '' || $inicio === '' || $fin === '') {
        volverConError("Todos los espacios deben tener espacio, fecha, hora de inicio y hora fin.", $formData);
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        volverConError("Hay una fecha inválida en uno de los espacios.", $formData);
    }

    if ($fecha < $fechaMinima) {
        volverConError("Las reservas solo pueden hacerse a partir de 10 días después de la fecha actual.", $formData);
    }

    if (!preg_match('/^\d{2}:\d{2}$/', $inicio) || !preg_match('/^\d{2}:\d{2}$/', $fin)) {
        volverConError("Hay un formato de hora inválido en uno de los espacios.", $formData);
    }

    if (strtotime($inicio) >= strtotime($fin)) {
        volverConError("La hora de inicio debe ser menor que la hora de fin en todos los espacios.", $formData);
    }

    $stmtEspacio->bind_param("i", $espacio_id);
    $stmtEspacio->execute();
    $datosEspacio = $stmtEspacio->get_result()->fetch_assoc();

    if (!$datosEspacio) {
        volverConError("Uno de los espacios seleccionados no existe.", $formData);
    }

    $nombreEspacio = $datosEspacio['nombre'] ?? 'Espacio';
    $minEspacio = isset($datosEspacio['minimo_alumnos']) ? (int)$datosEspacio['minimo_alumnos'] : 0;
    $maxEspacio = isset($datosEspacio['maximo_alumnos']) ? (int)$datosEspacio['maximo_alumnos'] : 0;

    if ($minEspacio > 0 && $cant < $minEspacio) {
        volverConError("La cantidad de alumnos para el espacio '{$nombreEspacio}' no puede ser menor a {$minEspacio}.", $formData);
    }

    if ($maxEspacio > 0 && $cant > $maxEspacio) {
        volverConError("La cantidad de alumnos para el espacio '{$nombreEspacio}' no puede ser mayor a {$maxEspacio}.", $formData);
    }

    $stmtConflicto->bind_param("isss", $espacio_id, $fecha, $inicio, $fin);
    $stmtConflicto->execute();
    $conflicto = $stmtConflicto->get_result();

    if ($conflicto && $conflicto->num_rows > 0) {
        $dato = $conflicto->fetch_assoc();
        volverConError(
            "Conflicto detectado: el espacio '{$dato['espacio']}' ya está reservado o pendiente en la fecha {$fecha} dentro de ese horario.",
            $formData
        );
    }
}

/* VALIDAR LICENCIATURAS GENERALES */
if (!is_array($lic_cuat) || count($lic_cuat) === 0) {
    volverConError("Debes agregar al menos una licenciatura con su cuatrimestre.", $formData);
}

foreach ($lic_cuat as $par) {
    $lic = trim($par['licenciatura'] ?? '');
    $cuat = trim($par['cuatrimestre'] ?? '');

    if ($lic === '' || $cuat === '') {
        volverConError("Cada pareja de licenciatura y cuatrimestre debe estar completa.", $formData);
    }

    if (!in_array($lic, $licPermitidas, true)) {
        volverConError("La licenciatura '{$lic}' no está permitida para tu usuario.", $formData);
    }
}

/* PREPARAR LICENCIATURAS/CUATRIMESTRES */
$licenciaturasTexto = [];
$cuatrimestresTexto = [];

foreach ($lic_cuat as $par) {
    $lic = trim($par['licenciatura'] ?? '');
    $cuat = trim($par['cuatrimestre'] ?? '');

    if ($lic !== '') {
        $licenciaturasTexto[] = $lic;
    }

    if ($cuat !== '') {
        $cuatrimestresTexto[] = $cuat;
    }
}

$licenciaturas_val = implode(", ", $licenciaturasTexto);
$cuatrimestres_val = implode(", ", $cuatrimestresTexto);

$conexion->begin_transaction();

try {
    /* GUARDAR CABECERA */
    $sqlReserva = "INSERT INTO reservas
    (
        coordinador_id,
        tipo_actividad,
        nombre_actividad,
        folio_coordinador,
        fecha_entrega_formato,
        objetivo,
        asignatura,
        docente_responsable,
        coordinador_responsable
    )
    VALUES (?,?,?,?,?,?,?,?,?)";

    $stmtReserva = $conexion->prepare($sqlReserva);

    if (!$stmtReserva) {
        throw new Exception("No se pudo preparar la reserva.");
    }

    $stmtReserva->bind_param(
        "issssssss",
        $coordinador_id,
        $tipo_actividad,
        $nombre_actividad,
        $folio_coordinador,
        $fecha_entrega_formato,
        $objetivo,
        $asignatura,
        $docente_responsable,
        $coordinador_responsable
    );

    if (!$stmtReserva->execute()) {
        throw new Exception("No se pudo guardar la cabecera de la reserva.");
    }

    $reserva_id = (int)$stmtReserva->insert_id;

    /* GUARDAR DETALLES */
    $sqlDetalle = "INSERT INTO reserva_detalles
    (
        reserva_id,
        espacio_id,
        fecha,
        hora_inicio,
        hora_fin,
        licenciaturas,
        cuatrimestres,
        cantidad_alumnos,
        descripcion_actividad,
        requerimientos,
        costo_alumno,
        promocion_redes,
        observaciones,
        material_requerido
    )
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmtDetalle = $conexion->prepare($sqlDetalle);

    if (!$stmtDetalle) {
        throw new Exception("No se pudo preparar el guardado de detalles.");
    }

    for ($i = 0; $i < count($espacios); $i++) {
        $espacio_id = (int)$espacios[$i];
        $fecha = trim($fechas[$i]);
        $inicio = trim($hora_inicio[$i]);
        $fin = trim($hora_fin[$i]);
        $cant = $cantidad_alumnos_general;

        $descripcion_val = $descripcion_general;
        $requerimientos_val = trim($requerimientos[$i] ?? '');
        $costo_val = $costo_general;
        $promocion_val = $promocion_general;
        $obs_val = $obs_general;
        $material_val = $material_general;

        $stmtDetalle->bind_param(
            "iisssssissdsss",
            $reserva_id,
            $espacio_id,
            $fecha,
            $inicio,
            $fin,
            $licenciaturas_val,
            $cuatrimestres_val,
            $cant,
            $descripcion_val,
            $requerimientos_val,
            $costo_val,
            $promocion_val,
            $obs_val,
            $material_val
        );

        if (!$stmtDetalle->execute()) {
            throw new Exception("No se pudo guardar uno de los espacios de la reserva.");
        }
    }

    $conexion->commit();

    unset($_SESSION['form_reserva']);
    $_SESSION['ok_reserva'] = "La reserva se guardó correctamente.";
    header("Location: mis_reservas.php");
    exit();

} catch (Exception $e) {
    $conexion->rollback();
    volverConError($e->getMessage(), $formData);
}
?>
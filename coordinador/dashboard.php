<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'coordinador') {
    header("Location: ../auth/login.php");
    exit();
}

$coordinador_id = (int)$_SESSION['usuario_id'];

/* anuncios activos del directivo */
$sqlAnuncios = "SELECT a.*, u.nombre, u.apellido_paterno
                FROM anuncios a
                INNER JOIN usuarios u ON a.directivo_id = u.id
                WHERE a.estatus = 'activo'
                ORDER BY a.fecha_publicacion DESC
                LIMIT 5";
$anuncios = $conexion->query($sqlAnuncios);

/* reservas del coordinador para pintar calendario */
$sqlReservas = "SELECT rd.fecha, rd.hora_inicio, rd.hora_fin, e.nombre AS espacio, r.estado
                FROM reserva_detalles rd
                INNER JOIN reservas r ON rd.reserva_id = r.id
                INNER JOIN espacios e ON rd.espacio_id = e.id
                WHERE r.coordinador_id = ?
                ORDER BY rd.fecha ASC, rd.hora_inicio ASC";

$stmt = $conexion->prepare($sqlReservas);
if (!$stmt) {
    exit("Error al cargar las reservas del calendario.");
}
$stmt->bind_param("i", $coordinador_id);
$stmt->execute();
$res = $stmt->get_result();

$reservasPorFecha = [];
while ($fila = $res->fetch_assoc()) {
    $fecha = $fila['fecha'];
    if (!isset($reservasPorFecha[$fecha])) {
        $reservasPorFecha[$fecha] = [];
    }
    $reservasPorFecha[$fecha][] = $fila;
}

$titulo_pagina = 'Sistema de Reservas';
$menu_activo = 'inicio';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel Coordinador</title>
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
    font-size:22px;
}

.anuncio{
    border-left:6px solid #ef1c1c;
    background:#fafafa;
    border-radius:10px;
    padding:14px;
    margin-bottom:14px;
}

.anuncio h4{
    margin:0 0 8px 0;
    color:#111827;
}

.anuncio p{
    margin:6px 0;
    line-height:1.5;
}

.anuncio-meta{
    color:#6b7280;
    font-size:13px;
}

.calendar-controls{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:16px;
    flex-wrap:wrap;
    gap:10px;
}

.calendar-controls button{
    background:#1d4ed8;
    color:#fff;
    border:none;
    border-radius:8px;
    padding:10px 14px;
    cursor:pointer;
    font-weight:bold;
}

.calendar-controls button:hover{
    background:#1e40af;
}

.calendar-title{
    font-size:22px;
    color:#111827;
    font-weight:bold;
}

.calendar-grid{
    display:grid;
    grid-template-columns:repeat(7, 1fr);
    gap:10px;
}

.day-name{
    text-align:center;
    font-weight:bold;
    color:#374151;
    padding:10px 0;
}

.day{
    min-height:110px;
    background:#f9fafb;
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:10px;
    position:relative;
    cursor:pointer;
    transition:.2s;
}

.day:hover{
    background:#eef2ff;
    border-color:#c7d2fe;
}

.day.empty{
    background:#f3f4f6;
    cursor:default;
}

.day-number{
    font-weight:bold;
    color:#111827;
    margin-bottom:8px;
}

.day.has-event{
    border:2px solid #ef1c1c;
    background:#fff5f5;
}

.day.today{
    box-shadow:inset 0 0 0 2px #2563eb;
}

.badge-events{
    display:inline-block;
    background:#ef1c1c;
    color:#fff;
    border-radius:999px;
    padding:3px 8px;
    font-size:12px;
    font-weight:bold;
}

.legend{
    display:flex;
    gap:18px;
    flex-wrap:wrap;
    margin:16px 0 0 0;
}

.legend-item{
    display:flex;
    align-items:center;
    gap:8px;
    color:#374151;
    font-size:14px;
}

.legend-color{
    width:18px;
    height:18px;
    border-radius:6px;
}

.details-card{
    background:#f9fafb;
    border:1px solid #e5e7eb;
    border-radius:14px;
    padding:18px;
    margin-top:20px;
}

.details-card h4{
    color:#1d4ed8;
    margin-bottom:14px;
}

.reserva-item{
    border-left:6px solid #ef1c1c;
    background:#fff;
    border-radius:10px;
    padding:14px;
    margin-bottom:12px;
    box-shadow:0 2px 6px rgba(0,0,0,.04);
}

.estado{
    display:inline-block;
    padding:5px 10px;
    border-radius:20px;
    font-size:13px;
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

.estado.denegada{
    background:#fee2e2;
    color:#991b1b;
}

.estado.correccion{
    background:#dbeafe;
    color:#1d4ed8;
}

.vacio{
    color:#6b7280;
}

@media (max-width: 1000px){
    .calendar-grid{
        grid-template-columns:repeat(2, 1fr);
    }

    .day-name{
        display:none;
    }

    .main{
        padding:20px 14px;
    }
}
</style>
</head>
<body>

<?php include("../includes/menu_coordinador.php"); ?>

<main class="main">
    <section class="card">
        <h3>Anuncios del directivo</h3>

        <?php if ($anuncios && $anuncios->num_rows > 0) { ?>
            <?php while($a = $anuncios->fetch_assoc()) { ?>
                <div class="anuncio">
                    <h4><?php echo htmlspecialchars($a['titulo']); ?></h4>
                    <p><?php echo nl2br(htmlspecialchars($a['contenido'])); ?></p>
                    <div class="anuncio-meta">
                        Publicado por <?php echo htmlspecialchars($a['nombre'] . ' ' . $a['apellido_paterno']); ?>
                        | <?php echo htmlspecialchars($a['fecha_publicacion']); ?>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <p class="vacio">No hay anuncios publicados.</p>
        <?php } ?>
    </section>

    <section class="card">
        <h3>Calendario personal</h3>

        <div class="calendar-controls">
            <button type="button" id="prevMonth">◀ Mes anterior</button>
            <div class="calendar-title" id="calendarTitle"></div>
            <button type="button" id="nextMonth">Mes siguiente ▶</button>
        </div>

        <div class="calendar-grid" id="dayNames">
            <div class="day-name">Lunes</div>
            <div class="day-name">Martes</div>
            <div class="day-name">Miércoles</div>
            <div class="day-name">Jueves</div>
            <div class="day-name">Viernes</div>
            <div class="day-name">Sábado</div>
            <div class="day-name">Domingo</div>
        </div>

        <div class="calendar-grid" id="calendarGrid"></div>

        <div class="legend">
            <div class="legend-item">
                <span class="legend-color" style="background:#fff5f5;border:2px solid #ef1c1c;"></span>
                Día con reservas
            </div>
            <div class="legend-item">
                <span class="legend-color" style="background:#ffffff;box-shadow:inset 0 0 0 2px #2563eb;"></span>
                Día actual
            </div>
        </div>

        <div class="details-card">
            <h4 id="detailsTitle">Selecciona un día</h4>
            <div id="detailsContent" class="vacio">
                Da clic en un día del calendario para ver espacios reservados y horarios.
            </div>
        </div>
    </section>
</main>

<?php include("../includes/footer_coordinador.php"); ?>

<script>
const reservasPorFecha = <?php echo json_encode($reservasPorFecha, JSON_UNESCAPED_UNICODE); ?>;

const monthNames = [
    "Enero","Febrero","Marzo","Abril","Mayo","Junio",
    "Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"
];

let currentDate = new Date();
let currentMonth = currentDate.getMonth();
let currentYear = currentDate.getFullYear();

const calendarTitle = document.getElementById("calendarTitle");
const calendarGrid = document.getElementById("calendarGrid");
const detailsTitle = document.getElementById("detailsTitle");
const detailsContent = document.getElementById("detailsContent");

document.getElementById("prevMonth").addEventListener("click", () => {
    currentMonth--;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    renderCalendar(currentMonth, currentYear);
});

document.getElementById("nextMonth").addEventListener("click", () => {
    currentMonth++;
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    renderCalendar(currentMonth, currentYear);
});

function pad(n) {
    return n < 10 ? "0" + n : n;
}

function formatDateKey(year, month, day) {
    return year + "-" + pad(month + 1) + "-" + pad(day);
}

function renderCalendar(month, year) {
    calendarGrid.innerHTML = "";
    calendarTitle.textContent = monthNames[month] + " " + year;

    const firstDay = new Date(year, month, 1);
    let startDay = firstDay.getDay();
    startDay = startDay === 0 ? 6 : startDay - 1;

    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = new Date();

    for (let i = 0; i < startDay; i++) {
        const empty = document.createElement("div");
        empty.className = "day empty";
        calendarGrid.appendChild(empty);
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const dateKey = formatDateKey(year, month, day);
        const cell = document.createElement("div");
        cell.className = "day";

        const isToday =
            day === today.getDate() &&
            month === today.getMonth() &&
            year === today.getFullYear();

        if (isToday) {
            cell.classList.add("today");
        }

        if (reservasPorFecha[dateKey]) {
            cell.classList.add("has-event");
        }

        let html = `<div class="day-number">${day}</div>`;

        if (reservasPorFecha[dateKey]) {
            html += `<div class="badge-events">${reservasPorFecha[dateKey].length} reserva(s)</div>`;
        }

        cell.innerHTML = html;

        cell.addEventListener("click", () => {
            showDayDetails(dateKey);
        });

        calendarGrid.appendChild(cell);
    }
}

function showDayDetails(dateKey) {
    detailsTitle.textContent = "Reservas del día " + dateKey;

    if (!reservasPorFecha[dateKey] || reservasPorFecha[dateKey].length === 0) {
        detailsContent.innerHTML = `<p class="vacio">No hay reservas para este día.</p>`;
        return;
    }

    let html = "";
    reservasPorFecha[dateKey].forEach(item => {
        html += `
            <div class="reserva-item">
                <p><strong>Espacio:</strong> ${escapeHtml(item.espacio)}</p>
                <p><strong>Horario:</strong> ${formatearHora(item.hora_inicio)} - ${formatearHora(item.hora_fin)}</p>
                <p><strong>Estado:</strong> <span class="estado ${escapeHtml(item.estado)}">${capitalize(item.estado)}</span></p>
            </div>
        `;
    });

    detailsContent.innerHTML = html;
}

function formatearHora(hora) {
    if (!hora) return "";

    const partes = hora.split(":");
    let h = parseInt(partes[0], 10);
    const m = partes[1];
    const sufijo = h >= 12 ? "p. m." : "a. m.";

    h = h % 12;
    if (h === 0) h = 12;

    const horaTexto = String(h).padStart(2, "0");
    return `${horaTexto}:${m} ${sufijo}`;
}

function capitalize(text) {
    if (!text) return "";
    return text.charAt(0).toUpperCase() + text.slice(1);
}

function escapeHtml(text) {
    if (text === null || text === undefined) return "";
    return String(text)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

renderCalendar(currentMonth, currentYear);
</script>

</body>
</html>
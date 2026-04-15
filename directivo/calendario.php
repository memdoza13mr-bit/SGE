<?php
session_start();
include("../config/conexion.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'directivo') {
    header("Location: ../auth/login.php");
    exit();
}

/* traer todos los espacios disponibles o en mantenimiento */
$sqlEspacios = "SELECT id, nombre, ubicacion, capacidad, estatus
                FROM espacios
                WHERE estatus IN ('disponible', 'mantenimiento')
                ORDER BY nombre ASC";
$resEspacios = $conexion->query($sqlEspacios);

$espacios = [];
while ($esp = $resEspacios->fetch_assoc()) {
    $espacios[] = $esp;
}

/* traer todas las reservas activas para pintar calendario y detalle */
$sqlReservas = "SELECT
                    rd.espacio_id,
                    rd.fecha,
                    rd.hora_inicio,
                    rd.hora_fin,
                    rd.estado_detalle,
                    r.id AS reserva_id,
                    r.estado AS estado_reserva,
                    u.nombre,
                    u.apellido_paterno,
                    e.nombre AS espacio_nombre
                FROM reserva_detalles rd
                INNER JOIN reservas r ON rd.reserva_id = r.id
                INNER JOIN usuarios u ON r.coordinador_id = u.id
                INNER JOIN espacios e ON rd.espacio_id = e.id
                WHERE r.estado IN ('pendiente', 'autorizada')
                ORDER BY rd.fecha ASC, e.nombre ASC, rd.hora_inicio ASC";

$resReservas = $conexion->query($sqlReservas);

$reservasPorFecha = [];
while ($fila = $resReservas->fetch_assoc()) {
    $fecha = $fila['fecha'];
    if (!isset($reservasPorFecha[$fecha])) {
        $reservasPorFecha[$fecha] = [];
    }
    $reservasPorFecha[$fecha][] = $fila;
}

$titulo_pagina = 'Panel Directivo';
$menu_activo = 'calendario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Calendario del Directivo</title>
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
    font-size:30px;
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

.day.selected{
    box-shadow:inset 0 0 0 3px #16a34a;
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

.espacio-card{
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:18px;
    margin-bottom:18px;
    background:#fafafa;
}

.espacio-header h4{
    margin:0 0 8px 0;
    color:#111827;
    font-size:22px;
}

.espacio-meta{
    color:#6b7280;
    font-size:14px;
    margin-bottom:10px;
}

.reserva-item{
    border-left:6px solid #ef1c1c;
    background:#fff;
    border-radius:10px;
    padding:14px;
    margin-top:12px;
    box-shadow:0 2px 6px rgba(0,0,0,.04);
}

.libre-item{
    border-left:6px solid #16a34a;
    background:#f0fdf4;
    color:#166534;
    border-radius:10px;
    padding:14px;
    margin-top:12px;
    font-weight:bold;
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

.acciones{
    margin-top:10px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

/* modal */
.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.45);
    z-index:1100;
    justify-content:center;
    align-items:center;
    padding:20px;
}

.modal.show{
    display:flex;
}

.modal-content{
    width:100%;
    max-width:950px;
    max-height:88vh;
    overflow:auto;
    background:#fff;
    border-radius:18px;
    padding:24px;
    box-shadow:0 20px 40px rgba(0,0,0,.2);
    position:relative;
}

.modal-close{
    position:absolute;
    top:12px;
    right:14px;
    border:none;
    background:#ef4444;
    color:#fff;
    border-radius:8px;
    padding:8px 12px;
    font-weight:bold;
    cursor:pointer;
}

.modal-title{
    color:#1d4ed8;
    font-size:28px;
    margin-bottom:18px;
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

<?php include("../includes/menu_directivo.php"); ?>

<main class="main">
    <section class="card">
        <h3>Calendario de reservas</h3>

        <div class="calendar-controls">
            <button type="button" id="prevMonth">◀ Mes anterior</button>
            <div class="calendar-title" id="calendarTitle"></div>
            <button type="button" id="nextMonth">Mes siguiente ▶</button>
        </div>

        <div class="calendar-grid">
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
            <div class="legend-item">
                <span class="legend-color" style="background:#ffffff;box-shadow:inset 0 0 0 3px #16a34a;"></span>
                Día seleccionado
            </div>
        </div>
    </section>
</main>

<div class="modal" id="calendarModal">
    <div class="modal-content">
        <button class="modal-close" id="cerrarModal">Cerrar</button>
        <h3 class="modal-title" id="detailsTitle">Disponibilidad del día</h3>
        <div id="detailsContent" class="vacio"></div>
    </div>
</div>

<?php include("../includes/footer_directivo.php"); ?>

<script>
const reservasPorFecha = <?php echo json_encode($reservasPorFecha, JSON_UNESCAPED_UNICODE); ?>;
const espacios = <?php echo json_encode($espacios, JSON_UNESCAPED_UNICODE); ?>;

const monthNames = [
    "Enero","Febrero","Marzo","Abril","Mayo","Junio",
    "Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"
];

let currentDate = new Date();
let currentMonth = currentDate.getMonth();
let currentYear = currentDate.getFullYear();
let selectedDateKey = null;

const calendarTitle = document.getElementById("calendarTitle");
const calendarGrid = document.getElementById("calendarGrid");
const detailsTitle = document.getElementById("detailsTitle");
const detailsContent = document.getElementById("detailsContent");
const calendarModal = document.getElementById("calendarModal");
const cerrarModal = document.getElementById("cerrarModal");

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

cerrarModal.addEventListener("click", () => {
    calendarModal.classList.remove("show");
});

calendarModal.addEventListener("click", (e) => {
    if (e.target === calendarModal) {
        calendarModal.classList.remove("show");
    }
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

        if (selectedDateKey === dateKey) {
            cell.classList.add("selected");
        }

        let html = `<div class="day-number">${day}</div>`;

        if (reservasPorFecha[dateKey]) {
            html += `<div class="badge-events">${reservasPorFecha[dateKey].length} reserva(s)</div>`;
        }

        cell.innerHTML = html;

        cell.addEventListener("click", () => {
            selectedDateKey = dateKey;
            renderCalendar(currentMonth, currentYear);
            showDayAvailability(dateKey);
            calendarModal.classList.add("show");
        });

        calendarGrid.appendChild(cell);
    }
}

function showDayAvailability(dateKey) {
    detailsTitle.textContent = "Disponibilidad del día " + dateKey;

    const reservasDia = reservasPorFecha[dateKey] || [];

    if (reservasDia.length === 0) {
        detailsContent.innerHTML = `<p class="vacio">No hay reservas para este día.</p>`;
        return;
    }

    let html = "";

    const espaciosConReserva = espacios.filter(espacio =>
        reservasDia.some(r => parseInt(r.espacio_id) === parseInt(espacio.id))
    );

    espaciosConReserva.forEach(espacio => {
        const reservasEspacio = reservasDia
            .filter(r => parseInt(r.espacio_id) === parseInt(espacio.id))
            .sort((a, b) => a.hora_inicio.localeCompare(b.hora_inicio));

        html += `
            <div class="espacio-card">
                <div class="espacio-header">
                    <h4>${escapeHtml(espacio.nombre)}</h4>
                    <div class="espacio-meta">
                        Ubicación: ${escapeHtml(espacio.ubicacion || 'No especificada')}
                        |
                        Capacidad: ${escapeHtml(String(espacio.capacidad || 'No especificada'))}
                        |
                        Estado: ${escapeHtml(espacio.estatus)}
                    </div>
                </div>
        `;

        const huecos = calcularHuecos(reservasEspacio);

        reservasEspacio.forEach(reserva => {
            html += `
                <div class="reserva-item">
                    <p><strong>Ocupado:</strong> ${formatearHora(reserva.hora_inicio)} - ${formatearHora(reserva.hora_fin)}</p>
                    <p><strong>Coordinador:</strong> ${escapeHtml(reserva.nombre)} ${escapeHtml(reserva.apellido_paterno)}</p>
                    <p><strong>Estado:</strong>
                        <span class="estado ${escapeHtml(reserva.estado_reserva)}">
                            ${capitalize(reserva.estado_reserva)}
                        </span>
                    </p>
                    <div class="acciones">
                        <a class="btn" href="../imprimir_reserva.php?id=${escapeHtml(reserva.reserva_id)}">Ver</a>
                    </div>
                </div>
            `;
        });

        if (huecos.length > 0) {
            huecos.forEach(h => {
                html += `
                    <div class="libre-item">
                        Libre: ${formatearHora(h[0])} - ${formatearHora(h[1])}
                    </div>
                `;
            });
        }

        html += `</div>`;
    });

    detailsContent.innerHTML = html;
}

function calcularHuecos(reservasEspacio) {
    const inicioDia = "07:00:00";
    const finDia = "22:00:00";
    const huecos = [];

    if (reservasEspacio.length === 0) {
        huecos.push([inicioDia, finDia]);
        return huecos;
    }

    let cursor = inicioDia;

    reservasEspacio.forEach(r => {
        if (cursor < r.hora_inicio) {
            huecos.push([cursor, r.hora_inicio]);
        }
        if (cursor < r.hora_fin) {
            cursor = r.hora_fin;
        }
    });

    if (cursor < finDia) {
        huecos.push([cursor, finDia]);
    }

    return huecos;
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
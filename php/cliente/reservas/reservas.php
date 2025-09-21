<?php
include './../includes/header.php';
include './../../config.php';

$canchaSeleccionada = $_GET['cancha'] ?? null;

if (!$canchaSeleccionada) {
    header("Location: reservas_cancha.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM canchas WHERE cancha_id = ?");
$stmt->bind_param("i", $canchaSeleccionada);
$stmt->execute();
$cancha = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cancha) {
    die("Cancha no encontrada.");
}

$fechaHoy = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Reserva - <?= htmlspecialchars($cancha['nombre']) ?></title>

</head>
<body>
<div class="page-wrap">
    <div class="flow-header">
        <h1>Reserva - <?= htmlspecialchars($cancha['nombre']) ?></h1>

        <div class="steps-row">
            <div class="step active"><span class="circle">1</span><span class="label">Horario</span></div>
            <div class="step"><span class="circle">2</span><span class="label">Abono</span></div>
            <div class="step"><span class="circle">3</span><span class="label">Confirmación</span></div>
        </div>
    </div>

    <div class="reservation-container">
        <!-- COLUMNA IZQUIERDA: HORARIOS -->
        <div class="time-column">
            <div class="time-title">Turnos disponibles</div>
            <div class="time-list" id="horarios-lista">
                <!-- JS llenará dinámicamente los horarios -->
            </div>
        </div>

        <!-- COLUMNA DERECHA: CALENDARIO -->
        <div class="calendar">
            <div class="cal-card">
                <form method="POST" action="reservas_pago.php" onsubmit="return validarReserva()">
                    <input type="hidden" name="cancha_id" value="<?= $canchaSeleccionada ?>">
                    <input type="hidden" name="fecha" id="fecha">
                    <input type="hidden" name="hora_inicio" id="horaSelected">

                    <div class="calendar-header">
                        <button type="button" onclick="prevMonth()">◀</button>
                        <h3 id="calTitle"></h3>
                        <button type="button" onclick="nextMonth()">▶</button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Lun</th>
                                <th>Mar</th>
                                <th>Mié</th>
                                <th>Jue</th>
                                <th>Vie</th>
                                <th>Sáb</th>
                                <th>Dom</th>
                            </tr>
                        </thead>
                        <tbody id="calBody"></tbody>
                    </table>

                    <div class="calendar-footer">
                        <button type="submit" class="btn-next">Continuar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let selectedTime = null;
let currentDate = new Date();
let canchaId = <?= intval($canchaSeleccionada) ?>;

function seleccionarHora(el, hora) {
    // quitar selección anterior
    document.querySelectorAll(".time-slot.selected").forEach(item => item.classList.remove("selected"));
    el.classList.add("selected");
    document.getElementById("horaSelected").value = hora;
    selectedTime = hora;
}

function validarReserva() {
    if (!selectedTime) {
        alert("Por favor selecciona un horario antes de continuar.");
        return false;
    }
    return true;
}

function generarCalendario() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const calBody = document.getElementById("calBody");
    document.getElementById("calTitle").innerText =
        currentDate.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });

    calBody.innerHTML = "";
    let row = document.createElement("tr");

    // Días vacíos al inicio (lunes=0)
    const startOffset = (firstDay.getDay() + 6) % 7;
    for (let i = 0; i < startOffset; i++) {
        row.appendChild(document.createElement("td"));
    }

    for (let d = 1; d <= lastDay.getDate(); d++) {
        const date = new Date(year, month, d);
        const td = document.createElement("td");
        td.textContent = d;
        td.classList.add("calendar-day");

        // dataset con fecha ISO
        const yyyy = date.getFullYear();
        const mm = String(date.getMonth() + 1).padStart(2, '0');
        const dd = String(date.getDate()).padStart(2, '0');
        const fechaISO = `${yyyy}-${mm}-${dd}`;
        td.dataset.date = fechaISO;

        td.addEventListener('click', () => seleccionarFecha(date, td));
        row.appendChild(td);

        if (date.getDay() === 0) { // domingo
            calBody.appendChild(row);
            row = document.createElement("tr");
        }
    }
    calBody.appendChild(row);
}

function seleccionarFecha(date, tdElement) {
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    const fecha = `${yyyy}-${mm}-${dd}`;

    document.getElementById('fecha').value = fecha;

    document.querySelectorAll(".calendar-day").forEach(el => el.classList.remove("selected"));
    if (tdElement) tdElement.classList.add("selected");

    // limpiar selección previa
    selectedTime = null;
    document.getElementById("horaSelected").value = '';

    const url = `./get_horarios.php?cancha_id=${encodeURIComponent(canchaId)}&fecha=${encodeURIComponent(fecha)}`;
    fetch(url)
        .then(res => {
            if (!res.ok) throw new Error('Error en la respuesta del servidor: ' + res.status);
            const ct = res.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                return res.text().then(t => { throw new Error('Respuesta no JSON: ' + t); });
            }
            return res.json();
        })
        .then(data => {
            console.log("horarios recibidos:", data);
            renderHorarios(data);
        })
        .catch(err => {
            console.error("Error cargando horarios:", err);
            document.getElementById("horarios-lista").innerHTML = '<div style="color:#ffdddd">No se pudieron cargar los horarios.</div>';
        });
}

function renderHorarios(horarios) {
    const lista = document.getElementById("horarios-lista");
    lista.innerHTML = "";

    const horaInicioDia = 8;   // 08:00
    const horaFinDia = 22;     // hasta 21:30 incluido si intervalo=30
    const intervalo = 30;      // minutos

    function parseToMin(hora) {
        if (typeof hora === 'number') return hora;
        const parts = String(hora).split(':').map(Number);
        const hh = parts[0] || 0;
        const mm = parts[1] || 0;
        return hh * 60 + mm;
    }

    // Normalize data: crear array de objetos {inicio_min, fin_min, inicio, fin}
    const reservas = (horarios || []).map(r => {
        const inicio_min = ('inicio_min' in r) ? Number(r.inicio_min) : parseToMin(r.inicio || r.hora_inicio || '');
        const fin_min    = ('fin_min' in r) ? Number(r.fin_min) : parseToMin(r.fin || r.hora_fin || '');
        return {
            inicio_min,
            fin_min,
            inicio: r.inicio || r.hora_inicio || '',
            fin: r.fin || r.hora_fin || ''
        };
    });

    for (let h = horaInicioDia; h < horaFinDia; h++) {
        for (let m = 0; m < 60; m += intervalo) {
            const hh = String(h).padStart(2, '0');
            const mmStr = String(m).padStart(2, '0');
            const horaStr = `${hh}:${mmStr}`; // sin segundos, p. ej. "10:30"
            const slotStart = h * 60 + m;
            const slotEnd = slotStart + intervalo;

            // Ocupado si hay overlap entre [slotStart, slotEnd) y la reserva [inicio, fin)
            const ocupado = reservas.some(resv => {
                const inicio = Number(resv.inicio_min);
                const fin = Number(resv.fin_min);
                if (isNaN(inicio) || isNaN(fin)) return false;
                return (slotStart < fin) && (slotEnd > inicio);
            });

            const div = document.createElement("div");
            div.className = "time-slot";
            if (ocupado) div.classList.add("ocupado");
            div.textContent = horaStr;

            if (!ocupado) {
                div.addEventListener('click', () => seleccionarHora(div, horaStr + ":00"));
            } else {
                const ocupante = reservas.find(resv => (slotStart < resv.fin_min) && (slotEnd > resv.inicio_min));
                if (ocupante) {
                    div.title = `Ocupado ${ocupante.inicio} - ${ocupante.fin}`;
                }
            }

            lista.appendChild(div);
        }
    }
}

function prevMonth() {
    currentDate.setMonth(currentDate.getMonth() - 1);
    generarCalendario();
}

function nextMonth() {
    currentDate.setMonth(currentDate.getMonth() + 1);
    generarCalendario();
}

window.onload = () => {
    generarCalendario();

    // Seleccionar automáticamente hoy si pertenece al mes visible
    const hoy = new Date();
    if (hoy.getMonth() === currentDate.getMonth() && hoy.getFullYear() === currentDate.getFullYear()) {
        const yyyy = hoy.getFullYear();
        const mm = String(hoy.getMonth() + 1).padStart(2, '0');
        const dd = String(hoy.getDate()).padStart(2, '0');
        const fechaISO = `${yyyy}-${mm}-${dd}`;
        const tdHoy = document.querySelector(`.calendar-day[data-date="${fechaISO}"]`);
        if (tdHoy) {
            seleccionarFecha(hoy, tdHoy);
            return;
        }
    }

    const anyDay = document.querySelector(".calendar-day");
    if (anyDay) {
        const parts = anyDay.dataset.date.split('-');
        const dateObj = new Date(parts[0], Number(parts[1]) - 1, parts[2]);
        seleccionarFecha(dateObj, anyDay);
    }
};
</script>

<?php include './../includes/footer.php'; ?>
</body>
</html>

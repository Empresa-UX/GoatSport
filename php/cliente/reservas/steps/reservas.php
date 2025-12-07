<?php
/* =========================================================================
 * FILE: php/cliente/reservas/steps/reservas.php
 * ========================================================================= */
include './../../includes/header.php';
include './../../../config.php';

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
if (!$cancha) { die("Cancha no encontrada."); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Reserva - <?= htmlspecialchars($cancha['nombre']) ?></title>
  <style>
    :root{
      --c-disp-bg:#e8fbf3; --c-disp-bd:#cfeee2; --c-disp-tx:#0a5b4a;
      --c-resv-bg:#ffe5e7; --c-resv-bd:#f7c5ca; --c-resv-tx:#8b1d1d;
      --c-evnt-bg:#fff1df; --c-evnt-bd:#f5d7ae; --c-evnt-tx:#744015;
      --c-past-bg:#eef2f5; --c-past-bd:#d8e0e4; --c-past-tx:#6b7c85;
      --btn-grad1:#07566b; --btn-grad2:#054a56;
    }
    .reservation-container{ display:grid; grid-template-columns:minmax(220px,280px) 1fr; gap:22px; align-items:start; }
    @media (max-width:1000px){ .reservation-container{ grid-template-columns:1fr; } }

    .time-column{ display:grid; grid-template-rows:auto 1fr; min-height:0; }
    .time-title{ font-weight:800; font-size:16px; color:#eaffff; margin:0 0 8px; }
    .time-list{ min-height:0; max-height:560px; overflow:auto; padding-right:6px; }

    .calendar{ width:100%; }
    .cal-card{ position:relative; height:680px; display:grid; grid-template-rows:auto 1fr auto; border-radius:16px; overflow:hidden; box-shadow:0 14px 34px rgba(0,0,0,.23); background:#fff; }
    .calendar-header{ padding:12px 14px; background:linear-gradient(180deg,#0a6c7e,#054a56); border-bottom:1px solid rgba(255,255,255,.2); display:flex; align-items:center; justify-content:center; }
    #calTitle{ margin:0; color:#fff; font-weight:900; text-transform:capitalize; font-size:18px; letter-spacing:.2px; }

    .cal-arrow{ position:absolute; top:50%; transform:translateY(-50%); width:40px; height:40px; border:none; cursor:pointer; border-radius:12px; background:#fff; color:#054a56; font-weight:900; box-shadow:0 8px 18px rgba(0,0,0,.18); display:grid; place-items:center; }
    .cal-prev{ left:8px; } .cal-next{ right:8px; } .cal-arrow:hover{ filter:brightness(1.05); }

    table{ width:100%; border-collapse:separate; border-spacing:0 8px; }
    thead th{ font-size:12px; text-transform:uppercase; letter-spacing:.3px; color:#7aa1a5; padding-top:10px; text-align:center; }
    tbody td{
      text-align:center; padding:10px 0; cursor:pointer; border-radius:10px; border:1px solid transparent;
      /* importante: no fijamos negrita acá; lo maneja .calendar-day */
      color:#054a56; transition:background .06s, transform .06s;
    }

    /* HOVER BASE LIMITADO */
    tbody td:hover{ background:#eef8f8; }

    /* ===== Estado base y seleccionado del calendario ===== */
    .calendar-day{ font-weight:400; } /* base normal */
    .calendar-day.disabled{ opacity:.45; cursor:not-allowed; }

    /* Hover SOLO si no está seleccionado */
    .calendar-day:not(.selected):hover { background:#eef8f8; }

    .calendar-day.selected,
    .calendar-day.selected:hover{
      font-weight:900;                 /* negrita sólo seleccionado */
      background:#e9fbf9;
      border-color:#cfe9e7;
      box-shadow:inset 0 0 0 2px #a4dcd4;
      color:#054a56;
    }

    .calendar-footer{
      display:flex; align-items:center; justify-content:space-between; gap:12px;
      padding:12px; border-top:1px solid #eef3f3; background:#fff; flex-wrap:wrap;
    }
    .actions{ display:flex; gap:10px; }
    .btn{ appearance:none; border:none; border-radius:12px; font-weight:600; padding:10px 16px; cursor:pointer; }
    .btn-next{ color:#fff; background:linear-gradient(180deg,var(--btn-grad1),var(--btn-grad2)); box-shadow:0 10px 22px rgba(0,0,0,.18); }
    .btn-next:disabled{ opacity:.6; box-shadow:none; cursor:not-allowed; }
    .btn-secondary{ color:#0a5666; background:linear-gradient(180deg,#eaf5f6,#d8ecef); border:1px solid #cfe3e6; }
    .btn-secondary:hover{ filter:brightness(0.98); }

    /* ===== Chips (filtros) con estados visibles ===== */
    .filters{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .chip-toggle{
      position:relative; display:inline-flex; align-items:center; gap:8px;
      padding:8px 12px; border-radius:999px; font-size:12px; font-weight:800;
      cursor:pointer; user-select:none; border:1px solid #e4eef0; background:#f7fbfc; color:#0b5a66;
      transition:background .18s, border-color .18s, color .18s, box-shadow .2s, transform .08s, opacity .18s;
      outline:0;
    }
    .chip-toggle:focus-visible{ box-shadow:0 0 0 3px rgba(5,74,86,.25); }
    .chip-toggle .dot{ width:6px; height:10px; border-radius:999px; transition:transform .18s; }
    .chip-toggle[data-on="false"]{ opacity:.55; }
    .chip-toggle[data-on="true"]{ transform:translateY(-1px); box-shadow:0 8px 18px rgba(0,0,0,.08); }
    .chip-toggle[data-on="true"] .dot{ transform:scale(1.15); }

    /* Tema por tipo (ACTIVO) */
    .chip--disp[data-on="true"] { background:var(--c-disp-bg); border-color:var(--c-disp-bd); color:var(--c-disp-tx); }
    .chip--resv[data-on="true"] { background:var(--c-resv-bg); border-color:var(--c-resv-bd); color:var(--c-resv-tx); }
    .chip--evnt[data-on="true"] { background:var(--c-evnt-bg); border-color:var(--c-evnt-bd); color:var(--c-evnt-tx); }
    .chip--past[data-on="true"] { background:var(--c-past-bg); border-color:var(--c-past-bd); color:var(--c-past-tx); }

    /* Dot por tipo */
    .chip--disp .dot{ background:var(--c-disp-tx); border:1px solid var(--c-disp-tx); }
    .chip--resv .dot{ background:var(--c-resv-tx); border:1px solid var(--c-resv-tx); }
    .chip--evnt .dot{ background:var(--c-evnt-tx); border:1px solid var(--c-evnt-tx); }
    .chip--past .dot{ background:var(--c-past-tx); border:1px solid var(--c-past-tx); }

    /* ===== Slots ===== */
    .time-slot{
      user-select:none; cursor:pointer; border:1px solid transparent; border-radius:10px;
      font-weight:800; font-size:14px; text-align:center;
      padding:10px 12px; margin-bottom:8px; white-space:nowrap;
      transition:transform .08s, box-shadow .12s, background .12s, border-color .12s, color .12s;
    }
    .time-slot:hover{ transform:translateY(-1px); }
    .time-slot.selected{ box-shadow:0 10px 20px rgba(0,0,0,.22); border-color:#fff; }

    .time-slot.slot--disponible{ background:var(--c-disp-bg); color:var(--c-disp-tx); border-color:var(--c-disp-bd); }
    .time-slot.slot--reservado{  background:var(--c-resv-bg); color:var(--c-resv-tx); border-color:var(--c-resv-bd); cursor:not-allowed; }
    .time-slot.slot--evento{     background:var(--c-evnt-bg); color:var(--c-evnt-tx); border-color:var(--c-evnt-bd); cursor:not-allowed; }
    .time-slot.slot--pasado{     background:var(--c-past-bg); color:var(--c-past-tx); border-color:var(--c-past-bd); cursor:not-allowed; }
    .time-slot.selected.slot--disponible{ background:#fff; color:#054a56; }

    /* Filtro duro contra cualquier CSS externo */
    .is-hidden{ display:none !important; }
  </style>
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
      <div class="time-column">
        <h3 class="time-title">Turnos disponibles</h3>
        <div class="time-list" id="horarios-lista"></div>
      </div>

      <div class="calendar">
        <div class="cal-card">
          <button type="button" class="cal-arrow cal-prev" onclick="prevMonth()" aria-label="Mes anterior">◀</button>
          <button type="button" class="cal-arrow cal-next" onclick="nextMonth()" aria-label="Mes siguiente">▶</button>

          <form method="POST" action="reservas_pago.php" onsubmit="return validarReserva()">
            <input type="hidden" name="cancha_id" value="<?= (int)$canchaSeleccionada ?>">
            <input type="hidden" name="fecha" id="fecha">
            <input type="hidden" name="hora_inicio" id="horaSelected">

            <div class="calendar-header"><h3 id="calTitle"></h3></div>

            <table>
              <thead><tr><th>Lun</th><th>Mar</th><th>Mié</th><th>Jue</th><th>Vie</th><th>Sáb</th><th>Dom</th></tr></thead>
              <tbody id="calBody"></tbody>
            </table>

            <div class="calendar-footer">
              <!-- FILTROS -->
              <div class="filters" id="filters">
                <label class="chip-toggle chip--disp" role="button" tabindex="0" aria-pressed="true" data-filter="disponible" data-on="true">
                  <input type="checkbox" checked hidden><span class="dot"></span> Disponibles
                </label>
                <label class="chip-toggle chip--resv" role="button" tabindex="0" aria-pressed="true" data-filter="reservado" data-on="true">
                  <input type="checkbox" checked hidden><span class="dot"></span> Reservados
                </label>
                <label class="chip-toggle chip--evnt" role="button" tabindex="0" aria-pressed="true" data-filter="evento" data-on="true">
                  <input type="checkbox" checked hidden><span class="dot"></span> Eventos
                </label>
                <label class="chip-toggle chip--past" role="button" tabindex="0" aria-pressed="true" data-filter="pasado" data-on="true">
                  <input type="checkbox" checked hidden><span class="dot"></span> Pasados de hoy
                </label>
              </div>

              <div class="actions">
                <button type="button" class="btn btn-secondary" onclick="handleBack()">Volver</button>
                <button type="submit" class="btn btn-next" id="btnSubmit" disabled>Continuar</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

<script>
  let selectedTime = null;
  let currentDate = new Date();
  const canchaId = <?= intval($canchaSeleccionada) ?>;

  const filtersOn = new Set(['disponible','reservado','evento','pasado']);

  function handleBack(){
    if (document.referrer) history.back(); else location.href='reservas_cancha.php';
  }

  function seleccionarHora(el, hora) {
    document.querySelectorAll(".time-slot.selected").forEach(i => i.classList.remove("selected"));
    el.classList.add("selected");
    document.getElementById("horaSelected").value = hora;
    selectedTime = hora;
    document.getElementById('btnSubmit').disabled = !(selectedTime && document.getElementById('fecha').value);
  }
  function validarReserva() {
    if (!selectedTime) { alert("Por favor selecciona un horario antes de continuar."); return false; }
    return true;
  }

  function generarCalendario() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay  = new Date(year, month + 1, 0);
    const calBody  = document.getElementById("calBody");
    document.getElementById("calTitle").innerText =
      currentDate.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });

    calBody.innerHTML = "";
    let row = document.createElement("tr");

    const startOffset = (firstDay.getDay() + 6) % 7; // lunes=0
    for (let i = 0; i < startOffset; i++) row.appendChild(document.createElement("td"));

    const hoy = new Date(); hoy.setHours(0,0,0,0);

    for (let d = 1; d <= lastDay.getDate(); d++) {
      const date = new Date(year, month, d); date.setHours(0,0,0,0);
      const td = document.createElement("td");
      td.textContent = d;
      td.classList.add("calendar-day");

      const yyyy = date.getFullYear();
      const mm = String(date.getMonth() + 1).padStart(2, '0');
      const dd = String(date.getDate()).padStart(2, '0');
      const fechaISO = `${yyyy}-${mm}-${dd}`;
      td.dataset.date = fechaISO;

      if (date < hoy) td.classList.add('disabled');

      td.addEventListener('click', () => {
        if (td.classList.contains('disabled')) return;
        seleccionarFecha(date, td);
      });
      row.appendChild(td);

      if (date.getDay() === 0) { calBody.appendChild(row); row = document.createElement("tr"); }
    }
    calBody.appendChild(row);
  }

  function prevMonth(){ currentDate.setMonth(currentDate.getMonth()-1); generarCalendario(); }
  function nextMonth(){ currentDate.setMonth(currentDate.getMonth()+1); generarCalendario(); }

  function seleccionarFecha(date, tdElement) {
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    const fecha = `${yyyy}-${mm}-${dd}`;

    document.getElementById('fecha').value = fecha;
    document.getElementById('btnSubmit').disabled = !(selectedTime && fecha);

    document.querySelectorAll(".calendar-day").forEach(el => el.classList.remove("selected"));
    if (tdElement) tdElement.classList.add("selected");

    selectedTime = null;
    document.getElementById("horaSelected").value = '';

    const url = `../logica/horarios.php?cancha_id=${encodeURIComponent(canchaId)}&fecha=${encodeURIComponent(fecha)}`;
    const lista = document.getElementById("horarios-lista");
    lista.innerHTML = '<div style="padding:10px; color:#6b7; font-weight:800">Cargando…</div>';

    fetch(url, {cache:'no-store'})
      .then(res => res.text().then(t => ({ ok: res.ok, body: t, ct: res.headers.get('content-type')||'' })))
      .then(({ok, body, ct}) => {
        if (!ok) throw new Error('HTTP '+body);
        if (!ct.includes('application/json')) throw new Error('No JSON: '+body);
        return JSON.parse(body);
      })
      .then(data => renderHorarios(data))
      .catch(err => {
        console.error("Error cargando horarios:", err);
        lista.innerHTML = '<div style="color:#ffdddd">No se pudieron cargar los horarios.</div>';
      });
  }

  function setupFilters(){
    const cont = document.getElementById('filters');

    const toggleChip = (chip)=>{
      const key = chip.dataset.filter;
      const on = !(chip.dataset.on === 'true');
      chip.dataset.on = on ? 'true' : 'false';
      chip.setAttribute('aria-pressed', on ? 'true' : 'false');

      const input = chip.querySelector('input');
      if (input) input.checked = on;

      if (on) filtersOn.add(key); else filtersOn.delete(key);
      applyFilters();
    };

    cont.addEventListener('click', (e)=>{
      const chip = e.target.closest('.chip-toggle');
      if (!chip) return;
      e.preventDefault();
      toggleChip(chip);
    });
    cont.addEventListener('keydown', (e)=>{
      const chip = e.target.closest('.chip-toggle');
      if (!chip) return;
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggleChip(chip);
      }
    });
  }

  function applyFilters(){
    const list = document.getElementById('horarios-lista');
    list.querySelectorAll('.time-slot').forEach(el=>{
      const reason = el.getAttribute('data-reason'); // disponible|reservado|evento|pasado
      if (!reason) return;
      if (filtersOn.has(reason)) el.classList.remove('is-hidden');
      else el.classList.add('is-hidden');
    });
  }

  const SLOT_STYLES = {
    disponible: { bg: 'var(--c-disp-bg)', bd:'var(--c-disp-bd)', tx:'var(--c-disp-tx)' },
    reservado:  { bg: 'var(--c-resv-bg)', bd:'var(--c-resv-bd)', tx:'var(--c-resv-tx)' },
    evento:     { bg: 'var(--c-evnt-bg)', bd:'var(--c-evnt-bd)', tx:'var(--c-evnt-tx)' },
    pasado:     { bg: 'var(--c-past-bg)', bd:'var(--c-past-bd)', tx:'var(--c-past-tx)' },
  };

  function renderHorarios(payload) {
    const lista = document.getElementById("horarios-lista");
    lista.innerHTML = "";

    let apertura = '08:00:00';
    let cierre   = '22:00:00';
    const slotMin  = 30;
    let reservas = [];
    let bloques  = [];
    let dayBlocked = false;

    if (payload && typeof payload === 'object') {
      apertura   = payload.apertura || apertura;
      cierre     = payload.cierre   || cierre;
      reservas   = Array.isArray(payload.reservas) ? payload.reservas : [];
      bloques    = Array.isArray(payload.bloques)  ? payload.bloques  : [];
      dayBlocked = !!payload.day_blocked;
    } else if (Array.isArray(payload)) {
      reservas = payload;
    }

    if (dayBlocked) {
      const info = document.createElement('div');
      info.className = 'time-slot slot--evento';
      info.dataset.reason = 'evento';
      info.style.cssText = `
        background:${SLOT_STYLES.evento.bg};
        border-color:${SLOT_STYLES.evento.bd};
        color:${SLOT_STYLES.evento.tx};
        text-decoration:none;
      `;
      info.textContent = 'Día bloqueado por evento';
      lista.appendChild(info);
      applyFilters();
      return;
    }

    function toMin(hhmmss){ const [H='0',M='0'] = String(hhmmss||'').split(':'); return (+H)*60 + (+M); }

    const startMin = toMin(apertura);
    const endMin   = toMin(cierre);

    const ocupReservas = (reservas||[]).map(r => ({
      inicio_min: ('inicio_min' in r)?+r.inicio_min:toMin(r.inicio||r.hora_inicio),
      fin_min:    ('fin_min'    in r)?+r.fin_min   :toMin(r.fin||r.hora_fin)
    }));
    const ocupEventos  = (bloques||[]).map(b => ({
      inicio_min: ('inicio_min' in b)?+b.inicio_min:toMin(b.inicio),
      fin_min:    ('fin_min'    in b)?+b.fin_min   :toMin(b.fin)
    }));

    const selISO = document.getElementById('fecha').value;
    const now = new Date();
    const isToday = (() => {
      if (!selISO) return false;
      const [y,m,d] = selISO.split('-').map(Number);
      return now.getFullYear()===y && (now.getMonth()+1)===m && now.getDate()===d;
    })();
    const nowMin = now.getHours()*60 + now.getMinutes();

    let libres = 0;
    for (let s = startMin; s + slotMin <= endMin; s += slotMin) {
      const e = s + slotMin;
      const overlapReserva = ocupReservas.some(o => (s < o.fin_min) && (e > o.inicio_min));
      const overlapEvento  = ocupEventos.some(o => (s < o.fin_min) && (e > o.inicio_min));
      const isPastToday    = isToday && (s <= nowMin);

      let reason = 'disponible';
      if (overlapReserva) reason = 'reservado';
      else if (overlapEvento) reason = 'evento';
      else if (isPastToday) reason = 'pasado';

      const hh = String(Math.floor(s/60)).padStart(2,'0');
      const mm = String(s%60).padStart(2,'0');
      const horaStr = `${hh}:${mm}`;

      const div = document.createElement("div");
      div.className = `time-slot slot--${reason}`;
      div.dataset.reason = reason;
      div.textContent = horaStr;

      const st = SLOT_STYLES[reason];
      if (st) {
        div.style.background = st.bg;
        div.style.borderColor = st.bd;
        div.style.color = st.tx;
      }

      if (reason === 'reservado') { div.title = 'No disponible: ya está reservado'; div.setAttribute('aria-disabled','true'); }
      else if (reason === 'evento') { div.title = 'No disponible: evento especial'; div.setAttribute('aria-disabled','true'); }
      else if (reason === 'pasado') { div.title = 'No disponible: horario ya pasó'; div.setAttribute('aria-disabled','true'); }
      else { div.title = 'Disponible'; }

      if (reason === 'disponible') {
        libres++;
        div.addEventListener('click', () => seleccionarHora(div, horaStr + ":00"));
      }

      lista.appendChild(div);
    }

    if (!libres) {
      const msg = document.createElement('div');
      msg.className = 'time-slot slot--pasado';
      msg.dataset.reason = 'pasado';
      msg.style.cssText = `
        background:${SLOT_STYLES.pasado.bg}; border-color:${SLOT_STYLES.pasado.bd}; color:${SLOT_STYLES.pasado.tx};
        text-decoration:none;
      `;
      msg.textContent = 'Sin turnos disponibles para este día';
      lista.appendChild(msg);
    }

    applyFilters();
  }

  window.onload = () => {
    setupFilters();
    generarCalendario();

    const hoy = new Date();
    if (hoy.getMonth() === currentDate.getMonth() && hoy.getFullYear() === currentDate.getFullYear()) {
      const yyyy = hoy.getFullYear(), mm = String(hoy.getMonth()+1).padStart(2,'0'), dd = String(hoy.getDate()).padStart(2,'0');
      const fechaISO = `${yyyy}-${mm}-${dd}`;
      const tdHoy = document.querySelector(`.calendar-day[data-date="${fechaISO}"]`);
      if (tdHoy) { seleccionarFecha(hoy, tdHoy); return; }
    }
    const anyDay = document.querySelector(".calendar-day:not(.disabled)");
    if (anyDay) {
      const parts = anyDay.dataset.date.split('-');
      const dateObj = new Date(parts[0], Number(parts[1]) - 1, parts[2]);
      seleccionarFecha(dateObj, anyDay);
    }
  };
</script>

<?php include './../../includes/footer.php'; ?>
</body>
</html>

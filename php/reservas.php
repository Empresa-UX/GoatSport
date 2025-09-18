<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Reservas | Padel Alquiler</title>
    <style>
        :root{
            --teal-700: #054a56;
            --teal-600: #07566b;
            --teal-500: #1bab9d;
            --white: #ffffff;
            --card-bg: rgba(255,255,255,0.08);
        }

        *{ box-sizing: border-box; font-family: 'Arial', sans-serif; margin:0; padding:0; }

        body{
            min-height:100vh;
            background: linear-gradient(to bottom, var(--teal-700), var(--teal-500));
            color: var(--white);
            display:flex; flex-direction:column;
        }

        header{
            display:flex; justify-content:space-between; align-items:center;
            padding:18px 40px; background: rgba(0,0,0,0.12);
        }
        header img{ width:120px; }
        nav a{ margin-left:30px; text-decoration:none; color:#f0f0f0; font-size:15px; }
        nav a:hover{ color:#d9faff; }
        nav a.active{ font-weight:700; }

        /* PAGE LAYOUT */
        main{ flex:1; padding:40px 60px; display:flex; justify-content:center; }
        .page-wrap{ width:100%; max-width:1150px; }

        /* FLOW HEADER: título a la izquierda + steps */
        .flow-header{
            display:flex; align-items:flex-start; gap:30px; margin-bottom:28px;
        }

        .flow-header h1{
            font-size:36px; font-weight:700; color:#fff; margin-right:12px;
            /* colocarlo a la izquierda (ya está) */
        }

        .steps-row{ display:flex; gap:22px; align-items:center; margin-top:6px; }

        .step{
            display:flex; align-items:center; gap:10px; color: rgba(255,255,255,0.9);
            font-size:15px; opacity:0.85;
        }

        .step .circle{
            width:34px; height:34px; border-radius:50%;
            display:inline-flex; align-items:center; justify-content:center;
            font-weight:700;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        /* activo: fondo blanco con número oscuro */
        .step.active .circle{
            background: var(--white);
            color: var(--teal-700);
        }

        /* no activo: círculo semi-transparente */
        .step:not(.active) .circle{
            background: rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.9);
            border: 1px solid rgba(255,255,255,0.08);
        }

        .step .label{ color: rgba(255,255,255,0.95); font-weight:600; font-size:14px; opacity:0.95; }

        /* RESERVATION CONTAINER */
        .reservation-container{
            display:flex; gap:30px;
            background: var(--card-bg);
            border-radius:14px;
            padding:26px;
            box-shadow: 0 12px 35px rgba(0,0,0,0.25);
            align-items:flex-start;
        }

        /* LEFT: columna de horarios (scrollable) */
        .time-column{
            width:260px; min-width:220px;
            display:flex; flex-direction:column; gap:12px;
        }

        .time-title{
            font-size:25px; color: rgba(255,255,255,0.95); margin-bottom:6px; font-weight:600;text-align: center;
        }

        .time-list{
            background: rgba(255,255,255,0.06);
            border-radius:10px;
            padding:12px;
            height:440px; /* fija para scroll */
            overflow-y:auto;
            display:flex; flex-direction:column; gap:10px;
            scrollbar-width: thin;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.02);
        }

        /* custom scrollbar WebKit */
        .time-list::-webkit-scrollbar{ width:10px; }
        .time-list::-webkit-scrollbar-track{ background: transparent; }
        .time-list::-webkit-scrollbar-thumb{ background: rgba(255,255,255,0.08); border-radius:8px; }

        .time-slot{
            background: rgba(255,255,255,0.12);
            padding:12px 16px;
            border-radius:8px;
            text-align:center;
            cursor:pointer;
            transition: all 0.18s ease;
            color: #fff;
            font-weight:600;
        }

        .time-slot:hover{ transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,0.2); }

        .time-slot.selected{
            background: var(--white);
            color: var(--teal-700);
            font-weight:800;
            box-shadow: 0 8px 22px rgba(0,0,0,0.25);
        }

        /* RIGHT: calendar (blanco) */
        .calendar{
            flex:1;
            background: #fff;
            color: #043b3d;
            border-radius:10px;
            min-height:470px;
            display:flex; flex-direction:column;
            box-shadow: 0 10px 30px rgba(0,0,0,0.18);
        }

        .calendar .cal-card{ background:#f7f9f9; border-radius:8px; padding:14px; flex:1; display:flex; flex-direction:column; }

        .calendar table{ width:100%; border-collapse:collapse; color:#043b3d; }
        .calendar thead th{
            text-align:center; padding:10px 6px; font-weight:800; color:#043b3d; font-size:13px;
        }
        .calendar tbody td{ text-align:center; padding:10px 6px; font-size:13px; color:#043b3d; vertical-align:top; min-height:48px; }

        .calendar tbody td .time-cell{ display:block; margin:6px 0; color:#043b3d; font-weight:600; }

        .calendar-footer{ display:flex; justify-content:flex-end; margin-top:14px; }

        .btn-next{
            padding:10px 20px; border-radius:8px;
            background: linear-gradient(180deg,var(--teal-600),var(--teal-700));
            color:#fff; border:none; cursor:pointer; font-weight:700; font-size:15px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.2);
        }
        .btn-next:hover{ filter:brightness(1.05); transform:translateY(-1px); }

        /* Responsive */
        @media (max-width:900px){
            .reservation-container{ flex-direction:column; }
            .time-column{ width:100%; }
            .time-list{ height:280px; }
            .calendar{ min-height:260px; }
            .flow-header{ flex-direction:column; align-items:flex-start; gap:10px; }
        }

    </style>
</head>
<body>
<header>
    <img src="../img/logo_padel.png" alt="Logo Padel">
    <nav>
        <a href="home_cliente.php">Inicio</a>
        <a href="reservas.php" class="active">Mis Reservas</a>
        <a href="promociones.php">Promociones</a>
        <a href="ranking.php">Ranking</a>
        <a href="logout.php">Cerrar sesión</a>
    </nav>
</header>

<main>
    <div class="page-wrap">
        <!-- TITULO A LA IZQUIERDA y STEPS -->
        <div class="flow-header">
            <h1>Flujo de reserva</h1>

            <div class="steps-row" aria-hidden="false">
                <div class="step active" aria-current="step">
                    <span class="circle">1</span>
                    <span class="label">Selección de turno</span>
                </div>
                <div class="step">
                    <span class="circle">2</span>
                    <span class="label">Pago</span>
                </div>
                <div class="step">
                    <span class="circle">3</span>
                    <span class="label">Confirmación</span>
                </div>
            </div>
        </div>

        <!-- CONTENEDOR PRINCIPAL -->
        <div class="reservation-container" role="region" aria-label="Selector de turno">
            <!-- COLUMNA IZQUIERDA: HORARIOS (scroll) -->
            <div class="time-column">
                <div class="time-title">Elija un turno</div>
                <div class="time-list" id="timeList" tabindex="0" aria-label="Lista de horarios">
                    <!-- Lista de horarios (puedes generar dinámicamente en PHP si quieres) -->
                    <div class="time-slot" onclick="seleccionarHora(this,'08:00')">08:00</div>
                    <div class="time-slot" onclick="seleccionarHora(this,'08:30')">08:30</div>
                    <div class="time-slot" onclick="seleccionarHora(this,'09:00')">09:00</div>
                    <div class="time-slot" onclick="seleccionarHora(this,'09:30')">09:30</div>
                    <div class="time-slot" onclick="seleccionarHora(this,'10:00')">10:00</div>
                    <div class="time-slot" onclick="seleccionarHora(this,'10:30')">10:30</div>
                    <div class="time-slot" onclick="seleccionarHora(this,'11:00')">11:00</div>
                    <div class="time-slot" onclick="seleccionarHora(this,'11:30')">11:30</div>
                    <div class="time-slot" onclick="seleccionarHora(this,'12:00')">12:00</div>
                    <div class="time-slot" onclick="seleccionarHora(this,'12:30')">12:30</div>
                    <div class="time-slot" onclick="seleccionarHora(this,'13:00')">13:00</div>
                    <div class="time-slot" onclick="seleccionarHora(this,'13:30')">13:30</div>
                    <div class="time-slot" onclick="seleccionarHora(this,'14:00')">14:00</div>
                    <div class="time-slot" onclick="seleccionarHora(this,'14:30')">14:30</div>
                    <div class="time-slot" onclick="seleccionarHora(this,'15:00')">15:00</div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: CALENDARIO BLANCO -->
            <div class="calendar" role="region" aria-label="Calendario de disponibilidad">
                <div class="cal-card">
                    <table aria-hidden="false">
                        <thead>
                            <tr>
                                <th>MAR</th>
                                <th>MIÉ</th>
                                <th>JUE</th>
                                <th>VIE</th>
                                <th>SAB</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <span class="time-cell">08:00</span>
                                    <span class="time-cell">09:00</span>
                                </td>
                                <td>
                                    <span class="time-cell">10:00</span>
                                </td>
                                <td>
                                    <!-- vacío -->
                                </td>
                                <td>
                                    <span class="time-cell">09:00</span>
                                </td>
                                <td>
                                    <span class="time-cell">10:30</span>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <span class="time-cell">11:00</span>
                                </td>
                                <td>
                                    <span class="time-cell">12:00</span>
                                </td>
                                <td>
                                    <span class="time-cell">13:00</span>
                                </td>
                                <td>
                                    <span class="time-cell">14:00</span>
                                </td>
                                <td>
                                    <span class="time-cell">15:00</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="calendar-footer">
                        <button class="btn-next" id="btnNext" onclick="goNext()">Siguiente</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<footer style="text-align:center; padding:14px; background: rgba(0,0,0,0.12); font-size:13px;">
    <p>Padel Alquiler © <?= date("Y"); ?> - Todos los derechos reservados</p>
</footer>

<script>
    let selectedTime = null;
    function seleccionarHora(el, time){
        // quitar seleccionado previo
        document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
        // marcar actual
        el.classList.add('selected');
        selectedTime = time;

        // opcional: si el time-list está scrolleado, acercar el elemento visible (mejora UX)
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function goNext(){
        if(!selectedTime){
            alert('Por favor seleccioná primero un turno antes de continuar.');
            return;
        }
        // redirigir a la pantalla de pago pasando la hora seleccionada (puedes cambiar a POST si prefieres)
        const url = 'reservas_pago.php?hora=' + encodeURIComponent(selectedTime);
        window.location.href = url;
    }

    // mejora: permitir seleccionar con ENTER cuando se enfoque un elemento
    document.getElementById('timeList').addEventListener('keydown', function(e){
        const focused = document.activeElement;
        if((e.key === 'Enter' || e.key === ' ') && focused && focused.classList.contains('time-slot')){
            focused.click();
            e.preventDefault();
        }
    });

    // hacer cada .time-slot focusable para accesibilidad
    document.querySelectorAll('.time-slot').forEach(el => {
        el.setAttribute('tabindex', '0');
    });
</script>
</body>
</html>

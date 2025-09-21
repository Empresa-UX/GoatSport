<?php
include './../../config.php';
include './../includes/header.php';

$reserva_sess = $_SESSION['reserva'] ?? [];
$canchaId = $reserva_sess['cancha_id'] ?? $_POST['cancha_id'] ?? $_GET['cancha'] ?? null;
$fecha    = $reserva_sess['fecha'] ?? $_POST['fecha'] ?? null;
$horaRaw  = $reserva_sess['hora_inicio'] ?? $_POST['hora_inicio'] ?? null;

if (!$canchaId || !$fecha || !$horaRaw) {
    echo "<div class='page-wrap'><p>Error: faltan datos de la reserva (cancha, fecha u hora).</p>";
    echo "<p>GET: ".htmlspecialchars(json_encode($_GET))."</p>";
    echo "<p>POST: ".htmlspecialchars(json_encode($_POST))."</p>";
    echo "<p>SESSION: ".htmlspecialchars(json_encode($_SESSION['reserva'] ?? []))."</p></div>";
    include './../includes/footer.php';
    exit();
}

$canchaNombre = "Cancha #{$canchaId}";
$canchaPrecio = null;
if ($conn) {
    $stmt = $conn->prepare("SELECT nombre, precio FROM canchas WHERE cancha_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $canchaId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $canchaNombre = $row['nombre'];
            $canchaPrecio = $row['precio'];
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancha_id'], $_POST['fecha'], $_POST['hora_inicio'])) {
    $_SESSION['reserva'] = [
        'cancha_id'   => $_POST['cancha_id'],
        'fecha'       => $_POST['fecha'],
        'hora_inicio' => $_POST['hora_inicio'],
        // opcional: hora_fin por defecto 90 min después
        'hora_fin'    => date('H:i:s', strtotime($_POST['hora_inicio'] . ' +90 minutes'))
    ];
}

?>

<div class="page-wrap">
    <div class="flow-header">
        <h1>Flujo de Reserva</h1>
        <div class="steps-row">
            <div class="step"><span class="circle">1</span><span class="label">Selección del horario</span></div>
            <div class="step active"><span class="circle">2</span><span class="label">Abono</span></div>
            <div class="step"><span class="circle">3</span><span class="label">Confirmación</span></div>
        </div>
    </div>

    <div class="payment-container">
        <div class="payment-title" style="margin-top:6px;">Seleccione su método de pago</div>

        <form id="paymentForm" method="post" action="reservas_confirmacion.php" novalidate>
            <input type="hidden" name="metodo" id="metodoInput" value="">

            <div class="payment-options" role="list">
                <div class="payment-card" data-metodo="tarjeta" role="listitem" tabindex="0" aria-pressed="false">
                    <img src="./../../../img/tarjeta_credito_debido.png" alt="Tarjeta">
                    <span>Tarjeta de crédito / débito</span>
                </div>
                <div class="payment-card" data-metodo="mercadopago" role="listitem" tabindex="0" aria-pressed="false">
                    <img src="./../../../img/mercado_pago.png" alt="MercadoPago">
                    <span>Mercado Pago</span>
                </div>
                <div class="payment-card" data-metodo="efectivo" role="listitem" tabindex="0" aria-pressed="false">
                    <img src="./../../../img/pagar_presencial.png" alt="Efectivo">
                    <span>Pagar en el club</span>
                </div>
            </div>

            <div class="payment-footer" style="margin-top:18px;">
                <button type="button" class="btn-next" id="continueBtn">Continuar</button>
            </div>
        </form>
    </div>
</div>

<?php include './../includes/footer.php'; ?>

<script>
(function () {
    const cards = Array.from(document.querySelectorAll('.payment-card'));
    const metodoInput = document.getElementById('metodoInput');
    const continueBtn = document.getElementById('continueBtn');

    function clearSelection() {
        cards.forEach(c => {
            c.classList.remove('selected');
            c.setAttribute('aria-pressed', 'false');
        });
    }

    function selectCard(card) {
        clearSelection();
        card.classList.add('selected');
        card.setAttribute('aria-pressed', 'true');
        metodoInput.value = card.dataset.metodo || '';
    }

    cards.forEach(card => {
        card.addEventListener('click', () => selectCard(card));
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                selectCard(card);
            }
        });
    });

    continueBtn.addEventListener('click', () => {
        if (!metodoInput.value) {
            alert('Por favor seleccione un método de pago antes de continuar.');
            return;
        }
        document.getElementById('paymentForm').submit();
    });
})();
</script>

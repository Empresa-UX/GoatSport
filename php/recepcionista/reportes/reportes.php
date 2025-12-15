<?php
/* =====================================================================
 * file: php/recepcionista/reportes/reportes.php
 * ===================================================================== */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$recep_id     = (int)($_SESSION['usuario_id'] ?? 0);
$proveedor_id = (int)($_SESSION['proveedor_id'] ?? 0);
if (!$recep_id || !$proveedor_id) {
    header('Location: ../login.php');
    exit;
}

/* canchas del proveedor para el select (opcional) */
$canchas = [];
$st = $conn->prepare("SELECT cancha_id,nombre FROM canchas WHERE proveedor_id=? AND activa=1 ORDER BY nombre");
$st->bind_param("i", $proveedor_id);
$st->execute();
$rs = $st->get_result();
while ($r = $rs->fetch_assoc()) $canchas[] = $r;
$st->close();

function h($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>
<main>
    <?php if (isset($_GET['ok'])): ?>
        <script>
            alert('<?= addslashes($_GET["ok"]) ?>');
            if (history.replaceState) {
                const u = new URL(location.href);
                u.search = '';
                history.replaceState(null, '', u.toString());
            }
        </script>
    <?php elseif (isset($_GET['err'])): ?>
        <script>
            alert('<?= addslashes($_GET["err"]) ?>');
            if (history.replaceState) {
                const u = new URL(location.href);
                u.search = '';
                history.replaceState(null, '', u.toString());
            }
        </script>
    <?php endif; ?>

    <style>
        :root {
            --text: #043b3d;
            --muted: #6b7a80;
            --border: #d6dadd;
            --primary: #1bab9d;
            --ring: rgba(27, 171, 157, .12);
        }

        .form-card {
            max-width: 500px;
            margin: 24px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 28px rgba(0, 0, 0, .10);
            padding: 22px;
            border: 1px solid #eef2f3
        }

        .form-title {
            font-size: 18px;
            font-weight: 750;
            color: var(--text);
            text-align: center;
            margin: 6px 0 14px
        }

        .f {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin: 10px 0
        }

        .f label {
            font-weight: 700;
            color: #3a4a50;
            font-size: 14px
        }

        .inpt,
        .sel,
        .txt {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #fff;
            outline: none;
            transition: border-color .2s, box-shadow .2s
        }

        .txt {
            min-height: 110px;
            resize: none
        }

        /* no permitir redimensionar */
        .inpt:focus,
        .sel:focus,
        .txt:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--ring)
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px
        }

        @media (max-width:560px) {
            .row {
                grid-template-columns: 1fr
            }
        }

        .form-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 12px;
            width: 100%;            
        }

        .btn {
            background: #1bab9d;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            cursor: pointer;
            font-weight: 800;
            text-decoration: none;
            text-align: center;
            width: 50%;
            font-size: 16px;
        }

        .btn:hover {
            background: #159788
        }

        .btn-outline {
            background: #fff;
            color: #043b3d;
            border: 1px solid #cbd5e1
        }

        .btn-outline:hover {
            background: #f7f7f7
        }

        .help {
            font-size: 12px;
            color: #6b7280
        }

        .invalid {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, .15) !important
        }

        h2 {
            text-align: center;
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
    </style>

    <div class="form-card" role="region" aria-labelledby="title">
        <h2>Registrar reporte</h2>
        <?php
        $__canchas = $canchas; // pasar canchas al form incluido
        include __DIR__ . '/reportesForm.php';
        ?>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
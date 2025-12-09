<?php
/* =====================================================================
 * file: php/recepcionista/clientes/clientes.php
 * ===================================================================== */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<main>
  <div class="form-container">
    <h2>Registrar cliente</h2>

    <?php if (isset($_GET['ok'])): ?>
      <script>
        alert('Cliente registrado correctamente.');
        // limpia la querystring
        if (history.replaceState) {
          const url = new URL(window.location.href);
          url.search = '';
          history.replaceState(null, '', url.toString());
        }
      </script>
    <?php elseif (isset($_GET['err'])): ?>
      <script>
        alert('<?= addslashes($_GET["err"]) ?>');
        if (history.replaceState) {
          const url = new URL(window.location.href);
          url.search = '';
          history.replaceState(null, '', url.toString());
        }
      </script>
    <?php endif; ?>

    <?php include __DIR__ . '/clientesForm.php'; ?>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>

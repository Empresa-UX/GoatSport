<?php
/* =====================================================================
 * file: php/recepcionista/reportes/reportesForm.php  (INCLUIDO)
 * ===================================================================== */
if (!function_exists('h')) { function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
?>
<form method="POST" action="reportesAction.php" id="reporteForm" novalidate>
  <input type="hidden" name="action" value="create">

  <div class="f">
    <label>Título del reporte</label>
    <input class="inpt" type="text" name="nombre_reporte" maxlength="100" placeholder="Ej: Vidrio roto en puerta de Cancha 2" required>
  </div>

  <div class="f">
    <label>Descripción del reporte</label>
    <textarea class="txt" name="descripcion" placeholder="Describe lo ocurrido con la mayor precisión posible" required></textarea>
    <div class="help">No se puede dejar vacío.</div>
  </div>

  <div class="row">
    <div class="f">
      <label>Tipo de falla</label>
      <select class="sel" name="tipo_falla" id="tipoFalla" required>
        <option value="cancha">Cancha</option>
        <option value="sistema">Sistema</option>
      </select>
    </div>
    <!-- Fecha eliminada: se setea HOY en el backend -->
  </div>

  <div class="row">
    <div class="f">
      <label>Cancha</label>
      <select class="sel" name="cancha_id" id="canchaId">
        <option value="0">— Seleccionar —</option>
        <?php foreach((array)$__canchas as $c): ?>
          <option value="<?= (int)$c['cancha_id'] ?>"><?= h($c['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="f">
      <label>Reserva asociada</label>
      <input class="inpt" type="number" name="reserva_id" id="reservaId" min="1" placeholder="ID de reserva">
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn" id="btnSubmit">Crear reporte</button>
    <a href="/php/recepcionista/home_recepcionista.php" class="btn btn-outline">Cancelar</a>
  </div>
</form>

<script>
(function(){
  const form = document.getElementById('reporteForm');
  const tipo = document.getElementById('tipoFalla');
  const cancha = document.getElementById('canchaId');
  const reserva = document.getElementById('reservaId');

  function setDisabled(el, disabled){
    el.disabled = disabled;
    if (disabled) { el.classList.remove('invalid'); }
  }

  function applyMode(){
    const isSistema = (tipo.value === 'sistema');
    // Sistema -> deshabilitar y no requerir
    setDisabled(cancha, isSistema);
    setDisabled(reserva, isSistema);
    cancha.required = !isSistema;
    reserva.required = !isSistema;

    if (isSistema) {
      // limpiar valores para no forzar backend
      cancha.value = '0';
      reserva.value = '';
    }
  }

  function validate(){
    let ok = true;

    // título y descripción
    ['nombre_reporte','descripcion'].forEach(name=>{
      const el = form.elements[name];
      const good = !!(el && el.value && el.value.trim().length>0);
      el.classList.toggle('invalid', !good);
      ok = ok && good;
    });

    // reglas según tipo
    if (tipo.value === 'cancha') {
      const canchaOk = cancha.value && cancha.value !== '0';
      cancha.classList.toggle('invalid', !canchaOk);
      ok = ok && canchaOk;

      const reservaVal = reserva.value.trim();
      const reservaOk = /^\d+$/.test(reservaVal) && parseInt(reservaVal,10) > 0;
      reserva.classList.toggle('invalid', !reservaOk);
      ok = ok && reservaOk;
    }

    return ok;
  }

  tipo.addEventListener('change', applyMode);
  ['input','change','blur'].forEach(ev=>{
    form.addEventListener(ev, (e)=>{ if (e.target.matches('.inpt,.sel,.txt')) validate(); });
  });
  form.addEventListener('submit', (e)=>{
    applyMode();
    if (!validate()) { e.preventDefault(); alert('Completá los campos requeridos.'); }
  });

  applyMode(); // estado inicial
})();
</script>
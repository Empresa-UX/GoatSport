<?php
/* =========================================================================
 * Lista de solicitudes de proveedores pendientes + acciones
 * ========================================================================= */
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/cards.php';
include __DIR__ . '/../../config.php';

$sql = "
  SELECT 
    id,
    nombre,
    email,
    nombre_club,
    telefono,
    direccion,
    ciudad,
    estado,
    fecha_solicitud
  FROM solicitudes_proveedores
  WHERE estado = 'pendiente'
  ORDER BY COALESCE(nombre_club, nombre) ASC
";
$res  = $conn->query($sql);
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ddmm(?string $dt): string { if(!$dt) return '—'; $t=strtotime($dt); return $t?date('d/m',$t):'—'; }
?>
<div class="section">
  <div class="section-header" style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
    <h2 style="margin:0;">Proveedores pendientes</h2>
    <button onclick="location.href='./usuarios.php?view=proveedores'" class="btn-add">Volver a proveedores aprobados</button>
  </div>

  <?php if (isset($_GET['mail'])): ?>
    <div class="alert" style="margin:10px 0; padding:10px 12px; border-radius:8px;
         background: <?= $_GET['mail']==='ok' ? '#ecfdf5' : '#fff1f2' ?>;
         border:1px solid <?= $_GET['mail']==='ok' ? '#10b981' : '#f43f5e' ?>;">
      <?= $_GET['mail']==='ok' ? 'Correo enviado al proveedor.' : 'No se pudo enviar el correo (ver error_log).' ?>
    </div>
  <?php endif; ?>

  <style>
    :root{
      /* ⇩ Editá los anchos acá */
      --col-id: 30px; --col-nombre: 135px; --col-email: 170px; --col-club: 145px;
      --col-tel: 120px; --col-dir: 145px; --col-ciudad: 110px; --col-fecha: 60px; --col-acc: 90px;
    }
    .btn-add{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;text-decoration:none;font-weight:600;font-size:14px;transition:filter .15s ease, transform .03s ease;white-space:nowrap;}
    .btn-add:hover{background:#139488;}
    table{width:100%;border-collapse:separate;border-spacing:0;background:#fff;border-radius:12px;overflow:hidden;table-layout:fixed;}
    thead th{position:sticky;top:0;background:#f8fafc;z-index:1;text-align:left;font-weight:700;padding:10px 12px;font-size:13px;color:#334155;border-bottom:1px solid #e5e7eb;}
    tbody td{padding:10px 12px;border-bottom:1px solid #f1f5f9;vertical-align:top;}
    tbody tr:hover{background:#f7fbfd;}
    .col-id{width:var(--col-id)}.col-nombre{width:var(--col-nombre)}.col-email{width:var(--col-email)}
    .col-club{width:var(--col-club)}.col-tel{width:var(--col-tel)}.col-dir{width:var(--col-dir)}
    .col-ciudad{width:var(--col-ciudad)}.col-fecha{width:var(--col-fecha)}.col-acc{width:var(--col-acc)}
    .truncate{display:block;max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .btn-action{appearance:none;border:none;border-radius:8px;padding:6px 10px;cursor:pointer;font-weight:700;}
    .btn-action.edit{background:#e0f2ff;border:1px solid #bfd7ff;color:#1e40af;}     /* Aprobar */
    .btn-action.delete{background:#fde8e8;border:1px solid #f8c9c9;color:#7f1d1d;}  /* Denegar */
    .actions{display:flex;gap:6px;flex-wrap:wrap;align-items:center;}
  </style>

  <table>
    <thead>
      <tr>
        <th class="col-id">ID</th><th class="col-nombre">Nombre</th><th class="col-email">Email</th>
        <th class="col-club">Club</th><th class="col-tel">Teléfono</th><th class="col-dir">Dirección</th>
        <th class="col-ciudad">Ciudad</th><th class="col-fecha">Registro</th><th class="col-acc">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="9" style="text-align:center;">No hay proveedores pendientes</td></tr>
      <?php else: foreach($rows as $r): $reg=ddmm($r['fecha_solicitud']); ?>
        <tr>
          <td class="col-id"><?= (int)$r['id'] ?></td>
          <td class="col-nombre"><div class="truncate"><strong><?= h($r['nombre']) ?></strong></div></td>
          <td class="col-email"><span class="truncate"><?= h($r['email']) ?></span></td>
          <td class="col-club"><span class="truncate"><?= h(($r['nombre_club'] ?? '') ?: '—') ?></span></td>
          <td class="col-tel"><?= h(($r['telefono'] ?? '') ?: '—') ?></td>
          <td class="col-dir"><span class="truncate"><?= h(($r['direccion'] ?? '') ?: '—') ?></span></td>
          <td class="col-ciudad"><?= h(($r['ciudad'] ?? '') ?: '—') ?></td>
          <td class="col-fecha"><?= h($reg) ?></td>
          <td class="col-acc">
            <div class="actions">
              <form method="POST" action="proveedoresAction.php">
                <input type="hidden" name="action" value="approve_solicitud">
                <input type="hidden" name="solicitud_id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="btn-action edit" title="Aprobar">Aprobar</button>
              </form>
              <form method="POST" action="proveedoresAction.php" onsubmit="return confirm('¿Denegar este proveedor?');">
                <input type="hidden" name="action" value="deny_solicitud">
                <input type="hidden" name="solicitud_id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="btn-action delete" title="Denegar">Denegar</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

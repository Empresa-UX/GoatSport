<?php
/* =========================================================================
 * FILE: C:\Users\Gustavo\Desktop\Cristian\Proyectos\GoatSport\php\cliente\configuracion\configuracion.php
 * ========================================================================= */
include './../../config.php';
include './../includes/header.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: /php/login.php"); exit;
}

$userId  = (int)$_SESSION['usuario_id'];
$mensaje = '';

/* ===== Usuario (nombre/email) ===== */
$usuario = ['nombre' => '', 'email' => ''];
if ($st = $conn->prepare("SELECT nombre, email FROM usuarios WHERE user_id=? LIMIT 1")) {
    $st->bind_param("i", $userId);
    $st->execute();
    if ($row = $st->get_result()->fetch_assoc()) {
        $usuario['nombre'] = $row['nombre'];
        $usuario['email']  = $row['email'];
    }
    $st->close();
}

/* Helpers fecha (selects) */
function parse_date_y_m_d(?string $d): array {
    if (!$d) return [null,null,null];
    $p = explode('-', $d);
    return (count($p)===3) ? [(int)$p[0],(int)$p[1],(int)$p[2]] : [null,null,null];
}

/* ===== Guardar perfil ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Inputs básicos
    $telefono  = trim($_POST['telefono'] ?? '');
    $fnac_y    = (int)($_POST['fnac_y'] ?? 0);
    $fnac_m    = (int)($_POST['fnac_m'] ?? 0);
    $fnac_d    = (int)($_POST['fnac_d'] ?? 0);
    $fnac      = ($fnac_y && $fnac_m && $fnac_d) ? sprintf('%04d-%02d-%02d', $fnac_y, $fnac_m, $fnac_d) : '';

    $ciudad    = trim($_POST['ciudad'] ?? '');
    $barrio    = trim($_POST['barrio'] ?? '');         // NUEVO
    $bio       = trim($_POST['bio'] ?? '');

    $genero    = $_POST['genero'] ?? null;             // NUEVO
    $pref_cto  = $_POST['prefer_contacto'] ?? null;    // NUEVO

    // Padel
    $mano      = $_POST['mano_habil'] ?? null;
    $nivel     = isset($_POST['nivel_padel']) ? (int)$_POST['nivel_padel'] : null;
    $posicion  = $_POST['posicion_pref'] ?? null;

    $estilo_arr   = $_POST['estilo_juego']     ?? [];
    $freq         = $_POST['frecuencia_juego'] ?? null;
    $pala_m_arr   = $_POST['pala_marca']       ?? [];
    $pala_mod_arr = $_POST['pala_modelo']      ?? [];
    $horario      = $_POST['horario_pref']     ?? null;

    // Validaciones mínimas
    if ($nivel !== null && ($nivel < 1 || $nivel > 7)) { $nivel = null; }
    $mano     = in_array($mano, ['derecha','izquierda'], true) ? $mano : null;
    $posicion = in_array($posicion, ['drive','revés','mixto'], true) ? $posicion : null;
    $freq     = in_array($freq, ['ocasional','semanal','varias_por_semana','diaria'], true) ? $freq : null;
    $horario  = in_array($horario, ['maniana','tarde','noche'], true) ? $horario : null;

    $genero   = in_array($genero, ['masculino','femenino','otro','prefiero_no_decir'], true) ? $genero : null;
    $pref_cto = in_array($pref_cto, ['whatsapp','llamada','email'], true) ? $pref_cto : null;

    // Normalizaciones SET/listas
    $valid_estilo = ['ofensivo','defensivo','regular','globero','voleador','counter'];
    $estilo_fil = array_values(array_intersect($estilo_arr, $valid_estilo));
    $estilo_str = $estilo_fil ? implode(',', $estilo_fil) : null;

    $catalogo_marcas  = ['Bullpadel','Nox','Head','Babolat','Siux','StarVie'];
    $catalogo_modelos = ['Vertex 04','Hack 03','AT10 Genius','Delta Pro','Air Viper','Astrum Eris'];

    $pala_m_fil  = array_values(array_intersect($pala_m_arr,  $catalogo_marcas));
    $pala_m_str  = $pala_m_fil ? implode(',', $pala_m_fil) : null;

    $pala_mod_fil = array_values(array_intersect($pala_mod_arr, $catalogo_modelos));
    $pala_mod_str = $pala_mod_fil ? implode(',', $pala_mod_fil) : null;

    // Variables intermedias (evita "Only variables can be passed by reference")
    $param_cliente_id = $userId;
    $param_telefono   = ($telefono !== '') ? $telefono : null;
    $param_fnac       = ($fnac !== '') ? $fnac : null;
    $param_ciudad     = ($ciudad !== '') ? $ciudad : null;
    $param_barrio     = ($barrio !== '') ? $barrio : null;
    $param_bio        = ($bio !== '') ? $bio : null;

    $param_genero     = $genero;
    $param_mano       = $mano;
    $param_nivel      = $nivel;
    $param_posicion   = $posicion;
    $param_estilo     = $estilo_str;

    $param_pala_m     = $pala_m_str;
    $param_pala_mod   = $pala_mod_str;
    $param_freq       = $freq;
    $param_horario    = $horario;
    $param_pref_cto   = $pref_cto;

    // === SQL (17 placeholders) ===
    $sql = "
      INSERT INTO cliente_detalle
        (cliente_id, telefono, fecha_nacimiento, ciudad, barrio, bio,
         genero, mano_habil, nivel_padel, posicion_pref, estilo_juego,
         pala_marca, pala_modelo, frecuencia_juego, horario_pref, prefer_contacto)
      VALUES (?, ?, ?, ?, ?, ?,
              ?, ?, ?, ?, ?,
              ?, ?, ?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE
        telefono=VALUES(telefono),
        fecha_nacimiento=VALUES(fecha_nacimiento),
        ciudad=VALUES(ciudad),
        barrio=VALUES(barrio),
        bio=VALUES(bio),
        genero=VALUES(genero),
        mano_habil=VALUES(mano_habil),
        nivel_padel=VALUES(nivel_padel),
        posicion_pref=VALUES(posicion_pref),
        estilo_juego=VALUES(estilo_juego),
        pala_marca=VALUES(pala_marca),
        pala_modelo=VALUES(pala_modelo),
        frecuencia_juego=VALUES(frecuencia_juego),
        horario_pref=VALUES(horario_pref),
        prefer_contacto=VALUES(prefer_contacto)
    ";

    $st = $conn->prepare($sql);

    // ***** FIX: tipos SIN espacios y con 17 letras (1 i + 7 s + 1 i + 8 s) *****
    $types = "isssssssisssssss";

    $st->bind_param(
        $types,
        $param_cliente_id,
        $param_telefono,
        $param_fnac,
        $param_ciudad,
        $param_barrio,
        $param_bio,
        $param_genero,
        $param_mano,
        $param_nivel,
        $param_posicion,
        $param_estilo,
        $param_pala_m,
        $param_pala_mod,
        $param_freq,
        $param_horario,
        $param_pref_cto
    );

    if ($st->execute()) {
        $mensaje = "<p class='success'>✅ Perfil actualizado.</p>";
    } else {
        $mensaje = "<p class='error'>⚠️ Error al guardar el perfil.</p>";
    }
    $st->close();
}

/* ===== Cargar detalle para pintar ===== */
$det = [
  'telefono'=> '', 'fecha_nacimiento'=> '', 'ciudad'=> '', 'barrio'=>'', 'bio'=> '',
  'genero'=> null,
  'mano_habil'=> null, 'nivel_padel'=> null, 'posicion_pref'=> null, 'estilo_juego'=> [],
  'pala_marca'=> [], 'pala_modelo'=> [], 'frecuencia_juego'=> null, 'horario_pref'=> null,
  'prefer_contacto'=> null
];

$st = $conn->prepare("
  SELECT telefono, fecha_nacimiento, ciudad, barrio, bio,
         genero, mano_habil, nivel_padel, posicion_pref, estilo_juego,
         pala_marca, pala_modelo, frecuencia_juego, horario_pref, prefer_contacto
  FROM cliente_detalle WHERE cliente_id=? LIMIT 1
");
$st->bind_param("i", $userId);
$st->execute();
if ($row = $st->get_result()->fetch_assoc()) {
  $det['telefono']          = (string)($row['telefono'] ?? '');
  $det['fecha_nacimiento']  = (string)($row['fecha_nacimiento'] ?? '');
  $det['ciudad']            = (string)($row['ciudad'] ?? '');
  $det['barrio']            = (string)($row['barrio'] ?? '');
  $det['bio']               = (string)($row['bio'] ?? '');
  $det['genero']            = $row['genero'] ?? null;

  $det['mano_habil']        = $row['mano_habil'];
  $det['nivel_padel']       = $row['nivel_padel'] !== null ? (int)$row['nivel_padel'] : null;
  $det['posicion_pref']     = $row['posicion_pref'];
  $det['estilo_juego']      = !empty($row['estilo_juego']) ? explode(',', $row['estilo_juego']) : [];
  $det['pala_marca']        = !empty($row['pala_marca']) ? explode(',', $row['pala_marca']) : [];
  $det['pala_modelo']       = !empty($row['pala_modelo']) ? explode(',', $row['pala_modelo']) : [];
  $det['frecuencia_juego']  = $row['frecuencia_juego'];
  $det['horario_pref']      = $row['horario_pref'];
  $det['prefer_contacto']   = $row['prefer_contacto'] ?? null;
}
$st->close();

list($fnac_y, $fnac_m, $fnac_d) = parse_date_y_m_d($det['fecha_nacimiento']);
?>
<style>
.profile-grid{ display:grid; grid-template-columns: 1fr 1fr; gap:24px; }
@media (max-width:900px){ .profile-grid{ grid-template-columns:1fr; } }
.card-white{ padding:22px; border-radius:16px; background:#fff; color:#043b3d; box-shadow:0 12px 35px rgba(0,0,0,.25); }
.section-title{ margin:0 0 12px 0; font-size:18px; font-weight:800; color:#043b3d; }

.form-grid{ display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.form-grid .full{ grid-column:1 / -1; }

.torneo-form label{ font-weight:600; color:#043b3d; margin-bottom:6px; display:block; }
.torneo-form input[type="text"],
.torneo-form input[type="number"],
.torneo-form select,
.torneo-form textarea{
  width:100%; padding:12px 14px; font-size:1rem;
  border:1px solid #e1ecec; border-radius:10px; outline:none;
  background:#fff; color:#043b3d; transition:border .2s, box-shadow .2s;
}
.torneo-form input:focus,
.torneo-form select:focus,
.torneo-form textarea:focus{ border-color:#1bab9d; box-shadow:0 0 0 3px rgba(27,171,157,0.2); }

.date-row{ display:flex; gap:8px; }
.date-row select{ flex:1; }

fieldset{ border:1px solid #e1ecec; border-radius:12px; padding:12px 12px 4px; }
legend{ padding:0 8px; font-weight:700; color:#043b3d; }
.check-group{ display:flex; gap:12px; flex-wrap:wrap; }

.card-stretch{ display:flex; flex-direction:column; height:100%; }
.bio-wrap{ display:flex; flex-direction:column; gap:6px; height:100%; }
.bio-wrap textarea{ flex:1; min-height:180px; resize:vertical; }

.center-actions{ display:flex; justify-content:center; gap:12px; margin-top:20px; flex-wrap:wrap; }
.btn-secondary{
  text-decoration:none; display:inline-block; padding:12px 14px; border-radius:10px;
  border:1.5px solid #1bab9d; color:#1bab9d; background:#fff; font-weight:700; font-size:1rem;
  transition:background .2s, transform .1s;
}
.btn-secondary:hover{ background:rgba(27,171,157,.08); transform:translateY(-1px); }
.small-muted{ color:#5a6b6c; font-size:13px; }
</style>

<div class="page-wrap">
  <h1 class="page-title" style="text-align:center;">Mi perfil</h1>
  <?= $mensaje ?>

  <form method="POST" class="torneo-form">
    <div class="profile-grid">
      <!-- IZQ: Personales -->
      <div class="card-white card-stretch">
        <h2 class="section-title">Datos personales</h2>
        <div class="form-grid" style="flex:1;">
          <div>
            <label>Nombre</label>
            <input type="text" value="<?= htmlspecialchars($usuario['nombre']) ?>" readonly disabled>
          </div>
          <div>
            <label>Email</label>
            <input type="text" value="<?= htmlspecialchars($usuario['email']) ?>" readonly disabled>
          </div>

          <div>
            <label>Teléfono</label>
            <input type="text" name="telefono" value="<?= htmlspecialchars($det['telefono']) ?>" placeholder="+54 11 1234-5678">
          </div>

          <div>
            <label>Fecha de nacimiento</label>
            <div class="date-row">
              <?php $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']; ?>
              <select name="fnac_d">
                <option value="">Día</option>
                <?php for($d=1;$d<=31;$d++): ?>
                  <option value="<?= $d ?>" <?= ($fnac_d===$d)?'selected':'' ?>><?= sprintf('%02d',$d) ?></option>
                <?php endfor; ?>
              </select>
              <select name="fnac_m">
                <option value="">Mes</option>
                <?php for($m=1;$m<=12;$m++): ?>
                  <option value="<?= $m ?>" <?= ($fnac_m===$m)?'selected':'' ?>><?= $meses[$m] ?></option>
                <?php endfor; ?>
              </select>
              <select name="fnac_y">
                <option value="">Año</option>
                <?php $yearNow=(int)date('Y'); for($y=$yearNow-10; $y>=1930; $y--): ?>
                  <option value="<?= $y ?>" <?= ($fnac_y===$y)?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
              </select>
            </div>
          </div>

          <div>
            <label>Ciudad</label>
            <input type="text" name="ciudad" value="<?= htmlspecialchars($det['ciudad']) ?>" placeholder="Ej: Buenos Aires">
          </div>

          <div>
            <label>Barrio</label>
            <input type="text" name="barrio" value="<?= htmlspecialchars($det['barrio']) ?>" placeholder="Ej: Caballito">
          </div>

          <div>
            <label>Género</label>
            <select name="genero">
              <option value="">—</option>
              <option value="masculino"         <?= $det['genero']==='masculino'?'selected':'' ?>>Masculino</option>
              <option value="femenino"          <?= $det['genero']==='femenino'?'selected':'' ?>>Femenino</option>
              <option value="otro"              <?= $det['genero']==='otro'?'selected':'' ?>>Otro</option>
              <option value="prefiero_no_decir" <?= $det['genero']==='prefiero_no_decir'?'selected':'' ?>>Prefiero no decir</option>
            </select>
          </div>

          <div>
            <label>Preferencia de contacto</label>
            <select name="prefer_contacto">
              <option value="">—</option>
              <option value="whatsapp" <?= $det['prefer_contacto']==='whatsapp'?'selected':'' ?>>WhatsApp</option>
              <option value="llamada"  <?= $det['prefer_contacto']==='llamada'?'selected':'' ?>>Llamada telefónica</option>
              <option value="email"    <?= $det['prefer_contacto']==='email'?'selected':'' ?>>Email</option>
            </select>
          </div>

          <div class="full bio-wrap">
            <label>Bio</label>
            <textarea name="bio" placeholder="Contanos de vos (máx. 500)"><?= htmlspecialchars($det['bio']) ?></textarea>
          </div>
        </div>
      </div>

      <!-- DER: Padel -->
      <div class="card-white">
        <h2 class="section-title">Datos como jugador</h2>
        <div class="form-grid">
          <div>
            <label>Mano hábil</label>
            <select name="mano_habil">
              <option value="">—</option>
              <option value="derecha"   <?= $det['mano_habil']==='derecha'?'selected':'' ?>>Derecha</option>
              <option value="izquierda" <?= $det['mano_habil']==='izquierda'?'selected':'' ?>>Izquierda</option>
            </select>
          </div>
          <div>
            <label>Nivel (1–7)</label>
            <input type="number" name="nivel_padel" min="1" max="7" value="<?= htmlspecialchars($det['nivel_padel']) ?>" placeholder="Ej: 4">
          </div>
          <div>
            <label>Posición preferida</label>
            <select name="posicion_pref">
              <option value="">—</option>
              <option value="drive" <?= $det['posicion_pref']==='drive'?'selected':'' ?>>Drive</option>
              <option value="revés" <?= $det['posicion_pref']==='revés'?'selected':'' ?>>Revés</option>
              <option value="mixto" <?= $det['posicion_pref']==='mixto'?'selected':'' ?>>Mixto</option>
            </select>
          </div>
          <div>
            <label>Frecuencia de juego</label>
            <select name="frecuencia_juego">
              <option value="">—</option>
              <option value="ocasional"          <?= $det['frecuencia_juego']==='ocasional'?'selected':'' ?>>Ocasional</option>
              <option value="semanal"            <?= $det['frecuencia_juego']==='semanal'?'selected':'' ?>>Semanal</option>
              <option value="varias_por_semana"  <?= $det['frecuencia_juego']==='varias_por_semana'?'selected':'' ?>>Varias por semana</option>
              <option value="diaria"             <?= $det['frecuencia_juego']==='diaria'?'selected':'' ?>>Diaria</option>
            </select>
          </div>
          <div>
            <label>Horario preferido</label>
            <select name="horario_pref">
              <option value="">—</option>
              <option value="maniana" <?= $det['horario_pref']==='maniana'?'selected':'' ?>>Mañana</option>
              <option value="tarde"   <?= $det['horario_pref']==='tarde'?'selected':'' ?>>Tarde</option>
              <option value="noche"   <?= $det['horario_pref']==='noche'?'selected':'' ?>>Noche</option>
            </select>
          </div>

          <div class="full">
            <fieldset>
              <legend>Pala (marca)</legend>
              <div class="check-group">
                <?php
                  $marcas = ['Bullpadel','Nox','Head','Babolat','Siux','StarVie'];
                  foreach ($marcas as $m):
                    $checked = in_array($m, $det['pala_marca'], true) ? 'checked' : '';
                ?>
                  <label><input type="checkbox" name="pala_marca[]" value="<?= $m ?>" <?= $checked ?>> <?= $m ?></label>
                <?php endforeach; ?>
              </div>
            </fieldset>
          </div>

          <div class="full">
            <fieldset>
              <legend>Pala (modelo)</legend>
              <div class="check-group">
                <?php
                  $modelos = ['Vertex 04','Hack 03','AT10 Genius','Delta Pro','Air Viper','Astrum Eris'];
                  foreach ($modelos as $mm):
                    $checked = in_array($mm, $det['pala_modelo'], true) ? 'checked' : '';
                ?>
                  <label><input type="checkbox" name="pala_modelo[]" value="<?= $mm ?>" <?= $checked ?>> <?= $mm ?></label>
                <?php endforeach; ?>
              </div>
            </fieldset>
          </div>

          <div class="full">
            <fieldset>
              <legend>Estilo de juego</legend>
              <div class="check-group">
                <?php
                  $opts = ['ofensivo'=>'Ofensivo','defensivo'=>'Defensivo','regular'=>'Regular','globero'=>'Globero','voleador'=>'Voleador','counter'=>'Counter'];
                  foreach ($opts as $val=>$lab):
                    $chk = in_array($val, $det['estilo_juego'], true) ? 'checked' : '';
                ?>
                  <label><input type="checkbox" name="estilo_juego[]" value="<?= $val ?>" <?= $chk ?>> <?= $lab ?></label>
                <?php endforeach; ?>
              </div>
            </fieldset>
          </div>

          <div class="full">
          </div>
        </div>
      </div>
    </div>

    <div class="center-actions">
      <a class="btn-secondary" href="/php/cliente/home_cliente.php">🏠 Ir al inicio</a>
      <button type="submit" class="btn-add">Guardar cambios</button>
      <a class="btn-secondary" href="/php/logout.php">🚪 Cerrar sesión</a>
    </div>
  </form>
</div>

<?php include './../includes/footer.php'; ?>

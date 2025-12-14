<?php
/* =========================================================================
 * Aprobar / Denegar solicitudes de proveedores + envío de email (PHPMailer)
 * ========================================================================= */
session_start();
require_once __DIR__ . '/../../config.php';

// Requiere admin
if (!isset($_SESSION['usuario_id'], $_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: /php/login.php');
    exit;
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método inválido');
}

$action       = $_POST['action']        ?? '';
$solicitud_id = (int)($_POST['solicitud_id'] ?? 0);
if (!$solicitud_id || !in_array($action, ['approve_solicitud','deny_solicitud'], true)) {
    http_response_code(400);
    exit('Parámetros inválidos');
}

// Traer solicitud pendiente
$stmt = $conn->prepare("SELECT * FROM solicitudes_proveedores WHERE id = ? AND estado = 'pendiente'");
$stmt->bind_param("i", $solicitud_id);
$stmt->execute();
$sol = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sol) {
    header("Location: proveedoresPendientes.php?msg=no_encontrada");
    exit;
}

$email       = $sol['email'];
$nombre      = $sol['nombre'];
$club        = $sol['nombre_club'] ?? '';
$tel         = $sol['telefono'] ?? '';
$dir         = $sol['direccion'] ?? '';
$barrio      = $sol['barrio'] ?? '';
$ciudad      = ($sol['ciudad'] ?? '') ?: 'Buenos Aires';
$descripcion = $sol['descripcion'] ?? '';

/* -------------------------------------------------------------------------
 * Configs de contenido de mail (solo diseño)
 * ------------------------------------------------------------------------- */
$loginUrl     = 'http://localhost:3000/php/login.php';
$soporteEmail = 'soporte@goatsports.example';

// =============================
// PHPMailer
// =============================
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer/src/SMTP.php';

function enviar_mail(string $para, string $asunto, string $mensajeHTML, string $nombrePara = ''): bool {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug   = 0;
    $mail->Debugoutput = 'error_log';
    try {
        $mail->isSMTP();
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 30;
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'goatsportsoporte2025@gmail.com';
        $mail->Password   = 'rhgx ipqb yowi owpw'; // App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];
        $mail->setFrom('goatsportsoporte2025@gmail.com', 'GOAT Sports');
        $mail->addAddress($para, $nombrePara ?: $para);
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $mensajeHTML;
        $mail->AltBody = strip_tags($mensajeHTML);
        try {
            $mail->send();
            return true;
        } catch (Exception $e587) {
            $mail->smtpClose();
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->send();
            return true;
        }
    } catch (Exception $e) {
        error_log('[MAIL] ERROR: '.$mail->ErrorInfo);
        return false;
    }
}

// -----------------------------
// Denegar solicitud (solo cambia diseño de $mensaje)
// -----------------------------
if ($action === 'deny_solicitud') {
    $u = $conn->prepare("UPDATE solicitudes_proveedores SET estado = 'rechazado' WHERE id = ? AND estado = 'pendiente'");
    $u->bind_param("i", $solicitud_id);
    $u->execute();
    $ok = $u->affected_rows > 0;
    $u->close();

    if ($ok) {
        $asunto  = "Solicitud rechazada - GOAT Sports";
        $safeNom = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
        $safeClub= htmlspecialchars($club ?: 'tu organización', ENT_QUOTES, 'UTF-8');
        $preheader = "Tu solicitud para ser proveedor no fue aprobada por ahora. Te contamos por qué y cómo volver a postular.";
        $mensaje = "
<!DOCTYPE html><html><body style='margin:0;padding:0;background:#f6f9fc'>
  <span style='display:none !important;visibility:hidden;opacity:0;height:0;width:0;color:transparent'>{$preheader}</span>
  <div style='font-family:Arial,Helvetica,sans-serif;background:#e9f5f4;padding:32px'>
    <div style='max-width:640px;margin:0 auto;background:#ffffff;border-radius:16px;box-shadow:0 8px 24px rgba(0,0,0,.08);overflow:hidden'>
      <div style='padding:24px 24px 0'>
        <img src='https://i.postimg.cc/tChXrCQX/goatsport.jpg' alt='GOAT Sports' style='width:168px;height:auto;display:block;margin:0 auto 8px' />
        <h1 style='margin:8px 0 0;font-size:22px;line-height:1.3;color:#0b3d3f;text-align:center'>Solicitud rechazada</h1>
      </div>
      <div style='padding:24px 28px 8px'>
        <p style='margin:0 0 12px;font-size:15px;color:#0f172a'>Hola <strong>{$safeNom}</strong>,</p>
        <p style='margin:0 0 12px;font-size:15px;color:#334155'>Revisamos tu solicitud para trabajar con nosotros como proveedor en <strong>{$safeClub}</strong>, pero en esta instancia <strong>no pudimos aprobarla</strong>.</p>
        <div style='margin:16px 0;padding:14px 16px;border:1px solid #fee2e2;background:#fff1f2;border-radius:10px'>
          <p style='margin:0 0 6px;font-weight:bold;color:#7f1d1d'>¿Por qué puede ocurrir?</p>
          <ul style='margin:8px 0 0 18px;padding:0;color:#7f1d1d;font-size:14px;line-height:1.5'>
            <li>Datos incompletos o inconsistentes.</li>
            <li>Actividad que no se alinea con nuestras políticas.</li>
            <li>Falta de documentación de respaldo.</li>
          </ul>
        </div>
        <p style='margin:14px 0 10px;font-size:15px;color:#334155'>Si creés que se trata de un error o querés volver a postular, podés responder a este correo con la información faltante.</p>
        <div style='margin:18px 0;padding:14px 16px;border:1px dashed #cbd5e1;border-radius:10px;background:#f8fafc'>
          <p style='margin:0 0 6px;font-weight:bold;color:#0f172a'>Consejos para una re-postulación exitosa</p>
          <ul style='margin:8px 0 0 18px;padding:0;color:#334155;font-size:14px;line-height:1.5'>
            <li>Verificá que el correo y teléfono sean correctos y estén activos.</li>
            <li>Incluí una descripción breve de tus productos/servicios y cobertura.</li>
            <li>Adjuntá documentación que respalde tu actividad.</li>
          </ul>
        </div>
        <p style='margin:14px 0 0;font-size:13px;color:#64748b'>¿Dudas? Escribinos a <a href='mailto:{$soporteEmail}' style='color:#0ea5a4;text-decoration:none'>{$soporteEmail}</a>.</p>
        <p style='margin:4px 0 24px;font-size:12px;color:#94a3b8'>Este es un correo automático. No compartas contraseñas ni información sensible por este medio.</p>
      </div>
      <div style='padding:14px 24px;background:#083e49;color:#e2f3f1;text-align:center;font-size:12px'>© ".date('Y')." GOAT Sports — Calidad y confianza para tus equipos.</div>
    </div>
  </div>
</body></html>";
        $sent = enviar_mail($email, $asunto, $mensaje, $nombre);
        header("Location: proveedoresPendientes.php?msg=rechazado&mail=".($sent?'ok':'fail'));
        exit;
    }
    header("Location: proveedoresPendientes.php?msg=conflicto");
    exit;
}

// -----------------------------
// Aprobar solicitud + CREAR/ACTUALIZAR proveedores_detalle
// -----------------------------
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("SELECT user_id, rol FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($user_id, $rol_existente);
    $existe = $stmt->fetch();
    $stmt->close();

    $temp_password = null;

    if ($existe) {
        if ($rol_existente !== 'proveedor') {
            $up = $conn->prepare("UPDATE usuarios SET rol='proveedor' WHERE user_id=?");
            $up->bind_param("i", $user_id);
            $up->execute();
            $up->close();
        }
    } else {
        $temp_password = bin2hex(random_bytes(6));
        $hash = password_hash($temp_password, PASSWORD_DEFAULT);
        $ins = $conn->prepare("INSERT INTO usuarios (nombre, email, contrasenia, rol) VALUES (?, ?, ?, 'proveedor')");
        $ins->bind_param("sss", $nombre, $email, $hash);
        $ins->execute();
        $user_id = $conn->insert_id;
        $ins->close();
    }

    // ⇩ Crear/actualizar ficha en proveedores_detalle para este proveedor
    // Por qué: garantizar perfil editable apenas se aprueba
    $insDet = $conn->prepare("
        INSERT INTO proveedores_detalle
            (proveedor_id, nombre_club, telefono, direccion, ciudad, descripcion, barrio, estado)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, 'aprobado')
        ON DUPLICATE KEY UPDATE
            nombre_club = VALUES(nombre_club),
            telefono    = VALUES(telefono),
            direccion   = VALUES(direccion),
            ciudad      = VALUES(ciudad),
            descripcion = VALUES(descripcion),
            barrio      = VALUES(barrio),
            estado      = 'aprobado'
    ");
    $insDet->bind_param("issssss", $user_id, $club, $tel, $dir, $ciudad, $descripcion, $barrio);
    $insDet->execute();
    $insDet->close();

    $upd = $conn->prepare("UPDATE solicitudes_proveedores SET estado = 'aprobado' WHERE id = ? AND estado = 'pendiente'");
    $upd->bind_param("i", $solicitud_id);
    $upd->execute();
    $upd->close();

    $conn->commit();

    $asunto   = "Solicitud aprobada - GOAT Sports";
    $safeNom  = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
    $safeClub = htmlspecialchars($club ?: 'tu organización', ENT_QUOTES, 'UTF-8');
    $safeMail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

    $preheader = "¡Bienvenido! Tu solicitud fue aprobada. Te contamos cómo ingresar, tus primeros pasos y buenas prácticas de seguridad.";
    $credencialesHTML = $temp_password
        ? "<div style='margin:16px 0;padding:16px;border:1px solid #d1fae5;background:#ecfdf5;border-radius:12px'>
              <p style='margin:0 0 6px;font-weight:bold;color:#065f46'>Credenciales de acceso</p>
              <p style='margin:6px 0 0;font-size:14px;color:#064e3b'>
                Email: <strong>{$safeMail}</strong><br/>
                Contraseña temporal: <strong style='font-size:18px'>{$temp_password}</strong>
              </p>
              <p style='margin:10px 0 0;font-size:12px;color:#047857'>Por seguridad, cambiá la contraseña luego del primer ingreso.</p>
            </div>"
        : "<div style='margin:16px 0;padding:16px;border:1px solid #e2e8f0;background:#f8fafc;border-radius:12px'>
              <p style='margin:0 0 6px;font-weight:bold;color:#0f172a'>Tu cuenta ya existe</p>
              <p style='margin:6px 0 0;font-size:14px;color:#334155'>
                Ya tenés una cuenta activa con rol de proveedor asociada a <strong>{$safeMail}</strong>.
                Usá tu contraseña habitual. Si no la recordás, utilizá <em>Olvidé mi contraseña</em>.
              </p>
            </div>";

    $cta = "<a href='{$loginUrl}' style='display:inline-block;padding:12px 18px;border-radius:10px;background:#0ea5a4;color:#ffffff;text-decoration:none;font-weight:700' target='_blank' rel='noopener'>Ingresar ahora</a>";

    $mensaje = "
<!DOCTYPE html><html><body style='margin:0;padding:0;background:#f6f9fc'>
  <span style='display:none !important;visibility:hidden;opacity:0;height:0;width:0;color:transparent'>{$preheader}</span>
  <div style='font-family:Arial,Helvetica,sans-serif;background:#e9f5f4;padding:32px'>
    <div style='max-width:640px;margin:0 auto;background:#ffffff;border-radius:16px;box-shadow:0 8px 24px rgba(0,0,0,.08);overflow:hidden'>
      <div style='padding:24px 24px 0'>
        <img src='https://i.postimg.cc/tChXrCQX/goatsport.jpg' alt='GOAT Sports' style='width:168px;height:auto;display:block;margin:0 auto 8px' />
        <h1 style='margin:8px 0 0;font-size:22px;line-height:1.3;color:#0b3d3f;text-align:center'>Solicitud aprobada</h1>
      </div>
      <div style='padding:24px 28px 8px'>
        <p style='margin:0 0 12px;font-size:15px;color:#0f172a'>Hola <strong>{$safeNom}</strong>,</p>
        <p style='margin:0 0 12px;font-size:15px;color:#334155'>¡Gracias por postularte! Tu solicitud para trabajar con nosotros como proveedor en <strong>{$safeClub}</strong> fue <strong>aprobada</strong>.</p>
        {$credencialesHTML}
        <div style='margin:18px 0 8px'>
          <p style='margin:0 0 6px;font-weight:bold;color:#0f172a'>Cómo ingresar</p>
          <ol style='margin:8px 0 0 20px;padding:0;color:#334155;font-size:14px;line-height:1.6'>
            <li>Abrí el siguiente enlace de acceso: <a href='{$loginUrl}' target='_blank' style='color:#0ea5a4;text-decoration:none'>{$loginUrl}</a>.</li>
            <li>Iniciá sesión con tu email y la contraseña indicada arriba.</li>
            <li>Si es tu primer ingreso, cambiá la contraseña desde <em>Mi cuenta &gt; Seguridad</em>.</li>
          </ol>
          <div style='margin:14px 0'>{$cta}</div>
        </div>
        <div style='margin:16px 0;padding:14px 16px;border:1px dashed #cbd5e1;border-radius:10px;background:#f8fafc'>
          <p style='margin:0 0 6px;font-weight:bold;color:#0f172a'>Primeros pasos recomendados</p>
          <ul style='margin:8px 0 0 18px;padding:0;color:#334155;font-size:14px;line-height:1.6'>
            <li>Completá tus datos de facturación y contacto.</li>
            <li>Cargá tu catálogo de productos/servicios.</li>
            <li>Configurá zonas de cobertura y tiempos de entrega.</li>
          </ul>
        </div>
        <div style='margin:16px 0;padding:14px 16px;border:1px solid #e2e8f0;border-radius:10px;background:#fff'>
          <p style='margin:0 0 6px;font-weight:bold;color:#0f172a'>Buenas prácticas de seguridad</p>
          <ul style='margin:8px 0 0 18px;padding:0;color:#334155;font-size:14px;line-height:1.6'>
            <li>No compartas tu contraseña. Cambiala si sospechás uso indebido.</li>
            <li>Usá contraseñas únicas y difíciles de adivinar.</li>
            <li>Verificá siempre que el dominio de acceso sea el oficial.</li>
          </ul>
        </div>
        <p style='margin:14px 0 0;font-size:13px;color:#64748b'>¿Necesitás ayuda? Escribinos a <a href='mailto:{$soporteEmail}' style='color:#0ea5a4;text-decoration:none'>{$soporteEmail}</a>.</p>
        <p style='margin:4px 0 24px;font-size:12px;color:#94a3b8'>Este es un correo automático. Si no reconocés esta gestión, ignorá este mensaje.</p>
      </div>
      <div style='padding:14px 24px;background:#083e49;color:#e2f3f1;text-align:center;font-size:12px'>© ".date('Y')." GOAT Sports — Calidad y confianza para tus equipos.</div>
    </div>
  </div>
</body></html>";

    $sent = enviar_mail($email, $asunto, $mensaje, $nombre);
    header("Location: proveedoresPendientes.php?msg=aprobado&mail=".($sent?'ok':'fail'));
    exit;

} catch (Throwable $e) {
    $conn->rollback();
    error_log("APROBAR ERROR: " . $e->getMessage());
    header("Location: proveedoresPendientes.php?msg=error");
    exit;
}

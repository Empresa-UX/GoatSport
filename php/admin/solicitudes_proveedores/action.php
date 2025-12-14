<?php
include './../../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer/src/SMTP.php';



// detectar objeto de conexión (varía entre proyectos: $conexion o $conn)
$db = null;
if (isset($conexion) && $conexion) $db = $conexion;
if (isset($conn) && $conn) $db = $conn;

if (!$db) {
    die("Error: no se encontró la conexión a la BD. Compruebe conexion.php (variables \$conexion o \$conn).");
}

// obtener y validar parámetros
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$op = isset($_GET['op']) ? $_GET['op'] : '';

if ($id <= 0) {
    die("Error: ID inválido.");
}

if (!in_array($op, ['aprobar', 'rechazar'])) {
    die("Error: operación inválida. op debe ser 'aprobar' o 'rechazar'.");
}

// obtener la solicitud (prepared statement para evitar inyecciones)
$stmt = $db->prepare("SELECT * FROM solicitudes_proveedores WHERE id = ? AND estado = 'pendiente'");
if (!$stmt) {
    die("Error prepare SELECT: " . $db->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$sol = $res->fetch_assoc();
$stmt->close();

if (!$sol) {
    die("No existe la solicitud con ID $id.");
}

// datos comunes
$email = $sol['email'];
$nombre_contacto = $sol['nombre_contacto'];
$nombre_club = $sol['nombre_club'] ?? '';
$password_hash = $sol['password']; // asumimos que fue guardado hasheado en la solicitud
// Si la contraseña NO está hasheada ahí, habría que hashearla ahora. 
// (Preferible: en el registro ya la guardaste hasheada.)

// función de envío de email (HTML)
function enviar_mail($para, $asunto, $mensajeHTML)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'goatsportsoporte2025@gmail.com';
        $mail->Password = 'rhgx ipqb yowi owpw'; // app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('goatsportsoporte2025@gmail.com', 'GOAT Sports');
        $mail->addAddress($para);

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $mensajeHTML;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("MAIL ERROR: " . $mail->ErrorInfo);
        return false;
    }
}


if ($op === 'rechazar') {
    // marcar como rechazada y avisar
    $stmt = $db->prepare("UPDATE solicitudes_proveedores SET estado = 'rechazado' WHERE id = ? AND estado = 'pendiente'");
    if (!$stmt) {
        die("Error prepare UPDATE rechazar: " . $db->error);
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        die("La solicitud ya fue procesada.");
    }

    $stmt->close();

    $asunto = "Solicitud rechazada - GOAT Sports";
    $mensaje = "
        <p>Hola <strong>" . htmlspecialchars($nombre_contacto) . "</strong>,</p>
        <p>Lamentamos informarte que tu solicitud para registrar el club <strong>" . htmlspecialchars($nombre_club) . "</strong> ha sido <strong>rechazada</strong>.</p>
        <p>Si crees que hubo un error, contactanos.</p>
        <p>Saludos,<br>GOAT Sports</p>
    ";
    enviar_mail($email, $asunto, $mensaje);

    header("Location: listado.php?msg=rechazado");
    exit;
}

// === si llegamos acá: op == 'aprobar' ===
// Usamos transacción para mantener integridad
if (!$db->begin_transaction()) {
    die("No se pudo iniciar transacción: " . $db->error);
}

try {
    // 1) insertar en usuarios (nombre, email, contrasenia/contrasenia/ password segun tu esquema)
    // a priori usamos la columna 'contrasenia' o 'password' según tu BD. Ajustalo si tu columna se llama diferente.
    // Voy a intentar con 'contrasenia' (spanish) y si falla con 'password' (english).
    // 🔎 1) Verificar si el usuario ya existe por email
    $usuario_creado = false;

    $stmtCheck = $db->prepare("SELECT user_id FROM usuarios WHERE email = ?");
    $stmtCheck->bind_param("s", $email);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();

    if ($row = $resCheck->fetch_assoc()) {
        // ✔ Usuario ya existe
        $id_usuario = $row['user_id'];
        $usuario_creado = false;
    } else {
        // ➕ Crear nuevo usuario
        $stmtUser = $db->prepare(
            "INSERT INTO usuarios (nombre, email, contrasenia, rol)
         VALUES (?, ?, ?, 'proveedor')"
        );

        if (!$stmtUser) {
            throw new Exception("No se pudo preparar INSERT usuarios: " . $db->error);
        }

        $stmtUser->bind_param("sss", $nombre_contacto, $email, $password_hash);

        if (!$stmtUser->execute()) {
            throw new Exception("Error ejecutando INSERT usuarios: " . $stmtUser->error);
        }

        $id_usuario = $db->insert_id;
        $usuario_creado = true;

        $stmtUser->close();
    }

    $stmtCheck->close();



    $stmtDet = $db->prepare("
    INSERT INTO proveedores_detalle 
    (proveedor_id, nombre_club, telefono, direccion, ciudad, descripcion)
    VALUES (?, ?, ?, ?, ?, ?)
");

    if (!$stmtDet) {
        throw new Exception("Prepare proveedores_detalle: " . $db->error);
    }

    $telefono = $sol['telefono'];
    $direccion = $sol['direccion'];
    $ciudad = $sol['ciudad'];
    $descripcion = $sol['descripcion'];

    $stmtDet->bind_param(
        "isssss",
        $id_usuario,
        $nombre_club,
        $telefono,
        $direccion,
        $ciudad,
        $descripcion
    );

    if (!$stmtDet->execute()) {
        throw new Exception("Execute proveedores_detalle: " . $stmtDet->error);
    }

    $stmtDet->close();

    // 3) Actualizar estado de la solicitud a 'aprobado'
    $stmtUpd = $db->prepare("UPDATE solicitudes_proveedores SET estado = 'aprobado' WHERE id = ? AND estado = 'pendiente'");
    if (!$stmtUpd) throw new Exception("Prepare UPDATE solicitud: " . $db->error);
    $stmtUpd->bind_param("i", $id);
    if (!$stmtUpd->execute()) throw new Exception("Execute UPDATE solicitud: " . $stmtUpd->error);
    if ($stmtUpd->affected_rows === 0) {
        throw new Exception("La solicitud ya fue procesada.");
    }
    $stmtUpd->close();

    // Commit
    if (!$db->commit()) throw new Exception("Commit falló: " . $db->error);

    // 4) Enviar email de aprobación (informando que puede iniciar sesión)
    $asunto = "Solicitud aprobada - GOAT Sports";
    $mensaje = "
        <p>Hola <strong>" . htmlspecialchars($nombre_contacto) . "</strong>,</p>
        <p>Tu solicitud para registrar el club <strong>" . htmlspecialchars($nombre_club) . "</strong> fue <strong>aprobada</strong>.</p>
        <p>Puedes iniciar sesión con tu email: <strong>" . htmlspecialchars($email) . "</strong>.</p>
        <p>Saludos,<br>GOAT Sports</p>
    ";
    if (!enviar_mail($email, $asunto, $mensaje)) {
        error_log("ERROR: mail() falló al enviar a $email");
    }


    // redirigir al listado
    header("Location: listado.php?msg=aprobado");
    exit;
} catch (Exception $e) {
    // rollback y mostrar error (útil para debug)
    $db->rollback();
    $errorMsg = $e->getMessage();
    die("Ocurrió un error durante la aprobación: " . htmlspecialchars($errorMsg));
}

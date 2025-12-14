<?php
// ======================================================================
// file: php/register_prove.php
// ======================================================================
session_start();
require_once __DIR__ . "/config.php"; // Debe exponer $conn (mysqli)

$errores = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Campos requeridos
    $nombre_contacto = trim($_POST['nombre_contacto'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $nombre_club     = trim($_POST['nombre_club'] ?? '');
    $telefono        = trim($_POST['telefono'] ?? '');
    $direccion       = trim($_POST['direccion'] ?? '');
    $ciudad          = 'Buenos Aires'; // fijo (no se muestra en UI)

    // Validaciones mínimas + límites de la tabla
    if ($nombre_contacto === '') { $errores[] = "Ingresá el nombre de contacto."; }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errores[] = "Email inválido."; }
    if ($nombre_club === '') { $errores[] = "Ingresá el nombre del club."; }
    if ($telefono === '') { $errores[] = "Ingresá el teléfono."; }
    if ($direccion === '') { $errores[] = "Ingresá la dirección."; }
    if (mb_strlen($nombre_contacto) > 100) { $errores[] = "Nombre de contacto demasiado largo."; }
    if (mb_strlen($email) > 120) { $errores[] = "Email demasiado largo."; }
    if (mb_strlen($nombre_club) > 120) { $errores[] = "Nombre del club demasiado largo."; }
    if (mb_strlen($telefono) > 60) { $errores[] = "Teléfono demasiado largo."; }
    if (mb_strlen($direccion) > 150) { $errores[] = "Dirección demasiado larga."; }

    // Duplicados por email (si ya existe solicitud no rechazada)
    if (!$errores) {
        if ($stmt = $conn->prepare("SELECT id, estado FROM solicitudes_proveedores WHERE email = ? ORDER BY id DESC LIMIT 1")) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($sid, $sestado);
            if ($stmt->fetch() && $sestado !== 'rechazado') {
                $errores[] = "Ya existe una solicitud para este email con estado '{$sestado}'.";
            }
            $stmt->close();
        } else {
            $errores[] = "Error interno (duplicados).";
        }
    }

    // Insert
    if (!$errores) {
        $sql = "
            INSERT INTO solicitudes_proveedores
            (nombre, email, password, nombre_club, telefono, direccion, barrio, ciudad, descripcion, estado)
            VALUES (?, ?, NULL, ?, ?, ?, NULL, ?, NULL, DEFAULT)
        ";
        if ($ins = $conn->prepare($sql)) {
            $ins->bind_param(
                "ssssss",
                $nombre_contacto,
                $email,
                $nombre_club,
                $telefono,
                $direccion,
                $ciudad
            );
            if ($ins->execute()) {
                $solicitud_id = $conn->insert_id;

                // Notificación a admin (usuario_id = 1)
                $usuarioAdmin = 1;
                $tituloNotif  = "Nueva solicitud de proveedor";
                $mensajeNotif = "El club '{$nombre_club}' envió una solicitud (#{$solicitud_id}).";
                $tipoNotif    = "solicitud_proveedor";

                if ($n = $conn->prepare("
                    INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo, leida, creada_en)
                    VALUES (?, ?, ?, ?, 0, NOW())
                ")) {
                    $n->bind_param("isss", $usuarioAdmin, $tituloNotif, $mensajeNotif, $tipoNotif);
                    $n->execute();
                    $n->close();
                }

                header("Location: confirmacion_envio.php");
                exit;
            } else {
                $errores[] = "No se pudo guardar la solicitud.";
            }
            $ins->close();
        } else {
            $errores[] = "Error interno (insert).";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="icon" type="image/png" href="/img/isotipo_negro.jpeg">
    <title>Goat Sport | Registro de Proveedor</title>
    <style>
        * { font-family: 'Arial', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
        body { display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 100vh; background: linear-gradient(135deg, #054a56ff, #1bab9dff); padding: 24px; }
        .logo-container { text-align: center; margin-bottom: 14px; }
        .logo-container img { width: 160px; }
        .register-box { width: 420px; background: #fff; padding: 28px; border-radius: 16px; box-shadow: 0px 8px 24px rgba(0,0,0,.15); }
        h1 { margin-bottom: 16px; font-size: 1.6rem; color: #054a56; text-align: center; }
        .desc { font-size: 14px; color: #335; text-align: center; margin-bottom: 14px; }
        .input-group { margin-bottom: 14px; display: flex; flex-direction: column; gap: 8px; }
        .input-group label { font-size: 14px; color: #054a56; }
        .input-group input {
            width: 100%; padding: 14px; border: 1px solid #ccc; border-radius: 10px; background: #f9f9f9; font-size: 15px; transition: border-color .3s;
        }
        .input-group input:focus { border-color: #1bab9dff; outline: none; background: #fff; }
        .btn { width: 100%; padding: 14px; background: #1bab9dff; color: #fff; border: 0; border-radius: 10px; font-size: 16px; cursor: pointer; transition: background .3s; margin-top: 6px; }
        .btn:hover { background: #14897f; }
        .error { background: #ffecec; color: #b00020; border: 1px solid #ffb3b3; padding: 10px 12px; border-radius: 10px; font-size: 14px; margin-bottom: 14px; }
        .bottom-link { text-align: center; margin-top: 14px; font-size: 14px; }
        .bottom-link a { color: #1bab9dff; text-decoration: none; }
        .bottom-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="logo-container">
        <img src="/img/logotipo.png" alt="Logo Padel">
    </div>

    <div class="register-box">
        <h1>Solicitud de registro</h1>
        <p class="desc">Completá tus datos y un administrador aprobará tu alta como proveedor.</p>

        <?php if (!empty($errores)): ?>
            <div class="error">
                <?php foreach ($errores as $e): ?>
                    <div>• <?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="on" novalidate>
            <div class="input-group">
                <label for="nombre_contacto">Nombre de contacto</label>
                <input id="nombre_contacto" name="nombre_contacto" type="text" maxlength="100" required value="<?= htmlspecialchars($_POST['nombre_contacto'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="input-group">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" maxlength="120" required value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="input-group">
                <label for="nombre_club">Nombre del club</label>
                <input id="nombre_club" name="nombre_club" type="text" maxlength="120" required value="<?= htmlspecialchars($_POST['nombre_club'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="input-group">
                <label for="telefono">Teléfono</label>
                <input id="telefono" name="telefono" type="text" maxlength="60" required value="<?= htmlspecialchars($_POST['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="input-group">
                <label for="direccion">Dirección</label>
                <input id="direccion" name="direccion" type="text" maxlength="150" required value="<?= htmlspecialchars($_POST['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <button type="submit" class="btn">Enviar solicitud</button>
        </form>

        <div class="bottom-link">
            ¿Ya tenés cuenta? <a href="login.php">Iniciar sesión</a>
        </div>
    </div>
</body>
</html>

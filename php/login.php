<?php
// ======================================================================
// file: php/login.php
// ======================================================================
session_start();
require_once __DIR__ . "/config.php"; // Debe exponer $conn (mysqli)

// Redirección si ya hay sesión activa
if (isset($_SESSION['usuario_id'], $_SESSION['rol'])) {
    switch ($_SESSION['rol']) {
        case 'admin':
            header("Location: ./admin/home_admin.php"); exit;
        case 'proveedor':
            header("Location: ./proveedor/home_proveedor.php"); exit;
        case 'recepcionista':
            header("Location: ./recepcionista/home_recepcionista.php"); exit;
        case 'cliente':
        default:
            header("Location: ./cliente/home_cliente.php"); exit;
    }
}

$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Completa email y contraseña.";
    } else {
        $sql = "SELECT user_id, email, contrasenia, rol FROM usuarios WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) === 1) {
                mysqli_stmt_bind_result($stmt, $user_id, $user_email, $user_password, $rol);
                mysqli_stmt_fetch($stmt);

                $ok = false;
                if (is_string($user_password) && str_starts_with($user_password, '$')) {
                    $ok = password_verify($password, $user_password);
                }
                if (!$ok) { $ok = hash_equals((string)$user_password, (string)$password); }

                if ($ok) {
                    $_SESSION['usuario_id']    = (int)$user_id;
                    $_SESSION['usuario_email'] = $user_email;
                    $_SESSION['rol']           = $rol;

                    if ($rol === 'recepcionista') {
                        // Cargar proveedor_id desde recepcionista_detalle
                        $prov = 0;
                        if ($q = $conn->prepare("SELECT proveedor_id FROM recepcionista_detalle WHERE recepcionista_id = ? LIMIT 1")) {
                            $q->bind_param("i", $user_id);
                            $q->execute();
                            $q->bind_result($prov_id);
                            if ($q->fetch()) { $prov = (int)$prov_id; }
                            $q->close();
                        }
                        if ($prov <= 0) {
                            // por qué: sin vínculo no puede listar canchas ni operar
                            $_SESSION['flash_error'] = 'Tu usuario de recepción no está vinculado a un proveedor. Contactá al admin.';
                            header("Location: ./recepcionista/home_recepcionista.php"); exit;
                        }
                        $_SESSION['proveedor_id'] = $prov;
                        header("Location: ./recepcionista/home_recepcionista.php"); exit;
                    }

                    // Resto de roles
                    switch ($rol) {
                        case 'admin':     header("Location: ./admin/home_admin.php"); break;
                        case 'proveedor': header("Location: ./proveedor/home_proveedor.php"); break;
                        default:          header("Location: ./cliente/home_cliente.php"); break;
                    }
                    exit;
                } else {
                    $error = "Credenciales incorrectas.";
                }
            } else {
                $error = "Credenciales incorrectas.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "Error al preparar la consulta.";
        }
    }
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="icon" type="image/png" href="/img/isotipo_negro.jpeg">
    <title>Padel Alquiler | Login</title>
    <style>
        * { font-family: 'Arial', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
        body { display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100vh; background: linear-gradient(135deg, #054a56ff, #1bab9dff); }
        .logo-container { text-align: center; margin-bottom: 10px; }
        .logo-container img { width: 160px; }
        .login-box { width: 350px; height: 420px; background: white; padding: 35px 30px; border-radius: 16px; box-shadow: 0px 8px 24px rgba(0, 0, 0, 0.15); text-align: center; }
        h1 { margin-bottom: 20px; font-size: 1.7rem; color: #054a56; }
        .input-group { position: relative; margin-bottom: 20px; }
        .input-group input { width: 100%; padding: 15px 42px 15px 15px; border: 1px solid #ccc; border-radius: 10px; font-size: 16px; background-color: #f9f9f9; transition: border-color 0.3s; }
        .input-group input:focus { border-color: #1bab9dff; outline: none; background-color: #fff; }
        .input-group svg { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; fill: #999; }
        .btn { width: 100%; padding: 14px; background-color: #1bab9dff; color: white; border: none; border-radius: 10px; font-size: 16px; cursor: pointer; transition: background 0.3s ease; margin-top: 10px; }
        .btn:hover { background-color: #14897f; }
        .extra-links { margin-top: 18px; font-size: 14px; display: flex; gap: 12px; justify-content: center; }
        .extra-links a { color: #1bab9dff; text-decoration: none; }
        .extra-links a:hover { text-decoration: underline; }
        .error { color: #b00020; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="logo-container">
        <img src="/img/logotipo.png" alt="Logo Padel">
    </div>

    <div class="login-box">
        <h1>Iniciar Sesión</h1>

        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="POST" autocomplete="on" novalidate>
            <div class="input-group">
                <input type="email" name="email" placeholder="Correo electrónico" required>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 13.065 0 6V4l12 7 12-7v2l-12 7.065z"/></svg>
            </div>

            <div class="input-group">
                <input type="password" name="password" placeholder="Contraseña" required>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 9V7a5 5 0 0 0-10 0v2H5v14h14V9h-2zm-6-2a3 3 0 0 1 6 0v2H11V7z"/></svg>
            </div>

            <button type="submit" class="btn">Ingresar</button>
        </form>

        <div class="extra-links">
            <a href="register.php">¿No tienes una cuenta?</a>
            <a href="forgot.php">¿Olvidaste tu contraseña?</a>
        </div>
    </div>
</body>
</html>
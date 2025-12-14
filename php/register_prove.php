<?php
session_start();
include("config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recibir datos
    $nombre_contacto = $_POST['nombre_contacto'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $nombre_club = $_POST['nombre_club'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];
    $ciudad = $_POST['ciudad'];
    $descripcion = $_POST['descripcion'];

    // Hashear contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // ---------------------------------------------
    // 1) Guardar la solicitud del proveedor
    // ---------------------------------------------
    $insertSolicitud = $conn->prepare("
        INSERT INTO solicitudes_proveedores 
        (nombre_contacto, email, password, nombre_club, telefono, direccion, ciudad, descripcion, estado)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
    ");

    $insertSolicitud->bind_param("ssssssss",
        $nombre_contacto,
        $email,
        $password_hash,
        $nombre_club,
        $telefono,
        $direccion,
        $ciudad,
        $descripcion
    );

    $insertSolicitud->execute();

    // ID de la solicitud
    $solicitud_id = $conn->insert_id;

    // ---------------------------------------------
    // 2) Crear NOTIFICACIÓN
    // ---------------------------------------------
    $tituloNotif = "Nueva solicitud de proveedor";
    $mensajeNotif = "El proveedor '$nombre_club' envió una solicitud de registro.";
    $tipoNotif = "solicitud_proveedor";

    $usuarioAdmin = 1; // ID del admin

    $insertNotif = $conn->prepare("
        INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo, leida, creada_en)
        VALUES (?, ?, ?, ?, 0, NOW())
    ");

    $insertNotif->bind_param("isss", 
        $usuarioAdmin,
        $tituloNotif,
        $mensajeNotif,
        $tipoNotif
    );

    $insertNotif->execute();

    // ---------------------------------------------
    // 3) Redirigir a pantalla de éxito
    // ---------------------------------------------
    header("Location: solicitud_enviada_prove.php");
    exit;
}
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registro de Proveedor</title>

<style>
/* ======== ESTILO GENERAL ======== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: linear-gradient(135deg, #06606d, #23b9aa);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 25px;
}

/* ======== LOGO SUPERIOR (FUERA DE LA CAJA) ======== */
.logo-large {
    width: 160px;
    margin-bottom: 20px;
}

/* ======== CONTENEDOR DE REGISTRO ======== */
.register-box {
    width: 850px;
    background: #ffffff;
    padding: 40px 45px;
    border-radius: 18px;
    box-shadow: 0px 12px 28px rgba(0, 0, 0, 0.18);
    animation: fadeIn 0.4s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* TÍTULO */
.register-box h1 {
    color: #054a56;
    font-size: 2rem;
    text-align: center;
    margin-bottom: 25px;
    padding-bottom: 10px;
}

/* ======== GRID (2 COLUMNAS) ======== */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 35px;
}

/* SUBTÍTULOS */
.section-title {
    font-weight: bold;
    color: #0a5d66;
    font-size: 1.2rem;
    margin-bottom: 12px;
}

/* INPUTS */
.input-group {
    margin-bottom: 18px;
}

.input-group input,
.input-group textarea {
    width: 100%;
    padding: 14px;
    border-radius: 10px;
    border: 1px solid #cfd8dc;
    background-color: #f3f7f8;
    font-size: 15px;
    transition: 0.3s;
}

.input-group textarea {
    resize: none;
    height: 85px;
}

.input-group input:focus,
.input-group textarea:focus {
    border-color: #18a99a;
    background: #ffffff;
    box-shadow: 0 0 6px rgba(27, 171, 157, 0.25);
    outline: none;
}

/* BOTÓN */
.btn {
    width: 100%;
    background: #21b1a3;
    border: none;
    color: white;
    padding: 16px;
    border-radius: 12px;
    font-size: 17px;
    cursor: pointer;
    letter-spacing: 0.6px;
    transition: 0.3s ease;
}

.btn:hover {
    background: #148f83;
}

/* LINK INFERIOR */
.bottom-link {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
}

.bottom-link a {
    color: #0e7067;
    text-decoration: none;
}

.bottom-link a:hover {
    text-decoration: underline;
}

</style>
</head>

<body>

<!-- LOGO FUERA DE LA CAJA -->
<img src="/img/logotipo.png" class="logo-large" alt="Logo">

<div class="register-box">

    <h1> Solicitud de registro de Proveedor</h1>

    <form action="register_prove.php" method="POST">

        <div class="form-grid">

            <!-- ================== COLUMNA 1: DATOS DE CONTACTO ================== -->
            <div>
                <div class="section-title">Datos de contacto</div>

                <div class="input-group">
                    <input type="text" name="nombre_contacto" placeholder="Nombre de contacto" required>
                </div>

                <div class="input-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>

                <div class="input-group">
                    <input type="password" name="password" placeholder="Contraseña" required>
                </div>
            </div>

            <!-- ================== COLUMNA 2: DATOS DEL CLUB ================== -->
            <div>
                <div class="section-title">Datos del club</div>

                <div class="input-group">
                    <input type="text" name="nombre_club" placeholder="Nombre del club" required>
                </div>

                <div class="input-group">
                    <input type="text" name="telefono" placeholder="Teléfono" required>
                </div>

                <div class="input-group">
                    <input type="text" name="direccion" placeholder="Dirección" required>
                </div>

                <div class="input-group">
                    <input type="text" name="ciudad" placeholder="Ciudad" required>
                </div>

                <div class="input-group">
                    <textarea name="descripcion" placeholder="Descripción del club"></textarea>
                </div>
            </div>

        </div>

        <button type="submit" class="btn">Registrar</button>

    </form>

    <div class="bottom-link">
        ¿Ya tenés cuenta? <a href="login.php">Iniciar sesión</a>
    </div>

</div>

</body>
</html>

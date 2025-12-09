<?php
session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? null) !== 'recepcionista') {
    header("Location: /php/login.php");
    exit();
}

<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'proveedor') {
    header("Location: login.php");
    exit();
}

<?php
// ======================================================================
// file: php/recepcionista/includes/header.php  (REEMPLAZAR COMPLETO)
// ======================================================================
require_once __DIR__ . '/auth.php';           // asegura rol = recepcionista
require_once __DIR__ . '/../../config.php';   // $conn

// Fallback: si la sesión no tiene proveedor_id, intenta cargarlo
if (empty($_SESSION['proveedor_id'])) {
    $prov = 0;
    if (!empty($_SESSION['usuario_id'])) {
        if ($q = $conn->prepare("SELECT proveedor_id FROM recepcionista_detalle WHERE recepcionista_id = ? LIMIT 1")) {
            $q->bind_param("i", $_SESSION['usuario_id']);
            $q->execute();
            $q->bind_result($prov_id);
            if ($q->fetch()) { $prov = (int)$prov_id; }
            $q->close();
        }
    }
    if ($prov > 0) {
        $_SESSION['proveedor_id'] = $prov; // por qué: habilita filtrar canchas/pagos/reservas correctamente
    } else {
        // bloquea la vista con un mensaje claro
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Recepción | GoatSport</title>
        <link rel="icon" type="image/png" href="/img/isotipo_negro.jpeg">
        <link rel="stylesheet" href="../../../css/admin.css"><link rel="stylesheet" href="../../../css/adminCRUD.css"></head><body>';
        echo '<main><div class="section"><div class="section-header"><h2>Error de configuración</h2></div>';
        echo '<p>Este usuario de recepción no está vinculado a ningún proveedor. Pide al administrador que complete <code>recepcionista_detalle</code>.</p>';
        echo '<a href="/php/logout.php" class="btn-add btn-outline">Cerrar sesión</a></div></main></body></html>';
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Panel Recepcionista | GoatSport</title>
  <link rel="icon" type="image/png" href="/img/isotipo_negro.jpeg">
  <link rel="stylesheet" href="../../../css/admin.css">
  <link rel="stylesheet" href="../../../css/adminCRUD.css">
</head>
<body>

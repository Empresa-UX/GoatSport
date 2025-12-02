<?php
include '../../config.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'proveedor') {
    header("Location: ../login.php");
    exit();
}

$proveedor_id = $_SESSION['usuario_id'];
$action = $_POST['action'] ?? '';

// ADD / EDIT
if ($action === 'add' || $action === 'edit') {

    $promocion_id = $_POST['promocion_id'] ?? null;
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $cancha_id = $_POST['cancha_id'] !== '' ? (int)$_POST['cancha_id'] : null;
    $porcentaje_descuento = (float)$_POST['porcentaje_descuento'];
    $minima_reservas = (int)$_POST['minima_reservas'];
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;
    $fecha_fin = $_POST['fecha_fin'] ?? null;
    $hora_inicio = $_POST['hora_inicio'] ?: null;
    $hora_fin = $_POST['hora_fin'] ?: null;
    $dias_semana = isset($_POST['dias_semana']) ? implode(',', $_POST['dias_semana']) : null;
    $activa = isset($_POST['activa']) ? (int)$_POST['activa'] : 1;

    if ($action === 'add') {
        $sql = "
            INSERT INTO promociones
            (proveedor_id, cancha_id, nombre, descripcion, porcentaje_descuento,
             fecha_inicio, fecha_fin, hora_inicio, hora_fin, dias_semana,
             minima_reservas, activa)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "iissdsssssis",
            $proveedor_id, $cancha_id, $nombre, $descripcion, $porcentaje_descuento,
            $fecha_inicio, $fecha_fin, $hora_inicio, $hora_fin, $dias_semana,
            $minima_reservas, $activa
        );

    } else { // EDIT
        $sql = "
            UPDATE promociones
            SET cancha_id = ?, nombre = ?, descripcion = ?, porcentaje_descuento = ?,
                fecha_inicio = ?, fecha_fin = ?, hora_inicio = ?, hora_fin = ?,
                dias_semana = ?, minima_reservas = ?, activa = ?
            WHERE promocion_id = ? AND proveedor_id = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "issdsssssiisi",
            $cancha_id, $nombre, $descripcion, $porcentaje_descuento,
            $fecha_inicio, $fecha_fin, $hora_inicio, $hora_fin,
            $dias_semana, $minima_reservas, $activa,
            $promocion_id, $proveedor_id
        );
    }

    $stmt->execute();
    $stmt->close();

    header("Location: promociones.php");
    exit();
}

// DELETE
if ($action === 'delete') {
    $promocion_id = (int)$_POST['promocion_id'];

    $sql = "DELETE FROM promociones WHERE promocion_id = ? AND proveedor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $promocion_id, $proveedor_id);
    $stmt->execute();
    $stmt->close();

    header("Location: promociones.php");
    exit();
}

header("Location: promociones.php");
exit();

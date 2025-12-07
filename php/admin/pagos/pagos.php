<?php
include './../includes/header.php';
include './../includes/sidebar.php';
include './../includes/cards.php';
include './../../config.php';
?>

<div class="section">
    <div class="section-header">
        <h2>Pagos</h2>
        <button onclick="location.href='pagosForm.php'" class="btn-add">Registrar pago</button>
    </div>

    <style>
        .pill-metodo {
            display:inline-block;
            padding:3px 8px;
            border-radius:999px;
            font-size:12px;
            font-weight:bold;
        }
        .pill-metodo.club {
            background:#f1f5f9;
            color:#1e293b;
        }
        .pill-metodo.mercado_pago {
            background:#e0f2fe;
            color:#0369a1;
        }
        .pill-metodo.tarjeta {
            background:#ecfdf5;
            color:#047857;
        }
    </style>

    <table>
        <tr>
            <th>ID</th>
            <th>Reserva</th>
            <th>Jugador</th>
            <th>Proveedor / Cancha</th>
            <th>Método</th>
            <th>Monto</th>
            <th>Estado</th>
            <th>Fecha de pago</th>
            <th>Acciones</th>
        </tr>

        <?php
        $sql = "
            SELECT 
                p.pago_id,
                p.reserva_id,
                p.monto,
                p.metodo,
                p.estado,
                p.fecha_pago,
                u.nombre AS jugador,
                c.nombre AS cancha,
                prov.nombre AS proveedor
            FROM pagos p
            INNER JOIN usuarios u ON p.jugador_id = u.user_id
            LEFT JOIN reservas r ON p.reserva_id = r.reserva_id
            LEFT JOIN canchas c ON r.cancha_id = c.cancha_id
            LEFT JOIN usuarios prov ON c.proveedor_id = prov.user_id
            ORDER BY p.fecha_pago DESC, p.pago_id DESC
        ";

        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
                // Estado visual
                $estadoClass = '';
                if ($row['estado'] === 'pagado')   $estadoClass = 'status-available';
                elseif ($row['estado'] === 'pendiente') $estadoClass = 'status-pending';
                elseif ($row['estado'] === 'cancelado') $estadoClass = 'status-unavailable';

                // Método visual
                $metodo = $row['metodo'];
                $metodoClass = 'club';
                if ($metodo === 'mercado_pago') $metodoClass = 'mercado_pago';
                if ($metodo === 'tarjeta')      $metodoClass = 'tarjeta';

                // Fecha formateada
                $fechaPago = '-';
                if (!empty($row['fecha_pago'])) {
                    $fechaPago = date('d/m/Y H:i', strtotime($row['fecha_pago']));
                }
                ?>
                <tr>
                    <td><?= (int)$row['pago_id'] ?></td>
                    <td># <?= (int)$row['reserva_id'] ?></td>
                    <td><?= htmlspecialchars($row['jugador']) ?></td>
                    <td>
                        <?php if ($row['proveedor']): ?>
                            <div><strong><?= htmlspecialchars($row['proveedor']) ?></strong></div>
                        <?php endif; ?>
                        <?php if ($row['cancha']): ?>
                            <div style="font-size:12px; color:#555;">
                                <?= htmlspecialchars($row['cancha']) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="pill-metodo <?= $metodoClass ?>">
                            <?= ucfirst(str_replace('_',' ', $metodo)) ?>
                        </span>
                    </td>
                    <td>$<?= number_format($row['monto'], 2, ',', '.') ?></td>
                    <td><span class="status-pill <?= $estadoClass ?>"><?= ucfirst($row['estado']) ?></span></td>
                    <td><?= $fechaPago ?></td>
                    <td>
                        <button class="btn-action edit"
                            onclick="location.href='pagosForm.php?pago_id=<?= $row['pago_id'] ?>'">✏️</button>

                        <form method="POST" action="pagosAction.php" style="display:inline-block;"
                            onsubmit="return confirm('¿Seguro que quieres eliminar este pago?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="pago_id" value="<?= $row['pago_id'] ?>">
                            <button type="submit" class="btn-action delete">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php
            endwhile;
        else:
            ?>
            <tr>
                <td colspan="9" style="text-align:center;">No hay pagos registrados</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php include './../includes/footer.php'; ?>

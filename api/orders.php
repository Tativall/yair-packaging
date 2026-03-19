<?php
// =====================================================
// api/orders.php — API de pedidos
// =====================================================
session_start();
require_once '../config/database.php';

$action = $_GET['action'] ?? 'list';
$db     = getDB();

switch ($action) {

    // ── Crear pedido (público) ────────────────────────
    case 'create':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $nombre   = trim($input['nombre']   ?? '');
        $telefono = trim($input['telefono'] ?? '');
        if (!$nombre || !$telefono) jsonResponse(['error' => 'Nombre y teléfono son obligatorios'], 400);

        $codigo = 'ORD-' . strtoupper(substr(uniqid(), -6));

        $stmt = $db->prepare("INSERT INTO pedidos (codigo, nombre, telefono, email, empresa, producto_nombre, cantidad, medida, notas, via, ip)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $codigo,
            $nombre,
            $telefono,
            trim($input['email']    ?? ''),
            trim($input['empresa']  ?? ''),
            trim($input['producto'] ?? ''),
            trim($input['cantidad'] ?? ''),
            trim($input['medida']   ?? ''),
            trim($input['notas']    ?? ''),
            trim($input['via']      ?? 'web'),
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);

        // Obtener datos del negocio para WhatsApp/email
        $stmtS = $db->query("SELECT clave, valor FROM settings WHERE clave IN ('whatsapp','email_pedidos','nombre_negocio')");
        $settings = [];
        foreach ($stmtS->fetchAll() as $r) $settings[$r['clave']] = $r['valor'];

        $wa  = $settings['whatsapp']      ?? '595981000000';
        $em  = $settings['email_pedidos'] ?? '';
        $biz = $settings['nombre_negocio'] ?? 'Yair Packaging';

        $msgWa  = "*Nuevo pedido — {$biz}*\n\n";
        $msgWa .= "Código: {$codigo}\nCliente: {$nombre}\nTeléfono: {$telefono}";
        if (!empty($input['empresa']))  $msgWa .= "\nEmpresa: " . $input['empresa'];
        $msgWa .= "\n\nProducto: " . ($input['producto'] ?? '—');
        $msgWa .= "\nCantidad: "  . ($input['cantidad'] ?? '—');
        if (!empty($input['medida'])) $msgWa .= "\nMedida: " . $input['medida'];
        if (!empty($input['notas']))  $msgWa .= "\nComentarios: " . $input['notas'];

        $waUrl    = "https://wa.me/{$wa}?text=" . urlencode($msgWa);
        $emailUrl = '';
        if ($em) {
            $subject  = "Nuevo pedido #{$codigo} — {$nombre}";
            $body     = "Nuevo pedido recibido en {$biz}\n\nCódigo: {$codigo}\nCliente: {$nombre}\nTeléfono: {$telefono}\n"
                      . "Empresa: " . ($input['empresa'] ?? '—') . "\n"
                      . "Email cliente: " . ($input['email'] ?? '—') . "\n\n"
                      . "Producto: " . ($input['producto'] ?? '—') . "\n"
                      . "Cantidad: "  . ($input['cantidad'] ?? '—') . "\n"
                      . "Medida: "    . ($input['medida']   ?? '—') . "\n"
                      . "Comentarios: " . ($input['notas'] ?? '—');
            $emailUrl = "mailto:{$em}?subject=" . rawurlencode($subject) . "&body=" . rawurlencode($body);
        }

        jsonResponse([
            'success'      => true,
            'codigo'       => $codigo,
            'whatsapp_url' => $waUrl,
            'email_url'    => $emailUrl,
            'message'      => 'Pedido recibido correctamente'
        ]);
        break;

    // ── Listar pedidos (solo admin) ───────────────────
    case 'list':
        if (empty($_SESSION['admin_logged'])) jsonResponse(['error' => 'No autorizado'], 401);
        $stmt = $db->query("SELECT id, codigo, nombre, telefono, email, empresa, producto_nombre, cantidad, medida, notas, via, estado,
                                   strftime('%d/%m/%Y %H:%M', created_at) as fecha
                            FROM pedidos ORDER BY created_at DESC");
        jsonResponse(['success' => true, 'orders' => $stmt->fetchAll()]);
        break;

    // ── Estadísticas para dashboard ───────────────────
    case 'stats':
        if (empty($_SESSION['admin_logged'])) jsonResponse(['error' => 'No autorizado'], 401);
        $stats = [];
        $stats['total_pedidos']  = $db->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
        $stats['pedidos_nuevos'] = $db->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'nuevo'")->fetchColumn();
        $stats['total_productos']= $db->query("SELECT COUNT(*) FROM productos WHERE activo = 1")->fetchColumn();
        $stats['pedidos_hoy']    = $db->query("SELECT COUNT(*) FROM pedidos WHERE DATE(created_at) = date('now')")->fetchColumn();

        // Últimos 5
        $stmt = $db->query("SELECT codigo, nombre, producto_nombre, estado, strftime('%d/%m %H:%M', created_at) as fecha
                            FROM pedidos ORDER BY created_at DESC LIMIT 5");
        $stats['recientes'] = $stmt->fetchAll();

        jsonResponse(['success' => true, 'stats' => $stats]);
        break;

    // ── Cambiar estado ────────────────────────────────
    case 'status':
        if (empty($_SESSION['admin_logged'])) jsonResponse(['error' => 'No autorizado'], 401);
        $input   = json_decode(file_get_contents('php://input'), true) ?? [];
        $id      = (int)($input['id']     ?? 0);
        $status  = trim($input['status']  ?? '');
        $allowed = ['nuevo','leido','en_proceso','completado','cancelado'];
        if (!$id || !in_array($status, $allowed)) jsonResponse(['error' => 'Datos inválidos'], 400);
        $db->prepare("UPDATE pedidos SET estado = ? WHERE id = ?")->execute([$status, $id]);
        jsonResponse(['success' => true]);
        break;

    // ── Eliminar pedido ───────────────────────────────
    case 'delete':
        if (empty($_SESSION['admin_logged'])) jsonResponse(['error' => 'No autorizado'], 401);
        $id = (int)($_GET['id'] ?? 0);
        $db->prepare("DELETE FROM pedidos WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Acción no válida'], 400);
}

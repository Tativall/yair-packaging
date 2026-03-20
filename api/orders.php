<?php
require_once '../config/database.php';
startSession();

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';
$db     = getDB();

switch ($action) {
    case 'create':
        $input    = json_decode(file_get_contents('php://input'), true) ?? [];
        $nombre   = trim($input['nombre']   ?? '');
        $telefono = trim($input['telefono'] ?? '');
        if (!$nombre || !$telefono) jsonResponse(['error' => 'Nombre y teléfono son obligatorios'], 400);

        $codigo = 'ORD-' . strtoupper(substr(uniqid(), -6));
        $stmt   = $db->prepare("INSERT INTO pedidos (codigo,nombre,telefono,email,empresa,producto_nombre,cantidad,medida,notas,via,ip) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$codigo, $nombre, $telefono, trim($input['email']??''), trim($input['empresa']??''), trim($input['producto']??''), trim($input['cantidad']??''), trim($input['medida']??''), trim($input['notas']??''), trim($input['via']??'web'), $_SERVER['REMOTE_ADDR']??'']);

        $rows = $db->query("SELECT clave, valor FROM settings WHERE clave IN ('whatsapp','email_pedidos','nombre_negocio')")->fetchAll();
        $s    = [];
        foreach ($rows as $r) $s[$r['clave']] = $r['valor'];
        $wa  = $s['whatsapp'] ?? '595981000000';
        $em  = $s['email_pedidos'] ?? '';
        $biz = $s['nombre_negocio'] ?? 'Yair Packaging';

        $msg = "*Nuevo pedido — {$biz}*\n\nCódigo: {$codigo}\nCliente: {$nombre}\nTeléfono: {$telefono}";
        if (!empty($input['empresa']))  $msg .= "\nEmpresa: ".$input['empresa'];
        $msg .= "\n\nProducto: ".($input['producto']??'—')."\nCantidad: ".($input['cantidad']??'—');
        if (!empty($input['medida'])) $msg .= "\nMedida: ".$input['medida'];
        if (!empty($input['notas']))  $msg .= "\nComentarios: ".$input['notas'];

        $waUrl = "https://wa.me/{$wa}?text=".urlencode($msg);
        $emailUrl = '';
        if ($em) {
            $emailUrl = "mailto:{$em}?subject=".rawurlencode("Nuevo pedido #{$codigo}")."&body=".rawurlencode("Cliente: {$nombre}\nTeléfono: {$telefono}\nProducto: ".($input['producto']??'—')."\nCantidad: ".($input['cantidad']??'—')."\nComentarios: ".($input['notas']??'—'));
        }
        jsonResponse(['success'=>true,'codigo'=>$codigo,'whatsapp_url'=>$waUrl,'email_url'=>$emailUrl]);
        break;

    case 'list':
        if (empty($_SESSION['admin_logged'])) jsonResponse(['error'=>'No autorizado'],401);
        $stmt = $db->query("SELECT id,codigo,nombre,telefono,email,empresa,producto_nombre,cantidad,medida,notas,via,estado, strftime('%d/%m/%Y %H:%M',created_at) as fecha FROM pedidos ORDER BY created_at DESC");
        jsonResponse(['success'=>true,'orders'=>$stmt->fetchAll()]);
        break;

    case 'stats':
        if (empty($_SESSION['admin_logged'])) jsonResponse(['error'=>'No autorizado'],401);
        $stats = [
            'total_pedidos'   => $db->query("SELECT COUNT(*) FROM pedidos")->fetchColumn(),
            'pedidos_nuevos'  => $db->query("SELECT COUNT(*) FROM pedidos WHERE estado='nuevo'")->fetchColumn(),
            'total_productos' => $db->query("SELECT COUNT(*) FROM productos WHERE activo=1")->fetchColumn(),
            'pedidos_hoy'     => $db->query("SELECT COUNT(*) FROM pedidos WHERE date(created_at)=date('now')")->fetchColumn(),
        ];
        $recientes = $db->query("SELECT codigo,nombre,producto_nombre,estado,strftime('%d/%m %H:%M',created_at) as fecha FROM pedidos ORDER BY created_at DESC LIMIT 5")->fetchAll();
        $stats['recientes'] = $recientes;
        jsonResponse(['success'=>true,'stats'=>$stats]);
        break;

    case 'status':
        if (empty($_SESSION['admin_logged'])) jsonResponse(['error'=>'No autorizado'],401);
        $input   = json_decode(file_get_contents('php://input'), true) ?? [];
        $id      = (int)($input['id'] ?? 0);
        $status  = trim($input['status'] ?? '');
        $allowed = ['nuevo','leido','en_proceso','completado','cancelado'];
        if (!$id || !in_array($status, $allowed)) jsonResponse(['error'=>'Datos inválidos'],400);
        $db->prepare("UPDATE pedidos SET estado=? WHERE id=?")->execute([$status,$id]);
        jsonResponse(['success'=>true]);
        break;

    case 'delete':
        if (empty($_SESSION['admin_logged'])) jsonResponse(['error'=>'No autorizado'],401);
        $id = (int)($_GET['id'] ?? 0);
        $db->prepare("DELETE FROM pedidos WHERE id=?")->execute([$id]);
        jsonResponse(['success'=>true]);
        break;

    default:
        jsonResponse(['error'=>'Acción no válida'],400);
}

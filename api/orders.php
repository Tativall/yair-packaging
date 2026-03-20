<?php
require_once '../config/supabase.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action']??'list';

switch ($action) {
    case 'create':
        $input    = json_decode(file_get_contents('php://input'),true)??[];
        $nombre   = trim($input['nombre']??'');
        $telefono = trim($input['telefono']??'');
        if (!$nombre||!$telefono) jsonResponse(['error'=>'Nombre y teléfono obligatorios'],400);

        $codigo = 'ORD-'.strtoupper(substr(uniqid(),-6));
        $data   = [
            'codigo'           => $codigo,
            'nombre'           => $nombre,
            'telefono'         => $telefono,
            'email'            => trim($input['email']??''),
            'empresa'          => trim($input['empresa']??''),
            'producto_nombre'  => trim($input['producto']??''),
            'cantidad'         => trim($input['cantidad']??''),
            'medida'           => trim($input['medida']??''),
            'notas'            => trim($input['notas']??''),
            'via'              => trim($input['via']??'web'),
            'estado'           => 'nuevo',
            'ip'               => $_SERVER['REMOTE_ADDR']??'',
        ];
        supabase('POST','pedidos',$data);

        $rows = supabase('GET','settings?clave=in.(whatsapp,email_pedidos,nombre_negocio)&select=clave,valor');
        $s=[]; foreach($rows as $r) $s[$r['clave']]=$r['valor'];
        $wa=$s['whatsapp']??'595981000000'; $em=$s['email_pedidos']??''; $biz=$s['nombre_negocio']??'Yair Packaging';

        $msg = "*Nuevo pedido — {$biz}*\n\nCódigo: {$codigo}\nCliente: {$nombre}\nTeléfono: {$telefono}";
        if (!empty($input['empresa']))  $msg .= "\nEmpresa: ".$input['empresa'];
        $msg .= "\n\nProducto: ".($input['producto']??'—')."\nCantidad: ".($input['cantidad']??'—');
        if (!empty($input['medida'])) $msg .= "\nMedida: ".$input['medida'];
        if (!empty($input['notas']))  $msg .= "\nComentarios: ".$input['notas'];

        $waUrl = "https://wa.me/{$wa}?text=".urlencode($msg);
        $emailUrl = $em ? "mailto:{$em}?subject=".rawurlencode("Pedido #{$codigo}")."&body=".rawurlencode($msg) : '';
        jsonResponse(['success'=>true,'codigo'=>$codigo,'whatsapp_url'=>$waUrl,'email_url'=>$emailUrl]);
        break;

    case 'list':
        if (!isAdminLoggedIn()) jsonResponse(['error'=>'No autorizado'],401);
        $rows = supabase('GET','pedidos?select=*&order=created_at.desc');
        // Formatear fecha
        $rows = array_map(function($r){
            if (!empty($r['created_at'])) {
                $dt = new DateTime($r['created_at']);
                $r['fecha'] = $dt->format('d/m/Y H:i');
            }
            return $r;
        }, $rows);
        jsonResponse(['success'=>true,'orders'=>$rows]);
        break;

    case 'stats':
        if (!isAdminLoggedIn()) jsonResponse(['error'=>'No autorizado'],401);
        $todos    = supabase('GET','pedidos?select=id,estado,created_at');
        $prods    = supabase('GET','productos?select=id&activo=eq.true');
        $hoy      = date('Y-m-d');
        $stats = [
            'total_pedidos'   => count($todos),
            'pedidos_nuevos'  => count(array_filter($todos, fn($r)=>$r['estado']==='nuevo')),
            'total_productos' => count($prods),
            'pedidos_hoy'     => count(array_filter($todos, fn($r)=>str_starts_with($r['created_at']??'',$hoy))),
        ];
        $recientes = supabase('GET','pedidos?select=codigo,nombre,producto_nombre,estado,created_at&order=created_at.desc&limit=5');
        $stats['recientes'] = array_map(function($r){
            if (!empty($r['created_at'])){$dt=new DateTime($r['created_at']);$r['fecha']=$dt->format('d/m H:i');}
            return $r;
        }, $recientes);
        jsonResponse(['success'=>true,'stats'=>$stats]);
        break;

    case 'status':
        if (!isAdminLoggedIn()) jsonResponse(['error'=>'No autorizado'],401);
        $input   = json_decode(file_get_contents('php://input'),true)??[];
        $id      = (int)($input['id']??0);
        $status  = trim($input['status']??'');
        $allowed = ['nuevo','leido','en_proceso','completado','cancelado'];
        if (!$id||!in_array($status,$allowed)) jsonResponse(['error'=>'Inválido'],400);
        supabase('PATCH',"pedidos?id=eq.$id",['estado'=>$status]);
        jsonResponse(['success'=>true]);
        break;

    case 'delete':
        if (!isAdminLoggedIn()) jsonResponse(['error'=>'No autorizado'],401);
        supabase('DELETE','pedidos?id=eq.'.(int)($_GET['id']??0));
        jsonResponse(['success'=>true]);
        break;

    default: jsonResponse(['error'=>'Acción no válida'],400);
}

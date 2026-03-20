<?php
require_once '../config/supabase.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(200);exit;}

$readOnly = ['list','get','settings'];
$action   = $_GET['action'] ?? $_POST['action'] ?? 'list';
if (!in_array($action,$readOnly) && !isAdminLoggedIn()) jsonResponse(['error'=>'No autorizado'],401);

switch ($action) {
    case 'list':
        $active = ($_GET['active']??'')==='1';
        $q = 'productos?select=*,categorias(id,nombre,icono,color,orden)&order=id.asc';
        if ($active) $q .= '&activo=eq.true';
        $rows = supabase('GET', $q);
        $out = array_map(function($r){
            $r['categoria'] = $r['categorias']['nombre'] ?? '';
            $r['categoria_id'] = $r['categorias']['id'] ?? null;
            $r['cat_icono'] = $r['categorias']['icono'] ?? '';
            $r['cat_color'] = $r['categorias']['color'] ?? '';
            unset($r['categorias']);
            return $r;
        }, $rows);
        jsonResponse(['success'=>true,'products'=>$out]);
        break;
    case 'get':
        $id   = (int)($_GET['id']??0);
        $rows = supabase('GET', "productos?id=eq.$id&select=*,categorias(id,nombre)&limit=1");
        if (!$rows) jsonResponse(['error'=>'No encontrado'],404);
        $r = $rows[0]; $r['categoria']=$r['categorias']['nombre']??''; $r['categoria_id']=$r['categorias']['id']??null; unset($r['categorias']);
        jsonResponse(['success'=>true,'product'=>$r]);
        break;
    case 'create':
        $nombre = trim($_POST['nombre']??'');
        $catId  = (int)($_POST['categoria_id']??0);
        if (!$nombre||!$catId) jsonResponse(['error'=>'Nombre y categoría obligatorios'],400);
        $foto = '';
        if (!empty($_FILES['foto']['name'])&&$_FILES['foto']['error']===0) $foto=uploadToSupabase($_FILES['foto']);
        $data = [
            'nombre'       => $nombre,
            'descripcion'  => trim($_POST['descripcion']??''),
            'categoria_id' => $catId,
            'precio'       => (float)($_POST['precio']??0),
            'unidad'       => trim($_POST['unidad']??'unid'),
            'medidas'      => trim($_POST['medidas']??''),
            'etiqueta'     => trim($_POST['etiqueta']??''),
            'emoji'        => trim($_POST['emoji']??'📦'),
            'foto'         => $foto,
        ];
        $res = supabase('POST','productos',$data);
        jsonResponse(['success'=>true,'id'=>$res[0]['id']??0,'message'=>'Producto creado']);
        break;
    case 'update':
        $id     = (int)($_POST['id']??0);
        $nombre = trim($_POST['nombre']??'');
        $catId  = (int)($_POST['categoria_id']??0);
        if (!$id||!$nombre||!$catId) jsonResponse(['error'=>'Datos inválidos'],400);
        $fotoActual = trim($_POST['foto_actual']??'');
        $foto = $fotoActual;
        if (!empty($_FILES['foto']['name'])&&$_FILES['foto']['error']===0){
            $foto = uploadToSupabase($_FILES['foto']);
            if ($fotoActual) deleteFromSupabase($fotoActual);
        }
        $data = [
            'nombre'       => $nombre,
            'descripcion'  => trim($_POST['descripcion']??''),
            'categoria_id' => $catId,
            'precio'       => (float)($_POST['precio']??0),
            'unidad'       => trim($_POST['unidad']??'unid'),
            'medidas'      => trim($_POST['medidas']??''),
            'etiqueta'     => trim($_POST['etiqueta']??''),
            'emoji'        => trim($_POST['emoji']??'📦'),
            'foto'         => $foto,
        ];
        supabase('PATCH',"productos?id=eq.$id",$data);
        jsonResponse(['success'=>true,'message'=>'Producto actualizado']);
        break;
    case 'delete':
        $id = (int)($_GET['id']??0);
        $rows = supabase('GET',"productos?id=eq.$id&select=foto&limit=1");
        if (!empty($rows[0]['foto'])) deleteFromSupabase($rows[0]['foto']);
        supabase('DELETE',"productos?id=eq.$id");
        jsonResponse(['success'=>true]);
        break;
    case 'settings':
        $rows = supabase('GET','settings?select=clave,valor');
        $s=[]; foreach($rows as $r) $s[$r['clave']]=$r['valor'];
        jsonResponse(['success'=>true,'settings'=>$s]);
        break;
    case 'save_settings':
        $input   = json_decode(file_get_contents('php://input'),true)??[];
        $allowed = ['nombre_negocio','slogan','whatsapp','email_contacto','email_pedidos','direccion','horario'];
        foreach ($allowed as $k) {
            if (!isset($input[$k])) continue;
            supabase('POST','settings',['clave'=>$k,'valor'=>trim($input[$k])],['Prefer: resolution=merge-duplicates']);
        }
        jsonResponse(['success'=>true,'message'=>'Guardado']);
        break;
    case 'change_password':
        $input = json_decode(file_get_contents('php://input'),true)??[];
        $pass  = trim($input['password']??'');
        if (strlen($pass)<6) jsonResponse(['error'=>'Mínimo 6 caracteres'],400);
        supabase('POST','settings',['clave'=>'admin_password','valor'=>password_hash($pass,PASSWORD_DEFAULT)],['Prefer: resolution=merge-duplicates']);
        jsonResponse(['success'=>true]);
        break;
    default: jsonResponse(['error'=>'Acción no válida'],400);
}

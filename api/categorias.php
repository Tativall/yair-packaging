<?php
// api/categorias.php
require_once '../config/supabase.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $rows = supabase('GET','categorias?select=*&order=orden.asc');
        jsonResponse(['success'=>true,'categorias'=>$rows]);
        break;

    case 'create':
        if (!isAdminLoggedIn()) jsonResponse(['error'=>'No autorizado'],401);
        $input = json_decode(file_get_contents('php://input'),true)??[];
        $nombre = trim($input['nombre']??'');
        if (!$nombre) jsonResponse(['error'=>'Nombre obligatorio'],400);
        $data = ['nombre'=>$nombre,'icono'=>trim($input['icono']??'📦'),'color'=>trim($input['color']??'#fff3e0'),'orden'=>(int)($input['orden']??99),'activa'=>true];
        $res  = supabase('POST','categorias',$data);
        jsonResponse(['success'=>true,'id'=>$res[0]['id']??0,'message'=>'Categoría creada']);
        break;

    case 'update':
        if (!isAdminLoggedIn()) jsonResponse(['error'=>'No autorizado'],401);
        $id    = (int)($_GET['id']??0);
        $input = json_decode(file_get_contents('php://input'),true)??[];
        $nombre = trim($input['nombre']??'');
        if (!$id||!$nombre) jsonResponse(['error'=>'Datos inválidos'],400);
        $data = ['nombre'=>$nombre,'icono'=>trim($input['icono']??'📦'),'color'=>trim($input['color']??'#fff3e0'),'orden'=>(int)($input['orden']??99)];
        supabase('PATCH',"categorias?id=eq.$id",$data);
        jsonResponse(['success'=>true,'message'=>'Actualizado']);
        break;

    case 'delete':
        if (!isAdminLoggedIn()) jsonResponse(['error'=>'No autorizado'],401);
        $id = (int)($_GET['id']??0);
        supabase('DELETE',"categorias?id=eq.$id");
        jsonResponse(['success'=>true]);
        break;

    default: jsonResponse(['error'=>'Acción no válida'],400);
}

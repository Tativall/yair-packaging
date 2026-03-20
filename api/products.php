<?php
require_once '../config/database.php';
startSession();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$readOnlyActions = ['list', 'get', 'settings'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if (!in_array($action, $readOnlyActions)) {
    if (empty($_SESSION['admin_logged'])) {
        jsonResponse(['error' => 'No autorizado — sesión expirada, volvé a iniciar sesión'], 401);
    }
}

$db = getDB();

switch ($action) {
    case 'list':
        $onlyActive = isset($_GET['active']) && $_GET['active'] == '1';
        $sql = "SELECT p.*, c.nombre as categoria, c.icono as cat_icono, c.color as cat_color
                FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id";
        if ($onlyActive) $sql .= " WHERE p.activo = 1";
        $sql .= " ORDER BY c.orden ASC, p.orden ASC, p.id ASC";
        jsonResponse(['success' => true, 'products' => $db->query($sql)->fetchAll()]);
        break;

    case 'get':
        $id   = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT p.*, c.nombre as categoria FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE p.id = ?");
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if (!$p) jsonResponse(['error' => 'No encontrado'], 404);
        jsonResponse(['success' => true, 'product' => $p]);
        break;

    case 'create':
        $nombre    = trim($_POST['nombre'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        if (!$nombre || !$categoria) jsonResponse(['error' => 'Nombre y categoría son obligatorios'], 400);
        $stmtCat = $db->prepare("SELECT id FROM categorias WHERE nombre = ?");
        $stmtCat->execute([$categoria]);
        $cat   = $stmtCat->fetch();
        $catId = $cat ? $cat['id'] : null;
        $foto  = '';
        if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto = handleUpload($_FILES['foto']);
        }
        $stmt = $db->prepare("INSERT INTO productos (nombre,descripcion,categoria_id,precio,unidad,medidas,etiqueta,emoji,foto) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$nombre, trim($_POST['descripcion']??''), $catId, (float)($_POST['precio']??0), trim($_POST['unidad']??'unid'), trim($_POST['medidas']??''), trim($_POST['etiqueta']??''), trim($_POST['emoji']??'📦'), $foto]);
        jsonResponse(['success' => true, 'id' => $db->lastInsertId(), 'message' => 'Producto creado']);
        break;

    case 'update':
        $id     = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        if (!$id || !$nombre) jsonResponse(['error' => 'Datos inválidos'], 400);
        $stmtCat = $db->prepare("SELECT id FROM categorias WHERE nombre = ?");
        $stmtCat->execute([trim($_POST['categoria']??'')]);
        $cat   = $stmtCat->fetch();
        $catId = $cat ? $cat['id'] : null;
        $fotoActual = trim($_POST['foto_actual'] ?? '');
        $foto = $fotoActual;
        if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto = handleUpload($_FILES['foto']);
            if ($fotoActual && file_exists(__DIR__.'/../assets/uploads/'.$fotoActual)) unlink(__DIR__.'/../assets/uploads/'.$fotoActual);
        }
        $stmt = $db->prepare("UPDATE productos SET nombre=?,descripcion=?,categoria_id=?,precio=?,unidad=?,medidas=?,etiqueta=?,emoji=?,foto=?,updated_at=datetime('now') WHERE id=?");
        $stmt->execute([$nombre, trim($_POST['descripcion']??''), $catId, (float)($_POST['precio']??0), trim($_POST['unidad']??'unid'), trim($_POST['medidas']??''), trim($_POST['etiqueta']??''), trim($_POST['emoji']??'📦'), $foto, $id]);
        jsonResponse(['success' => true, 'message' => 'Producto actualizado']);
        break;

    case 'delete':
        $id   = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT foto FROM productos WHERE id=?");
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if ($p && $p['foto'] && file_exists(__DIR__.'/../assets/uploads/'.$p['foto'])) unlink(__DIR__.'/../assets/uploads/'.$p['foto']);
        $db->prepare("DELETE FROM productos WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true]);
        break;

    case 'settings':
        $rows = $db->query("SELECT clave, valor FROM settings")->fetchAll();
        $s = [];
        foreach ($rows as $r) $s[$r['clave']] = $r['valor'];
        jsonResponse(['success' => true, 'settings' => $s]);
        break;

    case 'save_settings':
        $input   = json_decode(file_get_contents('php://input'), true) ?? [];
        $allowed = ['nombre_negocio','slogan','whatsapp','email_contacto','email_pedidos','direccion','horario'];
        $stmt    = $db->prepare("INSERT OR REPLACE INTO settings (clave,valor) VALUES (?,?)");
        foreach ($allowed as $k) { if (isset($input[$k])) $stmt->execute([$k, trim($input[$k])]); }
        jsonResponse(['success' => true, 'message' => 'Ajustes guardados']);
        break;

    case 'change_password':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $pass  = trim($input['password'] ?? '');
        if (strlen($pass) < 6) jsonResponse(['error' => 'Mínimo 6 caracteres'], 400);
        $db->prepare("INSERT OR REPLACE INTO settings (clave,valor) VALUES ('admin_password',?)")->execute([password_hash($pass, PASSWORD_DEFAULT)]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Acción no válida'], 400);
}

function handleUpload($file) {
    $uploadDir = __DIR__ . '/../assets/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) throw new Exception('Formato no válido');
    if ($file['size'] > 3*1024*1024) throw new Exception('Imagen muy grande');
    $name = uniqid('prod_').'.'.$ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir.$name)) throw new Exception('Error al guardar');
    return $name;
}

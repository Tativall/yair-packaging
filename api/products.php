<?php
// =====================================================
// api/products.php — API de productos (Railway fix)
// =====================================================
session_start();
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$readOnlyActions = ['list', 'get', 'settings'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if (!in_array($action, $readOnlyActions)) {
    if (empty($_SESSION['admin_logged'])) {
        jsonResponse(['error' => 'No autorizado'], 401);
    }
}

$db = getDB();

switch ($action) {

    case 'list':
        $onlyActive = isset($_GET['active']) && $_GET['active'] == '1';
        $sql = "SELECT p.*, c.nombre as categoria, c.icono as cat_icono, c.color as cat_color
                FROM productos p
                LEFT JOIN categorias c ON p.categoria_id = c.id";
        if ($onlyActive) $sql .= " WHERE p.activo = 1";
        $sql .= " ORDER BY c.orden ASC, p.orden ASC, p.id ASC";
        $stmt = $db->query($sql);
        jsonResponse(['success' => true, 'products' => $stmt->fetchAll()]);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT p.*, c.nombre as categoria
                               FROM productos p
                               LEFT JOIN categorias c ON p.categoria_id = c.id
                               WHERE p.id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if (!$product) jsonResponse(['error' => 'Producto no encontrado'], 404);
        jsonResponse(['success' => true, 'product' => $product]);
        break;

    case 'create':
        $nombre    = trim($_POST['nombre'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        if (!$nombre || !$categoria) jsonResponse(['error' => 'Nombre y categoría son obligatorios'], 400);

        $stmtCat = $db->prepare("SELECT id FROM categorias WHERE nombre = ?");
        $stmtCat->execute([$categoria]);
        $cat   = $stmtCat->fetch();
        $catId = $cat ? $cat['id'] : null;

        $fotoNombre = '';
        if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fotoNombre = handleUpload($_FILES['foto']);
        }

        $stmt = $db->prepare("INSERT INTO productos (nombre, descripcion, categoria_id, precio, unidad, medidas, etiqueta, emoji, foto)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $nombre,
            trim($_POST['descripcion'] ?? ''),
            $catId,
            (float)($_POST['precio'] ?? 0),
            trim($_POST['unidad'] ?? 'unid'),
            trim($_POST['medidas'] ?? ''),
            trim($_POST['etiqueta'] ?? ''),
            trim($_POST['emoji'] ?? '📦'),
            $fotoNombre
        ]);
        jsonResponse(['success' => true, 'id' => $db->lastInsertId(), 'message' => 'Producto creado correctamente']);
        break;

    case 'update':
        $id     = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        if (!$id || !$nombre) jsonResponse(['error' => 'Datos inválidos'], 400);

        $stmtCat = $db->prepare("SELECT id FROM categorias WHERE nombre = ?");
        $stmtCat->execute([trim($_POST['categoria'] ?? '')]);
        $cat   = $stmtCat->fetch();
        $catId = $cat ? $cat['id'] : null;

        $fotoActual = trim($_POST['foto_actual'] ?? '');
        $fotoNombre = $fotoActual;
        if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fotoNombre = handleUpload($_FILES['foto']);
            if ($fotoActual) {
                $oldPath = __DIR__ . '/../assets/uploads/' . $fotoActual;
                if (file_exists($oldPath)) unlink($oldPath);
            }
        }

        $stmt = $db->prepare("UPDATE productos SET nombre=?, descripcion=?, categoria_id=?, precio=?,
                               unidad=?, medidas=?, etiqueta=?, emoji=?, foto=?,
                               updated_at=datetime('now') WHERE id=?");
        $stmt->execute([
            $nombre,
            trim($_POST['descripcion'] ?? ''),
            $catId,
            (float)($_POST['precio'] ?? 0),
            trim($_POST['unidad'] ?? 'unid'),
            trim($_POST['medidas'] ?? ''),
            trim($_POST['etiqueta'] ?? ''),
            trim($_POST['emoji'] ?? '📦'),
            $fotoNombre,
            $id
        ]);
        jsonResponse(['success' => true, 'message' => 'Producto actualizado correctamente']);
        break;

    case 'delete':
        $id   = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT foto FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        $p    = $stmt->fetch();
        if ($p && $p['foto']) {
            $path = __DIR__ . '/../assets/uploads/' . $p['foto'];
            if (file_exists($path)) unlink($path);
        }
        $db->prepare("DELETE FROM productos WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Producto eliminado']);
        break;

    case 'settings':
        $stmt = $db->query("SELECT clave, valor FROM settings");
        $rows = $stmt->fetchAll();
        $settings = [];
        foreach ($rows as $r) $settings[$r['clave']] = $r['valor'];
        jsonResponse(['success' => true, 'settings' => $settings]);
        break;

    case 'save_settings':
        if (empty($_SESSION['admin_logged'])) jsonResponse(['error' => 'No autorizado'], 401);
        $input   = json_decode(file_get_contents('php://input'), true) ?? [];
        $allowed = ['nombre_negocio','slogan','whatsapp','email_contacto','email_pedidos','direccion','horario'];
        $stmt    = $db->prepare("INSERT OR REPLACE INTO settings (clave, valor) VALUES (?, ?)");
        foreach ($allowed as $k) {
            if (isset($input[$k])) $stmt->execute([$k, trim($input[$k])]);
        }
        jsonResponse(['success' => true, 'message' => 'Ajustes guardados']);
        break;

    case 'change_password':
        if (empty($_SESSION['admin_logged'])) jsonResponse(['error' => 'No autorizado'], 401);
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $pass  = trim($input['password'] ?? '');
        if (strlen($pass) < 6) jsonResponse(['error' => 'Mínimo 6 caracteres'], 400);
        $stmt  = $db->prepare("INSERT OR REPLACE INTO settings (clave, valor) VALUES ('admin_password', ?)");
        $stmt->execute([password_hash($pass, PASSWORD_DEFAULT)]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Acción no válida'], 400);
}

function handleUpload($file) {
    $uploadDir  = __DIR__ . '/../assets/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $allowedExt = ['jpg','jpeg','png','webp','gif'];
    $ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) throw new Exception('Formato no válido');
    if ($file['size'] > 3 * 1024 * 1024) throw new Exception('Imagen muy grande (máx 3MB)');
    $newName = uniqid('prod_') . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
        throw new Exception('Error al guardar la imagen');
    }
    return $newName;
}

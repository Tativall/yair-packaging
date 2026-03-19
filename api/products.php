<?php
// =====================================================
// api/products.php — API de productos
// =====================================================
session_start();
require_once '../config/database.php';

// Solo admin puede modificar productos
$readOnlyActions = ['list', 'get'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if (!in_array($action, $readOnlyActions) && $action !== 'settings') {
    if (empty($_SESSION['admin_logged'])) {
        jsonResponse(['error' => 'No autorizado'], 401);
    }
}

$db = getDB();

switch ($action) {

    // ── Listar productos ──────────────────────────────
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

    // ── Obtener un producto ───────────────────────────
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

    // ── Crear producto ────────────────────────────────
    case 'create':
        $nombre    = trim($_POST['nombre'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        if (!$nombre || !$categoria) jsonResponse(['error' => 'Nombre y categoría son obligatorios'], 400);

        // Buscar ID de categoría
        $stmtCat = $db->prepare("SELECT id FROM categorias WHERE nombre = ?");
        $stmtCat->execute([$categoria]);
        $cat = $stmtCat->fetch();
        $catId = $cat ? $cat['id'] : null;

        // Subir foto si viene
        $fotoNombre = '';
        if (!empty($_FILES['foto']['name'])) {
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

    // ── Actualizar producto ───────────────────────────
    case 'update':
        $id     = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        if (!$id || !$nombre) jsonResponse(['error' => 'Datos inválidos'], 400);

        $stmtCat = $db->prepare("SELECT id FROM categorias WHERE nombre = ?");
        $stmtCat->execute([trim($_POST['categoria'] ?? '')]);
        $cat   = $stmtCat->fetch();
        $catId = $cat ? $cat['id'] : null;

        // Foto: nueva o mantener la actual
        $fotoActual = trim($_POST['foto_actual'] ?? '');
        $fotoNombre = $fotoActual;
        if (!empty($_FILES['foto']['name'])) {
            $fotoNombre = handleUpload($_FILES['foto']);
            // Eliminar foto anterior si existía
            if ($fotoActual && file_exists('../assets/uploads/' . $fotoActual)) {
                unlink('../assets/uploads/' . $fotoActual);
            }
        }

        $stmt = $db->prepare("UPDATE productos SET nombre=?, descripcion=?, categoria_id=?, precio=?,
                               unidad=?, medidas=?, etiqueta=?, emoji=?, foto=? WHERE id=?");
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

    // ── Eliminar producto ─────────────────────────────
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') jsonResponse(['error' => 'Método no permitido'], 405);
        $id   = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT foto FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        $p    = $stmt->fetch();
        if ($p && $p['foto'] && file_exists('../assets/uploads/' . $p['foto'])) {
            unlink('../assets/uploads/' . $p['foto']);
        }
        $db->prepare("DELETE FROM productos WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'Producto eliminado']);
        break;

    // ── Leer ajustes ──────────────────────────────────
    case 'settings':
        $stmt = $db->query("SELECT clave, valor FROM settings");
        $rows = $stmt->fetchAll();
        $settings = [];
        foreach ($rows as $r) $settings[$r['clave']] = $r['valor'];
        jsonResponse(['success' => true, 'settings' => $settings]);
        break;

    // ── Guardar ajustes ───────────────────────────────
    case 'save_settings':
        if (empty($_SESSION['admin_logged'])) jsonResponse(['error' => 'No autorizado'], 401);
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $allowed = ['nombre_negocio','slogan','whatsapp','email_contacto','email_pedidos','direccion','horario'];
        $stmt = $db->prepare("INSERT OR REPLACE INTO settings (clave, valor) VALUES (?, ?)");
        foreach ($allowed as $k) {
            if (isset($input[$k])) $stmt->execute([$k, trim($input[$k])]);
        }
        jsonResponse(['success' => true, 'message' => 'Ajustes guardados']);
        break;

    // ── Cambiar contraseña ────────────────────────────
    case 'change_password':
        if (empty($_SESSION['admin_logged'])) jsonResponse(['error' => 'No autorizado'], 401);
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $pass  = trim($input['password'] ?? '');
        if (strlen($pass) < 6) jsonResponse(['error' => 'La contraseña debe tener al menos 6 caracteres'], 400);
        $stmt  = $db->prepare("INSERT OR REPLACE INTO settings (clave, valor) VALUES ('admin_password', ?)");
        $stmt->execute([password_hash($pass, PASSWORD_DEFAULT)]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Acción no válida'], 400);
}

// ── Helper: subir imagen ──────────────────────────────
function handleUpload($file) {
    $uploadDir  = '../assets/uploads/';
    $allowedExt = ['jpg','jpeg','png','webp','gif'];
    $ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt))          throw new Exception('Formato de imagen no válido');
    if ($file['size'] > 3 * 1024 * 1024)       throw new Exception('La imagen supera los 3MB');
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $newName = uniqid('prod_') . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
        throw new Exception('Error al guardar la imagen');
    }
    return $newName;
}

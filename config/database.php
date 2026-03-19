<?php
// =====================================================
// YAIR PACKAGING — Configuración SQLite
// NO necesita MySQL — funciona en cualquier hosting PHP
// =====================================================

define('DB_PATH', __DIR__ . '/../data/yair_packaging.db');
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123');
define('SITE_URL', '');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        try {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA journal_mode=WAL');
            $pdo->exec('PRAGMA foreign_keys=ON');
            initDB($pdo);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

function initDB($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            clave TEXT NOT NULL UNIQUE,
            valor TEXT,
            updated_at TEXT DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS categorias (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            icono TEXT DEFAULT '📦',
            color TEXT DEFAULT '#fff3e0',
            orden INTEGER DEFAULT 0,
            activa INTEGER DEFAULT 1
        );
        CREATE TABLE IF NOT EXISTS productos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            descripcion TEXT,
            categoria_id INTEGER,
            precio REAL DEFAULT 0,
            unidad TEXT DEFAULT 'unid',
            medidas TEXT,
            etiqueta TEXT DEFAULT '',
            emoji TEXT DEFAULT '📦',
            foto TEXT DEFAULT '',
            activo INTEGER DEFAULT 1,
            orden INTEGER DEFAULT 0,
            created_at TEXT DEFAULT (datetime('now')),
            updated_at TEXT DEFAULT (datetime('now')),
            FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        );
        CREATE TABLE IF NOT EXISTS pedidos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            codigo TEXT NOT NULL UNIQUE,
            nombre TEXT NOT NULL,
            telefono TEXT NOT NULL,
            email TEXT,
            empresa TEXT,
            producto_nombre TEXT,
            cantidad TEXT,
            medida TEXT,
            notas TEXT,
            via TEXT DEFAULT 'web',
            estado TEXT DEFAULT 'nuevo',
            ip TEXT,
            created_at TEXT DEFAULT (datetime('now')),
            updated_at TEXT DEFAULT (datetime('now'))
        );
    ");
    $count = $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
    if ($count == 0) seedData($pdo);
}

function seedData($pdo) {
    $settings = [
        ['nombre_negocio','Yair Packaging'],['slogan','Embalajes profesionales para tu negocio'],
        ['whatsapp','595981000000'],['email_contacto','yairpackaging@gmail.com'],
        ['email_pedidos','yairpackaging@gmail.com'],['admin_password','admin123'],
        ['direccion','Asunción, Paraguay'],['horario','Lunes a Viernes 8:00 - 18:00'],
    ];
    $s = $pdo->prepare("INSERT OR IGNORE INTO settings (clave,valor) VALUES (?,?)");
    foreach ($settings as $r) $s->execute($r);

    $cats = [['Cartones','📦','#fff3e0',1],['Plásticos','🧴','#e0f2fe',2],['Isopor','🔲','#f0fdf4',3],['Accesorios','🛒','#f3e8ff',4]];
    $s = $pdo->prepare("INSERT INTO categorias (nombre,icono,color,orden) VALUES (?,?,?,?)");
    foreach ($cats as $c) $s->execute($c);

    $prods = [
        ['Caja Corrugada Simple','Ideal para mudanzas, envíos y almacenamiento general.',1,8500,'unid','30×20×20, 40×30×30, 60×40×40, A medida','popular','📦'],
        ['Caja Doble Pared','Mayor resistencia para cargas pesadas. Exportación y equipos.',1,18000,'unid','50×40×40, 80×60×60, A medida','','🗂️'],
        ['Caja para E-commerce','Diseñada para envíos. Cierre seguro, sin cinta.',1,7000,'unid','Pequeño, Mediano, Grande','popular','📬'],
        ['Plancha de Cartón','Para separadores, protección y armado de embalajes.',1,4000,'unid','1m×1m, 1.2m×0.8m, A medida','oferta','🃏'],
        ['Film Stretch','Para palletizar y asegurar cargas. Alta extensibilidad.',2,65000,'rollo','45cm×300m, 50cm×500m, Manual/Máquina','popular','🌀'],
        ['Plástico Burbuja','Protección acolchada para artículos frágiles.',2,85000,'rollo','50cm×50m, 100cm×50m, A medida','','🫧'],
        ['Bolsas de Polietileno','Transparentes, con cierre, autoadhesivo.',2,3500,'100 unid','10×15cm, 20×30cm, 40×60cm, A medida','oferta','🛍️'],
        ['Plancha de Isopor','Para aislación térmica, construcción y embalaje.',3,15000,'unid','1m×0.5m×1cm, 1m×0.5m×2cm, 1m×0.5m×5cm','popular','⬜'],
        ['Caja Térmica','Para alimentos, medicamentos y productos refrigerados.',3,35000,'unid','5L, 15L, 30L, 50L','','🧊'],
        ['Cinta de Embalaje','Transparente y marrón. Para cierre de cajas.',4,12000,'rollo','48mm×90m, 48mm×150m, 72mm×90m','popular','🟨'],
        ['Fleje Plástico','Para asegurar pallets y bultos. Manual y máquina.',4,45000,'caja','12mm, 16mm, 19mm','','🔗'],
    ];
    $s = $pdo->prepare("INSERT INTO productos (nombre,descripcion,categoria_id,precio,unidad,medidas,etiqueta,emoji) VALUES (?,?,?,?,?,?,?,?)");
    foreach ($prods as $p) $s->execute($p);
}

function requireAdmin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin_logged'])) {
        header('Location: ../admin/login.php');
        exit;
    }
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

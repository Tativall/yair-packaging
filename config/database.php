<?php
// config/database.php — Railway compatible con auth por cookie
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123');
define('AUTH_COOKIE', 'yair_admin_auth');
define('AUTH_SECRET', 'yair2025secret_xK9mP'); // clave para firmar el token

function startSession() {
    // No usamos sessions PHP en Railway — usamos cookies firmadas
    // Esta función existe para compatibilidad pero no hace nada
}

function isAdminLoggedIn() {
    $cookie = $_COOKIE[AUTH_COOKIE] ?? '';
    if (!$cookie) return false;
    // Verificar firma: formato "usuario:timestamp:firma"
    $parts = explode(':', $cookie);
    if (count($parts) !== 3) return false;
    [$user, $time, $sig] = $parts;
    // Token válido por 24 horas
    if (time() - (int)$time > 86400) return false;
    $expected = hash_hmac('sha256', $user . ':' . $time, AUTH_SECRET);
    return $user === ADMIN_USER && hash_equals($expected, $sig);
}

function loginAdmin() {
    $time = time();
    $sig  = hash_hmac('sha256', ADMIN_USER . ':' . $time, AUTH_SECRET);
    $val  = ADMIN_USER . ':' . $time . ':' . $sig;
    setcookie(AUTH_COOKIE, $val, [
        'expires'  => time() + 86400,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
}

function logoutAdmin() {
    setcookie(AUTH_COOKIE, '', ['expires' => time()-3600, 'path' => '/']);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $isRailway = !empty(getenv('RAILWAY_ENVIRONMENT'));
        $dataDir   = $isRailway ? '/tmp' : __DIR__ . '/../data';
        $dbPath    = $dataDir . '/yair_packaging.db';
        if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);
        try {
            $pdo = new PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA journal_mode=WAL');
            $pdo->exec('PRAGMA foreign_keys=ON');
            initDB($pdo);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Error BD: ' . $e->getMessage()]));
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
            email TEXT, empresa TEXT,
            producto_nombre TEXT, cantidad TEXT, medida TEXT, notas TEXT,
            via TEXT DEFAULT 'web',
            estado TEXT DEFAULT 'nuevo',
            ip TEXT,
            created_at TEXT DEFAULT (datetime('now')),
            updated_at TEXT DEFAULT (datetime('now'))
        );
    ");
    if ($pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn() == 0) seedData($pdo);
}

function seedData($pdo) {
    $s = $pdo->prepare("INSERT OR IGNORE INTO settings (clave,valor) VALUES (?,?)");
    foreach ([
        ['nombre_negocio','Yair Packaging'],['slogan','Embalajes profesionales para tu negocio'],
        ['whatsapp','595986537162'],['email_contacto','yairpackaging@gmail.com'],
        ['email_pedidos','yairpackaging@gmail.com'],['admin_password','admin123'],
        ['direccion','Asunción, Paraguay'],['horario','Lunes a Viernes 8:00 - 18:00 / Sab 08:00-13.00'],
    ] as $r) $s->execute($r);

    $s = $pdo->prepare("INSERT INTO categorias (nombre,icono,color,orden) VALUES (?,?,?,?)");
    foreach ([['Cartones','📦','#fff3e0',1],['Plásticos','🧴','#e0f2fe',2],['Isopor','🔲','#f0fdf4',3],['Accesorios','🛒','#f3e8ff',4]] as $c) $s->execute($c);

    $s = $pdo->prepare("INSERT INTO productos (nombre,descripcion,categoria_id,precio,unidad,medidas,etiqueta,emoji) VALUES (?,?,?,?,?,?,?,?)");
    foreach ([
        ['Caja Corrugada Simple','Ideal para mudanzas y envíos.',1,8500,'unid','30×20×20, 40×30×30, A medida','popular','📦'],
        ['Caja Doble Pared','Mayor resistencia para cargas pesadas.',1,18000,'unid','50×40×40, A medida','','🗂️'],
        ['Caja para E-commerce','Diseñada para envíos sin cinta.',1,7000,'unid','Pequeño, Mediano, Grande','popular','📬'],
        ['Film Stretch','Para palletizar y asegurar cargas.',2,65000,'rollo','45cm×300m, 50cm×500m','popular','🌀'],
        ['Plástico Burbuja','Protección para artículos frágiles.',2,85000,'rollo','50cm×50m, 100cm×50m','','🫧'],
        ['Bolsas de Polietileno','Transparentes con cierre.',2,3500,'100 unid','10×15cm, 20×30cm','oferta','🛍️'],
        ['Plancha de Isopor','Para aislación y embalaje.',3,15000,'unid','1m×0.5m×1cm, 1m×0.5m×2cm','popular','⬜'],
        ['Caja Térmica','Para alimentos refrigerados.',3,35000,'unid','5L, 15L, 30L, 50L','','🧊'],
        ['Cinta de Embalaje','Transparente y marrón.',4,12000,'rollo','48mm×90m, 72mm×90m','popular','🟨'],
        ['Fleje Plástico','Para asegurar pallets.',4,45000,'caja','12mm, 16mm, 19mm','','🔗'],
    ] as $p) $s->execute($p);
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

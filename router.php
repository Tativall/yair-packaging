<?php
// router.php — Enrutador para Railway (PHP built-in server)
// Permite URLs limpias sin Apache/Nginx
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Bloquear acceso a carpetas protegidas
if (preg_match('#^/(data|config)/#', $uri)) {
    http_response_code(403);
    echo '<h1>403 Forbidden</h1>';
    exit;
}

// URL /catalogo → index.php
if ($uri === '/catalogo' || $uri === '/catalogo/') {
    require __DIR__ . '/index.php';
    exit;
}

// URLs de admin limpias
$adminRoutes = [
    '/admin'            => '/admin/login.php',
    '/admin/'           => '/admin/login.php',
    '/admin/dashboard'  => '/admin/dashboard.php',
    '/admin/productos'  => '/admin/productos.php',
    '/admin/pedidos'    => '/admin/pedidos.php',
    '/admin/ajustes'    => '/admin/ajustes.php',
];
if (isset($adminRoutes[$uri])) {
    require __DIR__ . $adminRoutes[$uri];
    exit;
}

// Archivos estáticos (CSS, JS, imágenes)
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false; // Servir archivo directamente
}

// Raíz → catálogo
if ($uri === '/') {
    require __DIR__ . '/index.php';
    exit;
}

// Cualquier otro PHP existente
if (file_exists(__DIR__ . $uri . '.php')) {
    require __DIR__ . $uri . '.php';
    exit;
}
if (file_exists(__DIR__ . $uri)) {
    require __DIR__ . $uri;
    exit;
}

http_response_code(404);
echo '<h1>404 Not Found</h1>';

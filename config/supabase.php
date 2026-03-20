<?php
// =====================================================
// config/supabase.php — Configuración Supabase
// Reemplazá los valores con los de tu proyecto
// Settings → API en el dashboard de Supabase
// =====================================================
define('SUPABASE_URL',    'https://xgseyvuxkyrardbtewrd.supabase.co');
define('SUPABASE_KEY',    'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Inhnc2V5dnV4a3lyYXJkYnRld3JkIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzQwMDcyMTQsImV4cCI6MjA4OTU4MzIxNH0.JyXDYnMhmOnW8g4-Gr-FWdjt0q658FaTxiMHWTkALTk');
define('SUPABASE_BUCKET', 'productos');
define('AUTH_COOKIE',     'yair_admin_auth');
define('AUTH_SECRET',     'yair2025secret_xK9mP');
define('ADMIN_USER',      'admin');
define('ADMIN_PASS',      'admin123');

function supabase(string $method, string $endpoint, $body = null, array $extra = []): array {
    $url  = SUPABASE_URL . '/rest/v1/' . ltrim($endpoint, '/');
    $headers = [
        'Content-Type: application/json',
        'apikey: '           . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Prefer: return=representation',
    ];
    foreach ($extra as $h) $headers[] = $h;
    $opts = ['http' => [
        'method'        => $method,
        'header'        => implode("\r\n", $headers),
        'content'       => $body ? json_encode($body) : null,
        'ignore_errors' => true,
    ]];
    $r = file_get_contents($url, false, stream_context_create($opts));
    return json_decode($r ?: '[]', true) ?? [];
}

function uploadToSupabase(array $file): string {
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) throw new Exception('Formato no válido');
    if ($file['size'] > 3*1024*1024) throw new Exception('Imagen muy grande');
    $name    = uniqid('prod_') . '.' . $ext;
    $content = file_get_contents($file['tmp_name']);
    $mime    = mime_content_type($file['tmp_name']) ?: 'image/jpeg';
    $url     = SUPABASE_URL . '/storage/v1/object/' . SUPABASE_BUCKET . '/' . $name;
    $opts    = ['http' => [
        'method'        => 'POST',
        'header'        => "Content-Type: $mime\r\napikey: ".SUPABASE_KEY."\r\nAuthorization: Bearer ".SUPABASE_KEY,
        'content'       => $content,
        'ignore_errors' => true,
    ]];
    $r    = file_get_contents($url, false, stream_context_create($opts));
    $data = json_decode($r, true);
    if (empty($data['Key']) && empty($data['key'])) throw new Exception('Error al subir: ' . $r);
    return SUPABASE_URL . '/storage/v1/object/public/' . SUPABASE_BUCKET . '/' . $name;
}

function deleteFromSupabase(string $url): void {
    if (!$url || strpos($url, SUPABASE_URL) === false) return;
    $name = basename(parse_url($url, PHP_URL_PATH));
    $opts = ['http' => ['method'=>'DELETE','header'=>"apikey: ".SUPABASE_KEY."\r\nAuthorization: Bearer ".SUPABASE_KEY,'ignore_errors'=>true]];
    file_get_contents(SUPABASE_URL.'/storage/v1/object/'.SUPABASE_BUCKET.'/'.$name, false, stream_context_create($opts));
}

function isAdminLoggedIn(): bool {
    $c = $_COOKIE[AUTH_COOKIE] ?? '';
    if (!$c) return false;
    $p = explode(':', $c);
    if (count($p) !== 3) return false;
    [$u,$t,$s] = $p;
    if (time()-(int)$t > 86400) return false;
    return $u===ADMIN_USER && hash_equals(hash_hmac('sha256',$u.':'.$t,AUTH_SECRET), $s);
}

function loginAdmin(): void {
    $t = time(); $s = hash_hmac('sha256', ADMIN_USER.':'.$t, AUTH_SECRET);
    setcookie(AUTH_COOKIE, ADMIN_USER.':'.$t.':'.$s, ['expires'=>time()+86400,'path'=>'/','httponly'=>true,'samesite'=>'Lax','secure'=>(isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')]);
}

function logoutAdmin(): void {
    setcookie(AUTH_COOKIE, '', ['expires'=>time()-3600,'path'=>'/']);
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) { header('Location: /admin/login.php'); exit; }
}

function jsonResponse($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

<?php
// test.php — Diagnóstico de Railway
// Subir este archivo y abrir: tuURL/test.php
header('Content-Type: text/plain');

echo "=== DIAGNÓSTICO RAILWAY ===\n\n";

// PHP version
echo "PHP: " . PHP_VERSION . "\n";

// Extensions
echo "PDO SQLite: " . (extension_loaded('pdo_sqlite') ? 'SI' : 'NO') . "\n";
echo "PDO: " . (extension_loaded('pdo') ? 'SI' : 'NO') . "\n\n";

// Paths
echo "Dir actual: " . __DIR__ . "\n";
echo "Var RAILWAY: " . (getenv('RAILWAY_ENVIRONMENT') ?: 'no definida') . "\n\n";

// Probar /tmp
$tmpPath = '/tmp/test.db';
echo "Probando escritura en /tmp: ";
try {
    $pdo = new PDO('sqlite:' . $tmpPath);
    $pdo->exec("CREATE TABLE IF NOT EXISTS t (id INTEGER PRIMARY KEY)");
    $pdo->exec("INSERT INTO t VALUES (NULL)");
    $count = $pdo->query("SELECT COUNT(*) FROM t")->fetchColumn();
    echo "OK ($count registros)\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Probar carpeta data/
$dataPath = __DIR__ . '/../data/test.db';
echo "Probando escritura en data/: ";
try {
    if (!is_dir(dirname($dataPath))) mkdir(dirname($dataPath), 0755, true);
    $pdo2 = new PDO('sqlite:' . $dataPath);
    $pdo2->exec("CREATE TABLE IF NOT EXISTS t (id INTEGER PRIMARY KEY)");
    echo "OK\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Session test
session_start();
echo "\nSession ID: " . session_id() . "\n";
echo "Session dir: " . session_save_path() . "\n";

echo "\n=== FIN ===\n";

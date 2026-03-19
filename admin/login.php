<?php
// =====================================================
// admin/login.php — Login del administrador
// =====================================================
session_start();
require_once '../config/database.php';

if (!empty($_SESSION['admin_logged'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['usuario'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if ($user === ADMIN_USER) {
        $db   = getDB();
        $stmt = $db->query("SELECT valor FROM settings WHERE clave = 'admin_password'");
        $row  = $stmt->fetch();
        $stored = $row ? $row['valor'] : ADMIN_PASS;

        // Verificar: password_hash o texto plano (para instalación inicial)
        $ok = password_verify($pass, $stored) || $pass === $stored;

        if ($ok) {
            $_SESSION['admin_logged'] = true;
            $_SESSION['admin_user']   = $user;
            header('Location: dashboard.php');
            exit;
        }
    }
    $error = 'Usuario o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Yair Packaging</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
body{background:var(--primary);display:flex;align-items:center;justify-content:center;min-height:100vh;padding:1rem}
.login-box{background:#fff;border-radius:16px;padding:2.5rem;width:100%;max-width:380px;box-shadow:0 20px 60px rgba(0,0,0,0.3)}
.login-logo{font-family:var(--font-head);font-size:2rem;font-weight:800;color:var(--primary);margin-bottom:.25rem}
.login-logo span{color:var(--accent)}
.login-sub{font-size:.85rem;color:var(--muted);margin-bottom:2rem}
</style>
</head>
<body>
<div class="login-box">
  <div class="login-logo">YAIR <span>PACKAGING</span></div>
  <div class="login-sub">Panel de administración</div>

  <?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php">
    <div class="form-group">
      <label>Usuario</label>
      <input type="text" name="usuario" value="admin" autocomplete="username" required />
    </div>
    <div class="form-group">
      <label>Contraseña</label>
      <input type="password" name="password" placeholder="••••••••" autocomplete="current-password" required autofocus />
    </div>
    <button type="submit" class="btn btn-accent btn-block btn-lg" style="margin-top:.5rem">Ingresar</button>
  </form>

  <div style="text-align:center;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border)">
    <a href="../index.php" class="btn btn-outline btn-sm">← Ver catálogo público</a>
  </div>
</div>
</body>
</html>

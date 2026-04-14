<?php
require_once '../config/supabase.php';

// Si ya está autenticado ir al dashboard
if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['usuario'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if ($user === ADMIN_USER) {
        // Buscar contraseña en Supabase settings
        $rows   = supabase('GET', "settings?clave=eq.admin_password&select=valor&limit=1");
        $stored = $rows[0]['valor'] ?? ADMIN_PASS;
        $ok     = password_verify($pass, $stored) || $pass === $stored;
        if ($ok) {
            loginAdmin();
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

  <script>if(localStorage.getItem("theme")==="light") document.documentElement.setAttribute("data-theme","light");</script>
</head>
<body>
<div class="login-box">
  <div class="login-logo">YAIR <span>PACKAGING</span></div>
  <div class="login-sub">Panel de administración</div>
  <?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST" action="">
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
    <a href="../index.php" class="btn btn-outline btn-sm">← Ver catálogo</a>
  </div>
</div>
</body>
</html>

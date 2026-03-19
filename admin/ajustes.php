<?php
session_start();
require_once '../config/database.php';
requireAdmin();
$db = getDB();
$stmtS = $db->query("SELECT clave, valor FROM settings");
$settings = [];
foreach ($stmtS->fetchAll() as $r) $settings[$r['clave']] = $r['valor'];
$bizName   = $settings['nombre_negocio'] ?? 'Yair Packaging';
$newOrders = $db->query("SELECT COUNT(*) FROM pedidos WHERE estado='nuevo'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ajustes — <?= htmlspecialchars($bizName) ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.admin-layout{display:grid;grid-template-columns:220px 1fr;min-height:calc(100vh - 46px)}
.sidebar{background:var(--primary);padding:1.5rem 0;min-height:100%}
.sidebar-section{padding:.5rem 1.5rem;font-size:10px;font-weight:700;letter-spacing:1.5px;color:rgba(255,255,255,0.3);text-transform:uppercase;margin-top:1rem}
.sidebar-item{display:flex;align-items:center;gap:10px;padding:.7rem 1.5rem;color:rgba(255,255,255,0.65);font-size:.88rem;font-weight:500;text-decoration:none;border-left:3px solid transparent;transition:all .2s}
.sidebar-item:hover{background:rgba(255,255,255,0.07);color:#fff}
.sidebar-item.active{background:rgba(232,101,10,.15);color:var(--accent2);border-left-color:var(--accent)}
.sidebar-badge{margin-left:auto;background:var(--accent);color:#fff;font-size:9px;font-weight:700;padding:1px 6px;border-radius:10px}
.admin-content{padding:2rem;overflow-y:auto;background:var(--bg)}
.page-title{font-family:var(--font-head);font-size:1.8rem;font-weight:700;margin-bottom:.25rem}
.page-sub{font-size:.85rem;color:var(--muted);margin-bottom:1.75rem}
@media(max-width:700px){.admin-layout{grid-template-columns:1fr}.sidebar{display:none}}
</style>
</head>
<body>
<div class="topbar">
  <a href="../index.php" class="topbar-logo"><?= htmlspecialchars($bizName) ?></a>
  <div class="topbar-actions">
    <a href="../index.php" class="btn btn-ghost btn-sm" target="_blank">Ver catálogo</a>
    <a href="logout.php" class="btn btn-ghost btn-sm">Salir</a>
  </div>
</div>
<div class="admin-layout">
  <div class="sidebar">
    <div class="sidebar-section">Principal</div>
    <a href="dashboard.php" class="sidebar-item"><span>📊</span> Dashboard</a>
    <a href="productos.php" class="sidebar-item"><span>📦</span> Productos</a>
    <a href="pedidos.php" class="sidebar-item">
      <span>🛒</span> Pedidos
      <?php if ($newOrders > 0): ?><span class="sidebar-badge"><?= $newOrders ?></span><?php endif; ?>
    </a>
    <div class="sidebar-section">Configuración</div>
    <a href="ajustes.php" class="sidebar-item active"><span>⚙️</span> Ajustes</a>
  </div>
  <div class="admin-content">
    <div class="page-title">Ajustes</div>
    <div class="page-sub">Configuración del negocio y del sistema</div>

    <!-- INFO NEGOCIO -->
    <div class="card" style="margin-bottom:1.25rem">
      <div class="card-header"><div class="card-title">Información del negocio</div></div>
      <div class="form-row">
        <div class="form-group"><label>Nombre del negocio</label><input type="text" id="set-nombre_negocio" value="<?= htmlspecialchars($settings['nombre_negocio'] ?? '') ?>" /></div>
        <div class="form-group"><label>Eslogan</label><input type="text" id="set-slogan" value="<?= htmlspecialchars($settings['slogan'] ?? '') ?>" /></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>WhatsApp (con código de país)</label><input type="text" id="set-whatsapp" value="<?= htmlspecialchars($settings['whatsapp'] ?? '') ?>" placeholder="595981000000" /></div>
        <div class="form-group"><label>Email de contacto</label><input type="text" id="set-email_contacto" value="<?= htmlspecialchars($settings['email_contacto'] ?? '') ?>" /></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Email para recibir pedidos</label><input type="text" id="set-email_pedidos" value="<?= htmlspecialchars($settings['email_pedidos'] ?? '') ?>" /></div>
        <div class="form-group"><label>Dirección</label><input type="text" id="set-direccion" value="<?= htmlspecialchars($settings['direccion'] ?? '') ?>" /></div>
      </div>
      <div class="form-group"><label>Horario de atención</label><input type="text" id="set-horario" value="<?= htmlspecialchars($settings['horario'] ?? '') ?>" placeholder="Lun-Vie 8:00-18:00" /></div>
      <button class="btn btn-accent" onclick="saveSettings()">💾 Guardar ajustes</button>
    </div>

    <!-- SEGURIDAD -->
    <div class="card" style="margin-bottom:1.25rem">
      <div class="card-header"><div class="card-title">🔐 Seguridad</div></div>
      <div class="form-row">
        <div class="form-group"><label>Nueva contraseña</label><input type="password" id="set-pass" placeholder="Mínimo 6 caracteres" /></div>
        <div class="form-group"><label>Confirmar contraseña</label><input type="password" id="set-pass2" placeholder="Repetir contraseña" /></div>
      </div>
      <button class="btn btn-primary" onclick="changePassword()">Cambiar contraseña</button>
    </div>

    <!-- INFO SISTEMA -->
    <div class="card">
      <div class="card-header"><div class="card-title">ℹ️ Información del sistema</div></div>
      <div style="font-size:.88rem;color:var(--muted);line-height:2">
        <p><strong>Sistema:</strong> Yair Packaging v1.0</p>
        <p><strong>Desarrollado por:</strong> Sistema personalizado</p>
        <p><strong>Base de datos:</strong> MySQL/MariaDB</p>
        <p><strong>Backend:</strong> PHP 8+</p>
        <p style="margin-top:1rem"><strong>Credencial de acceso admin:</strong> usuario <code style="background:var(--bg);padding:2px 6px;border-radius:4px">admin</code></p>
      </div>
    </div>
  </div>
</div>
<script src="../assets/js/admin.js"></script>
</body>
</html>

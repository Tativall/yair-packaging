<?php
// =====================================================
// admin/dashboard.php — Panel principal
// =====================================================
require_once '../config/supabase.php';
requireAdmin();

// Stats rápidas desde Supabase
$prods       = supabase('GET', 'productos?select=id&activo=eq.true');
$totalProds  = count($prods);

$allOrders   = supabase('GET', 'pedidos?select=id,estado,created_at');
$totalOrders = count($allOrders);
$newOrders   = count(array_filter($allOrders, fn($r) => $r['estado'] === 'nuevo'));

$hoy         = date('Y-m-d');
$todayOrders = count(array_filter($allOrders, fn($r) => str_starts_with($r['created_at'] ?? '', $hoy)));

// Últimos 8 pedidos
$recientes = supabase('GET', 'pedidos?select=codigo,nombre,telefono,producto_nombre,estado,created_at&order=created_at.desc&limit=8');
foreach ($recientes as &$o) {
    if (!empty($o['created_at'])) {
        $dt = new DateTime($o['created_at']);
        $o['fecha'] = $dt->format('d/m H:i');
    }
}
unset($o);

// Nombre del negocio desde Supabase
$settingsRows = supabase('GET', 'settings?select=clave,valor');
$settings = [];
foreach ($settingsRows as $r) $settings[$r['clave']] = $r['valor'];
$bizName = $settings['nombre_negocio'] ?? 'Yair Packaging';

$estadoLabel = ['nuevo'=>'Nuevo','leido'=>'Leído','en_proceso'=>'En proceso','completado'=>'Completado','cancelado'=>'Cancelado'];
$estadoBadge = ['nuevo'=>'nuevo','leido'=>'leido','en_proceso'=>'proceso','completado'=>'hecho','cancelado'=>'cancelado'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — <?= htmlspecialchars($bizName) ?></title>
<link rel="stylesheet" href="../assets/css/style.css">

  <script>if(localStorage.getItem("theme")==="light") document.documentElement.setAttribute("data-theme","light");</script>
</head>
<body class="admin-body">
<div class="topbar">
  <button class="hamburger-btn" onclick="document.querySelector('.sidebar').classList.toggle('open')" aria-label="Menú"><span></span><span></span><span></span></button>
  <a href="../index.php" class="topbar-logo"><?= htmlspecialchars($bizName) ?></a>
  <div class="topbar-actions">
      <button class="btn btn-ghost btn-sm" onclick="toggleTheme()" title="Tema">🌓</button>
    <a href="../index.php" class="btn btn-ghost btn-sm" target="_blank">Ver catálogo</a>
    <a href="logout.php" class="btn btn-ghost btn-sm">Salir</a>
  </div>
</div>

<div class="admin-layout">
  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="sidebar-section">Principal</div>
    <a href="dashboard.php" class="sidebar-item active"><span>📊</span> Dashboard</a>
    <a href="productos.php" class="sidebar-item"><span>📦</span> Productos</a>
    <a href="pedidos.php" class="sidebar-item">
      <span>🛒</span> Pedidos
      <?php if ($newOrders > 0): ?><span class="sidebar-badge"><?= $newOrders ?></span><?php endif; ?>
    </a>
    <div class="sidebar-section">Atención</div>
    <a href="soporte.php" class="sidebar-item"><span>💬</span> Soporte en vivo <span class="sidebar-badge" id="sb-chat-badge" style="display:none">!</span></a>
    <div class="sidebar-section">Configuración</div>
    <a href="ajustes.php" class="sidebar-item"><span>⚙️</span> Ajustes</a>
  </div>

  <!-- CONTENT -->
  <div class="admin-content">
    <div class="page-header">
      <div>
        <div class="page-title">Dashboard</div>
        <div class="page-sub">Bienvenido al panel de <?= htmlspecialchars($bizName) ?></div>
      </div>
      <a href="productos.php" class="btn btn-accent">+ Nuevo producto</a>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
      <div class="stat-card stat-accent">
        <div class="stat-label">Productos activos</div>
        <div class="stat-value"><?= $totalProds ?></div>
        <div class="stat-desc">en el catálogo</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Pedidos nuevos</div>
        <div class="stat-value"><?= $newOrders ?></div>
        <div class="stat-desc">sin leer</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total pedidos</div>
        <div class="stat-value"><?= $totalOrders ?></div>
        <div class="stat-desc">recibidos</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Pedidos hoy</div>
        <div class="stat-value"><?= $todayOrders ?></div>
        <div class="stat-desc">en el día</div>
      </div>
    </div>

    <!-- ÚLTIMOS PEDIDOS -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">Últimos pedidos</div>
        <a href="pedidos.php" class="btn btn-outline btn-sm">Ver todos →</a>
      </div>
      <?php if (!$recientes): ?>
        <p style="color:var(--muted);font-size:.88rem;text-align:center;padding:2rem">No hay pedidos aún. Aparecerán cuando los clientes hagan pedidos desde el catálogo.</p>
      <?php else: ?>
        <div style="overflow-x:auto">
          <table>
            <thead><tr><th>Código</th><th>Cliente</th><th>Producto</th><th>Fecha</th><th>Estado</th><th>Acción</th></tr></thead>
            <tbody>
              <?php foreach ($recientes as $o): ?>
              <tr>
                <td><strong><?= htmlspecialchars($o['codigo']) ?></strong></td>
                <td><?= htmlspecialchars($o['nombre']) ?><br><span style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($o['telefono']) ?></span></td>
                <td><?= htmlspecialchars($o['producto_nombre'] ?? '—') ?></td>
                <td style="font-size:12px;color:var(--muted)"><?= $o['fecha'] ?? '' ?></td>
                <td><span class="badge badge-<?= $estadoBadge[$o['estado']] ?? 'nuevo' ?>"><?= $estadoLabel[$o['estado']] ?? $o['estado'] ?></span></td>
                <td><a href="pedidos.php" class="btn btn-outline btn-sm">Ver</a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>

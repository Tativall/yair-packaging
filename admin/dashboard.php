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
<style>
.admin-layout{display:grid;grid-template-columns:220px 1fr;min-height:calc(100vh - 46px)}
.sidebar{background:var(--primary);padding:1.5rem 0;min-height:100%}
.sidebar-section{padding:.5rem 1.5rem;font-size:10px;font-weight:700;letter-spacing:1.5px;color:rgba(255,255,255,0.3);text-transform:uppercase;margin-top:1rem}
.sidebar-item{display:flex;align-items:center;gap:10px;padding:.7rem 1.5rem;color:rgba(255,255,255,0.65);font-size:.88rem;font-weight:500;cursor:pointer;text-decoration:none;border-left:3px solid transparent;transition:all .2s}
.sidebar-item:hover{background:rgba(255,255,255,0.07);color:#fff}
.sidebar-item.active{background:rgba(232,101,10,0.15);color:var(--accent2);border-left-color:var(--accent)}
.sidebar-badge{margin-left:auto;background:var(--accent);color:#fff;font-size:9px;font-weight:700;padding:1px 6px;border-radius:10px}
.admin-content{padding:2rem;overflow-y:auto;background:var(--bg)}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.75rem}
.page-title{font-family:var(--font-head);font-size:1.8rem;font-weight:700}
.page-sub{font-size:.85rem;color:var(--muted);margin-top:2px}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem}
.stat-card{background:#fff;border:1px solid var(--border);border-radius:10px;padding:1.25rem}
.stat-label{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
.stat-value{font-family:var(--font-head);font-size:2.2rem;font-weight:700;color:var(--text);line-height:1}
.stat-desc{font-size:11px;color:var(--muted);margin-top:4px}
.stat-accent{border-color:var(--accent);background:rgba(232,101,10,.04)}
.stat-accent .stat-value{color:var(--accent)}
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
  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="sidebar-section">Principal</div>
    <a href="dashboard.php" class="sidebar-item active"><span>📊</span> Dashboard</a>
    <a href="productos.php" class="sidebar-item"><span>📦</span> Productos</a>
    <a href="pedidos.php" class="sidebar-item">
      <span>🛒</span> Pedidos
      <?php if ($newOrders > 0): ?><span class="sidebar-badge"><?= $newOrders ?></span><?php endif; ?>
    </a>
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


<!-- SCROLL TO TOP -->
<button class="scroll-top" id="scroll-top" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Volver arriba">&#8679;</button>
<script>
window.addEventListener('scroll',function(){
  document.getElementById('scroll-top').classList.toggle('visible',window.scrollY>300);
});
</script>

<!-- MOBILE NAV -->
<nav class="mobile-nav">
  <a href="dashboard.php" class="mobile-nav-item active"><span class="icon">📊</span>Panel</a>
  <a href="productos.php" class="mobile-nav-item"><span class="icon">📦</span>Productos</a>
  <a href="categorias.php" class="mobile-nav-item"><span class="icon">🏷️</span>Categorías</a>
  <a href="pedidos.php" class="mobile-nav-item">
    <span class="icon">🛒</span>Pedidos
    <?php if ($newOrders > 0): ?><span class="mobile-nav-badge"><?= $newOrders ?></span><?php endif; ?>
  </a>
  <a href="ajustes.php" class="mobile-nav-item"><span class="icon">⚙️</span>Ajustes</a>
</nav>

</body>
</html>

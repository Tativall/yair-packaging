<?php
require_once '../config/supabase.php';
requireAdmin();
$rows = supabase('GET','settings?clave=eq.nombre_negocio&select=valor&limit=1');
$bizName = $rows[0]['valor'] ?? 'Yair Packaging';
$newOrdersRows = supabase('GET','pedidos?estado=eq.nuevo&select=id');
$newOrders = count($newOrdersRows);

// Marcar como leídos los nuevos al entrar
supabase('PATCH','pedidos?estado=eq.nuevo',['estado'=>'leido']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedidos — <?= htmlspecialchars($bizName) ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.admin-layout{display:grid;grid-template-columns:220px 1fr;min-height:calc(100vh - 46px)}
.sidebar{background:var(--primary);padding:1.5rem 0;min-height:100%}
.sidebar-section{padding:.5rem 1.5rem;font-size:10px;font-weight:700;letter-spacing:1.5px;color:rgba(255,255,255,0.3);text-transform:uppercase;margin-top:1rem}
.sidebar-item{display:flex;align-items:center;gap:10px;padding:.7rem 1.5rem;color:rgba(255,255,255,0.65);font-size:.88rem;font-weight:500;text-decoration:none;border-left:3px solid transparent;transition:all .2s}
.sidebar-item:hover{background:rgba(255,255,255,0.07);color:#fff}
.sidebar-item.active{background:rgba(232,101,10,.15);color:var(--accent2);border-left-color:var(--accent)}
.admin-content{padding:2rem;overflow-y:auto;background:var(--bg)}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.75rem}
.page-title{font-family:var(--font-head);font-size:1.8rem;font-weight:700}
.page-sub{font-size:.85rem;color:var(--muted);margin-top:2px}
@media(max-width:700px){.admin-layout{grid-template-columns:1fr}.sidebar{display:none}}
</style>
</head>
<body class="admin-body">
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
    <a href="pedidos.php" class="sidebar-item active"><span>🛒</span> Pedidos</a>
    <div class="sidebar-section">Configuración</div>
    <a href="ajustes.php" class="sidebar-item"><span>⚙️</span> Ajustes</a>
  </div>
  <div class="admin-content">
    <div class="page-header">
      <div>
        <div class="page-title">Pedidos</div>
        <div class="page-sub">Pedidos recibidos de clientes</div>
      </div>
      <div style="display:flex;gap:8px">
        <select id="status-filter" onchange="loadOrdersFiltered()" style="padding:7px 12px;border:1.5px solid var(--border);border-radius:6px;font-size:.85rem;outline:none;font-family:var(--font-body)">
          <option value="">Todos los estados</option>
          <option value="nuevo">Nuevos</option>
          <option value="leido">Leídos</option>
          <option value="en_proceso">En proceso</option>
          <option value="completado">Completados</option>
          <option value="cancelado">Cancelados</option>
        </select>
      </div>
    </div>
    <div id="orders-list"><div style="text-align:center;padding:3rem;color:var(--muted)">Cargando pedidos...</div></div>
  </div>
</div>
<script src="../assets/js/admin.js"></script>
<script>
let allOrders = [];
async function loadOrdersFiltered() {
  if (!allOrders.length) {
    const res = await fetch('../api/orders.php?action=list');
    const d   = await res.json();
    if (d.success) allOrders = d.orders;
  }
  renderOrders(allOrders);
}
window.loadOrders = async function() {
  const res = await fetch('../api/orders.php?action=list');
  const d   = await res.json();
  if (d.success) { allOrders = d.orders; renderOrders(allOrders); }
};
loadOrdersFiltered();
</script>
</body>
</html>

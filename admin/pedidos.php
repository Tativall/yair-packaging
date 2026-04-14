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

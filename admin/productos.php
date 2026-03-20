<?php
session_start();
require_once '../config/database.php';
requireAdmin();
$db = getDB();
$stmtS = $db->query("SELECT clave, valor FROM settings WHERE clave='nombre_negocio'");
$r = $stmtS->fetch();
$bizName = $r ? $r['valor'] : 'Yair Packaging';
$newOrders = $db->query("SELECT COUNT(*) FROM pedidos WHERE estado='nuevo'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Productos — <?= htmlspecialchars($bizName) ?></title>
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
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.75rem}
.page-title{font-family:var(--font-head);font-size:1.8rem;font-weight:700}
.page-sub{font-size:.85rem;color:var(--muted);margin-top:2px}
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
    <a href="productos.php" class="sidebar-item active"><span>📦</span> Productos</a>
    <a href="pedidos.php" class="sidebar-item">
      <span>🛒</span> Pedidos
      <?php if ($newOrders > 0): ?><span class="sidebar-badge"><?= $newOrders ?></span><?php endif; ?>
    </a>
    <div class="sidebar-section">Configuración</div>
    <a href="ajustes.php" class="sidebar-item"><span>⚙️</span> Ajustes</a>
  </div>

  <div class="admin-content">
    <div class="page-header">
      <div>
        <div class="page-title">Productos</div>
        <div class="page-sub">Administrá tu catálogo completo</div>
      </div>
      <button class="btn btn-accent" onclick="openProductModal(null)">+ Nuevo producto</button>
    </div>

    <div class="table-wrap">
      <div class="table-toolbar">
        <div class="table-search">
          <input type="text" id="prod-search" placeholder="🔍 Buscar producto..." oninput="filterTable()" />
        </div>
        <select id="cat-filter" onchange="filterTable()" style="padding:7px 12px;border:1.5px solid var(--border);border-radius:6px;font-size:.85rem;outline:none;font-family:var(--font-body)">
          <option value="">Todas las categorías</option>
          <option>Cartones</option><option>Plásticos</option><option>Isopor</option><option>Accesorios</option>
        </select>
      </div>
      <div style="overflow-x:auto">
        <table>
          <thead><tr><th>Imagen</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Unidad</th><th>Etiqueta</th><th>Acciones</th></tr></thead>
          <tbody id="prod-tbody"><tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--muted)">Cargando...</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- MODAL PRODUCTO -->
<div class="overlay" id="overlay-product">
  <div class="modal" style="max-width:540px">
    <button class="modal-close" onclick="closeModal('product')">✕</button>
    <h3 id="prod-modal-title">Nuevo producto</h3>
    <p class="modal-sub">Completá los datos del producto</p>
    <input type="hidden" id="prod-id" />
    <input type="hidden" id="prod-photo-current" />

    <div class="form-row">
      <div class="form-group">
        <label>Nombre *</label>
        <input type="text" id="prod-nombre" placeholder="Ej: Caja Corrugada Simple" />
      </div>
      <div class="form-group">
        <label>Categoría *</label>
        <select id="prod-cat">
          <option value="">Seleccionar...</option>
          <option>Cartones</option><option>Plásticos</option><option>Isopor</option><option>Accesorios</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label>Descripción</label>
      <textarea id="prod-desc" placeholder="Describe el producto brevemente..."></textarea>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Precio (₲)</label>
        <input type="number" id="prod-precio" placeholder="8500" min="0" />
      </div>
      <div class="form-group">
        <label>Unidad de precio</label>
        <input type="text" id="prod-unidad" placeholder="unid, rollo, kg..." />
      </div>
    </div>

    <div class="form-group">
      <label>Medidas disponibles (separadas por coma)</label>
      <input type="text" id="prod-medidas" placeholder="30×20×20, 40×30×30, A medida" />
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Etiqueta especial</label>
        <select id="prod-badge">
          <option value="">Sin etiqueta</option>
          <option value="popular">⭐ Popular</option>
          <option value="new">🆕 Nuevo</option>
          <option value="oferta">🔥 Oferta</option>
        </select>
      </div>
      <div class="form-group">
        <label>Emoji (si no hay foto)</label>
        <input type="text" id="prod-emoji" placeholder="📦" maxlength="4" />
      </div>
    </div>

    <div class="form-group">
      <label>Foto del producto</label>
      <div class="upload-area" onclick="document.getElementById('prod-photo-input').click()">
        <div style="font-size:1.5rem;margin-bottom:4px">📷</div>
        <strong>Tocá para subir foto o sacar una</strong>
        <p>JPG, PNG, WEBP — máx. 3MB</p>
        <input type="file" id="prod-photo-input" accept="image/*" capture="environment" onchange="previewPhoto(event)" style="display:none" />
        <img id="prod-photo-preview" class="upload-preview" alt="preview" />
      </div>
    </div>

    <div class="modal-footer">
      <button class="btn btn-accent" style="flex:1;justify-content:center" onclick="saveProduct()">💾 Guardar producto</button>
      <button class="btn btn-outline" onclick="closeModal('product')">Cancelar</button>
    </div>
  </div>
</div>

<script src="../assets/js/admin.js"></script>
<script>
let allProds = [];
async function init() {
  const res  = await fetch('../api/products.php?action=list');
  const data = await res.json();
  if (data.success) { allProds = data.products; renderProductTable(allProds); }
}
function filterTable() {
  renderProductTable(allProds);
}
// Override para usar allProds local
const _orig = window.renderProductTable;
window.renderProductTable = function(products) {
  if (!products && allProds) products = allProds;
  _orig && _orig(products);
};
// Override loadProducts para actualizar allProds
window.loadProducts = async function() {
  const res  = await fetch('../api/products.php?action=list');
  const data = await res.json();
  if (data.success) { allProds = data.products; renderProductTable(allProds); }
};
init();
</script>
</body>
</html>

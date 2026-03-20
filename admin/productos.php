<?php
session_start();
require_once '../config/supabase.php';
requireAdmin();

$settings = supabase('GET','settings?select=clave,valor');
$bizName = '';
foreach($settings as $s) if($s['clave']==='nombre_negocio') $bizName=$s['valor'];
$bizName = $bizName?:'Yair Packaging';

$pedidos = supabase('GET','pedidos?select=id&estado=eq.nuevo');
$newOrders = count($pedidos);

// Obtener categorías para el select
$categorias = supabase('GET','categorias?select=id,nombre&order=orden.asc');
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
    <a href="categorias.php" class="sidebar-item"><span>🏷️</span> Categorías</a>
    <a href="pedidos.php" class="sidebar-item"><span>🛒</span> Pedidos<?php if($newOrders>0): ?><span class="sidebar-badge"><?= $newOrders ?></span><?php endif; ?></a>
    <div class="sidebar-section">Configuración</div>
    <a href="ajustes.php" class="sidebar-item"><span>⚙️</span> Ajustes</a>
  </div>
  <div class="admin-content">
    <div class="page-header"><div><div class="page-title">Productos</div><div class="page-sub">Administrá tu catálogo completo</div></div><button class="btn btn-accent" onclick="openProductModal(null)">+ Nuevo producto</button></div>
    <div class="table-wrap">
      <div class="table-toolbar">
        <div class="table-search"><input type="text" id="prod-search" placeholder="🔍 Buscar producto..." oninput="filterTable()" /></div>
        <select id="cat-filter" onchange="filterTable()" style="padding:7px 12px;border:1.5px solid var(--border);border-radius:6px">
          <option value="">Todas las categorías</option>
          <?php foreach($categorias as $cat): ?>
          <option value="<?= htmlspecialchars($cat['nombre']) ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="overflow-x:auto"><table><thead><th>Imagen</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Unidad</th><th>Etiqueta</th><th>Acciones</th></thead><tbody id="prod-tbody"><tr><td colspan="7" style="text-align:center;padding:2rem">Cargando...</td></tr></tbody></table></div>
    </div>
  </div>
</div>
<div class="overlay" id="overlay-product"><div class="modal" style="max-width:540px"><button class="modal-close" onclick="closeModal('product')">✕</button><h3 id="prod-modal-title">Nuevo producto</h3><p class="modal-sub">Completá los datos del producto</p><input type="hidden" id="prod-id" /><input type="hidden" id="prod-photo-current" />
<div class="form-row"><div class="form-group"><label>Nombre *</label><input type="text" id="prod-nombre" /></div><div class="form-group"><label>Categoría *</label><select id="prod-cat"><option value="">Seleccionar...</option><?php foreach($categorias as $cat): ?><option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option><?php endforeach; ?></select></div></div>
<div class="form-group"><label>Descripción</label><textarea id="prod-desc"></textarea></div>
<div class="form-row"><div class="form-group"><label>Precio (₲)</label><input type="number" id="prod-precio" /></div><div class="form-group"><label>Unidad</label><input type="text" id="prod-unidad" placeholder="unid, rollo, kg..." /></div></div>
<div class="form-group"><label>Medidas (separadas por coma)</label><input type="text" id="prod-medidas" placeholder="30×20×20, 40×30×30" /></div>
<div class="form-row"><div class="form-group"><label>Etiqueta</label><select id="prod-badge"><option value="">Sin etiqueta</option><option value="popular">⭐ Popular</option><option value="new">🆕 Nuevo</option><option value="oferta">🔥 Oferta</option></select></div><div class="form-group"><label>Emoji</label><input type="text" id="prod-emoji" placeholder="📦" maxlength="4" /></div></div>
<div class="form-group"><label>Foto</label><div class="upload-area" onclick="document.getElementById('prod-photo-input').click()"><div style="font-size:1.5rem">📷</div><strong>Tocá para subir foto</strong><p>JPG, PNG, WEBP — máx. 3MB</p><input type="file" id="prod-photo-input" accept="image/*" capture="environment" onchange="previewPhoto(event)" style="display:none" /><img id="prod-photo-preview" class="upload-preview" alt="preview" /></div></div>
<div class="modal-footer"><button class="btn btn-accent" style="flex:1" onclick="saveProduct()">💾 Guardar</button><button class="btn btn-outline" onclick="closeModal('product')">Cancelar</button></div

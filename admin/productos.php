<?php
require_once '../config/supabase.php';
requireAdmin();
$rows = supabase('GET','settings?clave=eq.nombre_negocio&select=valor&limit=1');
$bizName = $rows[0]['valor'] ?? 'Yair Packaging';
$newOrders = count(supabase('GET','pedidos?estado=eq.nuevo&select=id'));
$categorias = supabase('GET','categorias?select=*&order=orden.asc');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Productos — <?= htmlspecialchars($bizName) ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.prod-card-admin{background:#fff;border:1.5px solid var(--border);border-radius:var(--radius);overflow:hidden;transition:all .2s;box-shadow:var(--shadow)}
.prod-card-admin:hover{box-shadow:var(--shadow-md);border-color:var(--accent)}
.prod-card-img{width:100%;height:120px;object-fit:cover;background:var(--bg);display:flex;align-items:center;justify-content:center;font-size:2.5rem;position:relative;overflow:hidden}
.prod-card-img img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0}
.prod-card-body{padding:.85rem}
.prod-card-name{font-weight:700;font-size:.92rem;margin-bottom:3px;line-height:1.3}
.prod-card-cat{font-size:.7rem;color:var(--muted);font-weight:600;margin-bottom:6px}
.prod-card-price{font-size:1rem;font-weight:800;color:var(--accent)}
.prod-card-footer{display:flex;gap:6px;margin-top:.75rem;padding-top:.75rem;border-top:1px solid var(--border)}
.prod-card-footer .btn{flex:1;font-size:.75rem;padding:7px 8px}
.prod-grid-admin{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:.85rem}
@media(max-width:500px){.prod-grid-admin{grid-template-columns:repeat(2,1fr)}}
</style>
</head>
<body>
<div class="topbar">
  <a href="../index.php" class="topbar-logo">YAIR <span>PACKAGING</span></a>
  <div class="topbar-actions">
    <a href="../index.php" class="btn btn-ghost btn-sm" target="_blank">Catálogo</a>
    <a href="logout.php" class="btn btn-ghost btn-sm">Salir</a>
  </div>
</div>

<div class="admin-layout">
  <div class="sidebar">
    <div class="sidebar-section">Principal</div>
    <a href="dashboard.php" class="sidebar-item"><span class="icon">📊</span> Dashboard</a>
    <a href="productos.php" class="sidebar-item active"><span class="icon">📦</span> Productos</a>
    <a href="categorias.php" class="sidebar-item"><span class="icon">🏷️</span> Categorías</a>
    <a href="pedidos.php" class="sidebar-item">
      <span class="icon">🛒</span> Pedidos
      <?php if($newOrders>0):?><span class="sidebar-badge"><?=$newOrders?></span><?php endif;?>
    </a>
    <div class="sidebar-section">Config</div>
    <a href="ajustes.php" class="sidebar-item"><span class="icon">⚙️</span> Ajustes</a>
  </div>

  <div class="admin-content">
    <div class="page-header">
      <div><div class="page-title">Productos</div><div class="page-sub">Administrá tu catálogo</div></div>
      <button class="btn btn-accent" onclick="openProductModal(null)">+ Nuevo</button>
    </div>

    <!-- Filtros -->
    <div style="display:flex;gap:8px;margin-bottom:1.25rem;flex-wrap:wrap">
      <div class="search-box" style="max-width:100%;flex:1">
        <span>🔍</span>
        <input type="text" id="prod-search" placeholder="Buscar producto..." oninput="filterProds()" />
      </div>
      <select id="cat-filter" onchange="filterProds()" style="padding:9px 12px;border:1.5px solid var(--border);border-radius:10px;font-family:var(--font);font-size:.85rem;outline:none;background:#fff">
        <option value="">Todas</option>
        <?php foreach($categorias as $c): ?>
        <option value="<?= htmlspecialchars($c['nombre']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="prod-grid-admin" id="prod-grid">
      <div style="grid-column:1/-1;text-align:center;padding:2rem;color:var(--muted)">Cargando...</div>
    </div>
  </div>
</div>

<!-- MODAL PRODUCTO (mobile-optimized) -->
<div class="overlay" id="overlay-product">
  <div class="modal" style="max-width:560px">
    <div class="modal-handle"></div>
    <button class="modal-close" onclick="closeModal('product')">✕</button>
    <h3 id="prod-modal-title">Nuevo producto</h3>
    <p class="modal-sub">Completá los datos del producto</p>
    <input type="hidden" id="prod-id" />
    <input type="hidden" id="prod-photo-current" />

    <div class="form-row">
      <div class="form-group">
        <label>Nombre *</label>
        <input type="text" id="prod-nombre" placeholder="Nombre del producto" autocomplete="off" />
      </div>
      <div class="form-group">
        <label>Categoría *</label>
        <select id="prod-cat">
          <option value="">Seleccionar...</option>
          <?php foreach($categorias as $c): ?>
          <option value="<?= htmlspecialchars($c['nombre']) ?>"><?= htmlspecialchars($c['icono']) ?> <?= htmlspecialchars($c['nombre']) ?></option>
          <?php endforeach; ?>
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
        <input type="number" id="prod-precio" placeholder="8500" min="0" inputmode="numeric" />
      </div>
      <div class="form-group">
        <label>Unidad</label>
        <input type="text" id="prod-unidad" placeholder="unid, rollo, kg..." />
      </div>
    </div>

    <div class="form-group">
      <label>Medidas (separadas por coma)</label>
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
        <label>Emoji (sin foto)</label>
        <input type="text" id="prod-emoji" placeholder="📦" maxlength="4" style="font-size:1.5rem;text-align:center" />
      </div>
    </div>

    <!-- FOTO — mobile optimized -->
    <div class="form-group">
      <label>📷 Foto del producto</label>
      <div class="upload-area" id="upload-area">
        <input type="file" id="prod-photo-input" accept="image/*" onchange="previewPhoto(event)" />
        <div class="upload-icon">📷</div>
        <div class="upload-text">Tocá para elegir foto</div>
        <div class="upload-hint">JPG, PNG, WEBP — máx. 3MB · En celular podés elegir galería o cámara</div>
        <img id="prod-photo-preview" class="upload-preview" alt="preview" />
      </div>
      <button type="button" onclick="clearPhoto()" id="btn-clear-photo" style="display:none;margin-top:6px" class="btn btn-outline btn-sm">🗑 Quitar foto</button>
    </div>

    <div class="modal-footer">
      <button class="btn btn-accent btn-block" onclick="saveProduct()">💾 Guardar producto</button>
    </div>
  </div>
</div>

<!-- MOBILE NAV -->
<nav class="mobile-nav">
  <a href="dashboard.php" class="mobile-nav-item"><span class="icon">📊</span>Panel</a>
  <a href="productos.php" class="mobile-nav-item active"><span class="icon">📦</span>Productos</a>
  <a href="categorias.php" class="mobile-nav-item"><span class="icon">🏷️</span>Categorías</a>
  <a href="pedidos.php" class="mobile-nav-item">
    <span class="icon">🛒</span>Pedidos
    <?php if($newOrders>0):?><span class="mobile-nav-badge"><?=$newOrders?></span><?php endif;?>
  </a>
  <a href="ajustes.php" class="mobile-nav-item"><span class="icon">⚙️</span>Config</a>
</nav>

<script src="../assets/js/admin.js"></script>
<script>
let allProds = [];

async function loadProds() {
  const res  = await fetch('/api/products.php?action=list');
  const data = await res.json();
  if (data.success) { allProds = data.products; filterProds(); }
}

function filterProds() {
  const search = document.getElementById('prod-search').value.toLowerCase();
  const catF   = document.getElementById('cat-filter').value;
  let filtered = allProds.filter(p => {
    const ms = !search || p.nombre.toLowerCase().includes(search) || (p.descripcion||'').toLowerCase().includes(search);
    const mc = !catF   || p.categoria === catF;
    return ms && mc;
  });
  renderGrid(filtered);
}

function renderGrid(prods) {
  const grid = document.getElementById('prod-grid');
  if (!prods.length) {
    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--muted)">No se encontraron productos</div>';
    return;
  }
  const badgeMap = {popular:'⭐',new:'🆕',oferta:'🔥'};
  grid.innerHTML = prods.map(p => {
    const precio = Number(p.precio||0).toLocaleString('es-PY');
    const foto   = p.foto ? `<img src="${p.foto}" alt="${p.nombre}" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0">` : '';
    const badge  = p.etiqueta ? `<span style="position:absolute;top:6px;right:6px;background:var(--accent);color:#fff;font-size:.65rem;font-weight:700;padding:2px 7px;border-radius:10px">${badgeMap[p.etiqueta]||p.etiqueta}</span>` : '';
    return `<div class="prod-card-admin">
      <div class="prod-card-img" style="background:#f1f5f9">
        ${foto}<span style="position:relative;z-index:1;${p.foto?'opacity:0':''}">${p.emoji||'📦'}</span>${badge}
      </div>
      <div class="prod-card-body">
        <div class="prod-card-cat">${p.categoria||'—'}</div>
        <div class="prod-card-name">${p.nombre}</div>
        <div class="prod-card-price">₲ ${precio}<span style="font-size:.7rem;color:var(--muted);font-weight:400"> /${p.unidad||'unid'}</span></div>
        <div class="prod-card-footer">
          <button class="btn btn-outline" onclick="openProductModal(${p.id})">✏️ Editar</button>
          <button class="btn btn-red" onclick="deleteProduct(${p.id},'${p.nombre.replace(/'/g,'')}')">🗑</button>
        </div>
      </div>
    </div>`;
  }).join('');
}

async function openProductModal(id) {
  document.getElementById('prod-modal-title').textContent = id ? 'Editar producto' : 'Nuevo producto';
  document.getElementById('prod-id').value = id || '';
  ['prod-nombre','prod-cat','prod-desc','prod-precio','prod-unidad','prod-medidas','prod-badge'].forEach(fid=>{
    const el=document.getElementById(fid); if(el) el.value='';
  });
  document.getElementById('prod-emoji').value = '📦';
  document.getElementById('prod-photo-preview').style.display = 'none';
  document.getElementById('prod-photo-current').value = '';
  document.getElementById('btn-clear-photo').style.display = 'none';
  document.getElementById('prod-photo-input').value = '';

  if (id) {
    const res  = await fetch(`/api/products.php?action=get&id=${id}`);
    const data = await res.json();
    if (data.success) {
      const p = data.product;
      document.getElementById('prod-nombre').value  = p.nombre||'';
      document.getElementById('prod-cat').value     = p.categoria||'';
      document.getElementById('prod-desc').value    = p.descripcion||'';
      document.getElementById('prod-precio').value  = p.precio||'';
      document.getElementById('prod-unidad').value  = p.unidad||'';
      document.getElementById('prod-medidas').value = p.medidas||'';
      document.getElementById('prod-badge').value   = p.etiqueta||'';
      document.getElementById('prod-emoji').value   = p.emoji||'📦';
      document.getElementById('prod-photo-current').value = p.foto||'';
      if (p.foto) {
        const prev = document.getElementById('prod-photo-preview');
        prev.src = p.foto; prev.style.display = 'block';
        document.getElementById('btn-clear-photo').style.display = 'inline-flex';
      }
    }
  }
  document.getElementById('overlay-product').classList.add('open');
  setTimeout(()=>document.getElementById('prod-nombre').focus(), 300);
}

function previewPhoto(e) {
  const file = e.target.files[0];
  if (!file) return;
  if (file.size > 3*1024*1024) { showToast('La foto es demasiado grande (máx 3MB)','error'); return; }
  const reader = new FileReader();
  reader.onload = ev => {
    const prev = document.getElementById('prod-photo-preview');
    prev.src = ev.target.result; prev.style.display = 'block';
    document.getElementById('btn-clear-photo').style.display = 'inline-flex';
  };
  reader.readAsDataURL(file);
}

function clearPhoto() {
  document.getElementById('prod-photo-preview').style.display = 'none';
  document.getElementById('prod-photo-input').value = '';
  document.getElementById('prod-photo-current').value = '';
  document.getElementById('btn-clear-photo').style.display = 'none';
}

async function saveProduct() {
  const nombre = document.getElementById('prod-nombre').value.trim();
  const cat    = document.getElementById('prod-cat').value;
  if (!nombre) { showToast('El nombre es obligatorio','error'); return; }
  if (!cat)    { showToast('Seleccioná una categoría','error'); return; }

  const id = document.getElementById('prod-id').value;
  const formData = new FormData();
  formData.append('action', id ? 'update' : 'create');
  formData.append('id', id);
  formData.append('nombre', nombre);
  formData.append('categoria', cat);
  formData.append('descripcion', document.getElementById('prod-desc').value.trim());
  formData.append('precio', document.getElementById('prod-precio').value||'0');
  formData.append('unidad', document.getElementById('prod-unidad').value.trim()||'unid');
  formData.append('medidas', document.getElementById('prod-medidas').value.trim());
  formData.append('etiqueta', document.getElementById('prod-badge').value);
  formData.append('emoji', document.getElementById('prod-emoji').value||'📦');
  formData.append('foto_actual', document.getElementById('prod-photo-current').value);

  const photoFile = document.getElementById('prod-photo-input').files[0];
  if (photoFile) formData.append('foto', photoFile);

  const btn = document.querySelector('#overlay-product .btn-accent');
  btn.textContent = 'Guardando...'; btn.disabled = true;

  try {
    const res  = await fetch('/api/products.php', {method:'POST', body:formData});
    const data = await res.json();
    if (data.success) {
      closeModal('product');
      showToast(id ? '✅ Producto actualizado' : '✅ Producto creado');
      loadProds();
    } else { showToast(data.error||'Error al guardar','error'); }
  } catch(e) { showToast('Error de conexión','error'); }
  finally { btn.textContent = '💾 Guardar producto'; btn.disabled = false; }
}

async function deleteProduct(id, nombre) {
  if (!confirm(`¿Eliminás "${nombre}"?`)) return;
  const res  = await fetch(`/api/products.php?action=delete&id=${id}`, {method:'DELETE'});
  const data = await res.json();
  if (data.success) { showToast('Producto eliminado'); loadProds(); }
  else showToast('Error al eliminar','error');
}

function closeModal(id) { document.getElementById('overlay-'+id).classList.remove('open'); }
document.querySelectorAll('.overlay').forEach(ov=>ov.addEventListener('click',e=>{if(e.target===ov)ov.classList.remove('open')}));
loadProds();
</script>
</body>
</html>

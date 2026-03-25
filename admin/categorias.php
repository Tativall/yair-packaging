<?php
// admin/categorias.php — Gestión de categorías
require_once '../config/supabase.php';
requireAdmin();

$rows = supabase('GET','settings?clave=eq.nombre_negocio&select=valor&limit=1');
$bizName = $rows[0]['valor'] ?? 'Yair Packaging';
$newOrders = count(supabase('GET','pedidos?estado=eq.nuevo&select=id'));

// Contar productos por categoría
$productos = supabase('GET','productos?select=categoria_id&activo=eq.true');
$catCount = [];
foreach ($productos as $p) {
    $id = $p['categoria_id'];
    $catCount[$id] = ($catCount[$id] ?? 0) + 1;
}

$categorias = supabase('GET','categorias?select=*&order=orden.asc');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Categorías — <?= htmlspecialchars($bizName) ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
<div class="topbar">
  <a href="../index.php" class="topbar-logo">YAIR <span>PACKAGING</span></a>
  <div class="topbar-actions">
    <a href="../index.php" class="btn btn-ghost btn-sm" target="_blank">🌐 Catálogo</a>
    <a href="logout.php" class="btn btn-ghost btn-sm">Salir</a>
  </div>
</div>

<div class="admin-layout">
  <!-- SIDEBAR desktop -->
  <div class="sidebar">
    <div class="sidebar-section">Principal</div>
    <a href="dashboard.php" class="sidebar-item"><span class="icon">📊</span> Dashboard</a>
    <a href="productos.php" class="sidebar-item"><span class="icon">📦</span> Productos</a>
    <a href="categorias.php" class="sidebar-item active"><span class="icon">🏷️</span> Categorías</a>
    <a href="pedidos.php" class="sidebar-item">
      <span class="icon">🛒</span> Pedidos
      <?php if($newOrders>0):?><span class="sidebar-badge"><?=$newOrders?></span><?php endif;?>
    </a>
    <div class="sidebar-section">Config</div>
    <a href="ajustes.php" class="sidebar-item"><span class="icon">⚙️</span> Ajustes</a>
  </div>

  <div class="admin-content">
    <div class="page-header">
      <div>
        <div class="page-title">Categorías</div>
        <div class="page-sub">Organizá tus productos por categoría</div>
      </div>
      <button class="btn btn-accent" onclick="openCatModal(null)">+ Nueva categoría</button>
    </div>

    <div class="cat-grid" id="cat-grid">
      <?php foreach($categorias as $c): ?>
      <div class="cat-card">
        <div class="cat-icon" style="background:<?= htmlspecialchars($c['color']) ?>"><?= htmlspecialchars($c['icono']) ?></div>
        <div>
          <div class="cat-name"><?= htmlspecialchars($c['nombre']) ?></div>
          <div class="cat-count"><?= $catCount[$c['id']] ?? 0 ?> productos</div>
        </div>
        <div class="cat-actions">
          <button class="btn btn-outline btn-sm" onclick='openCatModal(<?= json_encode($c) ?>)'>✏️</button>
          <button class="btn btn-red btn-sm" onclick="deleteCat(<?= $c['id'] ?>, '<?= htmlspecialchars($c['nombre']) ?>')">🗑</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">ℹ️ Sobre las categorías</div></div>
      <p style="font-size:.88rem;color:var(--muted);line-height:1.7">
        Las categorías organizan tus productos en el catálogo. Podés crear tantas como necesites.<br>
        Cada categoría tiene un <strong>nombre</strong>, un <strong>emoji</strong> y un <strong>color</strong> de fondo.<br>
        Los productos se asignan a una categoría cuando los creás o editás.
      </p>
    </div>
  </div>
</div>

<!-- MODAL CATEGORÍA -->
<div class="overlay" id="overlay-cat">
  <div class="modal">
    <div class="modal-handle"></div>
    <button class="modal-close" onclick="closeModal('cat')">✕</button>
    <h3 id="cat-modal-title">Nueva categoría</h3>
    <p class="modal-sub">Completá los datos de la categoría</p>
    <input type="hidden" id="cat-id" />

    <div class="form-group">
      <label>Nombre *</label>
      <input type="text" id="cat-nombre" placeholder="Ej: Cartones, Plásticos, Maderas..." />
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Emoji representativo</label>
        <input type="text" id="cat-icono" placeholder="📦" maxlength="4" style="font-size:1.5rem;text-align:center" />
      </div>
      <div class="form-group">
        <label>Color de fondo</label>
        <input type="color" id="cat-color" value="#fff3e0" style="height:44px;padding:4px 8px;cursor:pointer" />
      </div>
    </div>
    <div class="form-group">
      <label>Orden en el catálogo</label>
      <input type="number" id="cat-orden" placeholder="1, 2, 3..." min="1" />
    </div>

    <div class="modal-footer">
      <button class="btn btn-accent btn-block" onclick="saveCat()">💾 Guardar categoría</button>
    </div>
  </div>
</div>

<!-- MOBILE NAV -->
<nav class="mobile-nav">
  <a href="dashboard.php" class="mobile-nav-item"><span class="icon">📊</span>Dashboard</a>
  <a href="productos.php" class="mobile-nav-item"><span class="icon">📦</span>Productos</a>
  <a href="categorias.php" class="mobile-nav-item active"><span class="icon">🏷️</span>Categorías</a>
  <a href="pedidos.php" class="mobile-nav-item">
    <span class="icon">🛒</span>Pedidos
    <?php if($newOrders>0):?><span class="mobile-nav-badge"><?=$newOrders?></span><?php endif;?>
  </a>
  <a href="ajustes.php" class="mobile-nav-item"><span class="icon">⚙️</span>Ajustes</a>
</nav>

<script src="../assets/js/admin.js"></script>
<script>
function openCatModal(cat) {
  document.getElementById('cat-modal-title').textContent = cat ? 'Editar categoría' : 'Nueva categoría';
  document.getElementById('cat-id').value    = cat?.id || '';
  document.getElementById('cat-nombre').value = cat?.nombre || '';
  document.getElementById('cat-icono').value  = cat?.icono  || '📦';
  document.getElementById('cat-color').value  = cat?.color  || '#fff3e0';
  document.getElementById('cat-orden').value  = cat?.orden  || '';
  document.getElementById('overlay-cat').classList.add('open');
}

async function saveCat() {
  const nombre = document.getElementById('cat-nombre').value.trim();
  if (!nombre) { showToast('El nombre es obligatorio', 'error'); return; }
  const id     = document.getElementById('cat-id').value;
  const payload = {
    nombre, icono: document.getElementById('cat-icono').value || '📦',
    color: document.getElementById('cat-color').value, orden: parseInt(document.getElementById('cat-orden').value)||99
  };
  const action = id ? 'update' : 'create';
  const res    = await fetch('/api/categorias.php?action='+action+(id?'&id='+id:''), {
    method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)
  });
  const data = await res.json();
  if (data.success) { showToast('✅ Categoría guardada'); closeModal('cat'); setTimeout(()=>location.reload(),800); }
  else showToast(data.error||'Error', 'error');
}

async function deleteCat(id, nombre) {
  if (!confirm(`¿Eliminás la categoría "${nombre}"? Los productos de esta categoría quedarán sin categoría.`)) return;
  const res  = await fetch('/api/categorias.php?action=delete&id='+id, {method:'DELETE'});
  const data = await res.json();
  if (data.success) { showToast('Categoría eliminada'); setTimeout(()=>location.reload(),800); }
  else showToast(data.error||'Error al eliminar','error');
}

function closeModal(id) { document.getElementById('overlay-'+id).classList.remove('open'); }
document.querySelectorAll('.overlay').forEach(ov=>ov.addEventListener('click',e=>{if(e.target===ov)ov.classList.remove('open')}));
</script>
</body>
</html>

<?php
require_once 'config/supabase.php';
$rows = supabase('GET','settings?select=clave,valor');
$settings = [];
foreach ($rows as $r) $settings[$r['clave']] = $r['valor'];
$bizName   = $settings['nombre_negocio'] ?? 'Yair Packaging';
$slogan    = $settings['slogan']         ?? 'Embalajes profesionales para tu negocio';
$whatsapp  = $settings['whatsapp']       ?? '595981000000';
$direccion = $settings['direccion']      ?? 'Asunción, Paraguay';
$horario   = $settings['horario']        ?? 'Lun-Vie 8:00-18:00';
$totalProds = count(supabase('GET','productos?select=id&activo=eq.true'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#0f172a">
<title><?= htmlspecialchars($bizName) ?> — Catálogo</title>
<meta name="description" content="<?= htmlspecialchars($slogan) ?>">
<link rel="stylesheet" href="assets/css/style.css">
<style>
/* Extra catalog styles */
.filter-chips{display:flex;gap:6px;padding:.75rem 1rem;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;border-bottom:1px solid var(--border);background:#fff}
.filter-chips::-webkit-scrollbar{display:none}
.chip{display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:20px;font-size:.78rem;font-weight:600;white-space:nowrap;cursor:pointer;border:1.5px solid var(--border);background:#fff;color:var(--muted);transition:all .2s;flex-shrink:0}
.chip.active{background:var(--accent);border-color:var(--accent);color:#fff}
.empty-state{text-align:center;padding:4rem 1rem;color:var(--muted)}
.empty-state .big{font-size:3.5rem;margin-bottom:.75rem}
</style>
</head>
<body>
<div class="catalog-bg">

<!-- TOPBAR -->
<div class="topbar">
  <div class="topbar-logo"><?= htmlspecialchars($bizName) ?></div>
  <div class="topbar-actions">
    <a href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>" target="_blank" class="btn btn-whatsapp btn-sm">💬 WhatsApp</a>
  </div>
</div>

<!-- HERO -->
<div class="hero">
  <div class="hero-inner">
    <div class="hero-badge">✨ Catálogo 2026</div>
    <h1>Embalajes /<br><span>Packaging</span><br>profesional</h1>
    <p><?= htmlspecialchars($slogan) ?></p>
    <div class="hero-actions">
      <button class="btn btn-accent btn-lg" onclick="openOrderModal(null,'')">📋 Hacer pedido</button>
      <a href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>?text=Hola!%20Vi%20su%20cat%C3%A1logo%20y%20quiero%20m%C3%A1s%20informaci%C3%B3n." target="_blank" class="btn btn-whatsapp btn-lg">💬 WhatsApp</a>
    </div>
    <div class="hero-stats">
      <div class="hero-stat"><strong id="stat-total"><?= $totalProds ?></strong><span>Productos</span></div>
      <div class="hero-stat"><strong>24h</strong><span>Entrega</span></div>
      <div class="hero-stat"><strong>15+</strong><span>Años</span></div>
    </div>
  </div>
</div>

<!-- FILTER CHIPS -->
<div class="filter-chips" id="filter-chips">
  <div class="chip active" onclick="filterCat('all',this)">🏪 Todos</div>
  <!-- Se cargan dinámicamente -->
</div>

<!-- CATÁLOGO -->
<div class="catalog-wrap">
  <div id="catalog-content">
    <div class="empty-state"><div class="big">⏳</div><p>Cargando productos...</p></div>
  </div>

  <!-- CONTACT STRIP -->
  <div class="contact-strip">
    <div>
      <h2>¿Necesitás algo especial?</h2>
      <p>Fabricamos a medida — <?= htmlspecialchars($horario) ?></p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:.5rem">
      <button class="btn btn-accent" onclick="openOrderModal(null,'')">📋 Pedir ahora</button>
      <a href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>" target="_blank" class="btn btn-whatsapp">💬 WhatsApp</a>
    </div>
  </div>
</div>

<footer>
  <p>© 2025 <span><?= htmlspecialchars($bizName) ?></span> — <?= htmlspecialchars($direccion) ?></p>
</footer>

<!-- MODAL PEDIDO -->
<div class="overlay" id="overlay-order">
  <div class="modal" style="max-width:480px">
    <div class="modal-handle"></div>
    <button class="modal-close" onclick="closeModal('order')">✕</button>
    <h3>Hacer un pedido</h3>
    <p class="modal-sub">Completá tus datos y te contactamos enseguida</p>
    <input type="hidden" id="order-product-id" />
    <div class="form-group">
      <label>Tu nombre *</label>
      <input type="text" id="order-name" placeholder="Ej: Juan García" autocomplete="name" />
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Teléfono *</label>
        <input type="tel" id="order-phone" placeholder="0981 000 000" autocomplete="tel" inputmode="tel" />
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" id="order-email" placeholder="tu@email.com" autocomplete="email" />
      </div>
    </div>
    <div class="form-group">
      <label>Empresa (opcional)</label>
      <input type="text" id="order-company" placeholder="Nombre de tu empresa" />
    </div>
    <div class="form-group">
      <label>Producto de interés</label>
      <input type="text" id="order-product-name" placeholder="¿Qué producto buscás?" />
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Cantidad aprox.</label>
        <input type="text" id="order-qty" placeholder="Ej: 500/mes" inputmode="numeric" />
      </div>
      <div class="form-group">
        <label>Medida/Tamaño</label>
        <input type="text" id="order-size" placeholder="Ej: 40×30×30" />
      </div>
    </div>
    <div class="form-group">
      <label>Comentarios</label>
      <textarea id="order-notes" placeholder="Impresión, color, urgencia..."></textarea>
    </div>
    <div class="modal-footer">
      <button class="btn btn-whatsapp btn-block" style="flex:1" onclick="submitOrder('whatsapp')">💬 WhatsApp</button>
      <button class="btn btn-accent btn-block" style="flex:1" onclick="submitOrder('email')">📧 Email</button>
    </div>
  </div>
</div>

<script>
let allProducts = [];
let currentFilter = 'all';

async function loadProducts() {
  try {
    const [prodRes, catRes] = await Promise.all([
      fetch('/api/products.php?action=list&active=1'),
      fetch('/api/categorias.php?action=list')
    ]);
    const prodData = await prodRes.json();
    const catData  = await catRes.json();

    if (prodData.success) {
      allProducts = prodData.products;
      document.getElementById('stat-total').textContent = allProducts.length;
    }
    if (catData.success) buildChips(catData.categorias);
    renderCatalog();
  } catch(e) {
    document.getElementById('catalog-content').innerHTML = '<div class="empty-state"><div class="big">⚠️</div><p>Error cargando productos</p></div>';
  }
}

function buildChips(cats) {
  const wrap = document.getElementById('filter-chips');
  const used = [...new Set(allProducts.map(p=>p.categoria).filter(Boolean))];
  const filtered = cats.filter(c => used.includes(c.nombre));
  filtered.forEach(c => {
    const chip = document.createElement('div');
    chip.className = 'chip';
    chip.innerHTML = `${c.icono} ${c.nombre}`;
    chip.onclick = () => filterCat(c.nombre, chip);
    wrap.appendChild(chip);
  });
}

function filterCat(cat, btn) {
  currentFilter = cat;
  document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
  btn.classList.add('active');
  renderCatalog();
}

function renderCatalog() {
  let filtered = allProducts;
  if (currentFilter !== 'all') filtered = allProducts.filter(p => p.categoria === currentFilter);

  const catColors = {};
  const container = document.getElementById('catalog-content');

  if (!filtered.length) {
    container.innerHTML = '<div class="empty-state"><div class="big">📭</div><p>No hay productos en esta categoría</p></div>';
    return;
  }

  // Group by category
  const grouped = {};
  filtered.forEach(p => {
    if (!grouped[p.categoria]) grouped[p.categoria] = [];
    grouped[p.categoria].push(p);
  });

  let html = '';
  Object.entries(grouped).forEach(([cat, items]) => {
    html += `<div style="margin-bottom:2rem">
      <div class="section-header">
        <div class="section-icon" style="background:${items[0].cat_color||'#f1f5f9'}">${items[0].cat_icono||'📦'}</div>
        <div>
          <div class="section-title">${cat}</div>
          <div class="section-sub">${items.length} producto${items.length>1?'s':''}</div>
        </div>
      </div>
      <div class="product-grid">
        ${items.map(p => renderCard(p)).join('')}
      </div>
    </div>`;
  });
  container.innerHTML = html;
}

function renderCard(p) {
  const sizes = p.medidas ? p.medidas.split(',').slice(0,3).map(s=>`<span class="size-tag">${s.trim()}</span>`).join('') : '';
  const badgeMap = {popular:'Popular',new:'Nuevo',oferta:'Oferta'};
  const badge = p.etiqueta ? `<span class="prod-badge badge-${p.etiqueta}-img">${badgeMap[p.etiqueta]}</span>` : '';
  const img   = p.foto ? `<img src="${p.foto}" alt="${esc(p.nombre)}" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0">` : '';
  const precio = Number(p.precio||0).toLocaleString('es-PY');
  return `<div class="product-card" onclick="openOrderModal(${p.id},'${esc(p.nombre)}')">
    <div class="product-img" style="background:${p.cat_color||'#f1f5f9'};position:relative;overflow:hidden">
      ${img}<span class="emoji-fallback" style="${p.foto?'opacity:0':''}">${p.emoji||'📦'}</span>${badge}
    </div>
    <div class="product-body">
      <div class="product-cat">${p.categoria}</div>
      <div class="product-name">${esc(p.nombre)}</div>
      <div class="product-desc">${esc(p.descripcion||'')}</div>
      ${sizes?`<div class="product-sizes">${sizes}</div>`:''}
      <div class="product-footer">
        <div><div class="price-label">Desde</div><div class="price">₲ ${precio} <small>/${p.unidad||'unid'}</small></div></div>
        <button class="btn btn-accent btn-sm" onclick="event.stopPropagation();openOrderModal(${p.id},'${esc(p.nombre)}')">Pedir</button>
      </div>
    </div>
  </div>`;
}

function openOrderModal(id, name) {
  document.getElementById('order-product-id').value   = id||'';
  document.getElementById('order-product-name').value = name||'';
  ['order-name','order-phone','order-email','order-company','order-qty','order-size'].forEach(i=>document.getElementById(i).value='');
  document.getElementById('order-notes').value='';
  document.getElementById('overlay-order').classList.add('open');
  setTimeout(()=>document.getElementById('order-name').focus(),300);
}

function closeModal(id) { document.getElementById('overlay-'+id).classList.remove('open'); }

async function submitOrder(via) {
  const name  = document.getElementById('order-name').value.trim();
  const phone = document.getElementById('order-phone').value.trim();
  if (!name||!phone) { showToast('Nombre y teléfono son obligatorios','error'); return; }
  const payload = {
    nombre:name, telefono:phone,
    email:document.getElementById('order-email').value.trim(),
    empresa:document.getElementById('order-company').value.trim(),
    producto:document.getElementById('order-product-name').value.trim(),
    cantidad:document.getElementById('order-qty').value.trim(),
    medida:document.getElementById('order-size').value.trim(),
    notas:document.getElementById('order-notes').value.trim(), via
  };
  try {
    document.querySelectorAll('#overlay-order .btn').forEach(b=>b.disabled=true);
    const res  = await fetch('/api/orders.php?action=create',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
    const data = await res.json();
    if (data.success) {
      closeModal('order');
      showToast('✅ Pedido enviado. Te contactamos pronto!');
      if (via==='whatsapp'&&data.whatsapp_url) setTimeout(()=>window.open(data.whatsapp_url,'_blank'),600);
      if (via==='email'&&data.email_url)       setTimeout(()=>window.open(data.email_url,'_blank'),600);
    } else showToast(data.error||'Error','error');
  } catch(e){ showToast('Error de conexión','error'); }
  finally { document.querySelectorAll('#overlay-order .btn').forEach(b=>b.disabled=false); }
}

function esc(s){ return String(s||'').replace(/'/g,"&#39;").replace(/</g,'&lt;'); }

function showToast(msg,type='success'){
  let wrap=document.getElementById('toast-wrap');
  if(!wrap){wrap=document.createElement('div');wrap.id='toast-wrap';wrap.className='toast-wrap';document.body.appendChild(wrap);}
  const t=document.createElement('div');t.className=`toast ${type}`;t.textContent=msg;wrap.appendChild(t);
  setTimeout(()=>t.remove(),3500);
}

document.querySelectorAll('.overlay').forEach(ov=>ov.addEventListener('click',e=>{if(e.target===ov)ov.classList.remove('open')}));
loadProducts();
</script>
</div>
</body>
</html>

<?php
// =====================================================
// index.php — Catálogo público (sin login requerido)
// =====================================================
require_once "config/supabase.php";;

// Obtener configuración del negocio
$rows = supabase('GET','settings?select=clave,valor');
$settings = [];
foreach ($rows as $r) $settings[$r['clave']] = $r['valor'];

$bizName   = $settings['nombre_negocio'] ?? 'Yair Packaging';
$slogan    = $settings['slogan']         ?? 'Embalajes profesionales para tu negocio';
$whatsapp  = $settings['whatsapp']       ?? '595981000000';
$direccion = $settings['direccion']      ?? 'Asunción, Paraguay';
$horario   = $settings['horario']        ?? 'Lun-Vie 8:00-18:00';

// Total de productos
$totalProds = count(supabase('GET','productos?select=id&activo=eq.true'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($bizName) ?> — Catálogo de Embalajes</title>
<meta name="description" content="<?= htmlspecialchars($slogan) ?>. Cartones, plásticos, isopor y accesorios de embalaje.">
<link rel="stylesheet" href="assets/css/style.css">
<style>
/* ── Estilos específicos del catálogo ── */
.hero{background:var(--primary);padding:3rem 2rem 2.5rem;position:relative;overflow:hidden}
.hero::after{content:'';position:absolute;top:0;right:0;width:45%;height:100%;background:linear-gradient(135deg,transparent 30%,rgba(232,101,10,0.15) 100%);pointer-events:none}
.hero-inner{max-width:1100px;margin:0 auto;position:relative;z-index:1}
.hero-badge{display:inline-block;background:var(--accent);color:#fff;font-family:var(--font-head);font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;padding:3px 12px;border-radius:2px;margin-bottom:1rem}
.hero h1{font-family:var(--font-head);font-size:clamp(2.2rem,5vw,3.8rem);font-weight:800;color:#fff;line-height:1.05;margin-bottom:.75rem}
.hero h1 span{color:var(--accent2)}
.hero p{color:rgba(255,255,255,0.65);font-size:.95rem;max-width:500px;margin-bottom:1.75rem;line-height:1.6}
.hero-actions{display:flex;gap:10px;flex-wrap:wrap}
.hero-stats{display:flex;gap:2rem;margin-top:2rem;padding-top:1.5rem;border-top:1px solid rgba(255,255,255,0.12);flex-wrap:wrap}
.hero-stat strong{display:block;font-family:var(--font-head);font-size:1.9rem;font-weight:700;color:var(--accent2);line-height:1}
.hero-stat span{font-size:.78rem;color:rgba(255,255,255,0.5)}
.catnav{background:#fff;border-bottom:2px solid var(--accent);position:sticky;top:46px;z-index:150}
.catnav-inner{max-width:1100px;margin:0 auto;padding:0 1.5rem;display:flex;overflow-x:auto}
.catnav-btn{padding:.9rem 1.1rem;font-family:var(--font-body);font-weight:600;font-size:.85rem;color:var(--muted);background:none;border:none;border-bottom:3px solid transparent;cursor:pointer;white-space:nowrap;transition:all .2s}
.catnav-btn:hover{color:var(--text)}
.catnav-btn.active{color:var(--accent);border-bottom-color:var(--accent)}
.catalog-wrap{max-width:1100px;margin:0 auto;padding:2rem 1.5rem 4rem}
.section-block{margin-bottom:2.5rem}
.section-header{display:flex;align-items:center;gap:12px;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid var(--border)}
.section-title{font-family:var(--font-head);font-size:1.6rem;font-weight:700}
.section-sub{font-size:.82rem;color:var(--muted);margin-top:2px}
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(245px,1fr));gap:1.1rem}
.product-card{background:var(--card);border:1px solid var(--border);border-radius:11px;overflow:hidden;transition:box-shadow .2s,transform .2s;cursor:pointer}
.product-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,0.1)}
.product-img{width:100%;height:155px;display:flex;align-items:center;justify-content:center;font-size:3.5rem;position:relative;overflow:hidden}
.emoji-fallback{position:relative;z-index:1}
.prod-badge{position:absolute;top:8px;right:8px;font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase;padding:3px 7px;border-radius:3px;z-index:2}
.badge-popular{background:var(--accent);color:#fff}
.badge-new{background:var(--green);color:#fff}
.badge-oferta{background:var(--red);color:#fff}
.product-body{padding:1rem}
.product-cat{font-size:9px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);margin-bottom:3px}
.product-name{font-family:var(--font-head);font-size:1.15rem;font-weight:700;margin-bottom:5px;line-height:1.2}
.product-desc{font-size:.8rem;color:var(--muted);line-height:1.5;margin-bottom:8px}
.product-sizes{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:10px}
.size-tag{background:var(--bg);border:1px solid var(--border);font-size:9px;font-weight:600;color:var(--muted);padding:2px 6px;border-radius:3px}
.product-footer{display:flex;align-items:center;justify-content:space-between;padding-top:8px;border-top:1px solid var(--border)}
.price{font-family:var(--font-head);font-size:1.2rem;font-weight:700;color:var(--accent)}
.price small{font-size:.72rem;color:var(--muted);font-family:var(--font-body);font-weight:400}
.price-label{font-size:9px;color:var(--muted)}
.contact-strip{background:var(--primary);border-radius:12px;padding:2rem 2.5rem;display:flex;align-items:center;justify-content:space-between;gap:1.5rem;flex-wrap:wrap;margin-top:2rem}
.contact-strip h2{font-family:var(--font-head);font-size:1.7rem;font-weight:700;color:#fff;margin-bottom:3px}
.contact-strip p{color:rgba(255,255,255,0.6);font-size:.88rem}
footer{background:var(--primary);color:rgba(255,255,255,0.5);text-align:center;padding:1.5rem;font-size:.82rem;margin-top:0}
footer span{color:var(--accent2)}
</style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
  <a href="index.php" class="topbar-logo"><?= htmlspecialchars($bizName) ?></a>
  <div class="topbar-actions">
    <a href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>" target="_blank" class="btn btn-whatsapp btn-sm">💬 WhatsApp</a>
    <!-- Admin link: tudominio.com/admin/login.php -->
  </div>
</div>

<!-- HERO -->
<div class="hero">
  <div class="hero-inner">
    <div class="hero-badge">Catálogo Oficial 2025</div>
    <h1>Embalajes &<br><span>Packaging</span><br>profesional</h1>
    <p><?= htmlspecialchars($slogan) ?></p>
    <div class="hero-actions">
      <button class="btn btn-accent btn-lg" onclick="openOrderModal(null, '')">📋 Hacer pedido</button>
      <a href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>?text=Hola!%20Vi%20su%20cat%C3%A1logo%20y%20me%20interesa%20m%C3%A1s%20informaci%C3%B3n." target="_blank" class="btn btn-whatsapp btn-lg">💬 WhatsApp</a>
    </div>
    <div class="hero-stats">
      <div class="hero-stat"><strong id="stat-total"><?= $totalProds ?></strong><span>Productos</span></div>
      <div class="hero-stat"><strong>24h</strong><span>Entrega</span></div>
      <div class="hero-stat"><strong>15+</strong><span>Años de experiencia</span></div>
    </div>
  </div>
</div>

<!-- NAV DE CATEGORÍAS -->
<div class="catnav">
  <div class="catnav-inner">
    <button class="catnav-btn active" onclick="filterCat('all',this)">Todos</button>
    <button class="catnav-btn" onclick="filterCat('Cartones',this)">📦 Cartones</button>
    <button class="catnav-btn" onclick="filterCat('Plásticos',this)">🧴 Plásticos</button>
    <button class="catnav-btn" onclick="filterCat('Isopor',this)">🔲 Isopor</button>
    <button class="catnav-btn" onclick="filterCat('Accesorios',this)">🛒 Accesorios</button>
  </div>
</div>

<!-- CATÁLOGO -->
<div class="catalog-wrap">
  <div id="catalog-content">
    <div style="text-align:center;padding:3rem;color:var(--muted)">Cargando productos...</div>
  </div>

  <!-- STRIP DE CONTACTO -->
  <div class="contact-strip">
    <div>
      <h2>¿Necesitás una solución personalizada?</h2>
      <p>Fabricamos a medida. Cotizá sin compromiso — <?= htmlspecialchars($horario) ?></p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <button class="btn btn-accent" onclick="openOrderModal(null,'')">📋 Hacer pedido</button>
      <a href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>" target="_blank" class="btn btn-whatsapp">💬 WhatsApp directo</a>
    </div>
  </div>
</div>

<footer>
  <p>© 2025 <span><?= htmlspecialchars($bizName) ?></span> — <?= htmlspecialchars($direccion) ?></p>
</footer>

<!-- MODAL PEDIDO -->
<div class="overlay" id="overlay-order">
  <div class="modal" style="max-width:480px">
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
        <label>Teléfono / WhatsApp *</label>
        <input type="tel" id="order-phone" placeholder="0981 000 000" autocomplete="tel" />
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
        <input type="text" id="order-qty" placeholder="Ej: 500 unid/mes" />
      </div>
      <div class="form-group">
        <label>Medida/Tamaño</label>
        <input type="text" id="order-size" placeholder="Ej: 40×30×30" />
      </div>
    </div>
    <div class="form-group">
      <label>Comentarios adicionales</label>
      <textarea id="order-notes" placeholder="Impresión personalizada, color, urgencia..."></textarea>
    </div>
    <div class="modal-footer">
      <button class="btn btn-whatsapp" style="flex:1;justify-content:center" onclick="submitOrder('whatsapp')">💬 WhatsApp</button>
      <button class="btn btn-accent"   style="flex:1;justify-content:center" onclick="submitOrder('email')">📧 Email</button>
    </div>
  </div>
</div>

<script src="assets/js/catalog.js"></script>
</body>
</html>

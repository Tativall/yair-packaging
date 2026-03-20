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

// Obtener categorías activas
$categorias = supabase('GET','categorias?select=*&activa=eq.true&order=orden.asc');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= htmlspecialchars($bizName) ?> — Catálogo de Embalajes</title>
<meta name="description" content="<?= htmlspecialchars($slogan) ?>">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

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
    <div class="hero-badge">✨ Catálogo 2025</div>
    <h1>Embalajes &<br><span>Packaging</span><br>profesional</h1>
    <p><?= htmlspecialchars($slogan) ?></p>
    <div class="hero-actions">
      <button class="btn btn-accent btn-lg" onclick="openOrderModal(null,'')">📋 Hacer pedido</button>
      <a href="https://wa.me/<?= htmlspecialchars($whatsapp) ?>?text=Hola!%20Vi%20su%20cat%C3%A1logo%20y%20me%20interesa%20m%C3%A1s%20informaci%C3%B3n." target="_blank" class="btn btn-whatsapp btn-lg">💬 WhatsApp</a>
    </div>
    <div class="hero-stats">
      <div class="hero-stat"><strong id="stat-total"><?= $totalProds ?></strong><span>Productos</span></div>
      <div class="hero-stat"><strong>24h</strong><span>Entrega</span></div>
      <div class="hero-stat"><strong>15+</strong><span>Años</span></div>
    </div>
  </div>
</div>

<!-- FILTER CHIPS (dinámico desde JS) -->
<div class="filter-chips" id="filter-chips">
  <div class="chip active" onclick="filterCat('all',this)">🏪 Todos</div>
  <!-- Categorías se cargan desde JS -->
</div>

<!-- CATÁLOGO -->
<div class="catalog-wrap">
  <div id="catalog-content">
    <div style="text-align:center;padding:3rem;color:var(--text-light)">Cargando productos...</div>
  </div>

  <!-- CONTACT STRIP -->
  <div class="contact-strip">
    <div>
      <h2>¿Necesitás algo especial?</h2>
      <p>Fabricamos a medida — <?= htmlspecialchars($horario) ?></p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
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
  <div class="modal">
    <button class="modal-close" onclick="closeModal('order')">✕</button>
    <h3>Hacer un pedido</h3>
    <p class="modal-sub">Completá tus datos y te contactamos enseguida</p>
    <input type="hidden" id="order-product-id" />
    <div class="form-group">
      <label>Tu nombre *</label>
      <input type="text" id

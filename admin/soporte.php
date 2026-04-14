<?php
// =====================================================
// admin/soporte.php — Panel de soporte en tiempo real
// =====================================================
require_once '../config/supabase.php';
requireAdmin();

$settingsRows = supabase('GET', 'settings?select=clave,valor');
$settings = [];
foreach ($settingsRows as $r) $settings[$r['clave']] = $r['valor'];
$bizName = $settings['nombre_negocio'] ?? 'Yair Packaging';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Soporte en Vivo — <?= htmlspecialchars($bizName) ?></title>
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
  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="sidebar-section">Principal</div>
    <a href="dashboard.php" class="sidebar-item"><span>📊</span> Dashboard</a>
    <a href="productos.php" class="sidebar-item"><span>📦</span> Productos</a>
    <a href="pedidos.php"   class="sidebar-item"><span>🛒</span> Pedidos</a>
    <div class="sidebar-section">Atención</div>
    <a href="soporte.php"   class="sidebar-item active">
      <span>💬</span> Soporte en vivo
      <span class="sidebar-badge" id="sidebar-pending-badge" style="display:none">0</span>
    </a>
    <div class="sidebar-section">Configuración</div>
    <a href="ajustes.php" class="sidebar-item"><span>⚙️</span> Ajustes</a>
  </div>

  <!-- SUPPORT GRID -->
  <div class="support-grid">

    <!-- Panel izquierdo: lista de tickets -->
    <div class="tickets-panel">
      <div class="tickets-header">
        <div class="tickets-title">💬 Conversaciones</div>
        <span class="tickets-count" id="tickets-total">0</span>
      </div>

      <!-- Filtros -->
      <div style="display:flex;gap:4px;padding:8px;border-bottom:1px solid var(--border);flex-shrink:0">
        <button class="chip active" id="filter-pending" onclick="filterTickets('pending',this)">🔴 Pendientes</button>
        <button class="chip" id="filter-active"  onclick="filterTickets('active',this)">🟡 Activos</button>
        <button class="chip" id="filter-closed"  onclick="filterTickets('closed',this)">✅ Cerrados</button>
      </div>

      <div class="tickets-list" id="tickets-list">
        <div style="text-align:center;padding:2rem;color:var(--muted)">Cargando...</div>
      </div>
    </div>

    <!-- Panel derecho: chat -->
    <div class="chat-panel" id="chat-panel-right">
      <div class="empty-chat" id="empty-chat">
        <div class="big">💬</div>
        <div style="font-weight:600">Seleccioná una conversación</div>
        <div style="font-size:.85rem">Aquí verás el chat con el cliente</div>
      </div>

      <!-- Contenido del ticket seleccionado (oculto al inicio) -->
      <div id="ticket-view" style="display:none;flex-direction:column;height:100%">
        <div class="chat-panel-header">
          <div class="chat-panel-avatar">👤</div>
          <div class="chat-panel-info">
            <div class="chat-panel-name" id="view-name">—</div>
            <div class="chat-panel-sub" id="view-phone">—</div>
          </div>
          <div class="chat-panel-actions" id="view-actions"></div>
        </div>

        <!-- Info del pedido -->
        <div class="ticket-info-box" id="view-info"></div>

        <!-- Mensajes -->
        <div class="admin-msgs" id="admin-msgs"></div>

        <!-- Input respuesta -->
        <div class="admin-input-area" id="admin-input-area" style="display:none">
          <input type="text" id="admin-input" placeholder="Escribí tu respuesta..." autocomplete="off" />
          <button class="admin-input-send" id="admin-send" onclick="adminSend()">➤</button>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Audio para notificación -->
<audio id="notify-sound" preload="auto">
  <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq4hGJCpPjtDqsYM6Hyd1sNjtrXI5KzZuqNnssH0/LDFtpNvttH9AMzBwpN3tt4Y7Li12pN/tuJA8KSx3pN/tuJQ7KCx4pN/tuJY6KCx5pN/tuJg5KCx5ot7tuJo3KCt5od3tuJk2KCt5oNztuJk1KCt5n9vtuJk0KCt5ntqsuJk0KCt5nNmsuJo0KCt5m9mruJo0KCt5mtiquJo0KCt4mdiquJo0KCt4mNiouJo0KCt4l9aouJo0KCt4ltWouJo0KCt4lNWouJo0KCt4k9SouJo0KCt4ktOouJo0KCt4kNKouJo0KCt3jtGnuJo0KCt3jNCnuJo0KCt3i8+nuJo0KCt3is6muJo0KCt2ic6luJo0KSt2ic2luJo0KSt2iMyluJo0KCt2h8uluJo0KCt2h8qluJo0KCt2hsqluJo0KCt2hsqkuJo0KCt2hsqkuJo0KCt2hsqkuJo0KCt2hsqkuZo0" type="audio/wav">
</audio>

<!-- Supabase JS SDK -->
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/dist/umd/supabase.js"></script>
<script>
const SUPABASE_URL = '<?= SUPABASE_URL ?>';
const SUPABASE_KEY = '<?= SUPABASE_KEY ?>';
const sb = supabase.createClient(SUPABASE_URL, SUPABASE_KEY);
const ADMIN_NAME = 'Admin'; // Podés personalizar por admin

let allTickets      = [];
let currentTicket   = null;
let currentFilter   = 'pending';
let msgChannel      = null;
let ticketChannel   = null;

// ============================================================
// CARGAR TICKETS
// ============================================================
async function loadTickets(status = currentFilter) {
  const res  = await fetch(`/api/chat.php?action=get_tickets&status=${status}`);
  const data = await res.json();
  allTickets = data.tickets || [];
  renderTicketList();
}

function renderTicketList() {
  const list = document.getElementById('tickets-list');
  document.getElementById('tickets-total').textContent = allTickets.length;

  if (!allTickets.length) {
    list.innerHTML = `<div style="text-align:center;padding:2rem;color:var(--muted)">
      <div style="font-size:2rem">✅</div>
      <div style="margin-top:8px;font-size:.88rem">No hay conversaciones ${currentFilter === 'pending' ? 'pendientes' : ''}</div>
    </div>`;
    return;
  }

  list.innerHTML = allTickets.map(t => {
    const statusLabel = { pending:'Pendiente', active:'En atención', closed:'Cerrado' }[t.status] || t.status;
    const time = t.created_at ? new Date(t.created_at).toLocaleTimeString('es', {hour:'2-digit',minute:'2-digit'}) : '';
    const isSelected = currentTicket?.id === t.id ? 'selected' : '';
    return `<div class="ticket-item status-${t.status} ${isSelected}" onclick="selectTicket('${t.id}')" id="ticket-item-${t.id}">
      <div class="ticket-name">👤 ${esc(t.cliente)}</div>
      <div class="ticket-preview">${esc(t.producto || 'Sin producto especificado')}</div>
      <div class="ticket-meta">
        <span class="ticket-time">${time}</span>
        <span class="ticket-status ${t.status}">${statusLabel}${t.admin_name ? ' · '+esc(t.admin_name) : ''}</span>
      </div>
    </div>`;
  }).join('');
}

// ============================================================
// SELECCIONAR TICKET
// ============================================================
async function selectTicket(id) {
  currentTicket = allTickets.find(t => t.id === id);
  if (!currentTicket) return;

  // Actualizar selección visual
  document.querySelectorAll('.ticket-item').forEach(el => el.classList.remove('selected'));
  document.getElementById('ticket-item-' + id)?.classList.add('selected');

  // Mostrar panel
  document.getElementById('empty-chat').style.display = 'none';
  const view = document.getElementById('ticket-view');
  view.style.display = 'flex';

  // Datos del cliente
  document.getElementById('view-name').textContent  = currentTicket.cliente;
  document.getElementById('view-phone').textContent = currentTicket.telefono || 'Sin teléfono';

  // Info del pedido
  document.getElementById('view-info').innerHTML = `
    <div class="info-row"><span class="info-label">Producto</span><span class="info-val">${esc(currentTicket.producto||'—')}</span></div>
    <div class="info-row"><span class="info-label">Cantidad</span><span class="info-val">${esc(currentTicket.cantidad||'—')}</span></div>
    <div class="info-row"><span class="info-label">Especif.</span><span class="info-val">${esc(currentTicket.especial||'—')}</span></div>
    <div class="info-row"><span class="info-label">Teléfono</span><span class="info-val">${esc(currentTicket.telefono||'—')}</span></div>`;

  // Botones de acción
  const actions = document.getElementById('view-actions');
  if (currentTicket.status === 'pending') {
    actions.innerHTML = `<button class="btn-claim" onclick="claimTicket()">✋ Atender</button>`;
    document.getElementById('admin-input-area').style.display = 'none';
  } else if (currentTicket.status === 'active') {
    const myTicket = currentTicket.admin_name === ADMIN_NAME;
    actions.innerHTML = myTicket
      ? `<button class="btn-close-chat" onclick="closeTicket()">✅ Cerrar chat</button>`
      : `<span style="font-size:.82rem;color:var(--muted)">Atendido por ${esc(currentTicket.admin_name)}</span>`;
    document.getElementById('admin-input-area').style.display = myTicket ? 'flex' : 'none';
  } else {
    actions.innerHTML = `<span style="font-size:.82rem;color:var(--muted)">Cerrado</span>`;
    document.getElementById('admin-input-area').style.display = 'none';
  }

  // Cargar mensajes
  await loadMessages(id);

  // Suscribir a Realtime del ticket
  subscribeToTicket(id);
}

async function loadMessages(ticketId) {
  const res  = await fetch(`/api/chat.php?action=get_messages&ticket_id=${ticketId}`);
  const data = await res.json();
  const msgs = document.getElementById('admin-msgs');
  msgs.innerHTML = '';
  (data.messages || []).forEach(m => appendAdminMsg(m.sender, m.mensaje, m.created_at));
  msgs.scrollTop = msgs.scrollHeight;
}

function appendAdminMsg(sender, texto, ts) {
  const msgs = document.getElementById('admin-msgs');
  const wrap = document.createElement('div');
  const isOut = sender === 'admin';
  const avatar = sender === 'user' ? '👤' : sender === 'bot' ? '🤖' : '';
  const time = ts ? new Date(ts).toLocaleTimeString('es', {hour:'2-digit',minute:'2-digit'}) : '';

  wrap.className = `admin-msg-wrap ${isOut ? 'admin-out' : sender}`;
  wrap.innerHTML = `
    ${!isOut ? `<div class="admin-msg-avatar">${avatar}</div>` : ''}
    <div>
      <div class="admin-bubble">${esc(texto)}</div>
      <div class="admin-msg-time">${time}</div>
    </div>`;
  msgs.appendChild(wrap);
  msgs.scrollTop = msgs.scrollHeight;
}

// ============================================================
// ACCIONES ADMIN
// ============================================================
async function claimTicket() {
  const res  = await fetch('/api/chat.php?action=claim_ticket', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ ticket_id: currentTicket.id, admin_name: ADMIN_NAME })
  });
  const data = await res.json();
  if (data.success) {
    currentTicket.status     = 'active';
    currentTicket.admin_name = ADMIN_NAME;
    // Re-render la vista
    document.getElementById('view-actions').innerHTML =
      `<button class="btn-close-chat" onclick="closeTicket()">✅ Cerrar chat</button>`;
    document.getElementById('admin-input-area').style.display = 'flex';
    document.getElementById('admin-input').focus();
  } else {
    alert('Este ticket ya fue tomado por otro admin.');
    loadTickets();
  }
}

async function adminSend() {
  const input = document.getElementById('admin-input');
  const texto = input.value.trim();
  if (!texto || !currentTicket) return;
  input.value = '';
  document.getElementById('admin-send').disabled = true;

  await fetch('/api/chat.php?action=send_message', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ ticket_id: currentTicket.id, sender: 'admin', mensaje: texto })
  });
  document.getElementById('admin-send').disabled = false;
  input.focus();
}

async function closeTicket() {
  if (!confirm('¿Cerrar este chat?')) return;
  await fetch('/api/chat.php?action=close_ticket', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ ticket_id: currentTicket.id })
  });
  currentTicket = null;
  document.getElementById('ticket-view').style.display = 'none';
  document.getElementById('empty-chat').style.display = 'flex';
  loadTickets();
}

// ============================================================
// REALTIME — escuchar cambios en tiempo real
// ============================================================
function subscribeToTicket(ticketId) {
  if (msgChannel) sb.removeChannel(msgChannel);

  msgChannel = sb
    .channel('admin-msgs-' + ticketId)
    .on('postgres_changes', {
      event: 'INSERT', schema: 'public', table: 'chat_messages',
      filter: `ticket_id=eq.${ticketId}`
    }, payload => {
      const m = payload.new;
      if (m.sender !== 'admin') {
        appendAdminMsg(m.sender, m.mensaje, m.created_at);
      }
    })
    .subscribe();
}

function subscribeGlobal() {
  // Escuchar nuevos tickets entrantes
  ticketChannel = sb
    .channel('admin-all-tickets')
    .on('postgres_changes', {
      event: 'INSERT', schema: 'public', table: 'chat_tickets'
    }, payload => {
      const t = payload.new;
      allTickets.unshift(t);
      renderTicketList();
      showNewTicketToast(t);
      updatePendingBadge();
    })
    .on('postgres_changes', {
      event: 'UPDATE', schema: 'public', table: 'chat_tickets'
    }, payload => {
      const t     = payload.new;
      const idx   = allTickets.findIndex(x => x.id === t.id);
      if (idx >= 0) allTickets[idx] = t;
      if (currentTicket?.id === t.id) {
        currentTicket = t;
        // Actualizar botones si el status cambió
        selectTicket(t.id);
      }
      if (currentFilter !== t.status) {
        // Quitar de lista si filtro no coincide
        allTickets = allTickets.filter(x => x.status === currentFilter);
      }
      renderTicketList();
      updatePendingBadge();
    })
    .subscribe();
}

async function updatePendingBadge() {
  const res  = await fetch('/api/chat.php?action=pending_count');
  const data = await res.json();
  const badge = document.getElementById('sidebar-pending-badge');
  if (data.count > 0) {
    badge.style.display = 'inline-flex';
    badge.textContent   = data.count;
  } else {
    badge.style.display = 'none';
  }
}

function showNewTicketToast(ticket) {
  // Sonido
  try { document.getElementById('notify-sound').play(); } catch(e){}

  const toast = document.createElement('div');
  toast.className = 'new-ticket-toast';
  toast.innerHTML = `
    <div class="toast-icon">💬</div>
    <div>
      <div class="toast-msg">Nuevo cliente: ${esc(ticket.cliente)}</div>
      <div class="toast-sub">${esc(ticket.producto||'Sin producto')} · Clic para atender</div>
    </div>
    <button class="toast-close" onclick="this.parentElement.remove()">✕</button>`;
  toast.onclick = (e) => { if (e.target.tagName !== 'BUTTON') { selectTicket(ticket.id); toast.remove(); }};
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 8000);
}

// ============================================================
// FILTROS
// ============================================================
function filterTickets(status, btn) {
  currentFilter = status;
  document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
  btn.classList.add('active');
  currentTicket = null;
  document.getElementById('ticket-view').style.display = 'none';
  document.getElementById('empty-chat').style.display  = 'flex';
  loadTickets(status);
}

// ============================================================
// UTILS
// ============================================================
function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// Enter para enviar
document.getElementById('admin-input')?.addEventListener('keydown', e => {
  if (e.key === 'Enter') adminSend();
});

// ============================================================
// INIT
// ============================================================
loadTickets();
subscribeGlobal();
updatePendingBadge();
setInterval(updatePendingBadge, 30000);
</script>

<!-- Supabase JS SDK -->
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/dist/umd/supabase.js"></script>
</body>
</html>

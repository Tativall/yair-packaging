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
<style>
/* ---- Layout ---- */
.admin-layout{display:grid;grid-template-columns:220px 1fr;min-height:calc(100vh - 46px)}
.sidebar{background:var(--primary);padding:1.5rem 0;min-height:100%}
.sidebar-section{padding:.5rem 1.5rem;font-size:10px;font-weight:700;letter-spacing:1.5px;color:rgba(255,255,255,0.3);text-transform:uppercase;margin-top:1rem}
.sidebar-item{display:flex;align-items:center;gap:10px;padding:.7rem 1.5rem;color:rgba(255,255,255,0.65);font-size:.88rem;font-weight:500;cursor:pointer;text-decoration:none;border-left:3px solid transparent;transition:all .2s}
.sidebar-item:hover{background:rgba(255,255,255,0.07);color:#fff}
.sidebar-item.active{background:rgba(232,101,10,0.15);color:var(--accent2);border-left-color:var(--accent)}
.sidebar-badge{margin-left:auto;background:var(--accent);color:#fff;font-size:9px;font-weight:700;padding:1px 6px;border-radius:10px}

/* ---- Chat layout ---- */
.support-grid{display:grid;grid-template-columns:300px 1fr;height:calc(100vh - 46px);overflow:hidden}
.tickets-panel{border-right:1px solid var(--border);display:flex;flex-direction:column;background:#fff}
.tickets-header{padding:1rem 1.25rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.tickets-title{font-weight:700;font-size:1rem}
.tickets-count{background:var(--accent);color:#fff;font-size:11px;font-weight:700;padding:2px 8px;border-radius:10px;min-width:20px;text-align:center}
.tickets-list{flex:1;overflow-y:auto;padding:8px}
.ticket-item{padding:12px;border-radius:10px;cursor:pointer;transition:background .15s;margin-bottom:4px;border:1.5px solid transparent}
.ticket-item:hover{background:#f9fafb}
.ticket-item.selected{background:#fff7ed;border-color:var(--accent)}
.ticket-item.status-active{background:#eff6ff;border-color:#93c5fd}
.ticket-name{font-weight:600;font-size:.9rem;margin-bottom:2px}
.ticket-preview{font-size:.77rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ticket-meta{display:flex;align-items:center;justify-content:space-between;margin-top:4px}
.ticket-time{font-size:.71rem;color:#9ca3af}
.ticket-status{font-size:.71rem;font-weight:600;padding:2px 8px;border-radius:10px}
.ticket-status.pending{background:#fef3c7;color:#92400e}
.ticket-status.active{background:#dbeafe;color:#1e40af}
.ticket-status.closed{background:#f3f4f6;color:#6b7280}

/* ---- Chat panel ---- */
.chat-panel{display:flex;flex-direction:column;height:100%;background:#f9fafb}
.chat-panel-header{padding:14px 20px;background:#fff;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;flex-shrink:0}
.chat-panel-avatar{width:42px;height:42px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0}
.chat-panel-info{flex:1}
.chat-panel-name{font-weight:700;font-size:.95rem}
.chat-panel-sub{font-size:.78rem;color:var(--muted);margin-top:1px}
.chat-panel-actions{display:flex;gap:8px}
.btn-claim{background:var(--accent);color:#fff;border:none;padding:7px 16px;border-radius:8px;font-weight:600;font-size:.82rem;cursor:pointer;transition:background .2s}
.btn-claim:hover{background:#c9560a}
.btn-close-chat{background:#ef4444;color:#fff;border:none;padding:7px 16px;border-radius:8px;font-weight:600;font-size:.82rem;cursor:pointer;transition:background .2s}
.btn-close-chat:hover{background:#dc2626}

/* ---- Mensajes admin ---- */
.admin-msgs{flex:1;overflow-y:auto;padding:16px 20px;display:flex;flex-direction:column;gap:8px}
.admin-msg-wrap{display:flex;align-items:flex-end;gap:8px;animation:msgIn .2s ease}
@keyframes msgIn{from{transform:translateY(8px);opacity:0}to{transform:translateY(0);opacity:1}}
.admin-msg-wrap.admin-out{flex-direction:row-reverse}
.admin-msg-avatar{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0}
.admin-msg-wrap.user .admin-msg-avatar{background:#fee2e2}
.admin-msg-wrap.bot  .admin-msg-avatar{background:#e5e7eb}
.admin-bubble{max-width:72%;padding:9px 14px;border-radius:14px;font-size:.87rem;line-height:1.45;word-break:break-word}
.admin-msg-wrap.user  .admin-bubble{background:#fff;border:1px solid #e5e7eb;border-bottom-left-radius:4px}
.admin-msg-wrap.bot   .admin-bubble{background:#f3f4f6;border:1px solid #e5e7eb;border-bottom-left-radius:4px;color:#6b7280;font-style:italic}
.admin-msg-wrap.admin-out .admin-bubble{background:var(--accent);color:#fff;border-bottom-right-radius:4px}
.admin-msg-time{font-size:.68rem;color:#9ca3af;margin-top:2px;text-align:right}
.admin-msg-wrap.admin-out .admin-msg-time{text-align:right}

/* ---- Input admin ---- */
.admin-input-area{padding:12px 20px;border-top:1px solid var(--border);background:#fff;display:flex;gap:10px;align-items:center;flex-shrink:0}
.admin-input-area input{flex:1;border:1.5px solid var(--border);border-radius:24px;padding:10px 18px;font-size:.88rem;outline:none;font-family:inherit;transition:border-color .2s}
.admin-input-area input:focus{border-color:var(--accent)}
.admin-input-send{width:42px;height:42px;border-radius:50%;background:var(--accent);color:#fff;border:none;cursor:pointer;font-size:1.1rem;display:flex;align-items:center;justify-content:center;transition:transform .2s,background .2s;flex-shrink:0}
.admin-input-send:hover{transform:scale(1.1);background:#c9560a}
.admin-input-send:disabled{background:#d1d5db;cursor:default;transform:none}

/* ---- Estado vacío ---- */
.empty-chat{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:12px;color:var(--muted)}
.empty-chat .big{font-size:3.5rem}

/* ---- Notificación de nuevo ticket ---- */
.new-ticket-toast{position:fixed;top:70px;right:20px;background:#fff;border:2px solid var(--accent);border-radius:12px;padding:12px 18px;box-shadow:0 8px 32px rgba(0,0,0,.12);display:flex;align-items:center;gap:12px;z-index:9999;animation:toastIn .35s cubic-bezier(.34,1.2,.64,1)}
@keyframes toastIn{from{transform:translateX(110%)}to{transform:translateX(0)}}
.new-ticket-toast .toast-icon{font-size:1.6rem}
.new-ticket-toast .toast-msg{font-size:.85rem;font-weight:600}
.new-ticket-toast .toast-sub{font-size:.77rem;color:var(--muted)}
.new-ticket-toast .toast-close{margin-left:auto;background:none;border:none;cursor:pointer;font-size:1.1rem;color:var(--muted)}

/* ---- Info lateral del ticket ---- */
.ticket-info-box{background:#fff;border:1px solid var(--border);border-radius:10px;padding:12px;margin:0 20px 12px;flex-shrink:0}
.ticket-info-box .info-row{display:flex;gap:8px;align-items:flex-start;font-size:.82rem;margin-bottom:4px}
.ticket-info-box .info-label{color:var(--muted);min-width:80px;font-weight:600}
.ticket-info-box .info-val{color:var(--text)}

@media(max-width:900px){.admin-layout{grid-template-columns:1fr}.sidebar{display:none}.support-grid{grid-template-columns:1fr}}
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

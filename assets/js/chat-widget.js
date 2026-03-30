// =====================================================
// assets/js/chat-widget.js — Widget de chat + chatbot
// =====================================================

(function () {
  const SUPABASE_URL = 'https://xgseyvuxkyrardbtewrd.supabase.co';
  const SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Inhnc2V5dnV4a3lyYXJkYnRld3JkIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzQwMDcyMTQsImV4cCI6MjA4OTU4MzIxNH0.JyXDYnMhmOnW8g4-Gr-FWdjt0q658FaTxiMHWTkALTk';

  let ticketId    = null;
  let botStep     = 0;
  let clientData  = { nombre: '', telefono: '', producto: '', cantidad: '', especial: '' };
  let realtimeSub = null;
  let isOpen      = false;
  let adminJoined = false;

  // ---- Flujo del bot ----
  const BOT_FLOW = [
    { ask: '¡Hola! 👋 Soy el asistente de **Yair Packaging**.\n¿Cómo te llamás?', field: 'nombre', type: 'text' },
    { ask: '¡Mucho gusto, {nombre}! 😊\n¿Qué tipo de producto necesitás?', field: 'producto', type: 'options',
      options: ['📦 Cajas de cartón', '🌀 Film Stretch', '🫧 Plástico burbuja', '🧊 Isopor / Térmico', '🎀 Accesorios', '🔍 No sé bien'] },
    { ask: '¿Cuántas unidades aproximadas necesitás?', field: 'cantidad', type: 'options',
      options: ['Menos de 100', '100 – 500', '500 – 1000', 'Más de 1000', 'No sé aún'] },
    { ask: '¿Tenés alguna medida o especificación especial? (Si no, escribí "No")', field: 'especial', type: 'text' },
    { ask: '¿Cuál es tu número de teléfono o WhatsApp para contactarte?', field: 'telefono', type: 'text' },
  ];

  // ---- Crear estructura HTML ----
  function buildWidget() {
    const fab = document.createElement('button');
    fab.id = 'chat-fab';
    fab.setAttribute('aria-label', 'Abrir chat');
    fab.innerHTML = '💬';
    fab.onclick = toggleChat;

    const win = document.createElement('div');
    win.id = 'chat-window';
    win.innerHTML = `
      <div class="chat-header">
        <div class="chat-header-avatar">🤖</div>
        <div class="chat-header-info">
          <div class="chat-header-name">Soporte Yair Packaging</div>
          <div class="chat-header-status"><span class="chat-status-dot"></span> En línea</div>
        </div>
        <button class="chat-header-close" onclick="window.__chatToggle()" aria-label="Cerrar">✕</button>
      </div>
      <div class="chat-messages" id="chat-msgs"></div>
      <div class="chat-options" id="chat-opts" style="display:none"></div>
      <div class="chat-waiting" id="chat-waiting" style="display:none">
        ⏳ Conectando con un representante... <strong>Respondemos en minutos</strong>
      </div>
      <div class="chat-input-area" id="chat-input-area">
        <input type="text" id="chat-input" placeholder="Escribí tu mensaje..." autocomplete="off" />
        <button class="chat-send-btn" id="chat-send" onclick="window.__chatSend()" aria-label="Enviar">➤</button>
      </div>`;

    document.body.appendChild(fab);
    document.body.appendChild(win);

    document.getElementById('chat-input').addEventListener('keydown', e => {
      if (e.key === 'Enter') window.__chatSend();
    });

    window.__chatToggle = toggleChat;
    window.__chatSend   = sendUserMessage;
    window.__chatOption = selectOption;
  }

  // ---- Abrir / cerrar ----
  function toggleChat() {
    isOpen = !isOpen;
    document.getElementById('chat-window').classList.toggle('open', isOpen);
    document.getElementById('chat-fab').classList.toggle('open', isOpen);
    removeBadge();
    if (isOpen && botStep === 0) setTimeout(startBot, 400);
  }

  // ---- Arrancar el chatbot ----
  function startBot() {
    botStep = 0;
    askNext();
  }

  function askNext() {
    if (botStep >= BOT_FLOW.length) {
      finishBot();
      return;
    }
    const step = BOT_FLOW[botStep];
    let text = step.ask.replace('{nombre}', clientData.nombre);
    setTimeout(() => {
      showTyping();
      setTimeout(() => {
        removeTyping();
        addBubble('bot', text);
        if (step.type === 'options') showOptions(step.options);
        else focusInput();
      }, 700);
    }, 300);
  }

  function showOptions(opts) {
    const wrap = document.getElementById('chat-opts');
    wrap.style.display = 'flex';
    wrap.innerHTML = opts.map(o =>
      `<button class="chat-option-btn" onclick="window.__chatOption('${o.replace(/'/g,"\\'")}')">
        ${o}
      </button>`
    ).join('');
    document.getElementById('chat-input-area').style.display = 'none';
  }

  function selectOption(val) {
    document.getElementById('chat-opts').style.display = 'none';
    document.getElementById('chat-opts').innerHTML = '';
    document.getElementById('chat-input-area').style.display = 'flex';
    addBubble('user', val);
    clientData[BOT_FLOW[botStep].field] = val;
    botStep++;
    askNext();
  }

  function sendUserMessage() {
    const input = document.getElementById('chat-input');
    const txt   = input.value.trim();
    if (!txt) return;
    input.value = '';

    addBubble('user', txt);

    if (!adminJoined && botStep < BOT_FLOW.length) {
      clientData[BOT_FLOW[botStep].field] = txt;
      botStep++;
      if (botStep < BOT_FLOW.length) {
        askNext();
      } else {
        finishBot();
      }
    } else if (ticketId) {
      // Chat libre con admin
      fetch('/api/chat.php?action=send_message', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ticket_id: ticketId, sender: 'user', mensaje: txt })
      });
    }
    focusInput();
  }

  // ---- Terminar bot, crear ticket ----
  async function finishBot() {
    document.getElementById('chat-input-area').style.display = 'none';
    showTyping();
    await sleep(900);
    removeTyping();
    addBubble('bot', `¡Gracias, ${clientData.nombre}! 🙌 Ya recibí tu consulta:\n\n• **Producto:** ${clientData.producto}\n• **Cantidad:** ${clientData.cantidad}\n• **Especificación:** ${clientData.especial}\n\nEn un momento un representante te atiende aquí mismo. 🧑‍💼`);
    document.getElementById('chat-waiting').style.display = 'block';

    // Crear ticket en Supabase
    try {
      const res  = await fetch('/api/chat.php?action=create_ticket', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(clientData)
      });
      const data = await res.json();
      if (data.success) {
        ticketId = data.ticket_id;
        document.getElementById('chat-input-area').style.display = 'flex';
        subscribeRealtime();
      }
    } catch(e) {
      addBubble('bot', 'Hubo un problema conectando. Por favor escribinos por WhatsApp 💬');
    }
  }

  // ---- Supabase Realtime (escuchar mensajes del admin) ----
  function subscribeRealtime() {
    if (!ticketId) return;
    const { createClient } = window.supabase;
    const client = createClient(SUPABASE_URL, SUPABASE_KEY);

    realtimeSub = client
      .channel('chat-' + ticketId)
      .on('postgres_changes', {
        event:  'INSERT',
        schema: 'public',
        table:  'chat_messages',
        filter: `ticket_id=eq.${ticketId}`
      }, payload => {
        const msg = payload.new;
        if (msg.sender === 'admin') {
          if (!adminJoined) {
            adminJoined = true;
            document.getElementById('chat-waiting').style.display = 'none';
            addBubble('bot', '✅ ¡Un representante se unió al chat!');
          }
          addBubble('admin', msg.mensaje);
          if (!isOpen) showFabBadge();
        }
      })
      .on('postgres_changes', {
        event:  'UPDATE',
        schema: 'public',
        table:  'chat_tickets',
        filter: `id=eq.${ticketId}`
      }, payload => {
        if (payload.new.status === 'closed') {
          addBubble('bot', '✅ El chat fue cerrado. ¡Gracias por contactarnos! Si necesitás algo más, abrí el chat nuevamente.');
          ticketId   = null;
          adminJoined = false;
          botStep     = 0;
          clientData  = { nombre: '', telefono: '', producto: '', cantidad: '', especial: '' };
        }
      })
      .subscribe();
  }

  // ---- UI Helpers ----
  function addBubble(sender, text) {
    const msgs  = document.getElementById('chat-msgs');
    const wrap  = document.createElement('div');
    wrap.className = `chat-bubble-wrap ${sender}`;

    const avatar = sender === 'bot'   ? '🤖'
                 : sender === 'admin' ? '🧑‍💼'
                 : '';

    const formattedText = text
      .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
      .replace(/\n/g, '<br>');

    const now = new Date().toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' });

    wrap.innerHTML = `
      ${sender !== 'user' ? `<div class="chat-bubble-avatar">${avatar}</div>` : ''}
      <div>
        <div class="chat-bubble">${formattedText}</div>
        <div class="chat-bubble-time">${now}</div>
      </div>
      ${sender === 'user' ? '' : ''}
    `;
    msgs.appendChild(wrap);
    msgs.scrollTop = msgs.scrollHeight;
  }

  function showTyping() {
    const msgs = document.getElementById('chat-msgs');
    const el   = document.createElement('div');
    el.className = 'chat-bubble-wrap bot';
    el.id = 'chat-typing';
    el.innerHTML = `
      <div class="chat-bubble-avatar">🤖</div>
      <div class="chat-bubble">
        <div class="chat-typing"><span></span><span></span><span></span></div>
      </div>`;
    msgs.appendChild(el);
    msgs.scrollTop = msgs.scrollHeight;
  }

  function removeTyping() {
    const el = document.getElementById('chat-typing');
    if (el) el.remove();
  }

  function focusInput() {
    setTimeout(() => document.getElementById('chat-input')?.focus(), 100);
  }

  function showFabBadge() {
    const fab = document.getElementById('chat-fab');
    if (!fab.querySelector('.chat-badge')) {
      const badge = document.createElement('span');
      badge.className = 'chat-badge';
      badge.textContent = '1';
      fab.appendChild(badge);
    }
  }

  function removeBadge() {
    document.getElementById('chat-fab')?.querySelector('.chat-badge')?.remove();
  }

  function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

  // ---- Init ----
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', buildWidget);
  } else {
    buildWidget();
  }
})();

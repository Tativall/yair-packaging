// =====================================================
// YAIR PACKAGING — admin.js
// Lógica del panel de administración
// =====================================================

// =====================================================
// PRODUCTOS
// =====================================================
async function loadProducts() {
    const res  = await fetch('../api/products.php?action=list');
    const data = await res.json();
    if (data.success) renderProductTable(data.products);
}

function renderProductTable(products) {
    const search = (document.getElementById('prod-search')?.value || '').toLowerCase();
    const catF   = document.getElementById('cat-filter')?.value || '';
    const tbody  = document.getElementById('prod-tbody');
    if (!tbody) return;

    let filtered = products.filter(p => {
        const matchS = !search || p.nombre.toLowerCase().includes(search) || (p.descripcion || '').toLowerCase().includes(search);
        const matchC = !catF   || p.categoria === catF;
        return matchS && matchC;
    });

    if (!filtered.length) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--muted)">No se encontraron productos</td></tr>`;
        return;
    }

    tbody.innerHTML = filtered.map(p => {
        const catKey   = slugify(p.categoria);
        const precio   = Number(p.precio || 0).toLocaleString('es-PY');
        const badgeMap = { popular:'Popular', new:'Nuevo', oferta:'Oferta' };
        const imgHtml  = p.foto
            ? `<div class="td-img"><img src="../assets/uploads/${p.foto}" alt="${p.nombre}"></div>`
            : `<div class="td-img">${p.emoji || '📦'}</div>`;

        return `<tr>
            <td>${imgHtml}</td>
            <td>
                <strong>${p.nombre}</strong>
                ${p.descripcion ? `<br><span style="font-size:11px;color:var(--muted)">${p.descripcion.substring(0,55)}${p.descripcion.length > 55 ? '...' : ''}</span>` : ''}
            </td>
            <td><span class="badge badge-${catKey}">${p.categoria}</span></td>
            <td><strong>₲ ${precio}</strong></td>
            <td>${p.unidad || 'unid'}</td>
            <td>${p.etiqueta ? `<span class="badge badge-${p.etiqueta}">${badgeMap[p.etiqueta] || p.etiqueta}</span>` : '<span style="color:var(--muted);font-size:11px">—</span>'}</td>
            <td>
                <div class="td-actions">
                    <button class="btn btn-outline btn-sm" onclick="openProductModal(${p.id})">✏️ Editar</button>
                    <button class="btn btn-red btn-sm"     onclick="deleteProduct(${p.id}, '${p.nombre.replace(/'/g,"\\'")}')">🗑</button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

// Abrir modal de producto
async function openProductModal(id) {
    const isEdit = !!id;
    document.getElementById('prod-modal-title').textContent = isEdit ? 'Editar producto' : 'Nuevo producto';
    document.getElementById('prod-id').value = id || '';

    // Limpiar form
    ['prod-nombre','prod-cat','prod-desc','prod-precio','prod-unidad','prod-medidas','prod-badge','prod-emoji'].forEach(fid => {
        const el = document.getElementById(fid);
        if (el) el.value = '';
    });
    document.getElementById('prod-emoji').value = '📦';
    document.getElementById('prod-photo-preview').style.display = 'none';
    document.getElementById('prod-photo-preview').src = '';
    document.getElementById('prod-photo-current').value = '';

    if (isEdit) {
        const res  = await fetch(`../api/products.php?action=get&id=${id}`);
        const data = await res.json();
        if (data.success) {
            const p = data.product;
            document.getElementById('prod-nombre').value  = p.nombre || '';
            document.getElementById('prod-cat').value     = p.categoria || '';
            document.getElementById('prod-desc').value    = p.descripcion || '';
            document.getElementById('prod-precio').value  = p.precio || '';
            document.getElementById('prod-unidad').value  = p.unidad || '';
            document.getElementById('prod-medidas').value = p.medidas || '';
            document.getElementById('prod-badge').value   = p.etiqueta || '';
            document.getElementById('prod-emoji').value   = p.emoji || '📦';
            document.getElementById('prod-photo-current').value = p.foto || '';
            if (p.foto) {
                const prev = document.getElementById('prod-photo-preview');
                prev.src = `../assets/uploads/${p.foto}`;
                prev.style.display = 'block';
            }
        }
    }
    document.getElementById('overlay-product').classList.add('open');
}

// Preview foto
function previewPhoto(e) {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > 3 * 1024 * 1024) { showToast('La foto es demasiado grande (máx 3MB)', 'error'); return; }
    const reader = new FileReader();
    reader.onload = ev => {
        const prev = document.getElementById('prod-photo-preview');
        prev.src   = ev.target.result;
        prev.style.display = 'block';
    };
    reader.readAsDataURL(file);
}

// Guardar producto
async function saveProduct() {
    const nombre = document.getElementById('prod-nombre').value.trim();
    const cat    = document.getElementById('prod-cat').value;
    if (!nombre) { showToast('El nombre es obligatorio', 'error'); return; }
    if (!cat)    { showToast('Seleccioná una categoría', 'error'); return; }

    const formData = new FormData();
    formData.append('action', document.getElementById('prod-id').value ? 'update' : 'create');
    formData.append('id',          document.getElementById('prod-id').value);
    formData.append('nombre',      nombre);
    formData.append('categoria',   cat);
    formData.append('descripcion', document.getElementById('prod-desc').value.trim());
    formData.append('precio',      document.getElementById('prod-precio').value || '0');
    formData.append('unidad',      document.getElementById('prod-unidad').value.trim() || 'unid');
    formData.append('medidas',     document.getElementById('prod-medidas').value.trim());
    formData.append('etiqueta',    document.getElementById('prod-badge').value);
    formData.append('emoji',       document.getElementById('prod-emoji').value || '📦');
    formData.append('foto_actual', document.getElementById('prod-photo-current').value);

    const photoFile = document.getElementById('prod-photo-input').files[0];
    if (photoFile) formData.append('foto', photoFile);

    try {
        const res  = await fetch('../api/products.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            closeModal('product');
            showToast(data.message || '✅ Producto guardado');
            loadProducts();
            updateDashStats();
        } else {
            showToast(data.error || 'Error al guardar', 'error');
        }
    } catch (e) {
        showToast('Error de conexión', 'error');
    }
}

// Eliminar producto
async function deleteProduct(id, nombre) {
    if (!confirm(`¿Eliminás el producto "${nombre}"?`)) return;
    const res  = await fetch(`../api/products.php?action=delete&id=${id}`, { method: 'DELETE' });
    const data = await res.json();
    if (data.success) {
        showToast('Producto eliminado');
        loadProducts();
        updateDashStats();
    } else {
        showToast(data.error || 'Error al eliminar', 'error');
    }
}

// =====================================================
// PEDIDOS
// =====================================================
async function loadOrders() {
    const res  = await fetch('../api/orders.php?action=list');
    const data = await res.json();
    if (data.success) renderOrders(data.orders);
}

function renderOrders(orders) {
    const container = document.getElementById('orders-list');
    if (!container) return;

    const statusFilter = document.getElementById('status-filter')?.value || '';
    let filtered = orders;
    if (statusFilter) filtered = orders.filter(o => o.estado === statusFilter);

    if (!filtered.length) {
        container.innerHTML = `<div style="text-align:center;padding:3rem;color:var(--muted)">
            <div style="font-size:3rem;margin-bottom:1rem">📭</div>
            <p>No hay pedidos en esta categoría.</p>
        </div>`;
        return;
    }

    const estadoLabel = { nuevo:'Nuevo', leido:'Leído', en_proceso:'En proceso', completado:'Completado', cancelado:'Cancelado' };
    const estadoBadge = { nuevo:'nuevo', leido:'leido', en_proceso:'proceso', completado:'hecho', cancelado:'cancelado' };

    container.innerHTML = filtered.map(o => `
    <div class="order-card card" style="margin-bottom:1rem" id="oc-${o.id}">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:0.75rem">
            <div>
                <div style="font-family:var(--font-head);font-size:1.1rem;font-weight:700">${o.codigo}</div>
                <div style="font-size:11px;color:var(--muted)">${o.fecha}</div>
            </div>
            <span class="badge badge-${estadoBadge[o.estado] || 'nuevo'}">${estadoLabel[o.estado] || o.estado}</span>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;font-size:0.85rem;margin-bottom:0.75rem">
            <div><label style="font-size:10px;color:var(--muted);font-weight:600;text-transform:uppercase">Cliente</label><p>${o.nombre}</p></div>
            <div><label style="font-size:10px;color:var(--muted);font-weight:600;text-transform:uppercase">Teléfono</label><p>${o.telefono}</p></div>
            ${o.empresa ? `<div><label style="font-size:10px;color:var(--muted);font-weight:600;text-transform:uppercase">Empresa</label><p>${o.empresa}</p></div>` : ''}
            ${o.email   ? `<div><label style="font-size:10px;color:var(--muted);font-weight:600;text-transform:uppercase">Email</label><p>${o.email}</p></div>` : ''}
            <div><label style="font-size:10px;color:var(--muted);font-weight:600;text-transform:uppercase">Producto</label><p>${o.producto_nombre || '—'}</p></div>
            <div><label style="font-size:10px;color:var(--muted);font-weight:600;text-transform:uppercase">Cantidad</label><p>${o.cantidad || '—'}</p></div>
            ${o.medida ? `<div><label style="font-size:10px;color:var(--muted);font-weight:600;text-transform:uppercase">Medida</label><p>${o.medida}</p></div>` : ''}
            ${o.notas  ? `<div style="grid-column:1/-1"><label style="font-size:10px;color:var(--muted);font-weight:600;text-transform:uppercase">Comentarios</label><p>${o.notas}</p></div>` : ''}
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;padding-top:0.75rem;border-top:1px solid var(--border)">
            <a href="https://wa.me/${(o.telefono || '').replace(/\D/g,'')}" target="_blank" class="btn btn-whatsapp btn-sm">💬 WhatsApp</a>
            ${o.email ? `<a href="mailto:${o.email}" class="btn btn-outline btn-sm">📧 Email</a>` : ''}
            <select class="btn btn-outline btn-sm" onchange="updateOrderStatus(${o.id}, this.value)" style="cursor:pointer">
                <option value="">Cambiar estado...</option>
                <option value="leido">Marcar leído</option>
                <option value="en_proceso">En proceso</option>
                <option value="completado">Completado</option>
                <option value="cancelado">Cancelado</option>
            </select>
            <button class="btn btn-red btn-sm" onclick="deleteOrder(${o.id})">🗑 Eliminar</button>
        </div>
    </div>`).join('');
}

async function updateOrderStatus(id, status) {
    if (!status) return;
    const res  = await fetch('../api/orders.php?action=status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status })
    });
    const data = await res.json();
    if (data.success) { showToast('Estado actualizado'); loadOrders(); updateDashStats(); }
    else showToast('Error al actualizar', 'error');
}

async function deleteOrder(id) {
    if (!confirm('¿Eliminás este pedido?')) return;
    const res  = await fetch(`../api/orders.php?action=delete&id=${id}`, { method: 'DELETE' });
    const data = await res.json();
    if (data.success) { showToast('Pedido eliminado'); loadOrders(); updateDashStats(); }
    else showToast('Error al eliminar', 'error');
}

// =====================================================
// DASHBOARD
// =====================================================
async function updateDashStats() {
    const res  = await fetch('../api/orders.php?action=stats');
    const data = await res.json();
    if (data.success) {
        const s = data.stats;
        if (document.getElementById('dash-total'))  document.getElementById('dash-total').textContent  = s.total_productos || 0;
        if (document.getElementById('dash-orders')) document.getElementById('dash-orders').textContent = s.total_pedidos  || 0;
        if (document.getElementById('dash-new'))    document.getElementById('dash-new').textContent    = s.pedidos_nuevos || 0;
        const badge = document.getElementById('orders-badge');
        if (badge) {
            const n = parseInt(s.pedidos_nuevos || 0);
            badge.style.display = n > 0 ? 'inline' : 'none';
            badge.textContent   = n;
        }
    }
}

// =====================================================
// AJUSTES
// =====================================================
async function loadSettings() {
    const res  = await fetch('../api/products.php?action=settings');
    const data = await res.json();
    if (data.success) {
        const s = data.settings;
        Object.keys(s).forEach(k => {
            const el = document.getElementById('set-' + k);
            if (el) el.value = s[k] || '';
        });
    }
}

async function saveSettings() {
    const fields = ['nombre_negocio','slogan','whatsapp','email_contacto','email_pedidos','direccion','horario'];
    const payload = {};
    fields.forEach(f => {
        const el = document.getElementById('set-' + f);
        if (el) payload[f] = el.value.trim();
    });

    const res  = await fetch('../api/products.php?action=save_settings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    const data = await res.json();
    showToast(data.success ? '✅ Ajustes guardados' : (data.error || 'Error'), data.success ? 'success' : 'error');
}

async function changePassword() {
    const p1 = document.getElementById('set-pass').value;
    const p2 = document.getElementById('set-pass2').value;
    if (!p1)       { showToast('Escribí una contraseña', 'error'); return; }
    if (p1 !== p2) { showToast('Las contraseñas no coinciden', 'error'); return; }
    if (p1.length < 6) { showToast('La contraseña debe tener al menos 6 caracteres', 'error'); return; }

    const res  = await fetch('../api/products.php?action=change_password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ password: p1 })
    });
    const data = await res.json();
    if (data.success) {
        showToast('✅ Contraseña actualizada');
        document.getElementById('set-pass').value  = '';
        document.getElementById('set-pass2').value = '';
    } else showToast(data.error || 'Error al cambiar contraseña', 'error');
}

// =====================================================
// UTILIDADES
// =====================================================
function closeModal(id) {
    document.getElementById('overlay-' + id).classList.remove('open');
}

function slugify(str) {
    return (str || '').toLowerCase()
        .replace(/á/g,'a').replace(/é/g,'e').replace(/í/g,'i').replace(/ó/g,'o').replace(/ú/g,'u')
        .replace(/[^a-z0-9]/g,'');
}

function showToast(msg, type = 'success') {
    let wrap = document.getElementById('toast-wrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = 'toast-wrap';
        wrap.className = 'toast-wrap';
        document.body.appendChild(wrap);
    }
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.textContent = msg;
    wrap.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.overlay').forEach(ov => {
        ov.addEventListener('click', e => {
            if (e.target === ov) ov.classList.remove('open');
        });
    });
});

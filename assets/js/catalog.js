// =====================================================
// YAIR PACKAGING — catalog.js (Versión con categorías dinámicas)
// =====================================================

let allProducts = [];
let currentFilter = 'all';

async function loadProducts() {
    try {
        const res = await fetch('/api/products.php?action=list&active=1');
        const data = await res.json();
        if (data.success) {
            allProducts = data.products;
            const el = document.getElementById('stat-total');
            if (el) el.textContent = allProducts.length;
            renderCatalog();
        }
    } catch (e) {
        document.getElementById('catalog-content').innerHTML = 
            '<div style="text-align:center;padding:3rem;color:var(--muted)">Error cargando productos.</div>';
    }
}

function renderCatalog() {
    let filtered = allProducts;
    if (currentFilter !== 'all') {
        filtered = allProducts.filter(p => p.categoria === currentFilter);
    }
    
    // Agrupar por categoría
    const grouped = {};
    filtered.forEach(p => {
        if (!grouped[p.categoria]) grouped[p.categoria] = [];
        grouped[p.categoria].push(p);
    });
    
    const container = document.getElementById('catalog-content');
    if (!filtered.length) {
        container.innerHTML = `<div style="text-align:center;padding:3rem;color:var(--muted)">
            <div style="font-size:3rem;margin-bottom:1rem">📭</div>
            <p>No hay productos en esta categoría aún.</p>
        </div>`;
        return;
    }
    
    let html = '';
    for (const [cat, items] of Object.entries(grouped)) {
        const catColor = items[0]?.cat_color || '#fff3e0';
        const catIcono = items[0]?.cat_icono || '📦';
        html += `
        <div class="section-block">
            <div class="section-header">
                <div class="section-icon" style="background:${catColor};width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.3rem">${catIcono}</div>
                <div>
                    <div class="section-title">${escapeHtml(cat)}</div>
                    <div class="section-sub">${items.length} producto${items.length > 1 ? 's' : ''}</div>
                </div>
            </div>
            <div class="product-grid">
                ${items.map(p => renderProductCard(p, catColor)).join('')}
            </div>
        </div>`;
    }
    container.innerHTML = html;
}

function renderProductCard(p, bgColor) {
    const sizes = p.medidas ? p.medidas.split(',').map(s => `<span class="size-tag">${escapeHtml(s.trim())}</span>`).join('') : '';
    const badgeMap = { popular:'Popular', new:'Nuevo', oferta:'Oferta' };
    const badgeHtml = p.etiqueta ? `<span class="prod-badge badge-${p.etiqueta}">${badgeMap[p.etiqueta] || p.etiqueta}</span>` : '';
    const fotoSrc = p.foto ? `/assets/uploads/${p.foto}` : '';
    const imgHtml = fotoSrc ? `<img src="${fotoSrc}" alt="${escapeHtml(p.nombre)}" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0">` : '';
    const precio = Number(p.precio || 0).toLocaleString('es-PY');
    
    return `
    <div class="product-card" onclick="openOrderModal(${p.id}, '${escapeHtml(p.nombre)}')">
        <div class="product-img" style="background:${bgColor};position:relative;overflow:hidden">
            ${imgHtml}
            <span class="emoji-fallback" style="${fotoSrc ? 'opacity:0' : ''}">${p.emoji || '📦'}</span>
            ${badgeHtml}
        </div>
        <div class="product-body">
            <div class="product-cat">${escapeHtml(p.categoria)}</div>
            <div class="product-name">${escapeHtml(p.nombre)}</div>
            <div class="product-desc">${escapeHtml(p.descripcion || '')}</div>
            ${sizes ? `<div class="product-sizes">${sizes}</div>` : ''}
            <div class="product-footer">
                <div>
                    <div class="price-label">Desde</div>
                    <div class="price">₲ ${precio} <small>/${p.unidad || 'unid'}</small></div>
                </div>
                <button class="btn btn-accent btn-sm" onclick="event.stopPropagation();openOrderModal(${p.id},'${escapeHtml(p.nombre)}')">Pedir</button>
            </div>
        </div>
    </div>`;
}

function filterCat(cat, btn) {
    currentFilter = cat;
    document.querySelectorAll('.catnav-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    renderCatalog();
}

function openOrderModal(productId, productName) {
    document.getElementById('order-product-id').value = productId || '';
    document.getElementById('order-product-name').value = productName || '';
    ['order-name','order-phone','order-email','order-company','order-qty','order-size'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    const notes = document.getElementById('order-notes');
    if (notes) notes.value = '';
    clearFormErrors();
    const overlay = document.getElementById('overlay-order');
    if (overlay) overlay.classList.add('open');
}

function closeModal(id) {
    const overlay = document.getElementById('overlay-' + id);
    if (overlay) overlay.classList.remove('open');
}

async function submitOrder(via) {
    clearFormErrors();
    const name = document.getElementById('order-name').value.trim();
    const phone = document.getElementById('order-phone').value.trim();
    let valid = true;
    if (!name) { showFieldError('order-name', 'El nombre es obligatorio'); valid = false; }
    if (!phone) { showFieldError('order-phone', 'El teléfono es obligatorio'); valid = false; }
    if (!valid) return;
    
    const payload = {
        nombre: name,
        telefono: phone,
        email: document.getElementById('order-email').value.trim(),
        empresa: document.getElementById('order-company').value.trim(),
        producto: document.getElementById('order-product-name').value.trim(),
        cantidad: document.getElementById('order-qty').value.trim(),
        medida: document.getElementById('order-size').value.trim(),
        notas: document.getElementById('order-notes').value.trim(),
        via
    };
    
    try {
        const btns = document.querySelectorAll('#overlay-order .modal-footer .btn');
        btns.forEach(b => b.disabled = true);
        const res = await fetch('/api/orders.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            closeModal('order');
            showToast('✅ Pedido enviado. Te contactamos pronto!', 'success');
            if (via === 'whatsapp' && data.whatsapp_url) setTimeout(() => window.open(data.whatsapp_url, '_blank'), 500);
            if (via === 'email' && data.email_url) setTimeout(() => window.open(data.email_url, '_blank'), 500);
        } else {
            showToast(data.error || 'Error al enviar', 'error');
        }
    } catch (e) {
        showToast('Error de conexión', 'error');
    } finally {
        const btns = document.querySelectorAll('#overlay-order .modal-footer .btn');
        btns.forEach(b => b.disabled = false);
    }
}

function showFieldError(id, msg) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.borderColor = 'var(--red)';
    let err = el.nextElementSibling;
    if (!err || !err.classList.contains('form-error')) {
        err = document.createElement('div');
        err.className = 'form-error';
        el.parentNode.insertBefore(err, el.nextSibling);
    }
    err.textContent = msg;
}

function clearFormErrors() {
    document.querySelectorAll('.form-error').forEach(e => e.remove());
    document.querySelectorAll('#overlay-order input, #overlay-order textarea').forEach(el => {
        if (el) el.style.borderColor = '';
    });
}

function escapeHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
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
        ov.addEventListener('click', e => { if (e.target === ov) ov.classList.remove('open'); });
    });
    loadProducts();
});

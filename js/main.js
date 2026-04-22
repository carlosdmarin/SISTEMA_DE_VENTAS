// ============================================
// CONFIGURACIÓN DIRECTA DE SUPABASE
// ============================================
const SUPABASE_URL = 'https://ownjmawswuygfhltlzts.supabase.co';
const SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im93bmptYXdzdXd5Z2ZsaHRsenRzIiwicm9sZSI6ImFub24iLCJpYXQiOjE3MTM5NjU4NjAsImV4cCI6MjAyOTU0MTg2MH0.VEkbC4CmJ8P2Cp6dxwVfILXZvRL9YJrmZ7VhqR2pGZg';

// Variables para búsqueda
let ventasHoyOriginal = [];
let ventasHistorialOriginal = [];

// ============================================
// FUNCIÓN GENÉRICA PARA LLAMAR A SUPABASE
// ============================================
async function supabaseQuery(endpoint, method = 'GET', body = null) {
    const headers = {
        'apikey': SUPABASE_KEY,
        'Authorization': `Bearer ${SUPABASE_KEY}`,
        'Content-Type': 'application/json'
    };
    
    if (method === 'POST' || method === 'PATCH') {
        headers['Prefer'] = 'return=representation';
    }
    
    const options = {
        method: method,
        headers: headers
    };
    
    if (body) {
        options.body = JSON.stringify(body);
    }
    
    const response = await fetch(`${SUPABASE_URL}/rest/v1/${endpoint}`, options);
    
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    return await response.json();
}

/* ──── Drawer ──── */
function openDrawer() { 
    document.getElementById('drawer').classList.add('open'); 
    document.getElementById('overlay').classList.add('open'); 
}

function closeDrawer() { 
    document.getElementById('drawer').classList.remove('open'); 
    document.getElementById('overlay').classList.remove('open'); 
}

/* ──── Navigation ──── */
function goTo(page) {
    closeDrawer();
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.drawer-nav-item').forEach(n => n.classList.remove('active'));
    document.getElementById('page-' + page).classList.add('active');
    document.getElementById('nav-' + page).classList.add('active');
    
    if (page === 'hoy') {
        cargarVentasHoy();
    }
    if (page === 'ventas') {
        const nombreInput = document.getElementById('nombre');
        const kilosInput = document.getElementById('kilos');
        const totalSpan = document.getElementById('totalMostrado');
        const estadoSelect = document.getElementById('estado');
        
        if (nombreInput) nombreInput.value = '';
        if (kilosInput) kilosInput.value = '';
        if (totalSpan) totalSpan.textContent = '0.00';
        if (estadoSelect) estadoSelect.value = 'pendiente';
    }
}

/* ──── Date display ──── */
function updateDateDisplay() {
    const now = new Date();
    const opts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const fechaElem = document.getElementById('drawer-fecha');
    if (fechaElem) fechaElem.textContent = now.toLocaleDateString('es-PE', opts);
    
    actualizarBadge();
}
updateDateDisplay();

/* ──── Actualizar badge ──── */
async function actualizarBadge() {
    try {
        const hoy = new Date().toISOString().split('T')[0];
        const ventas = await supabaseQuery(`ventas?select=*&fecha_registro=gte.${hoy}`);
        
        let total = 0;
        ventas.forEach(v => {
            if (v.estado === 'cancelado') {
                total += parseFloat(v.total);
            }
        });
        
        const badge = document.getElementById('topbar-badge');
        if (badge) badge.innerHTML = `<i class="fas fa-soles"></i> S/ ${total.toFixed(2)}`;
        
    } catch (error) {
        console.error('Error al actualizar badge:', error);
        const badge = document.getElementById('topbar-badge');
        if (badge) badge.innerHTML = `S/ 0.00`;
    }
}

/* ──── Calc total ──── */
const kilosInput = document.getElementById('kilos');
if (kilosInput) {
    kilosInput.addEventListener('input', function() {
        const total = (parseFloat(this.value) || 0) * 4;
        const totalSpan = document.getElementById('totalMostrado');
        if (totalSpan) totalSpan.textContent = total.toFixed(2);
    });
}

/* ──── Save venta ──── */
async function guardarVenta() {
    const nombre = document.getElementById('nombre').value.trim();
    const kilos = parseFloat(document.getElementById('kilos').value);
    const estadoSelect = document.getElementById('estado');
    let estado = estadoSelect ? estadoSelect.value : 'pendiente';
    
    if (estado === 'pagado') estado = 'cancelado';
    
    if (!nombre) {
        return Swal.fire({
            icon: 'warning',
            title: '¡Falta el nombre!',
            text: 'Escribe el nombre del cliente',
            confirmButtonColor: '#2e9050'
        });
    }
    if (!kilos || kilos <= 0) {
        return Swal.fire({
            icon: 'warning',
            title: '¡Kilos inválidos!',
            text: 'Ingresa los kilos de ropa correctamente',
            confirmButtonColor: '#2e9050'
        });
    }
    
    const venta = {
        nombre_cliente: nombre,
        kilos: kilos,
        total: kilos * 4,
        estado: estado
    };
    
    try {
        await supabaseQuery('ventas', 'POST', venta);
        
        Swal.fire({
            icon: 'success',
            title: '¡Venta guardada!',
            html: `<b>${nombre}</b> · ${kilos} kg · <b>S/ ${(kilos * 4).toFixed(2)}</b>`,
            confirmButtonColor: '#2e9050',
            timer: 2000,
            showConfirmButton: false
        });
        
        document.getElementById('nombre').value = '';
        document.getElementById('kilos').value = '';
        document.getElementById('totalMostrado').textContent = '0.00';
        document.getElementById('estado').value = 'pendiente';
        
        actualizarBadge();
        
        const pageHoy = document.getElementById('page-hoy');
        if (pageHoy && pageHoy.classList.contains('active')) {
            cargarVentasHoy();
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo guardar la venta.',
            confirmButtonColor: '#2e9050'
        });
    }
}

/* ──── Build card HTML ──── */
function buildCard(v) {
    let badgeClass = '';
    let badgeText = '';
    
    if (v.estado === 'pendiente') {
        badgeClass = 'badge-pending';
        badgeText = '⏳ Pendiente';
    } else if (v.estado === 'cancelado') {
        badgeClass = 'badge-paid';
        badgeText = '✅ Pagado';
    } else {
        badgeClass = 'badge-pending';
        badgeText = v.estado;
    }
    
    const cardClass = v.estado === 'cancelado' ? 'pagado' : '';
    const nombreCompleto = v.nombre_cliente || 'Cliente';
    const initials = nombreCompleto.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
    const fecha = new Date(v.fecha_registro);
    const hora = fecha.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
    const idVenta = v.id_venta;
    
    const payBtn = v.estado === 'pendiente'
        ? `<button class="btn btn-pay" onclick="pagarVenta(${idVenta}, '${nombreCompleto.replace(/'/g, "\\'")}')">
            <i class="fas fa-money-bill-wave"></i> Marcar pagado
           </button>`
        : '';
    
    return `
        <div class="venta-card ${cardClass}">
            <div class="venta-top">
                <div class="venta-name">
                    <div class="avatar">${initials}</div>
                    ${nombreCompleto}
                </div>
                <div class="badge ${badgeClass}">${badgeText}</div>
            </div>
            <div class="venta-meta">
                <span><i class="fas fa-weight-hanging"></i> ${v.kilos} kg</span>
                <span><i class="fas fa-clock"></i> ${hora}</span>
                <span><i class="fas fa-tag"></i> S/ 4.00/kg</span>
            </div>
            <div class="venta-footer">
                <div class="venta-price"><sup>S/</sup>${parseFloat(v.total).toFixed(2)}</div>
                ${payBtn}
            </div>
        </div>`;
}

/* ──── Load today ──── */
async function cargarVentasHoy() {
    const listaVentas = document.getElementById('listaVentas');
    if (!listaVentas) return;
    
    listaVentas.innerHTML = '<div class="loader"><div class="spinner"></div> Cargando ventas…</div>';
    
    try {
        const hoy = new Date().toISOString().split('T')[0];
        const ventas = await supabaseQuery(`ventas?select=*&fecha_registro=gte.${hoy}&order=fecha_registro.desc`);
        
        ventasHoyOriginal = ventas;
        
        let total = 0;
        let kilos = 0;
        ventas.forEach(v => {
            total += parseFloat(v.total);
            kilos += parseFloat(v.kilos);
        });
        
        const stats = document.getElementById('statsHoy');
        if (stats) {
            const statVals = stats.querySelectorAll('.stat-val');
            if (statVals[0]) statVals[0].textContent = ventas.length;
            if (statVals[1]) statVals[1].textContent = kilos.toFixed(1);
            if (statVals[2]) statVals[2].innerHTML = 'S/ ' + total.toFixed(2);
        }
        
        if (!ventas.length) {
            listaVentas.innerHTML = `
                <div class="empty">
                    <div class="empty-icon"><i class="fas fa-soap"></i></div>
                    <p>Sin ventas por hoy</p>
                    <small>Toca el <strong>+</strong> para registrar la primera del día</small>
                </div>`;
            return;
        }
        
        listaVentas.innerHTML = `<div class="ventas-list">${ventas.map(buildCard).join('')}</div>`;
        
        const searchHoy = document.getElementById('searchHoy');
        if (searchHoy) searchHoy.value = '';
        const clearBtn = document.getElementById('clearSearchHoy');
        if (clearBtn) clearBtn.style.display = 'none';
        
    } catch (error) {
        console.error('Error en cargarVentasHoy:', error);
        listaVentas.innerHTML = `
            <div class="empty">
                <div class="empty-icon"><i class="fas fa-plug"></i></div>
                <p>Sin conexión al servidor</p>
                <small>Error: ${error.message}</small>
            </div>`;
    }
}

/* ──── Pay ──── */
async function pagarVenta(id, nombre) {
    const result = await Swal.fire({
        title: '¿Confirmar pago?',
        html: `Marcar venta de <b>${nombre}</b> como pagada`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2e9050',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Sí, cobrado ✓',
        cancelButtonText: 'Cancelar'
    });
    
    if (result.isConfirmed) {
        try {
            await supabaseQuery(`ventas?id_venta=eq.${id}`, 'PATCH', { estado: 'cancelado' });
            
            Swal.fire({
                icon: 'success',
                title: '¡Pagado!',
                timer: 1500,
                showConfirmButton: false
            });
            cargarVentasHoy();
            actualizarBadge();
        } catch (error) {
            console.error('Error al pagar:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo marcar como pagado',
                confirmButtonColor: '#2e9050'
            });
        }
    }
}

/* ──── Init ──── */
document.addEventListener('DOMContentLoaded', function() {
    cargarVentasHoy();
    setInterval(() => {
        const pageHoy = document.getElementById('page-hoy');
        if (pageHoy && pageHoy.classList.contains('active')) {
            cargarVentasHoy();
        }
    }, 15000);
    setInterval(actualizarBadge, 30000);
});

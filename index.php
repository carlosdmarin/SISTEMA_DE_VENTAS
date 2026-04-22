<?php
// index.php - VERSIÓN FINAL CON JAVASCRIPT EMBEBIDO
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#1a6b3a">
    <title>LavaSoft – Lavandería Sostenible</title>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,500;0,9..40,700;0,9..40,900;1,9..40,400&family=Fraunces:ital,wght@0,700;0,900;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<!-- Overlay -->
<div class="drawer-overlay" id="overlay" onclick="closeDrawer()"></div>

<!-- Side Drawer -->
<nav class="drawer" id="drawer">
    <div class="drawer-header">
        <div class="drawer-logo">
            <div class="bubble"><i class="fas fa-soap"></i></div>
            LavaSoft
        </div>
        <div class="drawer-sub"><i class="fas fa-leaf"></i> Lavandería Sostenible</div>
    </div>

    <div class="drawer-nav">
        <div class="drawer-nav-item active" id="nav-ventas" onclick="goTo('ventas')">
            <div class="icon"><i class="fas fa-plus-circle"></i></div>
            <div>
                <div>Nueva Venta</div>
                <div style="font-size:12px;opacity:.6;font-weight:400">Registrar pedido</div>
            </div>
        </div>
        <div class="drawer-nav-item" id="nav-hoy" onclick="goTo('hoy')">
            <div class="icon"><i class="fas fa-calendar-day"></i></div>
            <div>
                <div>Ventas de Hoy</div>
                <div style="font-size:12px;opacity:.6;font-weight:400">Pedidos del día</div>
            </div>
        </div>

        <div class="drawer-divider"></div>

        <div class="drawer-nav-item" id="nav-historial" onclick="goTo('historial')">
            <div class="icon"><i class="fas fa-clock-rotate-left"></i></div>
            <div>
                <div>Historial</div>
                <div style="font-size:12px;opacity:.6;font-weight:400">Buscar por fecha</div>
            </div>
        </div>
    </div>

    <div class="drawer-footer">
        Precio actual: <strong>S/ 4.00 / kg</strong><br>
        <span id="drawer-fecha" style="margin-top:4px;display:block"></span>
    </div>
</nav>

<!-- Top Bar -->
<header class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="openDrawer()" aria-label="Menú">
            <i class="fas fa-bars"></i>
        </button>
        <div class="topbar-title">Lava<span>Soft</span></div>
    </div>
    <div class="topbar-badge" id="topbar-badge">Cargando…</div>
</header>

<!-- PAGE: NUEVA VENTA -->
<div class="page active" id="page-ventas">
    <div style="padding-top:20px">
        <div class="section-title"><span class="dot"></span> Nueva Venta</div>

        <div class="form-card">
            <div class="field">
                <label><i class="fas fa-user"></i> Nombre del cliente</label>
                <input type="text" id="nombre" placeholder="Ej: María González" autocomplete="off">
            </div>

            <div class="field">
                <label><i class="fas fa-weight-hanging"></i> Kilos de ropa</label>
                <input type="number" id="kilos" step="0.1" min="0" placeholder="0.0" autocomplete="off">
            </div>

            <div class="total-pill">
                <div>
                    <div class="lbl"><i class="fas fa-calculator"></i> Total a pagar</div>
                    <div class="amount"><sup>S/</sup><span id="totalMostrado">0.00</span></div>
                    <div class="rate-chip">S/ 4.00 por kilo</div>
                </div>
                <div style="font-size:32px; opacity:.25; color:white">
                    <i class="fas fa-soap"></i>
                </div>
            </div>

            <div class="field">
                <label><i class="fas fa-tag"></i> Estado del pedido</label>
                <select id="estado">
                    <option value="pendiente">⏳ Pendiente de pago</option>
                    <option value="pagado">✅ Pagado al instante</option>
                </select>
            </div>

            <button class="btn btn-save" onclick="guardarVenta()">
                <i class="fas fa-save"></i> Guardar Venta
            </button>
        </div>
    </div>
</div>

<!-- PAGE: VENTAS DE HOY -->
<div class="page" id="page-hoy">
    <div style="padding-top:20px">
        <div class="section-title"><span class="dot"></span> Ventas de Hoy</div>

        <div class="stats-row" id="statsHoy">
            <div class="stat-card"><div class="stat-val" id="statPedidos">–</div><div class="stat-lbl">Pedidos</div></div>
            <div class="stat-card"><div class="stat-val" id="statKilos">–</div><div class="stat-lbl">Kilos</div></div>
            <div class="stat-card highlight"><div class="stat-val" id="statTotal">–</div><div class="stat-lbl">Total S/</div></div>
        </div>

        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="searchHoy" placeholder="Buscar por nombre..." autocomplete="off">
            <button class="btn-clear" id="clearSearchHoy" style="display:none" onclick="resetSearchHoy()">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>

        <div id="listaVentas">
            <div class="loader"><div class="spinner"></div> Cargando ventas…</div>
        </div>
    </div>
</div>

<!-- PAGE: HISTORIAL -->
<div class="page" id="page-historial">
    <div style="padding-top:20px">
        <div class="section-title"><span class="dot"></span> Historial de Ventas</div>

        <div class="filter-bar">
            <div class="filter-title"><i class="fas fa-filter"></i> Elige un día</div>
            <div class="date-row">
                <div class="date-field" style="flex:1">
                    <label>Fecha</label>
                    <input type="date" id="fechaDia">
                </div>
                <button class="btn-filter" onclick="buscarHistorial()">
                    <i class="fas fa-search"></i> Ver
                </button>
            </div>
            <div class="quick-dates">
                <button class="quick-btn" onclick="setQuick('hoy')">Hoy</button>
                <button class="quick-btn" onclick="setQuick('ayer')">Ayer</button>
                <button class="quick-btn" onclick="setQuick('antier')">Anteayer</button>
            </div>
        </div>

        <div class="hist-summary" id="histSummary" style="display:none">
            <div class="hist-stat">
                <div class="val" id="hTotalVentas">0</div>
                <div class="lbl">Ventas encontradas</div>
            </div>
            <div class="hist-stat">
                <div class="val" id="hTotalKilos">0</div>
                <div class="lbl">Kilos lavados</div>
            </div>
            <div class="hist-stat">
                <div class="val" id="hTotalIngreso">S/ 0</div>
                <div class="lbl">Ingresos totales</div>
            </div>
            <div class="hist-stat">
                <div class="val" id="hPagados">0</div>
                <div class="lbl">Pedidos pagados</div>
            </div>
        </div>

        <div class="search-bar historial-search">
            <i class="fas fa-search"></i>
            <input type="text" id="searchHistorial" placeholder="Buscar por nombre en historial..." autocomplete="off">
            <button class="btn-clear" id="clearSearchHistorial" style="display:none" onclick="resetSearchHistorial()">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>

        <div id="listaHistorial">
            <div class="empty">
                <div class="empty-icon"><i class="fas fa-calendar-search"></i></div>
                <p>Elige un rango de fechas</p>
                <small>y toca Buscar para ver el historial</small>
            </div>
        </div>
    </div>
</div>

<!-- FAB -->
<button class="fab" onclick="goTo('ventas')" title="Nueva venta">
    <i class="fas fa-plus"></i>
</button>

<!-- ============================================ -->
<!-- JAVASCRIPT EMBEBIDO - CONEXIÓN DIRECTA A SUPABASE -->
<!-- ============================================ -->
<script>
// ========== CONFIGURACIÓN DE SUPABASE ==========
const SUPABASE_URL = 'https://ownjmawswuygfhltlzts.supabase.co';
const SUPABASE_KEY = 'sb_publishable_5ceuA5WElQ_dB31Oddj1bg_Pa-7uFZz';

// Variables globales
let ventasHoyOriginal = [];
let ventasHistorialOriginal = [];

// ========== FUNCIÓN GENÉRICA PARA SUPABASE ==========
async function supabaseQuery(endpoint, method = 'GET', body = null) {
    const headers = {
        'apikey': SUPABASE_KEY,
        'Authorization': `Bearer ${SUPABASE_KEY}`,
        'Content-Type': 'application/json'
    };
    
    if (method === 'POST' || method === 'PATCH') {
        headers['Prefer'] = 'return=representation';
    }
    
    const options = { method, headers };
    if (body) options.body = JSON.stringify(body);
    
    const response = await fetch(`${SUPABASE_URL}/rest/v1/${endpoint}`, options);
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return await response.json();
}

// ========== DRAWER ==========
function openDrawer() { 
    document.getElementById('drawer').classList.add('open'); 
    document.getElementById('overlay').classList.add('open'); 
}
function closeDrawer() { 
    document.getElementById('drawer').classList.remove('open'); 
    document.getElementById('overlay').classList.remove('open'); 
}

// ========== NAVEGACIÓN ==========
function goTo(page) {
    closeDrawer();
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.drawer-nav-item').forEach(n => n.classList.remove('active'));
    document.getElementById('page-' + page).classList.add('active');
    document.getElementById('nav-' + page).classList.add('active');
    
    if (page === 'hoy') cargarVentasHoy();
    if (page === 'ventas') {
        document.getElementById('nombre').value = '';
        document.getElementById('kilos').value = '';
        document.getElementById('totalMostrado').textContent = '0.00';
        document.getElementById('estado').value = 'pendiente';
    }
}

// ========== FECHA ==========
function updateDateDisplay() {
    const now = new Date();
    const opts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const fechaElem = document.getElementById('drawer-fecha');
    if (fechaElem) fechaElem.textContent = now.toLocaleDateString('es-PE', opts);
    actualizarBadge();
}
updateDateDisplay();

// ========== CALCULAR TOTAL ==========
document.getElementById('kilos')?.addEventListener('input', function() {
    const total = (parseFloat(this.value) || 0) * 4;
    document.getElementById('totalMostrado').textContent = total.toFixed(2);
});

// ========== GUARDAR VENTA ==========
async function guardarVenta() {
    const nombre = document.getElementById('nombre').value.trim();
    const kilos = parseFloat(document.getElementById('kilos').value);
    let estado = document.getElementById('estado').value;
    if (estado === 'pagado') estado = 'cancelado';
    
    if (!nombre) {
        return Swal.fire({ icon: 'warning', title: '¡Falta el nombre!', text: 'Escribe el nombre del cliente' });
    }
    if (!kilos || kilos <= 0) {
        return Swal.fire({ icon: 'warning', title: '¡Kilos inválidos!', text: 'Ingresa los kilos correctamente' });
    }
    
    const venta = {
        nombre_cliente: nombre,
        kilos: kilos,
        total: kilos * 4,
        estado: estado
    };
    
    try {
        await supabaseQuery('ventas', 'POST', venta);
        Swal.fire({ icon: 'success', title: '¡Venta guardada!', timer: 1500, showConfirmButton: false });
        goTo('hoy');
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo guardar la venta' });
    }
}

// ========== CARGAR VENTAS DE HOY ==========
async function cargarVentasHoy() {
    const lista = document.getElementById('listaVentas');
    if (!lista) return;
    lista.innerHTML = '<div class="loader"><div class="spinner"></div> Cargando ventas…</div>';
    
    try {
        const hoy = new Date().toISOString().split('T')[0];
        const ventas = await supabaseQuery(`ventas?select=*&fecha_registro=gte.${hoy}&order=fecha_registro.desc`);
        ventasHoyOriginal = ventas;
        
        let total = 0, kilos = 0;
        ventas.forEach(v => { total += parseFloat(v.total); kilos += parseFloat(v.kilos); });
        
        document.getElementById('statPedidos').textContent = ventas.length;
        document.getElementById('statKilos').textContent = kilos.toFixed(1);
        document.getElementById('statTotal').textContent = 'S/ ' + total.toFixed(2);
        
        if (!ventas.length) {
            lista.innerHTML = '<div class="empty"><div class="empty-icon"><i class="fas fa-soap"></i></div><p>Sin ventas por hoy</p></div>';
            return;
        }
        
        lista.innerHTML = `<div class="ventas-list">${ventas.map(v => buildCard(v)).join('')}</div>`;
        document.getElementById('searchHoy').value = '';
        document.getElementById('clearSearchHoy').style.display = 'none';
    } catch (error) {
        console.error('Error:', error);
        lista.innerHTML = '<div class="empty"><p>Error de conexión</p></div>';
    }
}

// ========== CONSTRUIR TARJETA ==========
function buildCard(v) {
    const badgeClass = v.estado === 'pendiente' ? 'badge-pending' : 'badge-paid';
    const badgeText = v.estado === 'pendiente' ? '⏳ Pendiente' : '✅ Pagado';
    const nombre = v.nombre_cliente || 'Cliente';
    const initials = nombre.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();
    const fecha = new Date(v.fecha_registro);
    const hora = fecha.toLocaleTimeString('es-PE', { hour:'2-digit', minute:'2-digit' });
    const payBtn = v.estado === 'pendiente' 
        ? `<button class="btn-pay" onclick="pagarVenta(${v.id_venta}, '${nombre.replace(/'/g, "\\'")}')"><i class="fas fa-money-bill"></i> Pagar</button>` 
        : '';
    
    return `
        <div class="venta-card ${v.estado === 'cancelado' ? 'pagado' : ''}">
            <div class="venta-top">
                <div class="venta-name"><div class="avatar">${initials}</div>${nombre}</div>
                <div class="badge ${badgeClass}">${badgeText}</div>
            </div>
            <div class="venta-meta">
                <span><i class="fas fa-weight-hanging"></i> ${v.kilos} kg</span>
                <span><i class="fas fa-clock"></i> ${hora}</span>
            </div>
            <div class="venta-footer">
                <div class="venta-price"><sup>S/</sup>${parseFloat(v.total).toFixed(2)}</div>
                ${payBtn}
            </div>
        </div>`;
}

// ========== PAGAR VENTA ==========
async function pagarVenta(id, nombre) {
    const result = await Swal.fire({
        title: '¿Confirmar pago?',
        html: `Marcar venta de <b>${nombre}</b> como pagada`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí'
    });
    
    if (result.isConfirmed) {
        try {
            await supabaseQuery(`ventas?id_venta=eq.${id}`, 'PATCH', { estado: 'cancelado' });
            Swal.fire({ icon: 'success', title: '¡Pagado!', timer: 1000, showConfirmButton: false });
            cargarVentasHoy();
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Error' });
        }
    }
}

// ========== ACTUALIZAR BADGE ==========
async function actualizarBadge() {
    try {
        const hoy = new Date().toISOString().split('T')[0];
        const ventas = await supabaseQuery(`ventas?select=total,estado&fecha_registro=gte.${hoy}`);
        const total = ventas.filter(v => v.estado === 'cancelado').reduce((a, v) => a + parseFloat(v.total), 0);
        document.getElementById('topbar-badge').innerHTML = `<i class="fas fa-soles"></i> S/ ${total.toFixed(2)}`;
    } catch (e) {
        document.getElementById('topbar-badge').innerHTML = 'S/ 0.00';
    }
}

// ========== BUSCAR EN HOY ==========
document.getElementById('searchHoy')?.addEventListener('input', e => {
    const term = e.target.value.toLowerCase().trim();
    document.getElementById('clearSearchHoy').style.display = term ? 'flex' : 'none';
    const filtradas = term ? ventasHoyOriginal.filter(v => v.nombre_cliente?.toLowerCase().includes(term)) : ventasHoyOriginal;
    document.getElementById('listaVentas').innerHTML = filtradas.length 
        ? `<div class="ventas-list">${filtradas.map(v => buildCard(v)).join('')}</div>`
        : '<div class="empty"><p>Sin resultados</p></div>';
});

function resetSearchHoy() {
    document.getElementById('searchHoy').value = '';
    document.getElementById('clearSearchHoy').style.display = 'none';
    document.getElementById('listaVentas').innerHTML = `<div class="ventas-list">${ventasHoyOriginal.map(v => buildCard(v)).join('')}</div>`;
}

// ========== HISTORIAL ==========
async function buscarHistorial() {
    const fecha = document.getElementById('fechaDia').value;
    if (!fecha) return Swal.fire({ icon: 'info', title: 'Elige una fecha' });
    
    const lista = document.getElementById('listaHistorial');
    lista.innerHTML = '<div class="loader"><div class="spinner"></div> Buscando…</div>';
    
    try {
        const ventas = await supabaseQuery(`ventas?select=*&fecha_registro=gte.${fecha}&fecha_registro=lte.${fecha}T23:59:59&order=fecha_registro.desc`);
        ventasHistorialOriginal = ventas;
        
        document.getElementById('histSummary').style.display = 'grid';
        document.getElementById('hTotalVentas').textContent = ventas.length;
        document.getElementById('hTotalKilos').textContent = ventas.reduce((a,v) => a + parseFloat(v.kilos), 0).toFixed(1);
        document.getElementById('hTotalIngreso').textContent = 'S/ ' + ventas.reduce((a,v) => a + parseFloat(v.total), 0).toFixed(2);
        document.getElementById('hPagados').textContent = ventas.filter(v => v.estado === 'cancelado').length;
        
        lista.innerHTML = ventas.length 
            ? `<div class="ventas-list">${ventas.map(v => buildCard(v)).join('')}</div>`
            : '<div class="empty"><p>Sin ventas en esa fecha</p></div>';
    } catch (error) {
        lista.innerHTML = '<div class="empty"><p>Error al buscar</p></div>';
    }
}

function setQuick(opt) {
    const today = new Date();
    const fmt = d => d.toISOString().slice(0,10);
    if (opt === 'hoy') document.getElementById('fechaDia').value = fmt(today);
    else if (opt === 'ayer') { const y = new Date(today); y.setDate(y.getDate()-1); document.getElementById('fechaDia').value = fmt(y); }
    else if (opt === 'antier') { const a = new Date(today); a.setDate(a.getDate()-2); document.getElementById('fechaDia').value = fmt(a); }
    buscarHistorial();
}

// ========== INICIAR ==========
document.addEventListener('DOMContentLoaded', () => {
    const today = new Date().toISOString().slice(0,10);
    if (document.getElementById('fechaDia')) document.getElementById('fechaDia').value = today;
    cargarVentasHoy();
    setInterval(() => { if (document.getElementById('page-hoy')?.classList.contains('active')) cargarVentasHoy(); }, 15000);
    setInterval(actualizarBadge, 30000);
});
</script>
</body>
</html>

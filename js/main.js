// ============================================
// CONFIGURACIÓN - Cambia esto según donde estés
// ============================================

// Para desarrollo local (XAMPP)
const API = "http://localhost/SISTEMA_DE_VENTAS/api/ventas.php";

// Variables para búsqueda
let ventasHoyOriginal = [];
let ventasHistorialOriginal = [];

// ============================================
// CÓDIGO COMPLETO
// ============================================

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
        // Limpiar formulario al entrar
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
    
    // Actualizar badge del topbar con total del día
    actualizarBadge();
}
updateDateDisplay();

/* ──── Actualizar badge del topbar con total del día ──── */
async function actualizarBadge() {
    try {
        const res = await fetch(`${API}?hoy=1`);
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const ventas = await res.json();
        
        if (!Array.isArray(ventas)) {
            console.error('La respuesta no es un array:', ventas);
            const badge = document.getElementById('topbar-badge');
            if (badge) badge.innerHTML = `S/ 0.00`;
            return;
        }
        
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
        nombreCliente: nombre,
        kilos: kilos,
        total: kilos * 4,
        estado: estado
    };
    
    try {
        const response = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(venta)
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Venta guardada!',
                html: `<b>${nombre}</b> · ${kilos} kg · <b>S/ ${(kilos * 4).toFixed(2)}</b>`,
                confirmButtonColor: '#2e9050',
                timer: 2000,
                showConfirmButton: false
            });
            
            const nombreInput = document.getElementById('nombre');
            const kilosInputElem = document.getElementById('kilos');
            const totalSpan = document.getElementById('totalMostrado');
            const estadoSelectElem = document.getElementById('estado');
            
            if (nombreInput) nombreInput.value = '';
            if (kilosInputElem) kilosInputElem.value = '';
            if (totalSpan) totalSpan.textContent = '0.00';
            if (estadoSelectElem) estadoSelectElem.value = 'pendiente';
            
            actualizarBadge();
            
            const pageHoy = document.getElementById('page-hoy');
            if (pageHoy && pageHoy.classList.contains('active')) {
                cargarVentasHoy();
            }
        } else {
            throw new Error(data.error || 'Error al guardar');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo guardar la venta. Verifica que el servidor esté corriendo.',
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
    const nombreCompleto = v.nombre_cliente || v.nombreCliente || 'Cliente';
    const initials = nombreCompleto.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
    const fecha = new Date(v.fecha_registro);
    const hora = fecha.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
    const idVenta = v.id_venta || v.idVenta;
    
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
        const res = await fetch(`${API}?hoy=1&t=${Date.now()}`);
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const ventas = await res.json();
        
        if (!Array.isArray(ventas)) {
            console.error('La respuesta no es un array:', ventas);
            listaVentas.innerHTML = `
                <div class="empty">
                    <div class="empty-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <p>Error: Respuesta inválida del servidor</p>
                </div>`;
            return;
        }
        
        // Guardar copia original para búsqueda
        ventasHoyOriginal = ventas;
        
        // Calcular estadísticas
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
        
        // Resetear buscador
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

/* ──── Funciones de búsqueda para Ventas de Hoy ──── */
function buscarEnVentasHoy(termino) {
    const searchTerm = termino.toLowerCase().trim();
    const clearBtn = document.getElementById('clearSearchHoy');
    const listaVentas = document.getElementById('listaVentas');
    
    if (searchTerm === '') {
        if (clearBtn) clearBtn.style.display = 'none';
        mostrarVentasHoy(ventasHoyOriginal);
        actualizarStatsHoy(ventasHoyOriginal);
        return;
    }
    
    if (clearBtn) clearBtn.style.display = 'flex';
    const resultados = ventasHoyOriginal.filter(v => 
        (v.nombre_cliente || v.nombreCliente).toLowerCase().includes(searchTerm)
    );
    
    mostrarVentasHoy(resultados);
    actualizarStatsHoy(resultados);
}

function mostrarVentasHoy(ventas) {
    const listaVentas = document.getElementById('listaVentas');
    if (!listaVentas) return;
    
    if (!ventas.length) {
        listaVentas.innerHTML = `
            <div class="empty">
                <div class="empty-icon"><i class="fas fa-search"></i></div>
                <p>No se encontraron resultados</p>
                <small>Prueba con otro nombre</small>
            </div>`;
        return;
    }
    listaVentas.innerHTML = `<div class="ventas-list">${ventas.map(buildCard).join('')}</div>`;
}

function actualizarStatsHoy(ventas) {
    const total = ventas.reduce((a, v) => a + parseFloat(v.total), 0);
    const kilos = ventas.reduce((a, v) => a + parseFloat(v.kilos), 0);
    const stats = document.getElementById('statsHoy');
    if (stats) {
        const statVals = stats.querySelectorAll('.stat-val');
        if (statVals[0]) statVals[0].textContent = ventas.length;
        if (statVals[1]) statVals[1].textContent = kilos.toFixed(1);
        if (statVals[2]) statVals[2].innerHTML = 'S/ ' + total.toFixed(2);
    }
}

function resetSearchHoy() {
    const searchInput = document.getElementById('searchHoy');
    if (searchInput) {
        searchInput.value = '';
        buscarEnVentasHoy('');
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
            const response = await fetch(`${API}/${id}/pagar`, { method: 'PUT' });
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Pagado!',
                    timer: 1500,
                    showConfirmButton: false
                });
                cargarVentasHoy();
                actualizarBadge();
            } else {
                throw new Error(data.error || 'Error al pagar');
            }
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

/* ──── Historial search ──── */
async function buscarHistorial() {
    const fechaDia = document.getElementById('fechaDia');
    if (!fechaDia || !fechaDia.value) {
        return Swal.fire({
            icon: 'info',
            title: 'Elige una fecha',
            text: 'Selecciona una fecha para ver el historial',
            confirmButtonColor: '#2e9050'
        });
    }
    
    const fecha = fechaDia.value;
    const listaHistorial = document.getElementById('listaHistorial');
    const histSummary = document.getElementById('histSummary');
    
    if (listaHistorial) listaHistorial.innerHTML = '<div class="loader"><div class="spinner"></div> Buscando…</div>';
    if (histSummary) histSummary.style.display = 'none';
    
    try {
        const res = await fetch(`${API}?desde=${fecha}&hasta=${fecha}&t=${Date.now()}`);
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const ventas = await res.json();
        
        if (!Array.isArray(ventas)) {
            console.error('Respuesta inválida:', ventas);
            if (listaHistorial) {
                listaHistorial.innerHTML = `
                    <div class="empty">
                        <div class="empty-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <p>Error en la respuesta del servidor</p>
                    </div>`;
            }
            return;
        }
        
        // Guardar copia original del historial
        ventasHistorialOriginal = ventas;
        
        actualizarResumenHistorial(ventas);
        
        if (!ventas.length && listaHistorial) {
            listaHistorial.innerHTML = `
                <div class="empty">
                    <div class="empty-icon"><i class="fas fa-calendar-xmark"></i></div>
                    <p>Sin ventas en esa fecha</p>
                    <small>Prueba con otra fecha</small>
                </div>`;
            return;
        }
        
        if (listaHistorial) {
            listaHistorial.innerHTML = `<div class="ventas-list">${ventas.map(buildCard).join('')}</div>`;
        }
        
        // Resetear buscador de historial
        const searchHistorial = document.getElementById('searchHistorial');
        if (searchHistorial) searchHistorial.value = '';
        const clearBtn = document.getElementById('clearSearchHistorial');
        if (clearBtn) clearBtn.style.display = 'none';
        
    } catch (error) {
        console.error('Error en buscarHistorial:', error);
        if (listaHistorial) {
            listaHistorial.innerHTML = `
                <div class="empty">
                    <div class="empty-icon"><i class="fas fa-plug"></i></div>
                    <p>Error al buscar</p>
                    <small>Verifica la conexión con el servidor</small>
                </div>`;
        }
    }
}

function actualizarResumenHistorial(ventas) {
    const totalV = ventas.length;
    const totalK = ventas.reduce((a, v) => a + parseFloat(v.kilos), 0);
    const totalI = ventas.reduce((a, v) => a + parseFloat(v.total), 0);
    const pagados = ventas.filter(v => v.estado === 'cancelado').length;
    
    const hTotalVentas = document.getElementById('hTotalVentas');
    const hTotalKilos = document.getElementById('hTotalKilos');
    const hTotalIngreso = document.getElementById('hTotalIngreso');
    const hPagados = document.getElementById('hPagados');
    const histSummary = document.getElementById('histSummary');
    
    if (hTotalVentas) hTotalVentas.textContent = totalV;
    if (hTotalKilos) hTotalKilos.textContent = totalK.toFixed(1);
    if (hTotalIngreso) hTotalIngreso.textContent = 'S/ ' + totalI.toFixed(2);
    if (hPagados) hPagados.textContent = pagados;
    if (histSummary) histSummary.style.display = 'grid';
}

function mostrarHistorial(ventas) {
    const listaHistorial = document.getElementById('listaHistorial');
    if (!listaHistorial) return;
    
    if (!ventas.length) {
        listaHistorial.innerHTML = `
            <div class="empty">
                <div class="empty-icon"><i class="fas fa-search"></i></div>
                <p>No se encontraron resultados</p>
                <small>Prueba con otro nombre</small>
            </div>`;
        return;
    }
    listaHistorial.innerHTML = `<div class="ventas-list">${ventas.map(buildCard).join('')}</div>`;
}

function buscarEnHistorial(termino) {
    const searchTerm = termino.toLowerCase().trim();
    const clearBtn = document.getElementById('clearSearchHistorial');
    
    if (searchTerm === '') {
        if (clearBtn) clearBtn.style.display = 'none';
        mostrarHistorial(ventasHistorialOriginal);
        actualizarResumenHistorial(ventasHistorialOriginal);
        return;
    }
    
    if (clearBtn) clearBtn.style.display = 'flex';
    const resultados = ventasHistorialOriginal.filter(v => 
        (v.nombre_cliente || v.nombreCliente).toLowerCase().includes(searchTerm)
    );
    
    mostrarHistorial(resultados);
    actualizarResumenHistorial(resultados);
}

function resetSearchHistorial() {
    const searchInput = document.getElementById('searchHistorial');
    if (searchInput) {
        searchInput.value = '';
        buscarEnHistorial('');
    }
}

/* ──── Quick date buttons ──── */
function setQuick(opt) {
    const fechaDia = document.getElementById('fechaDia');
    if (!fechaDia) return;
    
    const today = new Date();
    const fmt = d => d.toISOString().slice(0, 10);
    
    if (opt === 'hoy') {
        fechaDia.value = fmt(today);
    } else if (opt === 'ayer') {
        const y = new Date(today);
        y.setDate(y.getDate() - 1);
        fechaDia.value = fmt(y);
    } else if (opt === 'antier') {
        const a = new Date(today);
        a.setDate(a.getDate() - 2);
        fechaDia.value = fmt(a);
    }
    
    buscarHistorial();
}

/* ──── Init ──── */
document.addEventListener('DOMContentLoaded', function() {
    // Set default date for historial
    const fechaDia = document.getElementById('fechaDia');
    if (fechaDia) {
        const today = new Date().toISOString().slice(0, 10);
        fechaDia.value = today;
    }
    
    // Auto-refresh hoy every 15s when on that page
    setInterval(() => {
        const pageHoy = document.getElementById('page-hoy');
        if (pageHoy && pageHoy.classList.contains('active')) {
            cargarVentasHoy();
        }
    }, 15000);
    
    // Cargar ventas de hoy al iniciar
    cargarVentasHoy();
    
    // Actualizar badge periódicamente
    setInterval(actualizarBadge, 30000);
    
    // ========== EVENT LISTENERS PARA BUSCADORES ==========
    
    // Buscador en Ventas de Hoy
    const searchHoy = document.getElementById('searchHoy');
    if (searchHoy) {
        searchHoy.addEventListener('input', (e) => buscarEnVentasHoy(e.target.value));
    }
    
    // Buscador en Historial
    const searchHistorial = document.getElementById('searchHistorial');
    if (searchHistorial) {
        searchHistorial.addEventListener('input', (e) => buscarEnHistorial(e.target.value));
    }
    
    // Botón limpiar en Ventas de Hoy
    const clearSearchHoy = document.getElementById('clearSearchHoy');
    if (clearSearchHoy) {
        clearSearchHoy.addEventListener('click', () => resetSearchHoy());
    }
    
    // Botón limpiar en Historial
    const clearSearchHistorial = document.getElementById('clearSearchHistorial');
    if (clearSearchHistorial) {
        clearSearchHistorial.addEventListener('click', () => resetSearchHistorial());
    }
});
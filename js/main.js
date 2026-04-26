// ============================================
// CONFIGURACIÓN
// ============================================

// Cambia esto según tu backend (si usas API)
const API = "http://localhost/SISTEMA_DE_VENTAS/api/ventas.php";

// Variables globales
let ventasHoyOriginal = [];
let ventasHistorialOriginal = [];
let reporteActual = 'diario';
let fechaHistorialActual = ''; // Guardar la fecha actual del historial

// ============================================
// FUNCIONES DE FECHA LOCAL (ARREGLADO)
// ============================================

function getLocalDateString(date = new Date()) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function getLocalDateTime(date = new Date()) {
    return date.toLocaleTimeString('es-PE', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: false 
    });
}

function parseLocalDate(dateString) {
    // Recibe "YYYY-MM-DD" y devuelve fecha en hora local
    const [year, month, day] = dateString.split('-');
    return new Date(year, month - 1, day);
}

// ============================================
// DRAWER
// ============================================

function openDrawer() {
    document.getElementById('drawer').classList.add('open');
    document.getElementById('overlay').classList.add('open');
}

function closeDrawer() {
    document.getElementById('drawer').classList.remove('open');
    document.getElementById('overlay').classList.remove('open');
}

// ============================================
// NAVEGACIÓN
// ============================================

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
    if (page === 'historial') {
        // Si hay una fecha guardada, recargar el historial
        if (fechaHistorialActual) {
            buscarHistorialPorFecha(fechaHistorialActual);
        }
    }
    if (page === 'reportes') {
        setReporte('diario');
    }
}

// ============================================
// FECHA Y BADGE
// ============================================

function updateDateDisplay() {
    const now = new Date();
    const opts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const fechaElem = document.getElementById('drawer-fecha');
    if (fechaElem) fechaElem.textContent = now.toLocaleDateString('es-PE', opts);
    actualizarBadge();
}

async function actualizarBadge() {
    try {
        const hoy = getLocalDateString();
        const res = await fetch(`${API}?desde=${hoy}&hasta=${hoy}`);
        if (!res.ok) throw new Error('Error');
        const ventas = await res.json();
        
        if (!Array.isArray(ventas)) {
            document.getElementById('topbar-badge').innerHTML = `S/ 0.00`;
            return;
        }
        
        let total = 0;
        ventas.forEach(v => {
            if (v.estado === 'cancelado') {
                total += parseFloat(v.total);
            }
        });
        
        document.getElementById('topbar-badge').innerHTML = `💰 S/ ${total.toFixed(2)}`;
    } catch (error) {
        console.error('Error al actualizar badge:', error);
        document.getElementById('topbar-badge').innerHTML = `S/ 0.00`;
    }
}

// ============================================
// CALCULAR TOTAL
// ============================================

const kilosInput = document.getElementById('kilos');
if (kilosInput) {
    kilosInput.addEventListener('input', function() {
        const total = (parseFloat(this.value) || 0) * 4;
        const totalSpan = document.getElementById('totalMostrado');
        if (totalSpan) totalSpan.textContent = total.toFixed(2);
    });
}

// ============================================
// GUARDAR VENTA
// ============================================

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
            confirmButtonColor: '#3182ce'
        });
    }
    if (!kilos || kilos <= 0) {
        return Swal.fire({
            icon: 'warning',
            title: '¡Kilos inválidos!',
            text: 'Ingresa los kilos de ropa correctamente',
            confirmButtonColor: '#3182ce'
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
                confirmButtonColor: '#3182ce',
                timer: 2000,
                showConfirmButton: false
            });
            
            document.getElementById('nombre').value = '';
            document.getElementById('kilos').value = '';
            document.getElementById('totalMostrado').textContent = '0.00';
            document.getElementById('estado').value = 'pendiente';
            
            actualizarBadge();
            
            // Actualizar vistas si están activas
            if (document.getElementById('page-hoy').classList.contains('active')) {
                cargarVentasHoy();
            }
            if (document.getElementById('page-historial').classList.contains('active') && fechaHistorialActual) {
                buscarHistorialPorFecha(fechaHistorialActual);
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
            confirmButtonColor: '#3182ce'
        });
    }
}

// ============================================
// BUILD CARD HTML (CON HORA LOCAL FIX)
// ============================================

// ============================================
// BUILD CARD HTML (CON HORA LOCAL CORRECTA - SIN UTC)
// ============================================

function buildCard(v) {
    let badgeClass = v.estado === 'pendiente' ? 'badge-pending' : 'badge-paid';
    let badgeText = v.estado === 'pendiente' ? '⏳ Pendiente' : '✅ Pagado';
    
    const cardClass = v.estado === 'cancelado' ? 'pagado' : '';
    const nombreCompleto = v.nombre_cliente || v.nombreCliente || 'Cliente';
    const initials = nombreCompleto.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
    
    // ========== CORRECCIÓN DEFINITIVA: Extraer hora directamente del string ==========
    let hora = '--:--';
    
    if (v.fecha_registro) {
        try {
            // La fecha viene como "2026-04-26 13:15:00" desde MySQL
            // Extraemos SOLO la hora sin conversión UTC
            if (typeof v.fecha_registro === 'string' && v.fecha_registro.includes(' ')) {
                const partes = v.fecha_registro.split(' ');
                if (partes.length >= 2) {
                    const horaParte = partes[1]; // "13:15:00"
                    const horaMinutos = horaParte.split(':');
                    if (horaMinutos.length >= 2) {
                        hora = `${horaMinutos[0]}:${horaMinutos[1]}`; // "13:15"
                    }
                }
            }
        } catch(e) {
            console.error('Error parseando hora:', e);
            hora = '--:--';
        }
    }
    
    const idVenta = v.id_venta || v.idVenta;
    
    const payBtn = v.estado === 'pendiente'
        ? `<button class="btn btn-pay" onclick="pagarVenta(${idVenta}, '${nombreCompleto.replace(/'/g, "\\'")}')">
            <i class="fas fa-money-bill-wave"></i> Marcar pagado
           </button>`
        : '';
    
    return `
        <div class="venta-card ${cardClass}" data-id="${idVenta}">
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
// ============================================
// CARGAR VENTAS DE HOY (FIX: FILTRAR POR FECHA LOCAL)
// ============================================

async function cargarVentasHoy() {
    const listaVentas = document.getElementById('listaVentas');
    if (!listaVentas) return;
    
    listaVentas.innerHTML = '<div class="loader"><div class="spinner"></div> Cargando ventas…</div>';
    
    try {
        const hoy = getLocalDateString();
        const res = await fetch(`${API}?desde=${hoy}&hasta=${hoy}&t=${Date.now()}`);
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
        
        const ventas = await res.json();
        
        if (!Array.isArray(ventas)) {
            listaVentas.innerHTML = `<div class="empty"><div class="empty-icon"><i class="fas fa-exclamation-triangle"></i></div><p>Error: Respuesta inválida del servidor</p></div>`;
            return;
        }
        
        ventasHoyOriginal = ventas;
        
        let total = 0, kilos = 0;
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
            listaVentas.innerHTML = `<div class="empty"><div class="empty-icon"><i class="fas fa-soap"></i></div><p>Sin ventas por hoy</p><small>Toca el <strong>+</strong> para registrar la primera del día</small></div>`;
            return;
        }
        
        listaVentas.innerHTML = `<div class="ventas-list">${ventas.map(buildCard).join('')}</div>`;
        
        const searchInput = document.getElementById('searchHoy');
        if (searchInput) searchInput.value = '';
        const clearBtn = document.getElementById('clearSearchHoy');
        if (clearBtn) clearBtn.style.display = 'none';
        
    } catch (error) {
        console.error('Error en cargarVentasHoy:', error);
        listaVentas.innerHTML = `<div class="empty"><div class="empty-icon"><i class="fas fa-plug"></i></div><p>Sin conexión al servidor</p><small>Error: ${error.message}</small></div>`;
    }
}

// ============================================
// BÚSQUEDA EN VENTAS DE HOY
// ============================================

function buscarEnVentasHoy(termino) {
    const searchTerm = termino.toLowerCase().trim();
    const clearBtn = document.getElementById('clearSearchHoy');
    
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
        listaVentas.innerHTML = `<div class="empty"><div class="empty-icon"><i class="fas fa-search"></i></div><p>No se encontraron resultados</p><small>Prueba con otro nombre</small></div>`;
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

// ============================================
// PAGAR VENTA (CON ACTUALIZACIÓN AUTOMÁTICA)
// ============================================

async function pagarVenta(id, nombre) {
    const result = await Swal.fire({
        title: '¿Confirmar pago?',
        html: `Marcar venta de <b>${nombre}</b> como pagada`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3182ce',
        cancelButtonColor: '#e53e3e',
        confirmButtonText: 'Sí, cobrado ✓',
        cancelButtonText: 'Cancelar'
    });
    
    if (result.isConfirmed) {
        try {
            const response = await fetch(`${API}/${id}/pagar`, { method: 'PUT' });
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({ icon: 'success', title: '¡Pagado!', timer: 1500, showConfirmButton: false });
                
                // Actualizar todas las vistas activas
                cargarVentasHoy();
                actualizarBadge();
                
                // Si el historial está abierto, recargarlo también
                if (document.getElementById('page-historial').classList.contains('active') && fechaHistorialActual) {
                    await buscarHistorialPorFecha(fechaHistorialActual);
                }
                
                // Si la página de reportes está activa, actualizar vista previa
                if (document.getElementById('page-reportes').classList.contains('active')) {
                    generarVistaPrevia();
                }
            } else {
                throw new Error(data.error || 'Error al pagar');
            }
        } catch (error) {
            console.error('Error al pagar:', error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo marcar como pagado', confirmButtonColor: '#3182ce' });
        }
    }
}

// ============================================
// BUSCAR HISTORIAL (MEJORADO)
// ============================================

async function buscarHistorial() {
    const fechaDia = document.getElementById('fechaDia');
    if (!fechaDia || !fechaDia.value) {
        return Swal.fire({ icon: 'info', title: 'Elige una fecha', text: 'Selecciona una fecha para ver el historial', confirmButtonColor: '#3182ce' });
    }
    
    const fecha = fechaDia.value;
    fechaHistorialActual = fecha;
    await buscarHistorialPorFecha(fecha);
}

async function buscarHistorialPorFecha(fecha) {
    const listaHistorial = document.getElementById('listaHistorial');
    const histSummary = document.getElementById('histSummary');
    
    if (listaHistorial) listaHistorial.innerHTML = '<div class="loader"><div class="spinner"></div> Buscando…</div>';
    if (histSummary) histSummary.style.display = 'none';
    
    try {
        const res = await fetch(`${API}?desde=${fecha}&hasta=${fecha}&t=${Date.now()}`);
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
        
        const ventas = await res.json();
        
        if (!Array.isArray(ventas)) {
            if (listaHistorial) listaHistorial.innerHTML = `<div class="empty"><div class="empty-icon"><i class="fas fa-exclamation-triangle"></i></div><p>Error en la respuesta del servidor</p></div>`;
            return;
        }
        
        ventasHistorialOriginal = ventas;
        actualizarResumenHistorial(ventas);
        
        if (!ventas.length && listaHistorial) {
            listaHistorial.innerHTML = `<div class="empty"><div class="empty-icon"><i class="fas fa-calendar-xmark"></i></div><p>Sin ventas en esa fecha</p><small>Prueba con otra fecha</small></div>`;
            return;
        }
        
        if (listaHistorial) listaHistorial.innerHTML = `<div class="ventas-list">${ventas.map(buildCard).join('')}</div>`;
        
        const searchInput = document.getElementById('searchHistorial');
        if (searchInput) searchInput.value = '';
        const clearBtn = document.getElementById('clearSearchHistorial');
        if (clearBtn) clearBtn.style.display = 'none';
        
    } catch (error) {
        console.error('Error en buscarHistorial:', error);
        if (listaHistorial) listaHistorial.innerHTML = `<div class="empty"><div class="empty-icon"><i class="fas fa-plug"></i></div><p>Error al buscar</p><small>Verifica la conexión con el servidor</small></div>`;
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
        listaHistorial.innerHTML = `<div class="empty"><div class="empty-icon"><i class="fas fa-search"></i></div><p>No se encontraron resultados</p><small>Prueba con otro nombre</small></div>`;
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

// ============================================
// FECHAS RÁPIDAS (FIX: USAR FECHA LOCAL)
// ============================================

function setQuick(opt) {
    const fechaDia = document.getElementById('fechaDia');
    if (!fechaDia) return;
    
    const today = new Date();
    const y = new Date(today);
    const a = new Date(today);
    
    y.setDate(today.getDate() - 1);
    a.setDate(today.getDate() - 2);
    
    if (opt === 'hoy') {
        fechaDia.value = getLocalDateString(today);
    } else if (opt === 'ayer') {
        fechaDia.value = getLocalDateString(y);
    } else if (opt === 'antier') {
        fechaDia.value = getLocalDateString(a);
    }
    
    buscarHistorial();
}

// ============================================
// REPORTES
// ============================================

function setReporte(tipo) {
    reporteActual = tipo;
    
    document.querySelectorAll('.btn-report').forEach(btn => btn.classList.remove('active'));
    if (event && event.target) event.target.classList.add('active');
    
    const selectorDiv = document.getElementById('selectorFecha');
    if (tipo === 'diario') {
        selectorDiv.innerHTML = '<input type="date" id="fechaReporte" class="fecha-input">';
    } else if (tipo === 'semanal') {
        selectorDiv.innerHTML = '<input type="week" id="fechaReporte" class="fecha-input">';
    } else {
        selectorDiv.innerHTML = '<input type="month" id="fechaReporte" class="fecha-input">';
    }
    
    const fechaReporte = document.getElementById('fechaReporte');
    if (fechaReporte) fechaReporte.value = getLocalDateString();
    
    generarVistaPrevia();
}

async function generarVistaPrevia() {
    const fechaValue = document.getElementById('fechaReporte')?.value;
    if (!fechaValue) return;
    
    try {
        let url = `${API}?`;
        if (reporteActual === 'diario') {
            url += `desde=${fechaValue}&hasta=${fechaValue}`;
        } else if (reporteActual === 'semanal') {
            const [year, week] = fechaValue.split('-W');
            const startDate = new Date(year, 0, 1 + (week - 1) * 7);
            const endDate = new Date(year, 0, 7 + (week - 1) * 7);
            url += `desde=${getLocalDateString(startDate)}&hasta=${getLocalDateString(endDate)}`;
        } else {
            const [year, month] = fechaValue.split('-');
            const startDate = `${year}-${month}-01`;
            const lastDay = new Date(year, month, 0).getDate();
            const endDate = `${year}-${month}-${lastDay}`;
            url += `desde=${startDate}&hasta=${endDate}`;
        }
        
        const res = await fetch(url);
        const ventas = await res.json();
        
        const previewDiv = document.getElementById('reportPreview');
        if (!ventas || !ventas.length) {
            previewDiv.innerHTML = `<div class="empty-report"><i class="fas fa-chart-simple"></i><p>No hay ventas en este período</p></div>`;
            return;
        }
        
        const totalVentas = ventas.length;
        const totalKilos = ventas.reduce((s, v) => s + parseFloat(v.kilos), 0);
        const totalIngresos = ventas.reduce((s, v) => s + parseFloat(v.total), 0);
        const pendientes = ventas.filter(v => v.estado === 'pendiente').length;
        
        previewDiv.innerHTML = `
            <div style="margin-bottom: 16px">
                <div style="font-weight: 700; margin-bottom: 8px">📊 Resumen ${reporteActual}</div>
                <div>📦 Ventas: <strong>${totalVentas}</strong></div>
                <div>⚖️ Kilos: <strong>${totalKilos.toFixed(1)} kg</strong></div>
                <div>💰 Ingresos: <strong>S/ ${totalIngresos.toFixed(2)}</strong></div>
                <div>⏳ Pendientes: <strong>${pendientes}</strong></div>
            </div>
            <hr style="margin: 12px 0">
            <div style="font-size: 13px; max-height: 200px; overflow-y: auto">
                ${ventas.slice(0, 10).map(v => `<div>• ${v.fecha_registro?.split('T')[0] || v.fecha} | ${v.nombre_cliente} | S/ ${parseFloat(v.total).toFixed(2)}</div>`).join('')}
                ${ventas.length > 10 ? `<div style="color: gray; margin-top: 8px">... y ${ventas.length - 10} más</div>` : ''}
            </div>
        `;
    } catch (error) {
        console.error('Error:', error);
    }
}

async function descargarReporte() {
    const fechaValue = document.getElementById('fechaReporte')?.value;
    if (!fechaValue) {
        Swal.fire('Error', 'Selecciona una fecha', 'error');
        return;
    }
    
    try {
        let url = `${API}?`;
        let periodo = '';
        
        if (reporteActual === 'diario') {
            url += `desde=${fechaValue}&hasta=${fechaValue}`;
            periodo = fechaValue;
        } else if (reporteActual === 'semanal') {
            const [year, week] = fechaValue.split('-W');
            const startDate = new Date(year, 0, 1 + (week - 1) * 7);
            const endDate = new Date(year, 0, 7 + (week - 1) * 7);
            url += `desde=${getLocalDateString(startDate)}&hasta=${getLocalDateString(endDate)}`;
            periodo = `Semana ${week} de ${year}`;
        } else {
            periodo = fechaValue;
            const [year, month] = fechaValue.split('-');
            const startDate = `${year}-${month}-01`;
            const lastDay = new Date(year, month, 0).getDate();
            const endDate = `${year}-${month}-${lastDay}`;
            url += `desde=${startDate}&hasta=${endDate}`;
        }
        
        const res = await fetch(url);
        const ventas = await res.json();
        
        if (!ventas || !ventas.length) {
            Swal.fire('Sin datos', 'No hay ventas en este período', 'info');
            return;
        }
        
        const totalVentas = ventas.length;
        const totalKilos = ventas.reduce((s, v) => s + parseFloat(v.kilos), 0);
        const totalIngresos = ventas.reduce((s, v) => s + parseFloat(v.total), 0);
        const pendientes = ventas.filter(v => v.estado === 'pendiente').length;
        
        let contenido = `LAVANDERÍA - REPORTE ${reporteActual.toUpperCase()}\n`;
        contenido += `Período: ${periodo}\n`;
        contenido += `Fecha generación: ${new Date().toLocaleString('es-PE')}\n`;
        contenido += `Precio por kilo: S/ 4.00\n`;
        contenido += `================================\n`;
        contenido += `Total ventas: ${totalVentas}\n`;
        contenido += `Total kilos: ${totalKilos.toFixed(1)} kg\n`;
        contenido += `Ingresos totales: S/ ${totalIngresos.toFixed(2)}\n`;
        contenido += `Pedidos pendientes: ${pendientes}\n`;
        contenido += `Pedidos pagados: ${totalVentas - pendientes}\n`;
        contenido += `================================\n\n`;
        contenido += `DETALLE DE VENTAS:\n`;
        
        ventas.forEach(v => {
            const fechaVenta = v.fecha_registro ? v.fecha_registro.split('T')[0] : v.fecha;
            contenido += `- ${fechaVenta} | ${v.nombre_cliente} | ${v.kilos} kg | S/ ${parseFloat(v.total).toFixed(2)} | ${v.estado === 'pendiente' ? 'PENDIENTE' : 'PAGADO'}\n`;
        });
        
        const blob = new Blob([contenido], { type: 'text/plain;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `reporte_${reporteActual}_${getLocalDateString()}.txt`;
        link.click();
        URL.revokeObjectURL(link.href);
        
        Swal.fire('¡Descargado!', 'Reporte generado correctamente', 'success');
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', 'No se pudo generar el reporte', 'error');
    }
}

// ============================================
// EVENT LISTENERS Y INICIALIZACIÓN
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    updateDateDisplay();
    
    const fechaDia = document.getElementById('fechaDia');
    if (fechaDia) {
        fechaDia.value = getLocalDateString();
    }
    
    // Actualizar ventas de hoy cada 15 segundos
    setInterval(() => {
        if (document.getElementById('page-hoy').classList.contains('active')) {
            cargarVentasHoy();
        }
        if (document.getElementById('page-historial').classList.contains('active') && fechaHistorialActual) {
            buscarHistorialPorFecha(fechaHistorialActual);
        }
    }, 15000);
    
    setInterval(actualizarBadge, 30000);
    cargarVentasHoy();
    
    const searchHoy = document.getElementById('searchHoy');
    if (searchHoy) searchHoy.addEventListener('input', (e) => buscarEnVentasHoy(e.target.value));
    
    const searchHistorial = document.getElementById('searchHistorial');
    if (searchHistorial) searchHistorial.addEventListener('input', (e) => buscarEnHistorial(e.target.value));
    
    const clearSearchHoy = document.getElementById('clearSearchHoy');
    if (clearSearchHoy) clearSearchHoy.addEventListener('click', () => resetSearchHoy());
    
    const clearSearchHistorial = document.getElementById('clearSearchHistorial');
    if (clearSearchHistorial) clearSearchHistorial.addEventListener('click', () => resetSearchHistorial());
    
    setReporte('diario');
});

// Función para depuración (opcional)
console.log('LavaSoft - Sistema cargado correctamente');

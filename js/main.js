// ============================================
// CONFIGURACIÓN - CAMBIAR CUANDO SUBAS A RENDER
// ============================================

// PARA PRODUCCIÓN en RENDER:
const API = window.location.origin + "/api/ventas.php";

// PARA PRUEBA LOCAL (descomenta esta y comenta la de arriba cuando estés en local)
// const API = "http://localhost/SISTEMA_DE_VENTAS/api/ventas.php";

// Variables globales
let ventasHoyOriginal = [];
let ventasHistorialOriginal = [];
let reporteActual = 'diario';
let fechaHistorialActual = '';

// ============================================
// FUNCIONES DE FECHA LOCAL
// ============================================

function getLocalDateString(date = new Date()) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// ============================================
// DRAWER
// ============================================

function openDrawer() {
    const drawer = document.getElementById('drawer');
    const overlay = document.getElementById('overlay');
    if (drawer) drawer.classList.add('open');
    if (overlay) overlay.classList.add('open');
}

function closeDrawer() {
    const drawer = document.getElementById('drawer');
    const overlay = document.getElementById('overlay');
    if (drawer) drawer.classList.remove('open');
    if (overlay) overlay.classList.remove('open');
}

// ============================================
// NAVEGACIÓN
// ============================================

function goTo(page) {
    closeDrawer();
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.drawer-nav-item').forEach(n => n.classList.remove('active'));
    
    const pageElem = document.getElementById('page-' + page);
    const navElem = document.getElementById('nav-' + page);
    if (pageElem) pageElem.classList.add('active');
    if (navElem) navElem.classList.add('active');
    
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
    if (page === 'historial' && fechaHistorialActual) {
        buscarHistorialPorFecha(fechaHistorialActual);
    }
    if (page === 'reportes') {
        setReporte('diario');
    }
}

// ============================================
// ACTUALIZAR BADGE
// ============================================

async function actualizarBadge() {
    try {
        const hoy = getLocalDateString();
        const res = await fetch(`${API}?desde=${hoy}&hasta=${hoy}&t=${Date.now()}`);
        const ventas = await res.json();
        
        let total = 0;
        if (Array.isArray(ventas)) {
            ventas.forEach(v => {
                if (v.estado === 'cancelado') {
                    total += parseFloat(v.total);
                }
            });
        }
        const badge = document.getElementById('topbar-badge');
        if (badge) badge.innerHTML = `💰 S/ ${total.toFixed(2)}`;
    } catch (error) {
        console.error('Error:', error);
        const badge = document.getElementById('topbar-badge');
        if (badge) badge.innerHTML = `S/ 0.00`;
    }
}

// ============================================
// GUARDAR VENTA
// ============================================

async function guardarVenta() {
    const nombre = document.getElementById('nombre').value.trim();
    const kilos = parseFloat(document.getElementById('kilos').value);
    let estado = document.getElementById('estado').value;
    
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
            
            const nombreInput = document.getElementById('nombre');
            const kilosInput = document.getElementById('kilos');
            const totalSpan = document.getElementById('totalMostrado');
            const estadoSelect = document.getElementById('estado');
            
            if (nombreInput) nombreInput.value = '';
            if (kilosInput) kilosInput.value = '';
            if (totalSpan) totalSpan.textContent = '0.00';
            if (estadoSelect) estadoSelect.value = 'pendiente';
            
            actualizarBadge();
            
            const pageHoy = document.getElementById('page-hoy');
            if (pageHoy && pageHoy.classList.contains('active')) {
                cargarVentasHoy();
            }
            const pageHistorial = document.getElementById('page-historial');
            if (pageHistorial && pageHistorial.classList.contains('active') && fechaHistorialActual) {
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
            text: 'No se pudo guardar la venta.', 
            confirmButtonColor: '#3182ce' 
        });
    }
}

// ============================================
// BUILD CARD (CORREGIDO - para Supabase)
// ============================================

function buildCard(v) {
    let badgeClass = v.estado === 'pendiente' ? 'badge-pending' : 'badge-paid';
    let badgeText = v.estado === 'pendiente' ? '⏳ Pendiente' : '✅ Pagado';
    
    const cardClass = v.estado === 'cancelado' ? 'pagado' : '';
    const nombreCompleto = v.nombre_cliente || v.nombreCliente || 'Cliente';
    const initials = nombreCompleto.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
    
    let hora = '--:--';
    if (v.fecha_registro) {
        try {
            if (v.fecha_registro.includes('T')) {
                const horaParte = v.fecha_registro.split('T')[1];
                if (horaParte) {
                    const hm = horaParte.split(':');
                    if (hm.length >= 2) {
                        hora = `${hm[0]}:${hm[1]}`;
                    }
                }
            } 
            else if (v.fecha_registro.includes(' ')) {
                const horaParte = v.fecha_registro.split(' ')[1];
                if (horaParte) {
                    const hm = horaParte.split(':');
                    if (hm.length >= 2) {
                        hora = `${hm[0]}:${hm[1]}`;
                    }
                }
            }
        } catch(e) { 
            console.log('Error al parsear hora:', e);
        }
    }
    
    const idVenta = v.id_venta;
    
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
// CARGAR VENTAS DE HOY
// ============================================

async function cargarVentasHoy() {
    const listaVentas = document.getElementById('listaVentas');
    if (!listaVentas) return;
    
    listaVentas.innerHTML = '<div class="loader"><div class="spinner"></div> Cargando ventas…</div>';
    
    try {
        const hoy = getLocalDateString();
        const res = await fetch(`${API}?desde=${hoy}&hasta=${hoy}&t=${Date.now()}`);
        const ventas = await res.json();
        
        console.log('Ventas recibidas:', ventas);
        
        if (!Array.isArray(ventas)) {
            listaVentas.innerHTML = `<div class="empty"><div class="empty-icon"><i class="fas fa-exclamation-triangle"></i></div><p>Error: Respuesta inválida</p><small>${JSON.stringify(ventas)}</small></div>`;
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
            listaVentas.innerHTML = `<div class="empty"><div class="empty-icon"><i class="fas fa-soap"></i></div><p>Sin ventas por hoy</p><small>Toca el <strong>+</strong> para registrar la primera</small></div>`;
            return;
        }
        
        listaVentas.innerHTML = `<div class="ventas-list">${ventas.map(buildCard).join('')}</div>`;
        
        const searchHoy = document.getElementById('searchHoy');
        if (searchHoy) searchHoy.value = '';
        const clearBtn = document.getElementById('clearSearchHoy');
        if (clearBtn) clearBtn.style.display = 'none';
        
    } catch (error) {
        console.error('Error:', error);
        listaVentas.innerHTML = `<div class="empty"><div class="empty-icon"><i class="fas fa-plug"></i></div><p>Sin conexión al servidor</p><small>${error.message}</small></div>`;
    }
}

// ============================================
// BUSCAR HISTORIAL
// ============================================

async function buscarHistorial() {
    const fechaDia = document.getElementById('fechaDia');
    if (!fechaDia || !fechaDia.value) {
        return Swal.fire({ 
            icon: 'info', 
            title: 'Elige una fecha', 
            text: 'Selecciona una fecha para ver el historial', 
            confirmButtonColor: '#3182ce' 
        });
    }
    
    fechaHistorialActual = fechaDia.value;
    await buscarHistorialPorFecha(fechaHistorialActual);
}

async function buscarHistorialPorFecha(fecha) {
    const listaHistorial = document.getElementById('listaHistorial');
    const histSummary = document.getElementById('histSummary');
    
    if (listaHistorial) listaHistorial.innerHTML = '<div class="loader"><div class="spinner"></div> Buscando…</div>';
    if (histSummary) histSummary.style.display = 'none';
    
    try {
        const res = await fetch(`${API}?desde=${fecha}&hasta=${fecha}&t=${Date.now()}`);
        const ventas = await res.json();
        
        console.log('Historial recibido:', ventas);
        
        if (!Array.isArray(ventas)) {
            if (listaHistorial) listaHistorial.innerHTML = `<div class="empty"><div class="empty-icon"><i class="fas fa-exclamation-triangle"></i></div><p>Error en la respuesta</p></div>`;
            return;
        }
        
        ventasHistorialOriginal = ventas;
        
        const totalV = ventas.length;
        const totalK = ventas.reduce((a, v) => a + parseFloat(v.kilos), 0);
        const totalI = ventas.reduce((a, v) => a + parseFloat(v.total), 0);
        const pagados = ventas.filter(v => v.estado === 'cancelado').length;
        
        const hTotalVentas = document.getElementById('hTotalVentas');
        const hTotalKilos = document.getElementById('hTotalKilos');
        const hTotalIngreso = document.getElementById('hTotalIngreso');
        const hPagados = document.getElementById('hPagados');
        
        if (hTotalVentas) hTotalVentas.textContent = totalV;
        if (hTotalKilos) hTotalKilos.textContent = totalK.toFixed(1);
        if (hTotalIngreso) hTotalIngreso.textContent = 'S/ ' + totalI.toFixed(2);
        if (hPagados) hPagados.textContent = pagados;
        if (histSummary) histSummary.style.display = 'grid';
        
        if (!ventas.length) {
            if (listaHistorial) listaHistorial.innerHTML = `<div class="empty"><div class="empty-icon"><i class="fas fa-calendar-xmark"></i></div><p>Sin ventas en esa fecha</p></div>`;
            return;
        }
        
        if (listaHistorial) listaHistorial.innerHTML = `<div class="ventas-list">${ventas.map(buildCard).join('')}</div>`;
        
        const searchHistorial = document.getElementById('searchHistorial');
        if (searchHistorial) searchHistorial.value = '';
        const clearBtn = document.getElementById('clearSearchHistorial');
        if (clearBtn) clearBtn.style.display = 'none';
        
    } catch (error) {
        console.error('Error:', error);
        if (listaHistorial) listaHistorial.innerHTML = `<div class="empty"><div class="empty-icon"><i class="fas fa-plug"></i></div><p>Error al buscar</p></div>`;
    }
}

// ============================================
// PAGAR VENTA - CORREGIDA
// ============================================

async function pagarVenta(id, nombre) {
    console.log('Intentando pagar venta - ID:', id, 'Cliente:', nombre);
    
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
            const url = `${API}/${id}/pagar`;
            console.log('URL de la petición:', url);
            
            const response = await fetch(url, { 
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            console.log('Respuesta HTTP status:', response.status);
            const data = await response.json();
            console.log('Respuesta del servidor:', data);
            
            if (data.success) {
                Swal.fire({ 
                    icon: 'success', 
                    title: '¡Pagado!', 
                    text: `Venta de ${nombre} marcada como pagada`,
                    timer: 1500, 
                    showConfirmButton: false 
                });
                
                await cargarVentasHoy();
                await actualizarBadge();
                
                const pageHistorial = document.getElementById('page-historial');
                if (pageHistorial && pageHistorial.classList.contains('active') && fechaHistorialActual) {
                    await buscarHistorialPorFecha(fechaHistorialActual);
                }
                
                const pageReportes = document.getElementById('page-reportes');
                if (pageReportes && pageReportes.classList.contains('active')) {
                    await generarVistaPrevia();
                }
            } else {
                throw new Error(data.error || 'Error al pagar');
            }
        } catch (error) {
            console.error('Error detallado:', error);
            Swal.fire({ 
                icon: 'error', 
                title: 'Error', 
                text: `No se pudo marcar como pagado: ${error.message}`, 
                confirmButtonColor: '#3182ce' 
            });
        }
    }
}

// ============================================
// FECHAS RÁPIDAS
// ============================================

function setQuick(opt) {
    const fechaDia = document.getElementById('fechaDia');
    if (!fechaDia) return;
    
    const today = new Date();
    const y = new Date(today);
    const a = new Date(today);
    y.setDate(today.getDate() - 1);
    a.setDate(today.getDate() - 2);
    
    if (opt === 'hoy') fechaDia.value = getLocalDateString(today);
    else if (opt === 'ayer') fechaDia.value = getLocalDateString(y);
    else if (opt === 'antier') fechaDia.value = getLocalDateString(a);
    
    buscarHistorial();
}

// ============================================
// REPORTES
// ============================================

function setReporte(tipo) {
    reporteActual = tipo;
    
    const botonesReporte = document.querySelectorAll('.btn-report');
    if (botonesReporte.length > 0) {
        botonesReporte.forEach(btn => btn.classList.remove('active'));
    }
    
    setTimeout(function() {
        const botones = document.querySelectorAll('.btn-report');
        for (let i = 0; i < botones.length; i++) {
            const btn = botones[i];
            const btnText = btn.textContent;
            if ((tipo === 'diario' && btnText.includes('📅')) ||
                (tipo === 'semanal' && btnText.includes('📆')) ||
                (tipo === 'mensual' && btnText.includes('📊'))) {
                btn.classList.add('active');
                break;
            }
        }
    }, 100);
    
    const selectorDiv = document.getElementById('selectorFecha');
    if (selectorDiv) {
        if (tipo === 'diario') selectorDiv.innerHTML = '<input type="date" id="fechaReporte" class="fecha-input">';
        else if (tipo === 'semanal') selectorDiv.innerHTML = '<input type="week" id="fechaReporte" class="fecha-input">';
        else selectorDiv.innerHTML = '<input type="month" id="fechaReporte" class="fecha-input">';
        
        const fechaReporte = document.getElementById('fechaReporte');
        if (fechaReporte) fechaReporte.value = getLocalDateString();
    }
    
    generarVistaPrevia();
}

async function generarVistaPrevia() {
    const fechaReporte = document.getElementById('fechaReporte');
    const fechaValue = fechaReporte?.value;
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
            const lastDay = new Date(year, month, 0).getDate();
            url += `desde=${year}-${month}-01&hasta=${year}-${month}-${lastDay}`;
        }
        
        const res = await fetch(url + `&t=${Date.now()}`);
        const ventas = await res.json();
        
        const previewDiv = document.getElementById('reportPreview');
        if (!previewDiv) return;
        
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
                ${ventas.slice(0, 10).map(v => `<div>• ${(v.fecha_registro || '').split('T')[0] || v.fecha} | ${v.nombre_cliente} | S/ ${parseFloat(v.total).toFixed(2)}</div>`).join('')}
                ${ventas.length > 10 ? `<div style="color: gray; margin-top: 8px">... y ${ventas.length - 10} más</div>` : ''}
            </div>
        `;
    } catch (error) {
        console.error('Error:', error);
    }
}

async function descargarReporte() {
    const fechaReporte = document.getElementById('fechaReporte');
    const fechaValue = fechaReporte?.value;
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
            const lastDay = new Date(year, month, 0).getDate();
            url += `desde=${year}-${month}-01&hasta=${year}-${month}-${lastDay}`;
        }
        
        const res = await fetch(url + `&t=${Date.now()}`);
        const ventas = await res.json();
        
        if (!ventas || !ventas.length) {
            Swal.fire('Sin datos', 'No hay ventas en este período', 'info');
            return;
        }
        
        let contenido = `LAVANDERÍA - REPORTE ${reporteActual.toUpperCase()}\n`;
        contenido += `Período: ${periodo}\n`;
        contenido += `Fecha generación: ${new Date().toLocaleString('es-PE')}\n`;
        contenido += `Precio por kilo: S/ 4.00\n`;
        contenido += `================================\n`;
        contenido += `Total ventas: ${ventas.length}\n`;
        contenido += `Total kilos: ${ventas.reduce((s, v) => s + parseFloat(v.kilos), 0).toFixed(1)} kg\n`;
        contenido += `Ingresos totales: S/ ${ventas.reduce((s, v) => s + parseFloat(v.total), 0).toFixed(2)}\n`;
        contenido += `================================\n\nDETALLE:\n`;
        
        ventas.forEach(v => {
            contenido += `- ${(v.fecha_registro || '').split('T')[0]} | ${v.nombre_cliente} | ${v.kilos} kg | S/ ${parseFloat(v.total).toFixed(2)} | ${v.estado === 'pendiente' ? 'PENDIENTE' : 'PAGADO'}\n`;
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
// BUSCADORES
// ============================================

function buscarEnVentasHoy(termino) {
    const searchTerm = termino.toLowerCase().trim();
    const resultados = ventasHoyOriginal.filter(v => (v.nombre_cliente || v.nombreCliente).toLowerCase().includes(searchTerm));
    const listaVentas = document.getElementById('listaVentas');
    const clearBtn = document.getElementById('clearSearchHoy');
    
    if (!listaVentas) return;
    
    if (!resultados.length) {
        listaVentas.innerHTML = `<div class="empty"><div class="empty-icon"><i class="fas fa-search"></i></div><p>No se encontraron resultados</p></div>`;
        if (clearBtn) clearBtn.style.display = searchTerm ? 'flex' : 'none';
        return;
    }
    listaVentas.innerHTML = `<div class="ventas-list">${resultados.map(buildCard).join('')}</div>`;
    if (clearBtn) clearBtn.style.display = searchTerm ? 'flex' : 'none';
}

function buscarEnHistorial(termino) {
    const searchTerm = termino.toLowerCase().trim();
    const resultados = ventasHistorialOriginal.filter(v => (v.nombre_cliente || v.nombreCliente).toLowerCase().includes(searchTerm));
    const listaHistorial = document.getElementById('listaHistorial');
    const clearBtn = document.getElementById('clearSearchHistorial');
    
    if (!listaHistorial) return;
    
    if (!resultados.length) {
        listaHistorial.innerHTML = `<div class="empty"><div class="empty-icon"><i class="fas fa-search"></i></div><p>No se encontraron resultados</p></div>`;
        if (clearBtn) clearBtn.style.display = searchTerm ? 'flex' : 'none';
        return;
    }
    listaHistorial.innerHTML = `<div class="ventas-list">${resultados.map(buildCard).join('')}</div>`;
    actualizarResumenHistorial(resultados);
    if (clearBtn) clearBtn.style.display = searchTerm ? 'flex' : 'none';
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
    
    if (hTotalVentas) hTotalVentas.textContent = totalV;
    if (hTotalKilos) hTotalKilos.textContent = totalK.toFixed(1);
    if (hTotalIngreso) hTotalIngreso.textContent = 'S/ ' + totalI.toFixed(2);
    if (hPagados) hPagados.textContent = pagados;
}

function resetSearchHoy() {
    const searchHoy = document.getElementById('searchHoy');
    if (searchHoy) searchHoy.value = '';
    mostrarVentasHoy(ventasHoyOriginal);
}

function resetSearchHistorial() {
    const searchHistorial = document.getElementById('searchHistorial');
    if (searchHistorial) searchHistorial.value = '';
    mostrarHistorial(ventasHistorialOriginal);
}

function mostrarVentasHoy(ventas) {
    const listaVentas = document.getElementById('listaVentas');
    if (!listaVentas) return;
    
    if (!ventas.length) {
        listaVentas.innerHTML = `<div class="empty"><div class="empty-icon"><i class="fas fa-soap"></i></div><p>Sin ventas por hoy</p></div>`;
        return;
    }
    listaVentas.innerHTML = `<div class="ventas-list">${ventas.map(buildCard).join('')}</div>`;
}

function mostrarHistorial(ventas) {
    const listaHistorial = document.getElementById('listaHistorial');
    if (!listaHistorial) return;
    
    if (!ventas.length) {
        listaHistorial.innerHTML = `<div class="empty"><div class="empty-icon"><i class="fas fa-search"></i></div><p>No se encontraron resultados</p></div>`;
        return;
    }
    listaHistorial.innerHTML = `<div class="ventas-list">${ventas.map(buildCard).join('')}</div>`;
}

// ============================================
// CALCULAR TOTAL AUTOMÁTICAMENTE
// ============================================

function initCalculoTotal() {
    const kilosInput = document.getElementById('kilos');
    const totalSpan = document.getElementById('totalMostrado');
    
    if (kilosInput && totalSpan) {
        const newKilosInput = kilosInput.cloneNode(true);
        kilosInput.parentNode.replaceChild(newKilosInput, kilosInput);
        
        newKilosInput.addEventListener('input', function() {
            const kilos = parseFloat(this.value) || 0;
            const total = kilos * 4.00;
            totalSpan.textContent = total.toFixed(2);
        });
        
        document.getElementById('kilos');
    }
}

// ============================================
// INICIALIZACIÓN
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    const now = new Date();
    const opts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const fechaDrawer = document.getElementById('drawer-fecha');
    if (fechaDrawer) fechaDrawer.textContent = now.toLocaleDateString('es-PE', opts);
    
    const fechaDia = document.getElementById('fechaDia');
    if (fechaDia) fechaDia.value = getLocalDateString();
    
    actualizarBadge();
    
    setInterval(() => {
        const pageHoy = document.getElementById('page-hoy');
        if (pageHoy && pageHoy.classList.contains('active')) {
            cargarVentasHoy();
        }
        const pageHistorial = document.getElementById('page-historial');
        if (pageHistorial && pageHistorial.classList.contains('active') && fechaHistorialActual) {
            buscarHistorialPorFecha(fechaHistorialActual);
        }
    }, 15000);
    
    cargarVentasHoy();
    
    const searchHoy = document.getElementById('searchHoy');
    if (searchHoy) searchHoy.addEventListener('input', (e) => buscarEnVentasHoy(e.target.value));
    
    const searchHistorial = document.getElementById('searchHistorial');
    if (searchHistorial) searchHistorial.addEventListener('input', (e) => buscarEnHistorial(e.target.value));
    
    const clearSearchHoy = document.getElementById('clearSearchHoy');
    if (clearSearchHoy) clearSearchHoy.addEventListener('click', () => resetSearchHoy());
    
    const clearSearchHistorial = document.getElementById('clearSearchHistorial');
    if (clearSearchHistorial) clearSearchHistorial.addEventListener('click', () => resetSearchHistorial());
    
    setTimeout(function() {
        initCalculoTotal();
    }, 500);
    
    setReporte('diario');
});

console.log('LavaSoft - Sistema cargado correctamente');

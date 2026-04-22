<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#1a6b3a">
    <title>LavaSoft – Lavandería Sostenible</title>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,500;0,9..40,700;0,9..40,900;1,9..40,400&family=Fraunces:ital,wght@0,700;0,900;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
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

<!-- ══════════════════ PAGE: NUEVA VENTA ══════════════════ -->
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

<!-- ══════════════════ PAGE: VENTAS DE HOY ══════════════════ -->
<div class="page" id="page-hoy">
    <div style="padding-top:20px">
        <div class="section-title"><span class="dot"></span> Ventas de Hoy</div>


        <div class="stats-row" id="statsHoy">
            <div class="stat-card"><div class="stat-val">–</div><div class="stat-lbl">Pedidos</div></div>
            <div class="stat-card"><div class="stat-val">–</div><div class="stat-lbl">Kilos</div></div>
            <div class="stat-card highlight"><div class="stat-val">–</div><div class="stat-lbl">Total S/</div></div>
        </div>
           <!-- BUSCADOR PARA VENTAS DE HOY -->
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="searchHoy" placeholder="Buscar por nombre..." autocomplete="off">
            <button class="btn-clear" id="clearSearchHoy" style="display:none">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>

        <div id="listaVentas">
            <div class="loader"><div class="spinner"></div> Cargando ventas…</div>
        </div>
    </div>
</div>

<!-- ══════════════════ PAGE: HISTORIAL ══════════════════ -->
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

                <!-- BUSCADOR PARA HISTORIAL -->
        <div class="search-bar historial-search">
                <i class="fas fa-search"></i>
                <input type="text" id="searchHistorial" placeholder="Buscar por nombre en historial..." autocomplete="off">
                <button class="btn-clear" id="clearSearchHistorial" style="display:none">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>
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
<script src="js/main.js"></script>
</body>
</html>
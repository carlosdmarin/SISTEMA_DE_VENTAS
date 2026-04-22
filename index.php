<?php
// index.php - Archivo principal
// No debe tener espacios ni saltos de línea antes de <!DOCTYPE>
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Lavasoft - Lavandería</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- CSS Local - Ruta absoluta desde la raíz -->
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
</head>
<body>
    <!-- Overlay para drawer -->
    <div class="overlay" id="overlay" onclick="closeDrawer()"></div>
    
    <!-- Drawer / Menú lateral -->
    <div class="drawer" id="drawer">
        <div class="drawer-header">
            <div class="drawer-title">
                <i class="fas fa-soap"></i> 
                <span>Lavasoft</span>
            </div>
            <button class="drawer-close" onclick="closeDrawer()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="drawer-user">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-info">
                <div class="user-name">Administrador</div>
                <div class="user-role">Lavandería</div>
            </div>
        </div>
        
        <nav class="drawer-nav">
            <div class="drawer-nav-item active" id="nav-hoy" onclick="goTo('hoy')">
                <i class="fas fa-calendar-day"></i>
                <span>Ventas de Hoy</span>
            </div>
            <div class="drawer-nav-item" id="nav-ventas" onclick="goTo('ventas')">
                <i class="fas fa-plus-circle"></i>
                <span>Nueva Venta</span>
            </div>
            <div class="drawer-nav-item" id="nav-historial" onclick="goTo('historial')">
                <i class="fas fa-history"></i>
                <span>Historial</span>
            </div>
        </nav>
        
        <div class="drawer-footer">
            <div id="drawer-fecha"></div>
        </div>
    </div>
    
    <!-- Topbar -->
    <div class="topbar">
        <button class="menu-btn" onclick="openDrawer()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="topbar-title">Lavasoft</div>
        <div class="topbar-badge" id="topbar-badge">
            <i class="fas fa-soles"></i> S/ 0.00
        </div>
    </div>
    
    <!-- Contenido principal -->
    <div class="main-content">
        <!-- Página: Ventas de Hoy -->
        <div class="page active" id="page-hoy">
            <div class="page-header">
                <h2><i class="fas fa-calendar-day"></i> Ventas de Hoy</h2>
            </div>
            
            <!-- Buscador -->
            <div class="search-container">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchHoy" class="search-input" placeholder="Buscar por nombre...">
                    <button class="search-clear" id="clearSearchHoy" style="display: none;" onclick="resetSearchHoy()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="stats-grid" id="statsHoy">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-receipt"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Ventas</div>
                        <div class="stat-val">0</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-weight-hanging"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Kilos</div>
                        <div class="stat-val">0.0</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-soles"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Total</div>
                        <div class="stat-val">S/ 0.00</div>
                    </div>
                </div>
            </div>
            
            <!-- Lista de ventas -->
            <div id="listaVentas" class="lista-ventas-container">
                <div class="loader"><div class="spinner"></div> Cargando ventas...</div>
            </div>
        </div>
        
        <!-- Página: Nueva Venta -->
        <div class="page" id="page-ventas">
            <div class="page-header">
                <h2><i class="fas fa-plus-circle"></i> Registrar Venta</h2>
            </div>
            
            <div class="form-card">
                <div class="form-group">
                    <label for="nombre"><i class="fas fa-user"></i> Nombre del Cliente</label>
                    <input type="text" id="nombre" class="form-control" placeholder="Ej: Juan Pérez" autocomplete="off">
                </div>
                
                <div class="form-group">
                    <label for="kilos"><i class="fas fa-weight-hanging"></i> Kilos de Ropa</label>
                    <input type="number" id="kilos" class="form-control" placeholder="0.00" step="0.01" min="0">
                    <small class="form-hint">Precio: S/ 4.00 por kilo</small>
                </div>
                
                <div class="form-group">
                    <label for="totalMostrado"><i class="fas fa-soles"></i> Total a Pagar</label>
                    <div class="total-display" id="totalMostrado">0.00</div>
                </div>
                
                <div class="form-group">
                    <label for="estado"><i class="fas fa-tag"></i> Estado</label>
                    <select id="estado" class="form-control">
                        <option value="pendiente">⏳ Pendiente</option>
                        <option value="pagado">✅ Pagado</option>
                    </select>
                </div>
                
                <button class="btn btn-success btn-block" onclick="guardarVenta()">
                    <i class="fas fa-save"></i> Guardar Venta
                </button>
            </div>
        </div>
        
        <!-- Página: Historial -->
        <div class="page" id="page-historial">
            <div class="page-header">
                <h2><i class="fas fa-history"></i> Historial de Ventas</h2>
            </div>
            
            <!-- Selector de fecha -->
            <div class="fecha-selector">
                <div class="quick-dates">
                    <button class="quick-btn" onclick="setQuick('hoy')">Hoy</button>
                    <button class="quick-btn" onclick="setQuick('ayer')">Ayer</button>
                    <button class="quick-btn" onclick="setQuick('antier')">Antier</button>
                </div>
                <div class="fecha-input-wrapper">
                    <i class="fas fa-calendar"></i>
                    <input type="date" id="fechaDia" class="form-control">
                    <button class="btn btn-primary" onclick="buscarHistorial()">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </div>
            
            <!-- Buscador por nombre -->
            <div class="search-container">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchHistorial" class="search-input" placeholder="Filtrar por nombre...">
                    <button class="search-clear" id="clearSearchHistorial" style="display: none;" onclick="resetSearchHistorial()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Resumen -->
            <div class="stats-grid" id="histSummary" style="display: none;">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-receipt"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Ventas</div>
                        <div class="stat-val" id="hTotalVentas">0</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-weight-hanging"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Kilos</div>
                        <div class="stat-val" id="hTotalKilos">0.0</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-soles"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Ingreso</div>
                        <div class="stat-val" id="hTotalIngreso">S/ 0.00</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <div class="stat-label">Pagados</div>
                        <div class="stat-val" id="hPagados">0</div>
                    </div>
                </div>
            </div>
            
            <!-- Lista de historial -->
            <div id="listaHistorial" class="lista-ventas-container">
                <div class="empty">
                    <div class="empty-icon"><i class="fas fa-calendar"></i></div>
                    <p>Selecciona una fecha para ver el historial</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Botón flotante para nueva venta -->
    <button class="fab" onclick="goTo('ventas')">
        <i class="fas fa-plus"></i>
    </button>
    
    <!-- JavaScript Local - Ruta absoluta desde la raíz -->
    <script src="/js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>

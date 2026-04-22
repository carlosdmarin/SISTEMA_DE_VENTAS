<?php
// index.php - VERSIÓN AUTO-CONTENIDA (Sin archivos externos)

// ========== CONFIGURACIÓN DE SUPABASE ==========
$host = 'aws-0-us-east-2.pooler.supabase.com';
$port = '5432';
$dbname = 'postgres';
$user = 'postgres.ownjmawswuygfhltlzts';
$password = 'Marin60563764';

// ========== FUNCIONES DE BASE DE DATOS ==========
function getConnection() {
    global $host, $port, $dbname, $user, $password;
    if (!function_exists('pg_connect')) return null;
    $conn_str = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
    return @pg_connect($conn_str);
}

// ========== API INTERNA ==========
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    $conn = getConnection();
    
    if (!$conn) {
        die(json_encode(['error' => 'Sin conexión a BD']));
    }
    
    // GET: Obtener ventas de hoy
    if ($_GET['api'] === 'ventas_hoy') {
        $fecha = date('Y-m-d');
        $result = pg_query_params($conn, 
            "SELECT id_venta, nombre_cliente, kilos, total, estado, fecha_registro 
             FROM ventas WHERE DATE(fecha_registro) = $1 ORDER BY fecha_registro DESC", 
            [$fecha]
        );
        $ventas = [];
        while ($row = pg_fetch_assoc($result)) $ventas[] = $row;
        echo json_encode($ventas);
        exit;
    }
    
    // POST: Guardar venta
    if ($_GET['api'] === 'guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $nombre = $input['nombreCliente'] ?? '';
        $kilos = floatval($input['kilos'] ?? 0);
        $total = floatval($input['total'] ?? 0);
        $estado = $input['estado'] ?? 'pendiente';
        
        $result = pg_query_params($conn,
            "INSERT INTO ventas (nombre_cliente, kilos, total, estado) VALUES ($1, $2, $3, $4) RETURNING id_venta",
            [$nombre, $kilos, $total, $estado]
        );
        $row = pg_fetch_assoc($result);
        echo json_encode(['success' => true, 'id_venta' => $row['id_venta']]);
        exit;
    }
    
    exit;
}

// ========== PÁGINA HTML ==========
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lavasoft</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .topbar { background: #2e9050; color: white; padding: 15px; display: flex; align-items: center; justify-content: space-between; }
        .menu-btn { background: none; border: none; color: white; font-size: 24px; cursor: pointer; }
        .badge { background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 20px; font-weight: bold; }
        .container { max-width: 600px; margin: 20px auto; padding: 0 15px; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 20px 0; }
        .stat-card { background: white; padding: 15px; border-radius: 10px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-val { font-size: 24px; font-weight: bold; color: #2e9050; }
        .venta-card { background: white; padding: 15px; border-radius: 10px; margin-bottom: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .venta-info h4 { margin-bottom: 5px; }
        .venta-monto { font-size: 20px; font-weight: bold; color: #2e9050; }
        .badge-pendiente { background: #fff3cd; color: #856404; padding: 5px 10px; border-radius: 5px; font-size: 12px; }
        .badge-pagado { background: #d4edda; color: #155724; padding: 5px 10px; border-radius: 5px; font-size: 12px; }
        .form-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; }
        .btn { background: #2e9050; color: white; border: none; padding: 15px; border-radius: 8px; font-size: 16px; cursor: pointer; width: 100%; font-weight: bold; }
        .btn-pay { background: #ffc107; color: #000; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; }
        .total-display { font-size: 32px; font-weight: bold; color: #2e9050; text-align: center; padding: 15px; background: #f0f8f0; border-radius: 8px; }
        .loader { text-align: center; padding: 40px; color: #666; }
        .spinner { border: 3px solid #f3f3f3; border-top: 3px solid #2e9050; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 10px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .fab { position: fixed; bottom: 20px; right: 20px; background: #2e9050; color: white; width: 60px; height: 60px; border-radius: 30px; border: none; font-size: 24px; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .page { display: none; }
        .page.active { display: block; }
        .drawer { position: fixed; top: 0; left: -280px; width: 280px; height: 100%; background: white; z-index: 1000; transition: left 0.3s; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
        .drawer.open { left: 0; }
        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; display: none; }
        .overlay.open { display: block; }
        .drawer-header { padding: 20px; background: #2e9050; color: white; display: flex; align-items: center; justify-content: space-between; }
        .drawer-nav-item { padding: 15px 20px; display: flex; align-items: center; gap: 10px; cursor: pointer; border-bottom: 1px solid #eee; }
        .drawer-nav-item:hover { background: #f5f5f5; }
        .drawer-nav-item.active { background: #e8f5e9; color: #2e9050; }
    </style>
</head>
<body>
    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="closeDrawer()"></div>
    
    <!-- Drawer -->
    <div class="drawer" id="drawer">
        <div class="drawer-header">
            <h3>🧼 Lavasoft</h3>
            <i class="fas fa-times" onclick="closeDrawer()" style="cursor:pointer;"></i>
        </div>
        <div class="drawer-nav-item active" id="nav-hoy" onclick="goTo('hoy')">
            <i class="fas fa-calendar-day"></i> Ventas de Hoy
        </div>
        <div class="drawer-nav-item" id="nav-ventas" onclick="goTo('ventas')">
            <i class="fas fa-plus-circle"></i> Nueva Venta
        </div>
    </div>
    
    <!-- Topbar -->
    <div class="topbar">
        <button class="menu-btn" onclick="openDrawer()"><i class="fas fa-bars"></i></button>
        <h3>Lavasoft</h3>
        <div class="badge" id="badge">S/ 0.00</div>
    </div>
    
    <!-- Contenido -->
    <div class="container">
        <!-- Página Hoy -->
        <div class="page active" id="page-hoy">
            <div class="stats" id="stats">
                <div class="stat-card">
                    <div class="stat-val" id="totalVentas">0</div>
                    <div>Ventas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-val" id="totalKilos">0.0</div>
                    <div>Kilos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-val" id="totalIngreso">S/ 0.00</div>
                    <div>Total</div>
                </div>
            </div>
            
            <div id="listaVentas">
                <div class="loader"><div class="spinner"></div> Cargando...</div>
            </div>
        </div>
        
        <!-- Página Nueva Venta -->
        <div class="page" id="page-ventas">
            <div class="form-card">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Nombre del Cliente</label>
                    <input type="text" id="nombre" placeholder="Ej: Juan Pérez">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-weight-hanging"></i> Kilos de Ropa</label>
                    <input type="number" id="kilos" placeholder="0.00" step="0.01" min="0">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-soles"></i> Total a Pagar</label>
                    <div class="total-display" id="totalMostrado">0.00</div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Estado</label>
                    <select id="estado">
                        <option value="pendiente">⏳ Pendiente</option>
                        <option value="pagado">✅ Pagado</option>
                    </select>
                </div>
                
                <button class="btn" onclick="guardarVenta()">
                    <i class="fas fa-save"></i> Guardar Venta
                </button>
            </div>
        </div>
    </div>
    
    <!-- FAB -->
    <button class="fab" onclick="goTo('ventas')">
        <i class="fas fa-plus"></i>
    </button>
    
    <script>
        // ========== VARIABLES GLOBALES ==========
        const API_BASE = window.location.origin + '?api=';
        
        // ========== FUNCIONES DE NAVEGACIÓN ==========
        function openDrawer() {
            document.getElementById('drawer').classList.add('open');
            document.getElementById('overlay').classList.add('open');
        }
        
        function closeDrawer() {
            document.getElementById('drawer').classList.remove('open');
            document.getElementById('overlay').classList.remove('open');
        }
        
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
                document.getElementById('nombre').value = '';
                document.getElementById('kilos').value = '';
                document.getElementById('totalMostrado').textContent = '0.00';
            }
        }
        
        // ========== CALCULAR TOTAL ==========
        document.getElementById('kilos').addEventListener('input', function() {
            const kilos = parseFloat(this.value) || 0;
            document.getElementById('totalMostrado').textContent = (kilos * 4).toFixed(2);
        });
        
        // ========== CARGAR VENTAS DE HOY ==========
        async function cargarVentasHoy() {
            const lista = document.getElementById('listaVentas');
            lista.innerHTML = '<div class="loader"><div class="spinner"></div> Cargando...</div>';
            
            try {
                const res = await fetch(API_BASE + 'ventas_hoy&t=' + Date.now());
                const texto = await res.text();
                
                console.log('Respuesta:', texto.substring(0, 200));
                
                const ventas = JSON.parse(texto);
                
                // Actualizar stats
                let totalVentas = ventas.length;
                let totalKilos = ventas.reduce((a, v) => a + parseFloat(v.kilos), 0);
                let totalIngreso = ventas.reduce((a, v) => a + parseFloat(v.total), 0);
                
                document.getElementById('totalVentas').textContent = totalVentas;
                document.getElementById('totalKilos').textContent = totalKilos.toFixed(1);
                document.getElementById('totalIngreso').textContent = 'S/ ' + totalIngreso.toFixed(2);
                document.getElementById('badge').innerHTML = `<i class="fas fa-soles"></i> S/ ${totalIngreso.toFixed(2)}`;
                
                if (ventas.length === 0) {
                    lista.innerHTML = '<div style="text-align:center;padding:40px;color:#666;"><i class="fas fa-soap" style="font-size:48px;margin-bottom:10px;"></i><p>Sin ventas por hoy</p></div>';
                    return;
                }
                
                lista.innerHTML = ventas.map(v => {
                    const badgeClass = v.estado === 'cancelado' ? 'badge-pagado' : 'badge-pendiente';
                    const badgeText = v.estado === 'cancelado' ? '✅ Pagado' : '⏳ Pendiente';
                    const payBtn = v.estado === 'pendiente' 
                        ? `<button class="btn-pay" onclick="pagarVenta(${v.id_venta})"><i class="fas fa-money-bill"></i> Pagar</button>` 
                        : '';
                    
                    return `
                        <div class="venta-card">
                            <div class="venta-info">
                                <h4>${v.nombre_cliente}</h4>
                                <small>${v.kilos} kg · ${new Date(v.fecha_registro).toLocaleTimeString('es-PE', {hour:'2-digit',minute:'2-digit'})}</small>
                                <span class="${badgeClass}">${badgeText}</span>
                            </div>
                            <div class="venta-monto">S/ ${parseFloat(v.total).toFixed(2)}</div>
                            ${payBtn}
                        </div>
                    `;
                }).join('');
                
            } catch (error) {
                console.error('Error:', error);
                lista.innerHTML = `
                    <div style="text-align:center;padding:40px;color:#666;">
                        <i class="fas fa-exclamation-triangle" style="font-size:48px;color:#f00;margin-bottom:10px;"></i>
                        <p>Error al cargar ventas</p>
                        <small>${error.message}</small>
                        <button class="btn" style="margin-top:20px;" onclick="cargarVentasHoy()">Reintentar</button>
                    </div>`;
            }
        }
        
        // ========== GUARDAR VENTA ==========
        async function guardarVenta() {
            const nombre = document.getElementById('nombre').value.trim();
            const kilos = parseFloat(document.getElementById('kilos').value);
            const estado = document.getElementById('estado').value === 'pagado' ? 'cancelado' : 'pendiente';
            
            if (!nombre) {
                Swal.fire({icon:'warning',title:'Falta nombre',text:'Ingresa el nombre del cliente'});
                return;
            }
            if (!kilos || kilos <= 0) {
                Swal.fire({icon:'warning',title:'Kilos inválidos',text:'Ingresa los kilos correctamente'});
                return;
            }
            
            const venta = {
                nombreCliente: nombre,
                kilos: kilos,
                total: kilos * 4,
                estado: estado
            };
            
            try {
                const res = await fetch(API_BASE + 'guardar', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(venta)
                });
                const data = await res.json();
                
                if (data.success) {
                    Swal.fire({icon:'success',title:'¡Venta guardada!',timer:1500,showConfirmButton:false});
                    goTo('hoy');
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            } catch (error) {
                Swal.fire({icon:'error',title:'Error',text:'No se pudo guardar: ' + error.message});
            }
        }
        
        // ========== PAGAR VENTA ==========
        async function pagarVenta(id) {
            const result = await Swal.fire({
                title: '¿Confirmar pago?',
                text: 'Marcar como pagada',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí',
                cancelButtonText: 'No'
            });
            
            if (result.isConfirmed) {
                try {
                    const res = await fetch(API_BASE + 'pagar', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id: id})
                    });
                    const data = await res.json();
                    if (data.success) {
                        Swal.fire({icon:'success',title:'¡Pagado!',timer:1000,showConfirmButton:false});
                        cargarVentasHoy();
                    }
                } catch (error) {
                    Swal.fire({icon:'error',title:'Error',text:'No se pudo pagar'});
                }
            }
        }
        
        // ========== INICIAR ==========
        cargarVentasHoy();
        setInterval(() => {
            if (document.getElementById('page-hoy').classList.contains('active')) {
                cargarVentasHoy();
            }
        }, 30000);
    </script>
</body>
</html>

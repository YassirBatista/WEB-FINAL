<?php
// NEXUS STELLAR SHIPYARDS - Funciones Globales
session_start();
require_once 'db_connect.php';

// Verificar autenticación
function requireAuth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol'])) {
        header('Location: /nexus_stellar_shipyards/auth/login.php');
        exit;
    }
}

// Verificar rol Admin
function requireAdmin() {
    requireAuth();
    if ($_SESSION['rol'] !== 'admin') {
        header('Location: /nexus_stellar_shipyards/cliente/dashboard.php');
        exit;
    }
}

// Verificar rol Cliente
function requireCliente() {
    requireAuth();
    if ($_SESSION['rol'] !== 'cliente') {
        header('Location: /nexus_stellar_shipyards/admin/dashboard.php');
        exit;
    }
}

// Obtener datos del usuario actual
function getUsuarioActual() {
    global $pdo;
    if (!isset($_SESSION['user_id'])) return null;
    $stmt = $pdo->prepare("SELECT u.*, f.nombre as faccion_nombre, f.color as faccion_color 
                           FROM usuarios u 
                           LEFT JOIN facciones f ON u.faccion_id = f.id 
                           WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Formatear créditos
function formatCredits($amount) {
    return number_format($amount, 0, ',', '.') . ' Cr';
}

// Formatear tiempo
function formatTime($seconds) {
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    return sprintf("%02d:%02d:%02d", $h, $m, $s);
}

// Estado con color
function getEstadoColor($estado) {
    $map = [
        'en_reparacion' => ['text' => 'EN REPARACIÓN', 'class' => 'estado-reparacion', 'color' => '#00FF9C'],
        'esperando_piezas' => ['text' => 'ESPERANDO PIEZAS', 'class' => 'estado-espera', 'color' => '#FFB800'],
        'diagnostico' => ['text' => 'DIAGNÓSTICO', 'class' => 'estado-diagnostico', 'color' => '#00D9FF'],
        'en_pruebas' => ['text' => 'EN PRUEBAS', 'class' => 'estado-pruebas', 'color' => '#00FF9C'],
        'pendiente' => ['text' => 'PENDIENTE', 'class' => 'estado-pendiente', 'color' => '#E8EDF5'],
        'operativa' => ['text' => 'OPERATIVA', 'class' => 'estado-operativa', 'color' => '#00FF9C'],
        'completada' => ['text' => 'COMPLETADA', 'class' => 'estado-completada', 'color' => '#00FF9C'],
        'disponible' => ['text' => 'DISPONIBLE', 'class' => 'estado-disponible', 'color' => '#00FF9C'],
        'stock_bajo' => ['text' => 'STOCK BAJO', 'class' => 'estado-stock-bajo', 'color' => '#FFB800'],
        'agotado' => ['text' => 'AGOTADO', 'class' => 'estado-agotado', 'color' => '#FF4D5A'],
    ];
    return $map[$estado] ?? ['text' => strtoupper($estado), 'class' => '', 'color' => '#E8EDF5'];
}

// Obtener contadores para dashboard admin
function getDashboardStats() {
    global $pdo;
    $stats = [];
    $stats['ordenes_activas'] = $pdo->query("SELECT COUNT(*) FROM ordenes WHERE estado != 'completada' AND estado != 'cancelada'")->fetchColumn();
    $stats['reparaciones_proceso'] = $pdo->query("SELECT COUNT(*) FROM reparaciones WHERE estado IN ('en_reparacion','esperando_piezas','diagnostico','en_pruebas')")->fetchColumn();
    $stats['naves_taller'] = $pdo->query("SELECT COUNT(*) FROM naves WHERE estado != 'operativa'")->fetchColumn();
    $stats['piezas_inventario'] = $pdo->query("SELECT SUM(stock) FROM piezas")->fetchColumn();
    $stats['tecnicos_activos'] = $pdo->query("SELECT COUNT(*) FROM tecnicos WHERE estado != 'descansando'")->fetchColumn();
    $stats['creditos_totales'] = $pdo->query("SELECT SUM(monto) FROM transacciones WHERE tipo = 'ingreso'")->fetchColumn();
    return $stats;
}

// Obtener alertas
function getAlertas($usuario_id = null, $limit = 5) {
    global $pdo;
    if ($usuario_id) {
        $stmt = $pdo->prepare("SELECT * FROM alertas WHERE usuario_id = ? OR usuario_id IS NULL ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$usuario_id, $limit]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM alertas ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
    }
    return $stmt->fetchAll();
}

// Obtener ordenes recientes
function getOrdenesRecientes($limit = 5) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT o.*, n.nombre as nave_nombre, u.nombre as cliente_nombre 
                           FROM ordenes o 
                           LEFT JOIN naves n ON o.nave_id = n.id 
                           LEFT JOIN usuarios u ON o.cliente_id = u.id 
                           ORDER BY o.fecha_creacion DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Obtener naves en taller
function getNavesEnTaller($limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT n.*, u.nombre as cliente_nombre 
                           FROM naves n 
                           LEFT JOIN usuarios u ON n.cliente_id = u.id 
                           WHERE n.estado != 'operativa' 
                           ORDER BY n.created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Obtener técnicos activos
function getTecnicosActivos($limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM tecnicos WHERE estado != 'descansando' ORDER BY FIELD(estado, 'en_reparacion', 'en_diagnostico', 'disponible') LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Obtener piezas más solicitadas
function getPiezasSolicitadas($limit = 5) {
    global $pdo;
    $stmt = $pdo->query("SELECT p.*, COUNT(r.id) as solicitudes 
                         FROM piezas p 
                         LEFT JOIN reparaciones r ON r.descripcion LIKE CONCAT('%', p.nombre, '%') 
                         GROUP BY p.id 
                         ORDER BY solicitudes DESC 
                         LIMIT $limit");
    return $stmt->fetchAll();
}

// Obtener distribución de naves por tipo
function getDistribucionNaves() {
    global $pdo;
    $stmt = $pdo->query("SELECT tipo, COUNT(*) as total FROM naves GROUP BY tipo");
    return $stmt->fetchAll();
}

// Obtener ingresos últimos 30 días
function getIngresos30Dias() {
    global $pdo;
    $stmt = $pdo->query("SELECT DATE(fecha) as dia, SUM(monto) as total 
                         FROM transacciones 
                         WHERE tipo = 'ingreso' AND fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                         GROUP BY DATE(fecha) 
                         ORDER BY dia");
    return $stmt->fetchAll();
}

// Obtener datos del cliente para su dashboard
function getClienteStats($cliente_id) {
    global $pdo;
    $stats = [];
    $stats['mis_naves'] = $pdo->prepare("SELECT COUNT(*) FROM naves WHERE cliente_id = ?")->execute([$cliente_id]) ? $pdo->query("SELECT COUNT(*) FROM naves WHERE cliente_id = $cliente_id")->fetchColumn() : 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM naves WHERE cliente_id = ?");
    $stmt->execute([$cliente_id]);
    $stats['mis_naves'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reparaciones r JOIN naves n ON r.nave_id = n.id WHERE n.cliente_id = ? AND r.estado != 'completada'");
    $stmt->execute([$cliente_id]);
    $stats['mis_reparaciones'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT saldo FROM usuarios WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $stats['saldo'] = $stmt->fetchColumn();

    return $stats;
}

// Obtener naves del cliente
function getMisNaves($cliente_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT n.*, h.nombre as hangar_nombre 
                           FROM naves n 
                           LEFT JOIN hangares h ON n.hangar_nivel = h.nivel 
                           WHERE n.cliente_id = ?");
    $stmt->execute([$cliente_id]);
    return $stmt->fetchAll();
}

// Obtener reparaciones del cliente
function getMisReparaciones($cliente_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT r.*, n.nombre as nave_nombre, n.codigo as nave_codigo, n.imagen as nave_imagen,
                                  t.nombre as tecnico_nombre, t.rango as tecnico_rango
                           FROM reparaciones r 
                           JOIN naves n ON r.nave_id = n.id 
                           LEFT JOIN tecnicos t ON r.tecnico_id = t.id
                           WHERE n.cliente_id = ? AND r.estado != 'completada'
                           ORDER BY r.fecha_inicio DESC");
    $stmt->execute([$cliente_id]);
    return $stmt->fetchAll();
}

// Obtener piezas disponibles para tienda (filtradas por facción del cliente)
function getPiezasTienda($faccion_id = null) {
    global $pdo;
    if ($faccion_id) {
        $stmt = $pdo->prepare("SELECT p.*, f.nombre as faccion_exclusiva_nombre, f.color as faccion_exclusiva_color
                               FROM piezas p 
                               LEFT JOIN facciones f ON p.faccion_exclusiva_id = f.id
                               WHERE p.faccion_exclusiva_id IS NULL OR p.faccion_exclusiva_id = ?
                               ORDER BY p.categoria, p.nombre");
        $stmt->execute([$faccion_id]);
    } else {
        $stmt = $pdo->query("SELECT p.*, f.nombre as faccion_exclusiva_nombre, f.color as faccion_exclusiva_color
                             FROM piezas p 
                             LEFT JOIN facciones f ON p.faccion_exclusiva_id = f.id
                             ORDER BY p.categoria, p.nombre");
    }
    return $stmt->fetchAll();
}

// Obtener historial del cliente
function getHistorialCliente($cliente_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT o.*, n.nombre as nave_nombre, n.codigo as nave_codigo
                           FROM ordenes o 
                           LEFT JOIN naves n ON o.nave_id = n.id
                           WHERE o.cliente_id = ?
                           ORDER BY o.fecha_creacion DESC");
    $stmt->execute([$cliente_id]);
    return $stmt->fetchAll();
}

function contarOrdenesPorEstado($estado) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ordenes WHERE estado = ?");
    $stmt->execute([$estado]);
    return $stmt->fetchColumn();
}

function getOrdenes($filtros = []) {
    global $pdo;
    $where = ['1=1'];
    $params = [];
    
    if (!empty($filtros['estado'])) {
        $where[] = "o.estado = ?";
        $params[] = $filtros['estado'];
    }
    if (!empty($filtros['busqueda'])) {
        $where[] = "(o.codigo LIKE ? OR u.nombre LIKE ? OR n.nombre LIKE ?)";
        $busq = '%' . $filtros['busqueda'] . '%';
        $params = array_merge($params, [$busq, $busq, $busq]);
    }
    
    $whereStr = implode(' AND ', $where);
    
    // Total
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM ordenes o 
        LEFT JOIN usuarios u ON o.cliente_id = u.id 
        LEFT JOIN naves n ON o.nave_id = n.id 
        WHERE $whereStr");
    $stmtTotal->execute($params);
    $total = $stmtTotal->fetchColumn();
    
    // Datos paginados
    $offset = (($filtros['pagina'] ?? 1) - 1) * ($filtros['limite'] ?? 15);
    $limite = intval($filtros['limite'] ?? 15);
    
    $stmt = $pdo->prepare("SELECT o.*, 
        u.nombre as cliente_nombre, 
        u.avatar as cliente_avatar,
        f.nombre as cliente_faccion,
        n.nombre as nave_nombre
        FROM ordenes o
        LEFT JOIN usuarios u ON o.cliente_id = u.id
        LEFT JOIN facciones f ON u.faccion_id = f.id
        LEFT JOIN naves n ON o.nave_id = n.id
        WHERE $whereStr
        ORDER BY o.fecha_creacion DESC
        LIMIT $limite OFFSET $offset");
    $stmt->execute($params);
    
    return ['data' => $stmt->fetchAll(), 'total' => $total];
}

function getClientesActivos() {
    global $pdo;
    return $pdo->query("SELECT id, nombre FROM usuarios WHERE rol = 'cliente' AND activo = 1 ORDER BY nombre")->fetchAll();
}

function getNavesDisponibles() {
    global $pdo;
    return $pdo->query("SELECT id, nombre FROM naves ORDER BY nombre")->fetchAll();
}
?>
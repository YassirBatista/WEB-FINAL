<?php
// NEXUS STELLAR SHIPYARDS — Control de Inventario (Año 2926)
// CSS separado: inventario.css (AGREGAR AL FINAL DE style.css)

require_once '../includes/functions.php';
requireAdmin();

$pageTitle = 'Inventario';

// ═══════════════════════════════════════════════════════
// PROCESAR ACCIONES POST
// ═══════════════════════════════════════════════════════
$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'registrar_movimiento':
                $pieza_id = (int)$_POST['pieza_id'];
                $tipo = $_POST['tipo_movimiento'];
                $cantidad = (int)$_POST['cantidad'];
                $descripcion = trim($_POST['descripcion'] ?? '');
                $usuario_id = $_SESSION['user_id'] ?? null;

                $stmt = $pdo->prepare("SELECT stock FROM piezas WHERE id = ?");
                $stmt->execute([$pieza_id]);
                $stockActual = (int)$stmt->fetchColumn();

                $nuevoStock = match($tipo) {
                    'entrada' => $stockActual + $cantidad,
                    'salida' => max(0, $stockActual - $cantidad),
                    'ajuste' => $cantidad,
                    default => $stockActual
                };

                $pdo->prepare("UPDATE piezas SET stock = ? WHERE id = ?")
                    ->execute([$nuevoStock, $pieza_id]);

                $nuevoEstado = match(true) {
                    $nuevoStock <= 0 => 'agotado',
                    $nuevoStock <= 5 => 'stock_bajo',
                    default => 'disponible'
                };
                $pdo->prepare("UPDATE piezas SET estado = ? WHERE id = ?")
                    ->execute([$nuevoEstado, $pieza_id]);

                $pdo->prepare("INSERT INTO movimientos_inventario (pieza_id, tipo, cantidad, usuario_id, descripcion) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$pieza_id, $tipo, $cantidad, $usuario_id, $descripcion]);

                $mensaje = 'Movimiento registrado correctamente';
                $tipoMensaje = 'success';
                break;
        }
    } catch (PDOException $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipoMensaje = 'error';
    }
}

// ═══════════════════════════════════════════════════════
// FILTROS
// ═══════════════════════════════════════════════════════
$filtroTipo = $_GET['tipo'] ?? '';
$filtroPieza = $_GET['pieza'] ?? '';
$filtroFecha = $_GET['fecha'] ?? '';
$busqueda = $_GET['buscar'] ?? '';
$vista = $_GET['vista'] ?? 'terminal';

if (!in_array($vista, ['visual', 'terminal'])) {
    $vista = 'terminal';
}

// ═══════════════════════════════════════════════════════
// CONSULTA MOVIMIENTOS
// ═══════════════════════════════════════════════════════
$sql = "SELECT m.*, 
            p.codigo as pieza_codigo, p.nombre as pieza_nombre, p.categoria as pieza_categoria, 
            p.imagen as pieza_imagen, p.stock as stock_actual,
            u.nombre as usuario_nombre, u.rol as usuario_rol
        FROM movimientos_inventario m
        LEFT JOIN piezas p ON m.pieza_id = p.id
        LEFT JOIN usuarios u ON m.usuario_id = u.id
        WHERE 1=1";
$params = [];

if ($filtroTipo) {
    $sql .= " AND m.tipo = ?";
    $params[] = $filtroTipo;
}
if ($filtroPieza) {
    $sql .= " AND m.pieza_id = ?";
    $params[] = (int)$filtroPieza;
}
if ($filtroFecha) {
    $sql .= " AND DATE(m.fecha) = ?";
    $params[] = $filtroFecha;
}
if ($busqueda) {
    $sql .= " AND (p.nombre LIKE ? OR p.codigo LIKE ? OR m.descripcion LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

$sql .= " ORDER BY m.fecha DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movimientos = $stmt->fetchAll();

// ═══════════════════════════════════════════════════════
// STATS
// ═══════════════════════════════════════════════════════
$totalMovimientos = count($movimientos);
$entradasHoy = count(array_filter($movimientos, fn($m) => ($m['tipo'] ?? '') === 'entrada' && date('Y-m-d', strtotime($m['fecha'])) === date('Y-m-d')));
$salidasHoy = count(array_filter($movimientos, fn($m) => ($m['tipo'] ?? '') === 'salida' && date('Y-m-d', strtotime($m['fecha'])) === date('Y-m-d')));

$tiposMov = ['entrada' => '⬇ ENTRADA', 'salida' => '⬆ SALIDA', 'ajuste' => '⚡ AJUSTE'];
$piezasLista = $pdo->query("SELECT id, codigo, nombre FROM piezas ORDER BY nombre")->fetchAll();
$stockCritico = $pdo->query("SELECT id, codigo, nombre, stock, categoria, imagen FROM piezas WHERE stock <= 5 ORDER BY stock ASC, nombre LIMIT 8")->fetchAll();

$usuario = getUsuarioActual();
require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <header class="top-header">
        <div class="header-title">CONTROL DE INVENTARIO — NEXUS</div>
        <div class="header-right">
            <div class="header-stat">
                <div class="label">MOV. HOY</div>
                <div class="value"><?php echo $entradasHoy + $salidasHoy; ?></div>
            </div>
            <div class="header-stat">
                <div class="label">STOCK CRÍTICO</div>
                <div class="value stat-value-critical"><?php echo count($stockCritico); ?></div>
            </div>
            <div class="header-stat">
                <div class="label">TOTAL REG.</div>
                <div class="value"><?php echo $totalMovimientos; ?></div>
            </div>
            <div class="user-profile">
                <img src="../assets/images/avatars/<?php echo htmlspecialchars($usuario['avatar'] ?? 'default_avatar.jpg'); ?>" 
                     alt="Admin" onerror="this.src='../assets/images/avatars/default_avatar.jpg'">
                <div class="info">
                    <div class="name"><?php echo htmlspecialchars($usuario['nombre'] ?? 'Admin'); ?></div>
                    <div class="role">ADMINISTRADOR</div>
                </div>
            </div>
        </div>
    </header>

    <div class="page-content">

        <!-- HEADER -->
        <div class="page-header">
            <h1>REGISTRO DE MOVIMIENTOS</h1>
            <div class="breadcrumb">
                <span class="breadcrumb-dot">●</span>
                ALMACÉN ORBITAL NEXUS — SECTOR 7G — AÑO 2926 &nbsp;|&nbsp; 
                TRAZABILIDAD COMPLETA
            </div>
        </div>

        <!-- STATS BAR -->
        <div class="taller-status-bar">
            <div class="taller-status-item">
                <span class="taller-status-label">ENTRADAS HOY</span>
                <span class="taller-status-value stat-value-operational"><?php echo $entradasHoy; ?></span>
            </div>
            <div class="taller-status-item">
                <span class="taller-status-label">SALIDAS HOY</span>
                <span class="taller-status-value stat-value-critical"><?php echo $salidasHoy; ?></span>
            </div>
            <div class="taller-status-item">
                <span class="taller-status-label">STOCK CRÍTICO</span>
                <span class="taller-status-value stat-value-warning"><?php echo count($stockCritico); ?> PIEZAS</span>
            </div>
            <div class="taller-status-item">
                <span class="taller-status-label">TOTAL REGISTROS</span>
                <span class="taller-status-value"><?php echo $totalMovimientos; ?></span>
            </div>
        </div>

        <!-- MENSAJE -->
        <?php if ($mensaje): ?>
        <div class="nexus-alert nexus-alert-<?php echo $tipoMensaje; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
        <?php endif; ?>

        <!-- ALERTAS STOCK CRÍTICO -->
        <?php if (count($stockCritico) > 0): ?>
        <div class="inventario-alertas-criticas">
            <div class="inventario-alertas-header">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--critical)" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span>ALERTAS DE STOCK CRÍTICO</span>
            </div>
            <div class="inventario-alertas-grid">
                <?php foreach($stockCritico as $sc): 
                    $imgCrit = !empty($sc['imagen']) ? $sc['imagen'] : 'default_part.jpg';
                    $estadoCrit = $sc['stock'] <= 0 ? 'AGOTADO' : 'STOCK BAJO';
                    $colorCrit = $sc['stock'] <= 0 ? 'var(--critical)' : 'var(--warning)';
                    $borderCrit = $sc['stock'] <= 0 ? 'rgba(255,77,90,0.3)' : 'rgba(255,184,0,0.3)';
                ?>
                <a href="piezas.php?buscar=<?php echo urlencode($sc['codigo']); ?>" class="inventario-alerta-item" style="border-color:<?php echo $borderCrit; ?>">
                    <div class="inventario-alerta-img" style="background-image:url('../assets/images/parts/<?php echo htmlspecialchars($imgCrit); ?>');border-color:<?php echo $colorCrit; ?>"></div>
                    <div>
                        <div class="inventario-alerta-nombre"><?php echo htmlspecialchars($sc['nombre']); ?></div>
                        <div class="inventario-alerta-stock" style="color:<?php echo $colorCrit; ?>">
                            <?php echo $estadoCrit; ?> — <?php echo (int)$sc['stock']; ?> uds
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- FILTROS + TOGGLE -->
        <div class="inventario-filtros-bar">
            <div class="naves-filtros">
                <div class="filtro-buscar">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                    <input type="text" id="buscarMov" placeholder="Buscar movimiento..." 
                           value="<?php echo htmlspecialchars($busqueda); ?>" onkeyup="if(event.key==='Enter') aplicarFiltros()">
                </div>
                <select class="filtro-select" id="filtroTipo" onchange="aplicarFiltros()">
                    <option value="">Todos los tipos</option>
                    <?php foreach($tiposMov as $k => $v): ?>
                    <option value="<?php echo $k; ?>" <?php echo $filtroTipo === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="filtro-select" id="filtroPieza" onchange="aplicarFiltros()">
                    <option value="">Todas las piezas</option>
                    <?php foreach($piezasLista as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo $filtroPieza == $p['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['codigo'] . ' — ' . $p['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <input type="date" id="filtroFecha" class="filtro-select" value="<?php echo htmlspecialchars($filtroFecha); ?>" 
                       onchange="aplicarFiltros()">
            </div>

            <div class="taller-toggle-vista">
                <a href="?vista=terminal<?php echo buildQueryParams(['vista']); ?>" 
                   class="taller-toggle-btn <?php echo $vista === 'terminal' ? 'active' : ''; ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>
                    </svg>
                    TERMINAL
                </a>
                <a href="?vista=visual<?php echo buildQueryParams(['vista']); ?>" 
                   class="taller-toggle-btn <?php echo $vista === 'visual' ? 'active' : ''; ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    VISUAL
                </a>
            </div>
        </div>

        <button class="btn-nexus btn-nueva-nave inventario-btn-registrar" onclick="abrirModalMovimiento()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            REGISTRAR MOVIMIENTO
        </button>

        <!-- SEPARADOR -->
        <div class="nexus-separator inventario-separator">
            <div class="nexus-sep-line"></div>
            <div class="nexus-sep-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--neon-cyan)" stroke-width="1.5">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
            </div>
            <div class="nexus-sep-line"></div>
        </div>

        <?php if(count($movimientos) === 0): ?>
        <div class="taller-empty">
            <div class="taller-empty-holograma">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="var(--neon-cyan-dim)" stroke-width="0.5">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                </svg>
            </div>
            <h3>SIN MOVIMIENTOS REGISTRADOS</h3>
            <p>El registro de inventario está vacío.<br>Registra el primer movimiento para comenzar la trazabilidad.</p>
        </div>

        <?php elseif($vista === 'terminal'): ?>
        <!-- VISTA TERMINAL -->
        <div class="taller-terminal">
            <div class="taller-terminal-header">
                <span class="taller-terminal-prompt">root@nexus-inventario:~$</span>
                <span class="taller-terminal-cmd">./listar_movimientos --tipo=<?php echo htmlspecialchars($filtroTipo ?: 'todos'); ?> --formato=tabla --total=<?php echo $totalMovimientos; ?></span>
            </div>

            <div class="taller-terminal-table">
                <div class="taller-terminal-row taller-terminal-header-row">
                    <span class="taller-terminal-col inventario-terminal-col-id">ID</span>
                    <span class="taller-terminal-col inventario-terminal-col-pieza">PIEZA</span>
                    <span class="taller-terminal-col inventario-terminal-col-tipo">TIPO</span>
                    <span class="taller-terminal-col inventario-terminal-col-cant">CANT</span>
                    <span class="taller-terminal-col inventario-terminal-col-stock">STOCK POST</span>
                    <span class="taller-terminal-col inventario-terminal-col-usuario">USUARIO</span>
                    <span class="taller-terminal-col inventario-terminal-col-desc">DESCRIPCIÓN</span>
                    <span class="taller-terminal-col inventario-terminal-col-fecha">FECHA</span>
                </div>

                <?php foreach($movimientos as $m): 
                    $tipoClass = match($m['tipo']) {
                        'entrada' => 'inventario-terminal-tipo-entrada',
                        'salida' => 'inventario-terminal-tipo-salida',
                        'ajuste' => 'inventario-terminal-tipo-ajuste',
                        default => ''
                    };
                    $tipoIcon = match($m['tipo']) {
                        'entrada' => '⬇',
                        'salida' => '⬆',
                        'ajuste' => '⚡',
                        default => '●'
                    };
                ?>
                <div class="taller-terminal-row">
                    <span class="taller-terminal-col inventario-terminal-col-id">
                        #<?php echo str_pad((int)$m['id'], 4, '0', STR_PAD_LEFT); ?>
                    </span>
                    <span class="taller-terminal-col inventario-terminal-col-pieza">
                        <?php echo htmlspecialchars($m['pieza_codigo'] ?? 'N/A'); ?>
                    </span>
                    <span class="taller-terminal-col inventario-terminal-col-tipo <?php echo $tipoClass; ?>">
                        <?php echo $tipoIcon; ?> <?php echo strtoupper($m['tipo']); ?>
                    </span>
                    <span class="taller-terminal-col inventario-terminal-col-cant">
                        <?php echo (int)$m['cantidad']; ?>
                    </span>
                    <span class="taller-terminal-col inventario-terminal-col-stock">
                        <?php echo (int)($m['stock_actual'] ?? 0); ?> uds
                    </span>
                    <span class="taller-terminal-col inventario-terminal-col-usuario">
                        <span class="text-dimmed"><?php echo htmlspecialchars($m['usuario_nombre'] ?? 'SISTEMA'); ?></span>
                        <span class="text-dimmed text-uppercase text-xs">[<?php echo strtoupper($m['usuario_rol'] ?? 'AUTO'); ?>]</span>
                    </span>
                    <span class="taller-terminal-col inventario-terminal-col-desc">
                        <?php echo htmlspecialchars($m['descripcion'] ?: '—'); ?>
                    </span>
                    <span class="taller-terminal-col inventario-terminal-col-fecha">
                        <?php echo date('d/m/Y H:i', strtotime($m['fecha'])); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="taller-terminal-footer">
                <span class="taller-terminal-prompt">root@nexus-inventario:~$</span>
                <span class="taller-terminal-cursor">_</span>
                <span class="taller-terminal-meta">Total: <?php echo $totalMovimientos; ?> registros | Última actualización: <?php echo date('H:i:s'); ?></span>
            </div>
        </div>

        <?php else: ?>
        <!-- VISTA VISUAL -->
        <div class="naves-grid-page">
            <?php foreach($movimientos as $m): 
                $cardClass = match($m['tipo']) {
                    'entrada' => 'inventario-card-entrada',
                    'salida' => 'inventario-card-salida',
                    'ajuste' => 'inventario-card-ajuste',
                    default => ''
                };
                $tipoColor = match($m['tipo']) {
                    'entrada' => 'var(--operational)',
                    'salida' => 'var(--critical)',
                    'ajuste' => 'var(--warning)',
                    default => 'var(--neon-cyan)'
                };
                $tipoBg = match($m['tipo']) {
                    'entrada' => 'rgba(0,255,156,0.1)',
                    'salida' => 'rgba(255,77,90,0.1)',
                    'ajuste' => 'rgba(255,184,0,0.1)',
                    default => 'rgba(0,217,255,0.1)'
                };
                $imgPieza = !empty($m['pieza_imagen']) ? $m['pieza_imagen'] : 'default_part.jpg';
            ?>
            <div class="nave-tarjeta <?php echo $cardClass; ?>">
                <div class="nave-tarjeta-imagen" style="background-image:url('../assets/images/parts/<?php echo htmlspecialchars($imgPieza); ?>')">
                    <div class="nave-tarjeta-overlay"></div>
                    <span class="nave-tarjeta-estado" style="color:<?php echo $tipoColor; ?>;border-color:<?php echo $tipoColor; ?>;background:<?php echo $tipoBg; ?>">
                        <?php echo strtoupper($m['tipo']); ?>
                    </span>
                    <div class="inventario-card-cantidad"><?php echo (int)$m['cantidad']; ?></div>
                </div>
                <div class="nave-tarjeta-info">
                    <h3 class="nave-tarjeta-nombre"><?php echo htmlspecialchars($m['pieza_nombre'] ?? 'Pieza Desconocida'); ?></h3>
                    <span class="nave-tarjeta-tipo"><?php echo htmlspecialchars($m['pieza_codigo'] ?? 'N/A'); ?> — <?php echo ucfirst($m['pieza_categoria'] ?? 'general'); ?></span>

                    <div class="nave-tarjeta-meta">
                        <div class="nave-tarjeta-meta-item">
                            <span class="nave-tarjeta-meta-label">STOCK ACTUAL</span>
                            <span class="nave-tarjeta-meta-value"><?php echo (int)($m['stock_actual'] ?? 0); ?> uds</span>
                        </div>
                        <div class="nave-tarjeta-meta-item">
                            <span class="nave-tarjeta-meta-label">REGISTRADO POR</span>
                            <span class="nave-tarjeta-meta-value">
                                <?php echo htmlspecialchars($m['usuario_nombre'] ?? 'Sistema'); ?>
                                <span class="text-dimmed text-uppercase text-xs">[<?php echo strtoupper($m['usuario_rol'] ?? 'AUTO'); ?>]</span>
                            </span>
                        </div>
                        <div class="nave-tarjeta-meta-item">
                            <span class="nave-tarjeta-meta-label">FECHA</span>
                            <span class="nave-tarjeta-meta-value"><?php echo date('d/m/Y H:i', strtotime($m['fecha'])); ?></span>
                        </div>
                        <?php if($m['descripcion']): ?>
                        <div class="nave-tarjeta-meta-item">
                            <span class="nave-tarjeta-meta-label">NOTA</span>
                            <span class="nave-tarjeta-meta-value text-dimmed text-xs"><?php echo htmlspecialchars($m['descripcion']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- MODAL REGISTRAR MOVIMIENTO -->
<div id="modalMovimiento" class="nexus-modal">
    <div class="nexus-modal-overlay" onclick="cerrarModalMovimiento()"></div>
    <div class="nexus-modal-content nexus-modal-content-sm">
        <div class="nexus-modal-header">
            <h3>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--neon-cyan)" stroke-width="2">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
                REGISTRAR MOVIMIENTO
            </h3>
            <button class="nexus-modal-close" onclick="cerrarModalMovimiento()">✕</button>
        </div>
        <form method="POST" id="formMovimiento" class="nexus-modal-body">
            <input type="hidden" name="action" value="registrar_movimiento">

            <div class="nexus-form-group">
                <label class="nexus-form-label">PIEZA</label>
                <select name="pieza_id" id="movPieza" class="nexus-form-input" required>
                    <option value="">Seleccionar pieza...</option>
                    <?php foreach($piezasLista as $p): 
                        $stockP = $pdo->query("SELECT stock FROM piezas WHERE id = {$p['id']}")->fetchColumn();
                    ?>
                    <option value="<?php echo $p['id']; ?>" data-stock="<?php echo (int)$stockP; ?>">
                        <?php echo htmlspecialchars($p['codigo'] . ' — ' . $p['nombre'] . ' (Stock: ' . (int)$stockP . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="nexus-form-row">
                <div class="nexus-form-group">
                    <label class="nexus-form-label">TIPO DE MOVIMIENTO</label>
                    <select name="tipo_movimiento" id="movTipo" class="nexus-form-input" required onchange="actualizarPreviewStock()">
                        <option value="entrada">⬇ ENTRADA</option>
                        <option value="salida">⬆ SALIDA</option>
                        <option value="ajuste">⚡ AJUSTE</option>
                    </select>
                </div>
                <div class="nexus-form-group">
                    <label class="nexus-form-label">CANTIDAD</label>
                    <input type="number" name="cantidad" id="movCantidad" class="nexus-form-input" min="1" value="1" required onchange="actualizarPreviewStock()">
                </div>
            </div>

            <div class="nexus-form-group">
                <label class="nexus-form-label">STOCK RESULTANTE (PREVISUALIZACIÓN)</label>
                <input type="text" id="stockPreview" class="nexus-form-input inventario-stock-preview" readonly>
            </div>

            <div class="nexus-form-group">
                <label class="nexus-form-label">DESCRIPCIÓN / MOTIVO</label>
                <textarea name="descripcion" class="nexus-form-input" rows="2" placeholder="Ej: Recepción de proveedor, uso en reparación OR-0091..."></textarea>
            </div>
        </form>
        <div class="nexus-modal-footer">
            <button type="button" class="btn-nexus btn-nexus-secondary" onclick="cerrarModalMovimiento()">CANCELAR</button>
            <button type="button" class="btn-nexus" onclick="document.getElementById('formMovimiento').submit()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
                </svg>
                REGISTRAR
            </button>
        </div>
    </div>
</div>

<script>
function buildQueryParams(exclude) {
    const params = new URLSearchParams();
    const current = new URLSearchParams(window.location.search);
    current.forEach((val, key) => {
        if (!exclude.includes(key)) params.set(key, val);
    });
    return params.toString() ? '&' + params.toString() : '';
}

function aplicarFiltros() {
    const params = new URLSearchParams();
    const buscar = document.getElementById('buscarMov').value;
    const tipo = document.getElementById('filtroTipo').value;
    const pieza = document.getElementById('filtroPieza').value;
    const fecha = document.getElementById('filtroFecha').value;
    const vista = new URLSearchParams(window.location.search).get('vista') || 'terminal';

    params.set('vista', vista);
    if (buscar) params.set('buscar', buscar);
    if (tipo) params.set('tipo', tipo);
    if (pieza) params.set('pieza', pieza);
    if (fecha) params.set('fecha', fecha);

    location.href = 'inventario.php?' + params.toString();
}

function abrirModalMovimiento() {
    document.getElementById('modalMovimiento').classList.add('active');
    document.body.style.overflow = 'hidden';
    actualizarPreviewStock();
}

function cerrarModalMovimiento() {
    document.getElementById('modalMovimiento').classList.remove('active');
    document.body.style.overflow = '';
}

function actualizarPreviewStock() {
    const select = document.getElementById('movPieza');
    const tipo = document.getElementById('movTipo').value;
    const cantidad = parseInt(document.getElementById('movCantidad').value) || 0;
    const preview = document.getElementById('stockPreview');

    const option = select.options[select.selectedIndex];
    const stockActual = parseInt(option.dataset.stock) || 0;

    let resultado = stockActual;
    if (tipo === 'entrada') resultado = stockActual + cantidad;
    else if (tipo === 'salida') resultado = Math.max(0, stockActual - cantidad);
    else if (tipo === 'ajuste') resultado = cantidad;

    preview.value = stockActual + ' → ' + resultado + ' unidades';
    preview.classList.remove('inventario-stock-preview-preview-ok', 'inventario-stock-preview-preview-bajo', 'inventario-stock-preview-preview-critico');

    if (resultado <= 0) preview.classList.add('inventario-stock-preview-preview-critico');
    else if (resultado <= 5) preview.classList.add('inventario-stock-preview-preview-bajo');
    else preview.classList.add('inventario-stock-preview-preview-ok');
}

document.getElementById('movPieza')?.addEventListener('change', actualizarPreviewStock);
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cerrarModalMovimiento(); });
</script>

<?php 
function buildQueryParams(array $exclude = []): string {
    $params = [];
    foreach ($_GET as $k => $v) {
        if (!in_array($k, $exclude) && $v !== '') {
            $params[] = urlencode($k) . '=' . urlencode($v);
        }
    }
    return $params ? '&' . implode('&', $params) : '';
}
require_once '../includes/footer.php'; 
?>
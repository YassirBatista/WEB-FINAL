<?php
// NEXUS STELLAR SHIPYARDS — Centro de Suministros (Piezas)
require_once '../includes/functions.php';
requireAdmin();

$pageTitle = 'Piezas';

// ═══════════════════════════════════════════════════════
// PROCESAR ACCIONES
// ═══════════════════════════════════════════════════════
$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'crear':
                $stmt = $pdo->prepare("INSERT INTO piezas (codigo, nombre, categoria, stock, precio, estado, faccion_exclusiva_id, descripcion, imagen) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['codigo'],
                    $_POST['nombre'],
                    $_POST['categoria'],
                    (int)$_POST['stock'],
                    (float)$_POST['precio'],
                    $_POST['estado'],
                    !empty($_POST['faccion_exclusiva_id']) ? (int)$_POST['faccion_exclusiva_id'] : null,
                    $_POST['descripcion'] ?? '',
                    $_POST['imagen'] ?? 'default_part.jpg'
                ]);
                $mensaje = 'Pieza registrada correctamente';
                $tipoMensaje = 'success';
                break;

            case 'update':
                $stmt = $pdo->prepare("UPDATE piezas SET codigo=?, nombre=?, categoria=?, stock=?, precio=?, estado=?, faccion_exclusiva_id=?, descripcion=?, imagen=? WHERE id=?");
                $stmt->execute([
                    $_POST['codigo'],
                    $_POST['nombre'],
                    $_POST['categoria'],
                    (int)$_POST['stock'],
                    (float)$_POST['precio'],
                    $_POST['estado'],
                    !empty($_POST['faccion_exclusiva_id']) ? (int)$_POST['faccion_exclusiva_id'] : null,
                    $_POST['descripcion'] ?? '',
                    $_POST['imagen'] ?? 'default_part.jpg',
                    (int)$_POST['id']
                ]);
                $mensaje = 'Pieza actualizada correctamente';
                $tipoMensaje = 'success';
                break;

            case 'ajustar_stock':
                $piezaId = (int)$_POST['id'];
                $cantidad = (int)$_POST['cantidad'];
                $tipoMov = $_POST['tipo_movimiento'];

                $stmt = $pdo->prepare("SELECT stock FROM piezas WHERE id = ?");
                $stmt->execute([$piezaId]);
                $stockActual = (int)$stmt->fetchColumn();

                $nuevoStock = $tipoMov === 'entrada' ? $stockActual + $cantidad : max(0, $stockActual - $cantidad);

                $pdo->prepare("UPDATE piezas SET stock = ? WHERE id = ?")->execute([$nuevoStock, $piezaId]);
                $pdo->prepare("INSERT INTO movimientos_inventario (pieza_id, tipo, cantidad, usuario_id, descripcion) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$piezaId, $tipoMov, $cantidad, $_SESSION['user_id'] ?? null, "Ajuste manual: $tipoMov"]);

                $mensaje = 'Stock ajustado correctamente';
                $tipoMensaje = 'success';
                break;

            case 'eliminar':
                $pdo->prepare("DELETE FROM piezas WHERE id = ?")->execute([(int)$_POST['id']]);
                $mensaje = 'Pieza eliminada';
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
$filtroCategoria = $_GET['categoria'] ?? '';
$filtroEstado = $_GET['estado'] ?? '';
$filtroFaccion = $_GET['faccion'] ?? '';
$busqueda = $_GET['buscar'] ?? '';

// Construir query
$sql = "SELECT p.*, f.nombre as faccion_nombre, f.color as faccion_color 
        FROM piezas p 
        LEFT JOIN facciones f ON p.faccion_exclusiva_id = f.id 
        WHERE 1=1";
$params = [];

if ($filtroCategoria) {
    $sql .= " AND p.categoria = ?";
    $params[] = $filtroCategoria;
}
if ($filtroEstado) {
    $sql .= " AND p.estado = ?";
    $params[] = $filtroEstado;
}
if ($filtroFaccion) {
    $sql .= " AND p.faccion_exclusiva_id = ?";
    $params[] = (int)$filtroFaccion;
}
if ($busqueda) {
    $sql .= " AND (p.nombre LIKE ? OR p.codigo LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

$sql .= " ORDER BY 
    FIELD(p.estado, 'agotado', 'stock_bajo', 'disponible'),
    p.categoria, p.nombre";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$piezas = $stmt->fetchAll();

// Datos para filtros
$categorias = ['motores', 'reactores', 'escudos', 'blindajes', 'navegacion', 'armas', 'bahias', 'especiales'];
$estadosPieza = ['disponible', 'stock_bajo', 'agotado'];
$facciones = $pdo->query("SELECT id, nombre, color FROM facciones ORDER BY nombre")->fetchAll();

// Stats
$totalPiezas = count($piezas);
$stockBajo = count(array_filter($piezas, fn($p) => $p['estado'] === 'stock_bajo'));
$agotadas = count(array_filter($piezas, fn($p) => $p['estado'] === 'agotado'));
$valorTotal = array_sum(array_map(fn($p) => $p['stock'] * $p['precio'], $piezas));

$usuario = getUsuarioActual();
require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <header class="top-header">
        <div class="header-title">ALMACÉN NEXUS — PIEZAS</div>
        <div class="header-right">
            <div class="header-stat">
                <div class="label">STOCK BAJO</div>
                <div class="value" style="color:var(--warning)"><?php echo $stockBajo; ?></div>
            </div>
            <div class="header-stat">
                <div class="label">AGOTADAS</div>
                <div class="value" style="color:var(--critical)"><?php echo $agotadas; ?></div>
            </div>
            <div class="header-stat">
                <div class="label">TOTAL</div>
                <div class="value"><?php echo $totalPiezas; ?></div>
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

        <!-- HEADER DE PÁGINA (estándar Nexus) -->
        <div class="page-header" style="margin-bottom: 24px;">
            <h1 style="font-family:'Orbitron',sans-serif;font-size:22px;color:var(--tech-white);text-transform:uppercase;letter-spacing:3px;margin-bottom:6px;">
                CENTRO DE SUMINISTROS
            </h1>
            <div class="breadcrumb" style="font-size:12px;color:var(--tech-white-dim);letter-spacing:1px;">
                <span style="color:var(--neon-cyan)">●</span> ALMACÉN ORBITAL NEXUS — SECTOR 7G — AÑO 2926 &nbsp;|&nbsp; 
                REFERENCIAS: <?php echo $totalPiezas; ?> PIEZAS
            </div>
        </div>

        <!-- STATS BAR (estilo Taller/Reparaciones) -->
        <div class="taller-status-bar" style="margin-bottom: 24px;">
            <div class="taller-status-item">
                <span class="taller-status-label">VALOR INVENTARIO</span>
                <span class="taller-status-value"><?php echo number_format($valorTotal, 0, ',', '.'); ?> Cr</span>
            </div>
            <div class="taller-status-item">
                <span class="taller-status-label">REFERENCIAS</span>
                <span class="taller-status-value"><?php echo $totalPiezas; ?></span>
            </div>
            <div class="taller-status-item">
                <span class="taller-status-label">STOCK BAJO</span>
                <span class="taller-status-value" style="color:var(--warning)"><?php echo $stockBajo; ?></span>
            </div>
            <div class="taller-status-item">
                <span class="taller-status-label">AGOTADAS</span>
                <span class="taller-status-value" style="color:var(--critical)"><?php echo $agotadas; ?></span>
            </div>
        </div>

        <!-- MENSAJE -->
        <?php if ($mensaje): ?>
        <div class="nexus-alert nexus-alert-<?php echo $tipoMensaje; ?>" style="margin-bottom: 20px;">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
        <?php endif; ?>

        <!-- FILTROS (estilo Naves) -->
        <div class="naves-filtros" style="margin-bottom: 24px;">
            <div class="filtro-buscar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" id="buscarPieza" placeholder="Buscar por nombre o código..." 
                       value="<?php echo htmlspecialchars($busqueda); ?>" onkeyup="if(event.key==='Enter') aplicarFiltros()">
            </div>
            <select class="filtro-select" id="filtroCategoria" onchange="aplicarFiltros()">
                <option value="">Todas las categorías</option>
                <?php foreach($categorias as $c): ?>
                <option value="<?php echo $c; ?>" <?php echo $filtroCategoria === $c ? 'selected' : ''; ?>>
                    <?php echo ucfirst($c); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <select class="filtro-select" id="filtroEstado" onchange="aplicarFiltros()">
                <option value="">Todos los estados</option>
                <?php foreach($estadosPieza as $e): ?>
                <option value="<?php echo $e; ?>" <?php echo $filtroEstado === $e ? 'selected' : ''; ?>>
                    <?php echo ucfirst($e); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <select class="filtro-select" id="filtroFaccion" onchange="aplicarFiltros()">
                <option value="">Todas las facciones</option>
                <?php foreach($facciones as $f): ?>
                <option value="<?php echo $f['id']; ?>" <?php echo $filtroFaccion == $f['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($f['nombre']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button class="btn-nexus btn-nueva-nave" onclick="abrirModal()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                NUEVA PIEZA
            </button>
        </div>

        <!-- SEPARADOR NEXUS -->
        <div class="nexus-separator" style="margin-bottom: 24px;">
            <div class="nexus-sep-line"></div>
            <div class="nexus-sep-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--neon-cyan)" stroke-width="1.5">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                    <polyline points="2 17 12 22 22 17"/>
                    <polyline points="2 12 12 17 22 12"/>
                </svg>
            </div>
            <div class="nexus-sep-line"></div>
        </div>

        <!-- GRID DE PIEZAS (estilo Naves — con imagen) -->
        <?php if(count($piezas) === 0): ?>
        <div class="empty-state-naves">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--neon-cyan-dim)" stroke-width="1">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
            </svg>
            <h3>SIN PIEZAS REGISTRADAS</h3>
            <p>El almacén orbital está vacío.<br>Registra nuevas piezas para comenzar.</p>
        </div>
        <?php else: ?>
        <div class="naves-grid-page">
            <?php foreach($piezas as $p): 
                $estadoClass = match($p['estado']) {
                    'agotado' => 'estado-agotado',
                    'stock_bajo' => 'estado-stock-bajo',
                    default => 'estado-disponible'
                };
                $estadoColor = match($p['estado']) {
                    'agotado' => 'var(--critical)',
                    'stock_bajo' => 'var(--warning)',
                    default => 'var(--operational)'
                };
                $estadoText = match($p['estado']) {
                    'agotado' => 'AGOTADO',
                    'stock_bajo' => 'STOCK BAJO',
                    default => 'DISPONIBLE'
                };
                $imgPieza = !empty($p['imagen']) ? $p['imagen'] : 'default_part.jpg';
                $barColor = match($p['estado']) {
                    'agotado' => 'var(--critical)',
                    'stock_bajo' => 'var(--warning)',
                    default => 'var(--neon-cyan)'
                };
                $barWidth = min(100, max(0, ($p['stock'] / 20) * 100));
            ?>
            <div class="nave-tarjeta <?php echo $estadoClass; ?>" data-id="<?php echo $p['id']; ?>">
                <!-- IMAGEN DE LA PIEZA (como en naves) -->
                <div class="nave-tarjeta-imagen" style="background-image:url('../assets/images/parts/<?php echo htmlspecialchars($imgPieza); ?>')">
                    <div class="nave-tarjeta-overlay"></div>
                    <span class="nave-tarjeta-estado <?php echo $estadoClass; ?>" style="color:<?php echo $estadoColor; ?>;border-color:<?php echo $estadoColor; ?>;background:<?php echo $estadoColor; ?>20">
                        <?php echo $estadoText; ?>
                    </span>
                </div>

                <div class="nave-tarjeta-info">
                    <h3 class="nave-tarjeta-nombre"><?php echo htmlspecialchars($p['nombre']); ?></h3>
                    <span class="nave-tarjeta-tipo"><?php echo strtoupper($p['categoria']); ?> — <?php echo htmlspecialchars($p['codigo']); ?></span>

                    <!-- Barra de stock -->
                    <div style="margin-bottom: 12px;">
                        <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--tech-white-dim);text-transform:uppercase;letter-spacing:2px;margin-bottom:6px;">
                            <span>STOCK</span>
                            <span style="color:var(--tech-white);font-weight:600;"><?php echo (int)$p['stock']; ?> uds</span>
                        </div>
                        <div style="width:100%;height:5px;background:rgba(255,255,255,0.05);border-radius:3px;overflow:hidden;">
                            <div style="height:100%;width:<?php echo $barWidth; ?>%;background:<?php echo $barColor; ?>;border-radius:3px;transition:width 0.8s ease;box-shadow:0 0 8px <?php echo $barColor; ?>40;"></div>
                        </div>
                    </div>

                    <div class="nave-tarjeta-meta">
                        <div class="nave-tarjeta-meta-item">
                            <span class="nave-tarjeta-meta-label">PRECIO UNIT.</span>
                            <span class="nave-tarjeta-meta-value" style="font-family:'Rajdhani',monospace;font-weight:700;letter-spacing:1px">
                                <?php echo number_format((float)$p['precio'], 0, ',', '.'); ?> Cr
                            </span>
                        </div>
                        <div class="nave-tarjeta-meta-item">
                            <span class="nave-tarjeta-meta-label">VALOR TOTAL</span>
                            <span class="nave-tarjeta-meta-value" style="font-family:'Rajdhani',monospace;font-weight:700;letter-spacing:1px">
                                <?php echo number_format((float)$p['precio'] * (int)$p['stock'], 0, ',', '.'); ?> Cr
                            </span>
                        </div>
                        <?php if($p['faccion_nombre']): ?>
                        <div class="nave-tarjeta-meta-item">
                            <span class="nave-tarjeta-meta-label">EXCLUSIVA</span>
                            <span class="nave-tarjeta-meta-value">
                                <span class="nave-tarjeta-cliente">
                                    <span class="nave-tarjeta-faccion-dot" style="background:<?php echo $p['faccion_color']; ?>"></span>
                                    <?php echo htmlspecialchars($p['faccion_nombre']); ?>
                                </span>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="nave-tarjeta-acciones">
                        <button class="btn-nexus btn-nexus-sm" onclick="abrirModalStock(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['nombre']); ?>', <?php echo $p['stock']; ?>)">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            STOCK
                        </button>
                        <button class="btn-nexus btn-nexus-sm btn-editar" onclick="editarPieza(<?php echo $p['id']; ?>)">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            EDITAR
                        </button>
                        <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar esta pieza?')">
                            <input type="hidden" name="action" value="eliminar">
                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                            <button type="submit" class="btn-nexus btn-nexus-sm btn-nexus-danger">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- DATOS OCULTOS PARA EL MODAL -->
            <script>
            window.piezaData_<?php echo $p['id']; ?> = <?php echo json_encode([
                'id' => $p['id'],
                'codigo' => $p['codigo'],
                'nombre' => $p['nombre'],
                'categoria' => $p['categoria'],
                'stock' => $p['stock'],
                'precio' => $p['precio'],
                'estado' => $p['estado'],
                'faccion_exclusiva_id' => $p['faccion_exclusiva_id'],
                'descripcion' => $p['descripcion'] ?? '',
                'imagen' => $imgPieza
            ]); ?>;
            </script>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     MODAL: NUEVA/EDITAR PIEZA
     ═══════════════════════════════════════════════════════ -->
<div id="modalPieza" class="nexus-modal">
    <div class="nexus-modal-overlay" onclick="cerrarModal()"></div>
    <div class="nexus-modal-content" style="max-width:560px">
        <div class="nexus-modal-header">
            <h3 id="modalTitulo">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--neon-cyan)" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                </svg>
                NUEVA PIEZA
            </h3>
            <button class="nexus-modal-close" onclick="cerrarModal()">✕</button>
        </div>
        <form method="POST" id="formPieza" class="nexus-modal-body">
            <input type="hidden" name="action" id="formAction" value="crear">
            <input type="hidden" name="id" id="piezaId">

            <div class="nexus-form-row">
                <div class="nexus-form-group">
                    <label class="nexus-form-label">CÓDIGO</label>
                    <input type="text" name="codigo" id="codigo" class="nexus-form-input" required>
                </div>
                <div class="nexus-form-group">
                    <label class="nexus-form-label">NOMBRE</label>
                    <input type="text" name="nombre" id="nombre" class="nexus-form-input" required>
                </div>
            </div>

            <div class="nexus-form-row">
                <div class="nexus-form-group">
                    <label class="nexus-form-label">CATEGORÍA</label>
                    <select name="categoria" id="categoria" class="nexus-form-input" required>
                        <?php foreach($categorias as $c): ?>
                        <option value="<?php echo $c; ?>"><?php echo ucfirst($c); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="nexus-form-group">
                    <label class="nexus-form-label">ESTADO</label>
                    <select name="estado" id="estado" class="nexus-form-input" required>
                        <option value="disponible">Disponible</option>
                        <option value="stock_bajo">Stock Bajo</option>
                        <option value="agotado">Agotado</option>
                    </select>
                </div>
            </div>

            <div class="nexus-form-row">
                <div class="nexus-form-group">
                    <label class="nexus-form-label">STOCK</label>
                    <input type="number" name="stock" id="stock" class="nexus-form-input" min="0" value="0" required>
                </div>
                <div class="nexus-form-group">
                    <label class="nexus-form-label">PRECIO (Cr)</label>
                    <input type="number" name="precio" id="precio" class="nexus-form-input" min="0" step="0.01" value="0" required>
                </div>
            </div>

            <div class="nexus-form-group">
                <label class="nexus-form-label">FACCIÓN EXCLUSIVA (opcional)</label>
                <select name="faccion_exclusiva_id" id="faccion_exclusiva_id" class="nexus-form-input">
                    <option value="">Ninguna — Uso general</option>
                    <?php foreach($facciones as $f): ?>
                    <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="nexus-form-group">
                <label class="nexus-form-label">IMAGEN</label>
                <input type="text" name="imagen" id="imagen" class="nexus-form-input" placeholder="nombre_archivo.jpg">
                <div class="nexus-form-imagen-preview" style="margin-top:8px;">
                    <img id="imagenPreview" src="../assets/images/parts/default_part.jpg" alt="Pieza">
                    <span class="nexus-form-imagen-nota">Guarda la imagen en /assets/images/parts/</span>
                </div>
            </div>

            <div class="nexus-form-group">
                <label class="nexus-form-label">DESCRIPCIÓN</label>
                <textarea name="descripcion" id="descripcion" class="nexus-form-input" rows="3"></textarea>
            </div>
        </form>
        <div class="nexus-modal-footer">
            <button type="button" class="btn-nexus btn-nexus-secondary" onclick="cerrarModal()">CANCELAR</button>
            <button type="button" class="btn-nexus" onclick="document.getElementById('formPieza').submit()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
                </svg>
                GUARDAR
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     MODAL: AJUSTAR STOCK
     ═══════════════════════════════════════════════════════ -->
<div id="modalStock" class="nexus-modal">
    <div class="nexus-modal-overlay" onclick="cerrarModalStock()"></div>
    <div class="nexus-modal-content" style="max-width:400px">
        <div class="nexus-modal-header">
            <h3>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--neon-cyan)" stroke-width="2">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                AJUSTAR STOCK
            </h3>
            <button class="nexus-modal-close" onclick="cerrarModalStock()">✕</button>
        </div>
        <form method="POST" id="formStock" class="nexus-modal-body">
            <input type="hidden" name="action" value="ajustar_stock">
            <input type="hidden" name="id" id="stockPiezaId">

            <div class="nexus-form-group">
                <label class="nexus-form-label">PIEZA</label>
                <input type="text" id="stockPiezaNombre" class="nexus-form-input" readonly style="opacity:0.7">
            </div>

            <div class="nexus-form-group">
                <label class="nexus-form-label">STOCK ACTUAL</label>
                <input type="text" id="stockActual" class="nexus-form-input" readonly style="opacity:0.7">
            </div>

            <div class="nexus-form-row">
                <div class="nexus-form-group">
                    <label class="nexus-form-label">TIPO DE MOVIMIENTO</label>
                    <select name="tipo_movimiento" class="nexus-form-input" required>
                        <option value="entrada">⬇ ENTRADA</option>
                        <option value="salida">⬆ SALIDA</option>
                    </select>
                </div>
                <div class="nexus-form-group">
                    <label class="nexus-form-label">CANTIDAD</label>
                    <input type="number" name="cantidad" class="nexus-form-input" min="1" value="1" required>
                </div>
            </div>
        </form>
        <div class="nexus-modal-footer">
            <button type="button" class="btn-nexus btn-nexus-secondary" onclick="cerrarModalStock()">CANCELAR</button>
            <button type="button" class="btn-nexus" onclick="document.getElementById('formStock').submit()">
                APLICAR MOVIMIENTO
            </button>
        </div>
    </div>
</div>

<script>
// Datos de piezas para edición
const piezasData = <?php echo json_encode(array_map(fn($p) => [
    'id' => $p['id'],
    'codigo' => $p['codigo'],
    'nombre' => $p['nombre'],
    'categoria' => $p['categoria'],
    'stock' => $p['stock'],
    'precio' => $p['precio'],
    'estado' => $p['estado'],
    'faccion_exclusiva_id' => $p['faccion_exclusiva_id'],
    'descripcion' => $p['descripcion'] ?? '',
    'imagen' => !empty($p['imagen']) ? $p['imagen'] : 'default_part.jpg'
], $piezas)); ?>;

function abrirModal() {
    document.getElementById('formAction').value = 'crear';
    document.getElementById('modalTitulo').innerHTML = `
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--neon-cyan)" stroke-width="2">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
        </svg> NUEVA PIEZA`;
    document.getElementById('formPieza').reset();
    document.getElementById('piezaId').value = '';
    document.getElementById('imagenPreview').src = '../assets/images/parts/default_part.jpg';
    document.getElementById('modalPieza').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function editarPieza(id) {
    const p = piezasData.find(x => x.id == id);
    if (!p) return;

    document.getElementById('formAction').value = 'update';
    document.getElementById('modalTitulo').innerHTML = `
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--neon-cyan)" stroke-width="2">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
        </svg> EDITAR PIEZA`;

    document.getElementById('piezaId').value = p.id;
    document.getElementById('codigo').value = p.codigo;
    document.getElementById('nombre').value = p.nombre;
    document.getElementById('categoria').value = p.categoria;
    document.getElementById('estado').value = p.estado;
    document.getElementById('stock').value = p.stock;
    document.getElementById('precio').value = p.precio;
    document.getElementById('faccion_exclusiva_id').value = p.faccion_exclusiva_id || '';
    document.getElementById('descripcion').value = p.descripcion || '';
    document.getElementById('imagen').value = p.imagen;
    document.getElementById('imagenPreview').src = '../assets/images/parts/' + p.imagen;

    document.getElementById('modalPieza').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function cerrarModal() {
    document.getElementById('modalPieza').classList.remove('active');
    document.body.style.overflow = '';
}

function abrirModalStock(id, nombre, stock) {
    document.getElementById('stockPiezaId').value = id;
    document.getElementById('stockPiezaNombre').value = nombre;
    document.getElementById('stockActual').value = stock + ' unidades';
    document.getElementById('modalStock').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function cerrarModalStock() {
    document.getElementById('modalStock').classList.remove('active');
    document.body.style.overflow = '';
}

function aplicarFiltros() {
    const params = new URLSearchParams();
    const buscar = document.getElementById('buscarPieza').value;
    const cat = document.getElementById('filtroCategoria').value;
    const est = document.getElementById('filtroEstado').value;
    const fac = document.getElementById('filtroFaccion').value;

    if (buscar) params.set('buscar', buscar);
    if (cat) params.set('categoria', cat);
    if (est) params.set('estado', est);
    if (fac) params.set('faccion', fac);

    location.href = 'piezas.php?' + params.toString();
}

// Preview de imagen al escribir
document.getElementById('imagen')?.addEventListener('input', function() {
    const val = this.value.trim();
    document.getElementById('imagenPreview').src = '../assets/images/parts/' + (val || 'default_part.jpg');
});

// Cerrar con Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        cerrarModal();
        cerrarModalStock();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
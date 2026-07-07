<?php
// NEXUS STELLAR SHIPYARDS — Centro de Naves Espaciales\
require_once __DIR__ . '/../includes/functions.php';
require_once '../includes/db_connect.php';
requireAdmin();

$pageTitle = 'Naves';

// ============================================
// PROCESAR NUEVA NAVE (AJAX)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_nave') {
    header('Content-Type: application/json');
    
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $tipo = $_POST['tipo'];
    $estado = $_POST['estado'];
    $hangar_nivel = (int)$_POST['hangar_nivel'];
    $cliente_id = (int)$_POST['cliente_id'];
    
    if (empty($codigo) || empty($nombre) || empty($tipo)) {
        echo json_encode(['success' => false, 'message' => 'Código, nombre y tipo son obligatorios']);
        exit;
    }
    
    try {
        // Generar imagen según tipo
        $imagen = 'default_ship.jpg';
        $imagenesPorTipo = [
            'caza' => 'nave_raptor.jpg',
            'fragata' => 'nave_eclipse.jpg',
            'acorazado' => 'nave_valkyrie.jpg',
            'nodriza' => 'nave_valkyrie.jpg',
            'comercial' => 'nave_venture.jpg',
            'transporte' => 'nave_cargo.jpg',
            'crucero' => 'nave_shadow.jpg',
            'portanaves' => 'nave_valkyrie.jpg',
        ];
        if (isset($imagenesPorTipo[$tipo])) {
            $imagen = $imagenesPorTipo[$tipo];
        }
        
        $stmt = $pdo->prepare("INSERT INTO naves (codigo, nombre, tipo, estado, hangar_nivel, cliente_id, imagen, tiempo_restante) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([$codigo, $nombre, $tipo, $estado, $hangar_nivel, $cliente_id ?: null, $imagen]);
        
        echo json_encode(['success' => true, 'message' => 'Nave creada correctamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ============================================
// PROCESAR ACTUALIZACIÓN (AJAX)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_nave') {
    header('Content-Type: application/json');
    
    $id = (int)$_POST['id'];
    $nombre = trim($_POST['nombre']);
    $tipo = $_POST['tipo'];
    $estado = $_POST['estado'];
    $hangar_nivel = (int)$_POST['hangar_nivel'];
    $cliente_id = (int)$_POST['cliente_id'];
    $codigo = trim($_POST['codigo']);
    
    try {
        $stmt = $pdo->prepare("UPDATE naves 
            SET nombre = ?, tipo = ?, estado = ?, hangar_nivel = ?, cliente_id = ?, codigo = ?
            WHERE id = ?");
        $stmt->execute([$nombre, $tipo, $estado, $hangar_nivel, $cliente_id ?: null, $codigo, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Nave actualizada correctamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ============================================
// OBTENER DATOS
// ============================================

// Filtros
$filtroTipo = $_GET['tipo'] ?? '';
$filtroEstado = $_GET['estado'] ?? '';
$filtroHangar = $_GET['hangar'] ?? '';
$busqueda = $_GET['buscar'] ?? '';

// Construir query
$sql = "SELECT n.*, u.nombre as cliente_nombre, u.faccion_id, f.color as faccion_color, f.nombre as faccion_nombre
        FROM naves n
        LEFT JOIN usuarios u ON n.cliente_id = u.id
        LEFT JOIN facciones f ON u.faccion_id = f.id
        WHERE 1=1";
$params = [];

if ($filtroTipo) {
    $sql .= " AND n.tipo = ?";
    $params[] = $filtroTipo;
}
if ($filtroEstado) {
    $sql .= " AND n.estado = ?";
    $params[] = $filtroEstado;
}
if ($filtroHangar) {
    $sql .= " AND n.hangar_nivel = ?";
    $params[] = (int)$filtroHangar;
}
if ($busqueda) {
    $sql .= " AND (n.nombre LIKE ? OR n.codigo LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

$sql .= " ORDER BY n.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$naves = $stmt->fetchAll();

// Datos para selects
$tiposNave = $pdo->query("SELECT DISTINCT tipo FROM naves ORDER BY tipo")->fetchAll(PDO::FETCH_COLUMN);
$estadosNave = ['operativa', 'en_reparacion', 'esperando_piezas', 'diagnostico', 'en_pruebas', 'pendiente', 'destruida'];
$hangares = $pdo->query("SELECT nivel, nombre FROM hangares ORDER BY nivel DESC")->fetchAll();
$clientes = $pdo->query("SELECT id, nombre, faccion_id FROM usuarios WHERE rol = 'cliente' ORDER BY nombre")->fetchAll();

$usuario = getUsuarioActual();
require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';
?>

<link rel="stylesheet" href="/nexus_stellar_shipyards/assets/css/naves.css">

<div class="main-content">
    <header class="top-header">
        <div class="header-title">CENTRO DE NAVES ESPACIALES</div>
        <div class="header-right">
            <div class="header-stat">
                <div class="label">FLOTA TOTAL</div>
                <div class="value"><?php echo count($naves); ?> NAVES</div>
            </div>
            <div class="header-stat">
                <div class="label">EN REPARACIÓN</div>
                <div class="value" style="color:var(--warning)">
                    <?php echo count(array_filter($naves, fn($n) => $n['estado'] !== 'operativa')); ?>
                </div>
            </div>
            <div class="user-profile">
                <img src="../assets/images/avatars/<?php echo htmlspecialchars($usuario['avatar'] ?? 'default_avatar.jpg'); ?>" 
                     alt="Admin" onerror="this.src='../assets/images/avatars/default_avatar.jpg'">
                <div class="info">
                    <div class="name"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
                    <div class="role">ADMINISTRADOR</div>
                </div>
            </div>
        </div>
    </header>

    <div class="page-content">
        
        <!-- HEADER -->
        <div class="page-header">
            <h1 class="page-title">CENTRO DE NAVES</h1>
            <div class="breadcrumb">
                <span class="breadcrumb-dot"></span> SECTOR ORBITAL NEXUS &nbsp;|&nbsp; 
                AÑO 2926 &nbsp;|&nbsp; 
                FLOTA REGISTRADA: <?php echo count($naves); ?> NAVES
            </div>
        </div>

        <!-- FILTROS -->
        <div class="naves-filtros">
            <div class="filtro-buscar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" id="buscarNave" placeholder="Buscar por nombre o código..." 
                       value="<?php echo htmlspecialchars($busqueda); ?>">
            </div>
            <select class="filtro-select" id="filtroTipo">
                <option value="">Todas las clases</option>
                <?php foreach($tiposNave as $t): ?>
                <option value="<?php echo $t; ?>" <?php echo $filtroTipo === $t ? 'selected' : ''; ?>>
                    <?php echo ucfirst($t); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <select class="filtro-select" id="filtroEstado">
                <option value="">Todos los estados</option>
                <?php foreach($estadosNave as $e): 
                    $est = getEstadoColor($e);
                ?>
                <option value="<?php echo $e; ?>" <?php echo $filtroEstado === $e ? 'selected' : ''; ?>>
                    <?php echo $est['text']; ?>
                </option>
                <?php endforeach; ?>
            </select>
            <select class="filtro-select" id="filtroHangar">
                <option value="">Todos los hangares</option>
                <?php foreach($hangares as $h): ?>
                <option value="<?php echo $h['nivel']; ?>" <?php echo $filtroHangar == $h['nivel'] ? 'selected' : ''; ?>>
                    Hangar Nivel <?php echo $h['nivel']; ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button class="btn-nexus btn-nueva-nave" id="btnNuevaNave">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                NUEVA NAVE
            </button>
        </div>

        <!-- GRID DE NAVES -->
        <div class="naves-grid-page">
            <?php if(count($naves) === 0): ?>
            <div class="empty-state-naves">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--neon-cyan-dim)" stroke-width="1">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>
                </svg>
                <h3>SIN NAVES REGISTRADAS</h3>
                <p>No se encontraron naves con los filtros aplicados.</p>
            </div>
            <?php else: ?>
            <?php foreach($naves as $nave): 
                $estado = getEstadoColor($nave['estado']);
                $imgNave = !empty($nave['imagen']) ? $nave['imagen'] : 'default_ship.jpg';
                $colorFaccion = $nave['faccion_color'] ?? 'var(--neon-cyan)';
            ?>
            <div class="nave-tarjeta" data-id="<?php echo $nave['id']; ?>">
                <div class="nave-tarjeta-imagen" style="background-image:url('../assets/images/ships/<?php echo htmlspecialchars($imgNave); ?>')">
                    <div class="nave-tarjeta-overlay"></div>
                    <span class="nave-tarjeta-estado <?php echo $estado['class']; ?>">
                        <?php echo $estado['text']; ?>
                    </span>
                </div>
                <div class="nave-tarjeta-info">
                    <h3 class="nave-tarjeta-nombre"><?php echo htmlspecialchars($nave['nombre']); ?></h3>
                    <span class="nave-tarjeta-tipo"><?php echo strtoupper($nave['tipo']); ?></span>
                    
                    <div class="nave-tarjeta-meta">
                        <div class="nave-tarjeta-meta-item">
                            <span class="nave-tarjeta-meta-label">CLIENTE</span>
                            <span class="nave-tarjeta-meta-value">
                                <?php if($nave['cliente_nombre']): ?>
                                    <span class="nave-tarjeta-cliente">
                                        <span class="nave-tarjeta-faccion-dot" style="background:<?php echo $colorFaccion; ?>"></span>
                                        <?php echo htmlspecialchars($nave['cliente_nombre']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="opacity:0.5">Sin asignar</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="nave-tarjeta-meta-item">
                            <span class="nave-tarjeta-meta-label">HANGAR</span>
                            <span class="nave-tarjeta-meta-value">Nivel <?php echo (int)$nave['hangar_nivel']; ?></span>
                        </div>
                        <div class="nave-tarjeta-meta-item">
                            <span class="nave-tarjeta-meta-label">CÓDIGO</span>
                            <span class="nave-tarjeta-meta-value nave-tarjeta-codigo">
                                <?php echo htmlspecialchars($nave['codigo']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="nave-tarjeta-acciones">
                        <a href="naves.php?ver=<?php echo $nave['id']; ?>" class="btn-nexus btn-nexus-sm">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                            VER
                        </a>
                        <button class="btn-nexus btn-nexus-sm btn-editar" onclick="abrirModalEditar(<?php echo $nave['id']; ?>)">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            EDITAR
                        </button>
                        <a href="reparaciones.php?nave=<?php echo $nave['id']; ?>" class="btn-nexus btn-nexus-sm">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                            </svg>
                            REPARAR
                        </a>
                    </div>
                </div>
            </div>

            <!-- DATOS OCULTOS PARA EL MODAL -->
            <script>
            window.naveData_<?php echo $nave['id']; ?> = <?php echo json_encode([
                'id' => $nave['id'],
                'nombre' => $nave['nombre'],
                'tipo' => $nave['tipo'],
                'estado' => $nave['estado'],
                'hangar_nivel' => $nave['hangar_nivel'],
                'cliente_id' => $nave['cliente_id'],
                'codigo' => $nave['codigo'],
                'imagen' => $imgNave
            ]); ?>;
            </script>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================
     MODAL NUEVA NAVE (INLINE)
     ============================================ -->
<div id="modalNuevaNave" class="nexus-modal">
    <div class="nexus-modal-overlay" onclick="cerrarModalNueva()"></div>
    <div class="nexus-modal-content">
        <div class="nexus-modal-header">
            <h3>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--neon-cyan)" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                REGISTRAR NUEVA NAVE
            </h3>
            <button class="nexus-modal-close" onclick="cerrarModalNueva()">✕</button>
        </div>
        
        <form id="formNuevaNave" class="nexus-modal-body">
            <input type="hidden" name="action" value="create_nave">
            
            <div class="nexus-form-group">
                <label class="nexus-form-label">CÓDIGO *</label>
                <input type="text" name="codigo" id="new_codigo" class="nexus-form-input" placeholder="Ej: NX-001" required>
            </div>
            
            <div class="nexus-form-group">
                <label class="nexus-form-label">NOMBRE *</label>
                <input type="text" name="nombre" id="new_nombre" class="nexus-form-input" placeholder="Ej: Valkyrie MK-II" required>
            </div>
            
            <div class="nexus-form-row">
                <div class="nexus-form-group">
                    <label class="nexus-form-label">TIPO *</label>
                    <select name="tipo" id="new_tipo" class="nexus-form-input" required>
                        <option value="">Seleccionar tipo...</option>
                        <option value="caza">Caza</option>
                        <option value="fragata">Fragata</option>
                        <option value="acorazado">Acorazado</option>
                        <option value="nodriza">Nodriza</option>
                        <option value="comercial">Comercial</option>
                        <option value="transporte">Transporte</option>
                        <option value="crucero">Crucero</option>
                        <option value="portanaves">Portanaves</option>
                    </select>
                </div>
                
                <div class="nexus-form-group">
                    <label class="nexus-form-label">ESTADO</label>
                    <select name="estado" id="new_estado" class="nexus-form-input">
                        <option value="pendiente" selected>Pendiente</option>
                        <option value="operativa">Operativa</option>
                        <option value="en_reparacion">En Reparación</option>
                        <option value="diagnostico">Diagnóstico</option>
                        <option value="esperando_piezas">Esperando Piezas</option>
                        <option value="en_pruebas">En Pruebas</option>
                    </select>
                </div>
            </div>
            
            <div class="nexus-form-row">
                <div class="nexus-form-group">
                    <label class="nexus-form-label">HANGAR</label>
                    <select name="hangar_nivel" id="new_hangar" class="nexus-form-input">
                        <?php foreach($hangares as $h): ?>
                        <option value="<?php echo $h['nivel']; ?>">Nivel <?php echo $h['nivel']; ?> — <?php echo $h['nombre']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="nexus-form-group">
                    <label class="nexus-form-label">CLIENTE</label>
                    <select name="cliente_id" id="new_cliente" class="nexus-form-input">
                        <option value="0">Sin asignar</option>
                        <?php foreach($clientes as $c): ?>
                        <option value="<?php echo $c['id']; ?>">
                            <?php echo htmlspecialchars($c['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>
        
        <div class="nexus-modal-footer">
            <button type="button" class="btn-nexus btn-nexus-secondary" onclick="cerrarModalNueva()">CANCELAR</button>
            <button type="button" class="btn-nexus" onclick="guardarNuevaNave()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                CREAR NAVE
            </button>
        </div>
    </div>
</div>

<!-- ============================================
     MODAL EDITAR NAVE
     ============================================ -->
<div id="modalEditar" class="nexus-modal">
    <div class="nexus-modal-overlay" onclick="cerrarModal()"></div>
    <div class="nexus-modal-content">
        <div class="nexus-modal-header">
            <h3>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--neon-cyan)" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                EDITAR NAVE
            </h3>
            <button class="nexus-modal-close" onclick="cerrarModal()">✕</button>
        </div>
        
        <form id="formEditarNave" class="nexus-modal-body">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="action" value="update_nave">
            
            <div class="nexus-form-group">
                <label class="nexus-form-label">CÓDIGO</label>
                <input type="text" name="codigo" id="edit_codigo" class="nexus-form-input" required>
            </div>
            
            <div class="nexus-form-group">
                <label class="nexus-form-label">NOMBRE</label>
                <input type="text" name="nombre" id="edit_nombre" class="nexus-form-input" required>
            </div>
            
            <div class="nexus-form-row">
                <div class="nexus-form-group">
                    <label class="nexus-form-label">TIPO</label>
                    <select name="tipo" id="edit_tipo" class="nexus-form-input">
                        <?php foreach($tiposNave as $t): ?>
                        <option value="<?php echo $t; ?>"><?php echo ucfirst($t); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="nexus-form-group">
                    <label class="nexus-form-label">ESTADO</label>
                    <select name="estado" id="edit_estado" class="nexus-form-input">
                        <?php foreach($estadosNave as $e): 
                            $est = getEstadoColor($e);
                        ?>
                        <option value="<?php echo $e; ?>"><?php echo $est['text']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="nexus-form-row">
                <div class="nexus-form-group">
                    <label class="nexus-form-label">HANGAR</label>
                    <select name="hangar_nivel" id="edit_hangar" class="nexus-form-input">
                        <?php foreach($hangares as $h): ?>
                        <option value="<?php echo $h['nivel']; ?>">Nivel <?php echo $h['nivel']; ?> — <?php echo $h['nombre']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="nexus-form-group">
                    <label class="nexus-form-label">CLIENTE</label>
                    <select name="cliente_id" id="edit_cliente" class="nexus-form-input">
                        <option value="0">Sin asignar</option>
                        <?php foreach($clientes as $c): ?>
                        <option value="<?php echo $c['id']; ?>">
                            <?php echo htmlspecialchars($c['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="nexus-form-group">
                <label class="nexus-form-label">IMAGEN ACTUAL</label>
                <div class="nexus-form-imagen-preview">
                    <img id="edit_imagen_preview" src="" alt="Nave">
                    <span class="nexus-form-imagen-nota">La imagen se asigna automáticamente según el modelo</span>
                </div>
            </div>
        </form>
        
        <div class="nexus-modal-footer">
            <button type="button" class="btn-nexus btn-nexus-secondary" onclick="cerrarModal()">CANCELAR</button>
            <button type="button" class="btn-nexus" onclick="guardarNave()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
                </svg>
                GUARDAR CAMBIOS
            </button>
        </div>
    </div>
</div>

<script>
// ============================================
// MODAL NUEVA NAVE
// ============================================
document.getElementById('btnNuevaNave').addEventListener('click', function() {
    document.getElementById('modalNuevaNave').classList.add('active');
    document.body.style.overflow = 'hidden';
});

function cerrarModalNueva() {
    document.getElementById('modalNuevaNave').classList.remove('active');
    document.body.style.overflow = '';
    document.getElementById('formNuevaNave').reset();
}

async function guardarNuevaNave() {
    const form = document.getElementById('formNuevaNave');
    const formData = new FormData(form);
    
    // Validación
    const codigo = document.getElementById('new_codigo').value.trim();
    const nombre = document.getElementById('new_nombre').value.trim();
    const tipo = document.getElementById('new_tipo').value;
    
    if (!codigo || !nombre || !tipo) {
        alert('Código, nombre y tipo son obligatorios');
        return;
    }
    
    try {
        const response = await fetch('naves.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (e) {
        alert('Error al crear nave: ' + e.message);
    }
}

// ============================================
// MODAL EDITAR NAVE
// ============================================
function abrirModalEditar(id) {
    const data = window['naveData_' + id];
    if (!data) return;
    
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_codigo').value = data.codigo;
    document.getElementById('edit_nombre').value = data.nombre;
    document.getElementById('edit_tipo').value = data.tipo;
    document.getElementById('edit_estado').value = data.estado;
    document.getElementById('edit_hangar').value = data.hangar_nivel;
    document.getElementById('edit_cliente').value = data.cliente_id || 0;
    document.getElementById('edit_imagen_preview').src = '../assets/images/ships/' + data.imagen;
    
    document.getElementById('modalEditar').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function cerrarModal() {
    document.getElementById('modalEditar').classList.remove('active');
    document.body.style.overflow = '';
}

async function guardarNave() {
    const form = document.getElementById('formEditarNave');
    const formData = new FormData(form);
    
    try {
        const response = await fetch('naves.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (e) {
        alert('Error al guardar: ' + e.message);
    }
}

// Cerrar con Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        cerrarModal();
        cerrarModalNueva();
    }
});

// Filtros en tiempo real
document.getElementById('buscarNave')?.addEventListener('input', debounce(() => {
    aplicarFiltros();
}, 300));

['filtroTipo', 'filtroEstado', 'filtroHangar'].forEach(id => {
    document.getElementById(id)?.addEventListener('change', aplicarFiltros);
});

function aplicarFiltros() {
    const params = new URLSearchParams();
    const buscar = document.getElementById('buscarNave').value;
    const tipo = document.getElementById('filtroTipo').value;
    const estado = document.getElementById('filtroEstado').value;
    const hangar = document.getElementById('filtroHangar').value;
    
    if (buscar) params.set('buscar', buscar);
    if (tipo) params.set('tipo', tipo);
    if (estado) params.set('estado', estado);
    if (hangar) params.set('hangar', hangar);
    
    location.href = 'naves.php?' + params.toString();
}

function debounce(fn, ms) {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn(...args), ms);
    };
}
</script>

<?php require_once '../includes/footer.php'; ?>
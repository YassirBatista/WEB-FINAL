<?php
// NEXUS STELLAR SHIPYARDS — Centro de Reparaciones (Año 2926)
// VERSIÓN LIMPIA — Sin estilos inline, todo por clases CSS

require_once '../includes/functions.php';
requireAdmin();

$pageTitle = 'Reparaciones';

// ═══════════════════════════════════════════════════════
// PROCESAR ACCIONES POST
// ═══════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_estado') {
        $id = (int)($_POST['id'] ?? 0);
        $estado = $_POST['estado'] ?? '';
        if ($id > 0 && !empty($estado)) {
            try {
                $pdo->prepare("UPDATE reparaciones SET estado = ? WHERE id = ?")
                    ->execute([$estado, $id]);
            } catch (PDOException $e) {
                error_log("NEXUS ERROR update_estado: " . $e->getMessage());
            }
        }
        header('Location: reparaciones.php');
        exit;
    }
    
    if ($action === 'asignar_tecnico') {
        $id = (int)($_POST['id'] ?? 0);
        $tecnico_id = (int)($_POST['tecnico_id'] ?? 0);
        if ($id > 0 && $tecnico_id > 0) {
            try {
                $pdo->prepare("UPDATE reparaciones SET tecnico_id = ? WHERE id = ?")
                    ->execute([$tecnico_id, $id]);
            } catch (PDOException $e) {
                error_log("NEXUS ERROR asignar_tecnico: " . $e->getMessage());
            }
        }
        header('Location: reparaciones.php');
        exit;
    }
}

// ═══════════════════════════════════════════════════════
// FILTROS GET
// ═══════════════════════════════════════════════════════
$filtroEstado = $_GET['estado'] ?? '';
$filtroTecnico = $_GET['tecnico'] ?? '';
$vista = $_GET['vista'] ?? 'visual';

if (!in_array($vista, ['visual', 'terminal'])) {
    $vista = 'visual';
}

// ═══════════════════════════════════════════════════════
// CONSULTA 1: TODAS las reparaciones (ESTADÍSTICAS GLOBALES)
// ═══════════════════════════════════════════════════════
try {
    $sqlStats = "SELECT r.estado, r.costo
                 FROM reparaciones r
                 LEFT JOIN naves n ON r.nave_id = n.id
                 WHERE 1=1";
    $stmtStats = $pdo->query($sqlStats);
    $todasReparaciones = $stmtStats->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("NEXUS ERROR stats query: " . $e->getMessage());
    $todasReparaciones = [];
}

$stats = [
    'total'       => 0,
    'en_proceso'  => 0,
    'esperando'   => 0,
    'pendientes'  => 0,
    'completadas' => 0,
    'eficiencia'  => '94.7%',
    'tiempo_prom' => '4.2 HRS',
];

if (is_array($todasReparaciones)) {
    $stats['total']       = count($todasReparaciones);
    $stats['en_proceso']  = count(array_filter($todasReparaciones, fn($r) => in_array($r['estado'] ?? '', ['en_reparacion', 'diagnostico', 'en_pruebas'])));
    $stats['esperando']   = count(array_filter($todasReparaciones, fn($r) => ($r['estado'] ?? '') === 'esperando_piezas'));
    $stats['pendientes']  = count(array_filter($todasReparaciones, fn($r) => ($r['estado'] ?? '') === 'pendiente'));
    $stats['completadas'] = count(array_filter($todasReparaciones, fn($r) => ($r['estado'] ?? '') === 'completada'));
}

// ═══════════════════════════════════════════════════════
// CONSULTA 2: Reparaciones FILTRADAS
// ═══════════════════════════════════════════════════════
try {
    $sql = "SELECT r.*, 
            n.nombre as nave_nombre, n.tipo as nave_tipo, n.imagen as nave_imagen, n.codigo as nave_codigo,
            u.nombre as cliente_nombre, u.faccion_id,
            f.color as faccion_color,
            t.nombre as tecnico_nombre, t.estado as tecnico_estado, t.avatar as tecnico_avatar
            FROM reparaciones r
            LEFT JOIN naves n ON r.nave_id = n.id
            LEFT JOIN usuarios u ON n.cliente_id = u.id
            LEFT JOIN facciones f ON u.faccion_id = f.id
            LEFT JOIN tecnicos t ON r.tecnico_id = t.id
            WHERE 1=1";
    $params = [];

    if ($filtroEstado) {
        $sql .= " AND r.estado = ?";
        $params[] = $filtroEstado;
    }
    if ($filtroTecnico) {
        $sql .= " AND r.tecnico_id = ?";
        $params[] = (int)$filtroTecnico;
    }

    $sql .= " ORDER BY 
        FIELD(r.estado, 'en_reparacion', 'esperando_piezas', 'diagnostico', 'en_pruebas', 'pendiente', 'completada'),
        r.fecha_inicio DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reparaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("NEXUS ERROR reparaciones query: " . $e->getMessage());
    $reparaciones = [];
}

if (!is_array($reparaciones)) {
    $reparaciones = [];
}

// ═══════════════════════════════════════════════════════
// DATOS PARA FILTROS
// ═══════════════════════════════════════════════════════
$estadosRep = ['pendiente', 'diagnostico', 'en_reparacion', 'esperando_piezas', 'en_pruebas', 'completada'];

try {
    $tecnicos = $pdo->query("SELECT id, nombre FROM tecnicos WHERE estado != 'descansando' ORDER BY nombre")
                     ->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("NEXUS ERROR tecnicos query: " . $e->getMessage());
    $tecnicos = [];
}
if (!is_array($tecnicos)) {
    $tecnicos = [];
}

// ═══════════════════════════════════════════════════════
// FUNCIONES AUXILIARES LOCALES
// ═══════════════════════════════════════════════════════
if (!function_exists('getEstadoColor')) {
    function getEstadoColor(string $estado): array {
        $map = [
            'pendiente'        => ['text' => 'PENDIENTE',        'color' => '#E8EDF5', 'class' => 'estado-pendiente'],
            'diagnostico'      => ['text' => 'DIAGNÓSTICO',      'color' => '#00D9FF', 'class' => 'estado-diagnostico'],
            'en_reparacion'    => ['text' => 'EN REPARACIÓN',    'color' => '#00FF9C', 'class' => 'estado-reparacion'],
            'esperando_piezas' => ['text' => 'ESPERANDO PIEZAS', 'color' => '#FFB800', 'class' => 'estado-espera'],
            'en_pruebas'       => ['text' => 'EN PRUEBAS',       'color' => '#00FF9C', 'class' => 'estado-pruebas'],
            'completada'       => ['text' => 'COMPLETADA',       'color' => '#00FF9C', 'class' => 'estado-operativa'],
        ];
        return $map[$estado] ?? ['text' => strtoupper($estado), 'color' => '#00D9FF', 'class' => 'estado-diagnostico'];
    }
}

if (!function_exists('formatTime')) {
    function formatTime(int $seconds): string {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}

if (!function_exists('formatCredits')) {
    function formatCredits(int $amount): string {
        return number_format($amount, 0, ',', '.') . ' Cr';
    }
}

$usuario = getUsuarioActual();
require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <header class="top-header">
        <div class="header-title">TALLER NEXUS — REPARACIONES</div>
        <div class="header-right">
            <div class="header-stat">
                <div class="label">EN PROCESO</div>
                <div class="value stat-value-warning"><?php echo (int)$stats['en_proceso']; ?></div>
            </div>
            <div class="header-stat">
                <div class="label">ESPERANDO</div>
                <div class="value stat-value-cyan"><?php echo (int)$stats['esperando']; ?></div>
            </div>
            <div class="header-stat">
                <div class="label">PENDIENTES</div>
                <div class="value stat-value-critical"><?php echo (int)$stats['pendientes']; ?></div>
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
        
        <div class="taller-header">
            <div class="taller-header-left">
                <h1 class="taller-titulo">CENTRO DE REPARACIONES</h1>
                <div class="taller-subtitulo">
                    <span class="taller-punto"></span>
                    TALLER ORBITAL NEXUS — SECTOR 7G — AÑO 2926
                </div>
            </div>
            <div class="taller-toggle-vista">
                <a href="?vista=visual<?php echo $filtroEstado ? '&estado='.urlencode($filtroEstado) : ''; ?><?php echo $filtroTecnico ? '&tecnico='.urlencode($filtroTecnico) : ''; ?>" 
                   class="taller-toggle-btn <?php echo $vista === 'visual' ? 'active' : ''; ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    VISUAL
                </a>
                <a href="?vista=terminal<?php echo $filtroEstado ? '&estado='.urlencode($filtroEstado) : ''; ?><?php echo $filtroTecnico ? '&tecnico='.urlencode($filtroTecnico) : ''; ?>" 
                   class="taller-toggle-btn <?php echo $vista === 'terminal' ? 'active' : ''; ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>
                    </svg>
                    TERMINAL
                </a>
            </div>
        </div>

        <div class="taller-status-bar">
            <div class="taller-status-item">
                <span class="taller-status-label">CAPACIDAD</span>
                <span class="taller-status-value"><?php echo (int)$stats['total']; ?> / 50</span>
                <div class="taller-status-barra">
                    <div class="taller-status-fill" style="width:<?php echo min(100, max(0, ($stats['total']/50)*100)); ?>%"></div>
                </div>
            </div>
            <div class="taller-status-item">
                <span class="taller-status-label">EFICIENCIA</span>
                <span class="taller-status-value text-operational"><?php echo htmlspecialchars($stats['eficiencia']); ?></span>
            </div>
            <div class="taller-status-item">
                <span class="taller-status-label">TIEMPO PROMEDIO</span>
                <span class="taller-status-value"><?php echo htmlspecialchars($stats['tiempo_prom']); ?></span>
            </div>
            <div class="taller-status-item">
                <span class="taller-status-label">ALERTAS</span>
                <span class="taller-status-value <?php echo ((int)$stats['esperando'] > 0) ? 'stat-value-critical' : 'text-operational'; ?>">
                    <?php echo (int)$stats['esperando']; ?> CRÍTICAS
                </span>
            </div>
        </div>

        <div class="naves-filtros">
            <select class="filtro-select" onchange="location.href='?vista=<?php echo $vista; ?>&estado='+encodeURIComponent(this.value)<?php echo $filtroTecnico ? "+'&tecnico=".urlencode($filtroTecnico)."'" : ''; ?>">
                <option value="">Todos los estados</option>
                <?php 
                $conteoPorEstado = [];
                foreach ($todasReparaciones as $r) {
                    $e = $r['estado'] ?? 'desconocido';
                    $conteoPorEstado[$e] = ($conteoPorEstado[$e] ?? 0) + 1;
                }
                foreach($estadosRep as $e): 
                    $est = getEstadoColor($e);
                    $count = $conteoPorEstado[$e] ?? 0;
                ?>
                <option value="<?php echo htmlspecialchars($e); ?>" <?php echo $filtroEstado === $e ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($est['text']); ?> (<?php echo (int)$count; ?>)
                </option>
                <?php endforeach; ?>
            </select>
            
            <select class="filtro-select" onchange="location.href='?vista=<?php echo $vista; ?>&tecnico='+encodeURIComponent(this.value)<?php echo $filtroEstado ? "+'&estado=".urlencode($filtroEstado)."'" : ''; ?>">
                <option value="">Todos los técnicos</option>
                <?php foreach($tecnicos as $t): ?>
                <option value="<?php echo (int)$t['id']; ?>" <?php echo $filtroTecnico == $t['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($t['nombre']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            
           
        </div>

        <?php if(count($reparaciones) === 0): ?>
        <div class="taller-empty">
            <div class="taller-empty-holograma">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="var(--neon-cyan-dim)" stroke-width="0.5">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                </svg>
            </div>
            <h3>SIN ÓRDENES ACTIVAS</h3>
            <p>El taller orbital se encuentra en modo de espera.<br>Sistemas operativos al 100%.</p>
        </div>

        <?php elseif($vista === 'visual'): ?>
        <div class="taller-grid-visual">
            <?php foreach($reparaciones as $rep): 
                $estado = getEstadoColor($rep['estado'] ?? 'pendiente');
                $imgNave = !empty($rep['nave_imagen']) ? $rep['nave_imagen'] : 'default_ship.jpg';
                $colorFaccion = $rep['faccion_color'] ?? 'var(--neon-cyan)';
                $progreso = isset($rep['progreso']) ? (int)$rep['progreso'] : rand(15, 95);
                $tiempoRestante = isset($rep['tiempo_restante']) ? (int)$rep['tiempo_restante'] : rand(300, 7200);
                $costo = isset($rep['costo']) ? (int)$rep['costo'] : rand(100000, 900000);
            ?>
            <div class="taller-card-holograma">
                <div class="taller-scanline"></div>
                
                <div class="taller-holo-header">
                    <span class="taller-holo-estado <?php echo htmlspecialchars($estado['class']); ?>">
                        <span class="taller-holo-pulso" style="background:<?php echo htmlspecialchars($estado['color']); ?>"></span>
                        <?php echo htmlspecialchars($estado['text']); ?>
                    </span>
                    <span class="taller-holo-id">ORD-<?php echo str_pad((int)$rep['id'], 4, '0', STR_PAD_LEFT); ?></span>
                </div>
                
                <div class="taller-holo-imagen" style="background-image:url('../assets/images/ships/<?php echo htmlspecialchars($imgNave); ?>')">
                    <div class="taller-holo-overlay"></div>
                    <div class="taller-holo-glitch"></div>
                </div>
                
                <div class="taller-holo-info">
                    <h3 class="taller-holo-nombre"><?php echo htmlspecialchars($rep['nave_nombre'] ?? 'Nave Desconocida'); ?></h3>
                    <span class="taller-holo-tipo"><?php echo strtoupper(htmlspecialchars($rep['nave_tipo'] ?? 'DESCONOCIDO')); ?></span>
                    
                    <div class="taller-holo-progreso">
                        <div class="taller-holo-progreso-label">PROGRESO DEL SISTEMA</div>
                        <div class="taller-holo-barra">
                            <div class="taller-holo-fill" style="width:<?php echo (int)$progreso; ?>%;background:<?php echo htmlspecialchars($estado['color']); ?>"></div>
                        </div>
                        <span class="taller-holo-porcentaje" style="color:<?php echo htmlspecialchars($estado['color']); ?>"><?php echo (int)$progreso; ?>%</span>
                    </div>
                    
                    <div class="taller-holo-datos">
                        <div class="taller-holo-dato">
                            <span class="taller-holo-dato-label">CLIENTE</span>
                            <span class="taller-holo-dato-value">
                                <span class="taller-holo-faccion" style="background:<?php echo htmlspecialchars($colorFaccion); ?>"></span>
                                <?php echo htmlspecialchars($rep['cliente_nombre'] ?? 'SIN ASIGNAR'); ?>
                            </span>
                        </div>
                        <div class="taller-holo-dato">
                            <span class="taller-holo-dato-label">TÉCNICO</span>
                            <span class="taller-holo-dato-value">
                                <?php if(!empty($rep['tecnico_nombre'])): ?>
                                    <span class="taller-holo-tecnico-status <?php echo htmlspecialchars($rep['tecnico_estado'] ?? ''); ?>"></span>
                                    <?php echo htmlspecialchars($rep['tecnico_nombre']); ?>
                                <?php else: ?>
                                    <span class="text-dimmed">SIN ASIGNAR</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="taller-holo-dato">
                            <span class="taller-holo-dato-label">RESTANTE</span>
                            <span class="taller-holo-dato-value timer" data-seconds="<?php echo (int)$tiempoRestante; ?>">
                                <?php echo formatTime((int)$tiempoRestante); ?>
                            </span>
                        </div>
                        <div class="taller-holo-dato">
                            <span class="taller-holo-dato-label">COSTO</span>
                            <span class="taller-holo-dato-value"><?php echo formatCredits((int)$costo); ?></span>
                        </div>
                    </div>
                    
                    <div class="taller-holo-acciones">
                        <a href="reparaciones.php?ver=<?php echo (int)$rep['id']; ?>" class="btn-nexus btn-nexus-sm-flex">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            DIAGNÓSTICO
                        </a>
                        <form method="POST" style="flex:1">
                            <input type="hidden" name="action" value="update_estado">
                            <input type="hidden" name="id" value="<?php echo (int)$rep['id']; ?>">
                            <input type="hidden" name="estado" value="completada">
                            <button type="submit" class="btn-nexus btn-nexus-success">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                COMPLETAR
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <div class="taller-terminal">
            <div class="taller-terminal-header">
                <span class="taller-terminal-prompt">root@nexus-taller:~$</span>
                <span class="taller-terminal-cmd">./listar_reparaciones --estado=<?php echo htmlspecialchars($filtroEstado ?: 'todos'); ?> --formato=tabla</span>
            </div>
            
            <div class="taller-terminal-table">
                <div class="taller-terminal-row taller-terminal-header-row">
                    <span class="taller-terminal-col terminal-col-id">ID_ORDEN</span>
                    <span class="taller-terminal-col terminal-col-nave">NAVE</span>
                    <span class="taller-terminal-col terminal-col-cliente">CLIENTE</span>
                    <span class="taller-terminal-col terminal-col-estado">ESTADO</span>
                    <span class="taller-terminal-col terminal-col-tecnico">TECNICO</span>
                    <span class="taller-terminal-col terminal-col-progreso">PROGRESO</span>
                    <span class="taller-terminal-col terminal-col-costo">COSTO</span>
                    <span class="taller-terminal-col terminal-col-acciones">ACCIONES</span>
                </div>
                
                <?php foreach($reparaciones as $rep): 
                    $estado = getEstadoColor($rep['estado'] ?? 'pendiente');
                    $progreso = isset($rep['progreso']) ? (int)$rep['progreso'] : rand(15, 95);
                    $costo = isset($rep['costo']) ? (int)$rep['costo'] : rand(100000, 900000);
                ?>
                <div class="taller-terminal-row">
                    <span class="taller-terminal-col terminal-col-id">ORD-<?php echo str_pad((int)$rep['id'], 4, '0', STR_PAD_LEFT); ?></span>
                    <span class="taller-terminal-col terminal-col-nave"><?php echo htmlspecialchars($rep['nave_nombre'] ?? 'N/A'); ?></span>
                    <span class="taller-terminal-col terminal-col-cliente"><?php echo htmlspecialchars($rep['cliente_nombre'] ?? 'N/A'); ?></span>
                    <span class="taller-terminal-col terminal-col-estado" style="color:<?php echo htmlspecialchars($estado['color']); ?>">
                        [<?php echo htmlspecialchars($estado['text']); ?>]
                    </span>
                    <span class="taller-terminal-col terminal-col-tecnico"><?php echo htmlspecialchars($rep['tecnico_nombre'] ?? 'SIN_ASIGNAR'); ?></span>
                    <span class="taller-terminal-col terminal-col-progreso" style="color:<?php echo htmlspecialchars($estado['color']); ?>">
                        <?php echo (int)$progreso; ?>%
                    </span>
                    <span class="taller-terminal-col terminal-col-costo"><?php echo formatCredits((int)$costo); ?></span>
                    <span class="taller-terminal-col terminal-col-acciones terminal-actions">
                        <a href="reparaciones.php?ver=<?php echo (int)$rep['id']; ?>" class="terminal-link-ver">[VER]</a>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="update_estado">
                            <input type="hidden" name="id" value="<?php echo (int)$rep['id']; ?>">
                            <input type="hidden" name="estado" value="completada">
                            <button type="submit" class="terminal-btn-completar">[COMPLETAR]</button>
                        </form>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="taller-terminal-footer">
                <span class="taller-terminal-prompt">root@nexus-taller:~$</span>
                <span class="taller-terminal-cursor">_</span>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
function updateTimers() {
    document.querySelectorAll('.timer').forEach(el => {
        let seconds = parseInt(el.dataset.seconds);
        if (seconds > 0) {
            seconds--;
            el.dataset.seconds = seconds;
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = seconds % 60;
            el.textContent = String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
        }
    });
}
setInterval(updateTimers, 1000);
</script>

<?php require_once '../includes/footer.php'; ?>
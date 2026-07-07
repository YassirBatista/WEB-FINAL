<?php
/**
 * NEXUS STELLAR SHIPYARDS — Técnicos
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db_connect.php';
requireAdmin();
$pageTitle = 'Técnicos';

// Estadísticas
$stats = [
    'total'        => 0,
    'disponible'   => 0,
    'ocupado'      => 0,
    'descansando'  => 0,
];

// Filtros
$estadoFiltro = $_GET['estado'] ?? '';
$rangoFiltro  = $_GET['rango'] ?? '';
$busqueda     = $_GET['busqueda'] ?? '';
$pagina       = max(1, intval($_GET['pagina'] ?? 1));
$porPagina    = 12;

// Construir query
$where = ['1=1'];
$params = [];

if (!empty($estadoFiltro)) {
    $where[] = "estado = ?";
    $params[] = $estadoFiltro;
}
if (!empty($rangoFiltro)) {
    $where[] = "rango = ?";
    $params[] = $rangoFiltro;
}
if (!empty($busqueda)) {
    $where[] = "(nombre LIKE ? OR especialidad LIKE ?)";
    $busq = '%' . $busqueda . '%';
    $params = array_merge($params, [$busq, $busq]);
}

$whereStr = implode(' AND ', $where);

// Total
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM tecnicos WHERE $whereStr");
$stmtTotal->execute($params);
$total = $stmtTotal->fetchColumn();

// Datos paginados
$offset = ($pagina - 1) * $porPagina;

$stmt = $pdo->prepare("
    SELECT t.*,
           (SELECT COUNT(*) FROM reparaciones WHERE tecnico_id = t.id AND estado != 'completada') as reparaciones_activas
    FROM tecnicos t
    WHERE $whereStr
    ORDER BY FIELD(t.estado, 'en_reparacion', 'en_diagnostico', 'disponible', 'descansando'), t.nivel DESC, t.nombre
    LIMIT $porPagina OFFSET $offset
");
$stmt->execute($params);
$tecnicos = $stmt->fetchAll();

$totalPaginas = ceil($total / $porPagina);

// Calcular stats reales
$stats['total'] = $pdo->query("SELECT COUNT(*) FROM tecnicos")->fetchColumn();
$stats['disponible'] = $pdo->query("SELECT COUNT(*) FROM tecnicos WHERE estado = 'disponible'")->fetchColumn();
$stats['ocupado'] = $pdo->query("SELECT COUNT(*) FROM tecnicos WHERE estado IN ('en_reparacion', 'en_diagnostico')")->fetchColumn();
$stats['descansando'] = $pdo->query("SELECT COUNT(*) FROM tecnicos WHERE estado = 'descansando'")->fetchColumn();

// Rangos para filtro
$rangos = ['cadete', 'tecnico', 'especialista', 'ingeniero_jefe'];

// Helper para clase de rango
function getRangoClass($rango) {
    return str_replace('_', '-', $rango);
}

// Helper para clase de estado
function getEstadoClass($estado) {
    return str_replace('_', '-', $estado);
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_admin.php';
?>

<link rel="stylesheet" href="/nexus_stellar_shipyards/assets/css/tecnicos.css">

<main class="main-content">
    <div class="content-wrapper">

        <!-- Header -->
        <div class="tecnicos-header">
            <h1>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                Técnicos
            </h1>
            <div class="tecnicos-actions">
                
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="tecnicos-stats">
            <div class="tecnicos-stat-card">
                <div class="tecnicos-stat-icon total">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div class="tecnicos-stat-info">
                    <div class="tecnicos-stat-value"><?php echo number_format($stats['total']); ?></div>
                    <div class="tecnicos-stat-label">Total Técnicos</div>
                </div>
            </div>
            <div class="tecnicos-stat-card">
                <div class="tecnicos-stat-icon disponible">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div class="tecnicos-stat-info">
                    <div class="tecnicos-stat-value"><?php echo number_format($stats['disponible']); ?></div>
                    <div class="tecnicos-stat-label">Disponibles</div>
                </div>
            </div>
            <div class="tecnicos-stat-card">
                <div class="tecnicos-stat-icon ocupado">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                    </svg>
                </div>
                <div class="tecnicos-stat-info">
                    <div class="tecnicos-stat-value"><?php echo number_format($stats['ocupado']); ?></div>
                    <div class="tecnicos-stat-label">Ocupados</div>
                </div>
            </div>
            <div class="tecnicos-stat-card">
                <div class="tecnicos-stat-icon descansando">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                </div>
                <div class="tecnicos-stat-info">
                    <div class="tecnicos-stat-value"><?php echo number_format($stats['descansando']); ?></div>
                    <div class="tecnicos-stat-label">Descansando</div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <form method="GET" class="tecnicos-filters">
            <div class="filter-group">
                <label for="filtroEstado">Estado:</label>
                <select name="estado" id="filtroEstado" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <option value="disponible" <?php echo $estadoFiltro === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                    <option value="en_reparacion" <?php echo $estadoFiltro === 'en_reparacion' ? 'selected' : ''; ?>>En Reparación</option>
                    <option value="en_diagnostico" <?php echo $estadoFiltro === 'en_diagnostico' ? 'selected' : ''; ?>>En Diagnóstico</option>
                    <option value="descansando" <?php echo $estadoFiltro === 'descansando' ? 'selected' : ''; ?>>Descansando</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="filtroRango">Rango:</label>
                <select name="rango" id="filtroRango" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <?php foreach ($rangos as $r): ?>
                        <option value="<?php echo $r; ?>" <?php echo $rangoFiltro === $r ? 'selected' : ''; ?>>
                            <?php echo ucwords(str_replace('_', ' ', $r)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="busquedaTecnico">Buscar:</label>
                <input type="text" name="busqueda" id="busquedaTecnico" placeholder="Nombre o especialidad..." value="<?php echo htmlspecialchars($busqueda); ?>">
            </div>
        </form>

        <?php if (empty($tecnicos)): ?>
            <div class="tecnicos-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <p>No se encontraron técnicos.</p>
            </div>
        <?php else: ?>

            <!-- Grid de técnicos -->
            <div class="tecnicos-grid">
                <?php foreach ($tecnicos as $tecnico):
                    $estadoClass = getEstadoClass($tecnico['estado']);
                    $rangoClass = getRangoClass($tecnico['rango']);
                    $avatarPath = !empty($tecnico['avatar']) 
                        ? '/nexus_stellar_shipyards/assets/images/avatars/' . htmlspecialchars($tecnico['avatar'])
                        : '';
                    $nivelPorcentaje = min(100, ($tecnico['nivel'] / 5) * 100);
                ?>
                    <div class="tecnico-card">
                        <div class="tecnico-card-status-bar <?php echo $estadoClass; ?>"></div>
                        
                        <div class="tecnico-card-estado <?php echo $estadoClass; ?>">
                            <?php echo str_replace('_', ' ', ucfirst($tecnico['estado'])); ?>
                        </div>

                        <div class="tecnico-card-header">
                            <?php if ($avatarPath): ?>
                                <img src="<?php echo $avatarPath; ?>" 
                                     alt="<?php echo htmlspecialchars($tecnico['nombre']); ?>" 
                                     class="tecnico-avatar <?php echo $estadoClass; ?>"
                                     onerror="this.src='/nexus_stellar_shipyards/assets/images/avatars/tech_default.png'">
                            <?php else: ?>
                                <div class="tecnico-avatar <?php echo $estadoClass; ?>" style="display:flex;align-items:center;justify-content:center;background:var(--bg-header);color:var(--text-secondary);font-size:24px;font-weight:700;">
                                    <?php echo strtoupper(substr($tecnico['nombre'], 0, 2)); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="tecnico-card-info">
                                <div class="tecnico-card-name"><?php echo htmlspecialchars($tecnico['nombre']); ?></div>
                                <div class="tecnico-card-rango <?php echo $rangoClass; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $tecnico['rango'])); ?>
                                </div>
                                <div class="tecnico-card-especialidad">
                                    <?php echo htmlspecialchars($tecnico['especialidad'] ?? 'Sin especialidad'); ?>
                                </div>
                            </div>
                        </div>

                        <div class="tecnico-card-stats">
                            <div class="tecnico-card-stat">
                                <span class="tecnico-card-stat-value"><?php echo number_format($tecnico['reparaciones_completadas']); ?></span>
                                <span class="tecnico-card-stat-label">Reparaciones</span>
                            </div>
                            <div class="tecnico-card-stat">
                                <span class="tecnico-card-stat-value"><?php echo number_format($tecnico['reparaciones_activas']); ?></span>
                                <span class="tecnico-card-stat-label">Activas</span>
                            </div>
                            <div class="tecnico-card-stat">
                                <span class="tecnico-card-stat-value"><?php echo number_format($tecnico['nivel']); ?></span>
                                <span class="tecnico-card-stat-label">Nivel</span>
                            </div>
                        </div>

                        <div class="tecnico-card-nivel">
                            <div class="tecnico-card-nivel-header">
                                <span class="tecnico-card-nivel-label">Progreso de Nivel</span>
                                <span class="tecnico-card-nivel-value"><?php echo round($nivelPorcentaje); ?>%</span>
                            </div>
                            <div class="tecnico-card-nivel-bar">
                                <div class="tecnico-card-nivel-fill" style="width:<?php echo $nivelPorcentaje; ?>%"></div>
                            </div>
                        </div>

                        <div class="tecnico-card-horas">
                            <span class="tecnico-card-horas-label">Horas de Servicio</span>
                            <span class="tecnico-card-horas-value"><?php echo number_format($tecnico['horas_servicio']); ?>h</span>
                        </div>

                        <div class="tecnico-card-actions">
                            <button type="button" class="btn-icon" onclick="verTecnico(<?php echo $tecnico['id']; ?>)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                Ver
                            </button>
                            <button type="button" class="btn-icon" onclick="editarTecnico(<?php echo $tecnico['id']; ?>)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                                Editar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Paginación -->
            <?php if ($totalPaginas > 1): ?>
                <div class="tecnicos-pagination">
                    <button <?php echo $pagina <= 1 ? 'disabled' : ''; ?> onclick="location.href='?pagina=<?php echo $pagina - 1; ?>&estado=<?php echo urlencode($estadoFiltro); ?>&rango=<?php echo urlencode($rangoFiltro); ?>&busqueda=<?php echo urlencode($busqueda); ?>'">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                    </button>
                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <button class="<?php echo $i === $pagina ? 'active' : ''; ?>" onclick="location.href='?pagina=<?php echo $i; ?>&estado=<?php echo urlencode($estadoFiltro); ?>&rango=<?php echo urlencode($rangoFiltro); ?>&busqueda=<?php echo urlencode($busqueda); ?>'">
                            <?php echo $i; ?>
                        </button>
                    <?php endfor; ?>
                    <button <?php echo $pagina >= $totalPaginas ? 'disabled' : ''; ?> onclick="location.href='?pagina=<?php echo $pagina + 1; ?>&estado=<?php echo urlencode($estadoFiltro); ?>&rango=<?php echo urlencode($rangoFiltro); ?>&busqueda=<?php echo urlencode($busqueda); ?>'">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </button>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</main>

<script>
    function verTecnico(id) {
        window.location.href = 'tecnicos_ver.php?id=' + id;
    }

    function editarTecnico(id) {
        window.location.href = 'tecnicos_editar.php?id=' + id;
    }

    document.getElementById('btnNuevoTecnico').addEventListener('click', function() {
        window.location.href = 'tecnicos_nuevo.php';
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
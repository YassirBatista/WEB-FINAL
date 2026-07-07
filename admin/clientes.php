<?php
/**
 * NEXUS STELLAR SHIPYARDS — Clientes
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db_connect.php';
requireAdmin();

$pageTitle = 'Clientes';

// Estadísticas
$stats = [
    'total'    => 0,
    'activos'  => 0,
    'solaris'  => 0,
    'umbra'    => 0,
];

// Filtros
$faccionFiltro = $_GET['faccion'] ?? '';
$busqueda      = $_GET['busqueda'] ?? '';
$vista         = $_GET['vista'] ?? 'grid';
$pagina        = max(1, intval($_GET['pagina'] ?? 1));
$porPagina     = 12;

// Construir query
$where = ['u.rol = ?'];
$params = ['cliente'];

if (!empty($faccionFiltro)) {
    $where[] = "u.faccion_id = ?";
    $params[] = $faccionFiltro;
}
if (!empty($busqueda)) {
    $where[] = "(u.nombre LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $busq = '%' . $busqueda . '%';
    $params = array_merge($params, [$busq, $busq, $busq]);
}

$whereStr = implode(' AND ', $where);

// Total
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM usuarios u WHERE $whereStr");
$stmtTotal->execute($params);
$total = $stmtTotal->fetchColumn();

// Datos paginados
$offset = ($pagina - 1) * $porPagina;

$stmt = $pdo->prepare("
    SELECT u.*, 
           f.nombre as faccion_nombre, 
           f.color as faccion_color,
           (SELECT COUNT(*) FROM naves WHERE cliente_id = u.id) as total_naves,
           (SELECT COUNT(*) FROM ordenes WHERE cliente_id = u.id) as total_ordenes,
           (SELECT COUNT(*) FROM reparaciones r JOIN naves n ON r.nave_id = n.id WHERE n.cliente_id = u.id AND r.estado != 'completada') as reparaciones_activas
    FROM usuarios u
    LEFT JOIN facciones f ON u.faccion_id = f.id
    WHERE $whereStr
    ORDER BY u.created_at DESC
    LIMIT $porPagina OFFSET $offset
");
$stmt->execute($params);
$clientes = $stmt->fetchAll();

$totalPaginas = ceil($total / $porPagina);

// Facciones para filtro
$facciones = $pdo->query("SELECT id, nombre FROM facciones ORDER BY nombre")->fetchAll();

// Helper para clase de facción
function getFaccionClass($faccion) {
    if (stripos($faccion, 'solaris') !== false) return 'solaris';
    if (stripos($faccion, 'umbra') !== false) return 'umbra';
    if (stripos($faccion, 'corporaci') !== false || stripos($faccion, 'independ') !== false) return 'corp';
    if (stripos($faccion, 'mercenario') !== false) return 'merc';
    if (stripos($faccion, 'liga') !== false) return 'liga';
    return '';
}

// Calcular stats reales
$stats['total'] = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'cliente'")->fetchColumn();
$stats['activos'] = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'cliente' AND activo = 1")->fetchColumn();
$stats['solaris'] = $pdo->query("SELECT COUNT(*) FROM usuarios u JOIN facciones f ON u.faccion_id = f.id WHERE u.rol = 'cliente' AND f.nombre LIKE '%Solaris%'")->fetchColumn();
$stats['umbra'] = $pdo->query("SELECT COUNT(*) FROM usuarios u JOIN facciones f ON u.faccion_id = f.id WHERE u.rol = 'cliente' AND f.nombre LIKE '%Umbra%'")->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_admin.php';
?>

<link rel="stylesheet" href="/nexus_stellar_shipyards/assets/css/clientes.css">

<main class="main-content">
    <div class="content-wrapper">

        <!-- Header -->
        <div class="clientes-header">
            <h1>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Clientes
            </h1>
            <div class="clientes-actions">
                <div class="clientes-view-toggle">
                    <button type="button" class="<?php echo $vista === 'grid' ? 'active' : ''; ?>" onclick="location.href='?vista=grid&faccion=<?php echo urlencode($faccionFiltro); ?>&busqueda=<?php echo urlencode($busqueda); ?>'" title="Vista grid">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"/>
                            <rect x="14" y="3" width="7" height="7"/>
                            <rect x="14" y="14" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                        </svg>
                    </button>
                    <button type="button" class="<?php echo $vista === 'lista' ? 'active' : ''; ?>" onclick="location.href='?vista=lista&faccion=<?php echo urlencode($faccionFiltro); ?>&busqueda=<?php echo urlencode($busqueda); ?>'" title="Vista lista">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="8" y1="6" x2="21" y2="6"/>
                            <line x1="8" y1="12" x2="21" y2="12"/>
                            <line x1="8" y1="18" x2="21" y2="18"/>
                            <line x1="3" y1="6" x2="3.01" y2="6"/>
                            <line x1="3" y1="12" x2="3.01" y2="12"/>
                            <line x1="3" y1="18" x2="3.01" y2="18"/>
                        </svg>
                    </button>
                </div>
                
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="clientes-stats">
            <div class="clientes-stat-card">
                <div class="clientes-stat-icon total">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div class="clientes-stat-info">
                    <div class="clientes-stat-value"><?php echo number_format($stats['total']); ?></div>
                    <div class="clientes-stat-label">Total Clientes</div>
                </div>
            </div>
            <div class="clientes-stat-card">
                <div class="clientes-stat-icon activos">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div class="clientes-stat-info">
                    <div class="clientes-stat-value"><?php echo number_format($stats['activos']); ?></div>
                    <div class="clientes-stat-label">Activos</div>
                </div>
            </div>
            <div class="clientes-stat-card">
                <div class="clientes-stat-icon solaris">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="5"/>
                        <line x1="12" y1="1" x2="12" y2="3"/>
                        <line x1="12" y1="21" x2="12" y2="23"/>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                        <line x1="1" y1="12" x2="3" y2="12"/>
                        <line x1="21" y1="12" x2="23" y2="12"/>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                </div>
                <div class="clientes-stat-info">
                    <div class="clientes-stat-value"><?php echo number_format($stats['solaris']); ?></div>
                    <div class="clientes-stat-label">Imperio Solaris</div>
                </div>
            </div>
            <div class="clientes-stat-card">
                <div class="clientes-stat-icon umbra">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                </div>
                <div class="clientes-stat-info">
                    <div class="clientes-stat-value"><?php echo number_format($stats['umbra']); ?></div>
                    <div class="clientes-stat-label">Confederación Umbra</div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <form method="GET" class="clientes-filters">
            <input type="hidden" name="vista" value="<?php echo htmlspecialchars($vista); ?>">
            <div class="filter-group">
                <label for="filtroFaccion">Facción:</label>
                <select name="faccion" id="filtroFaccion" onchange="this.form.submit()">
                    <option value="">Todas las facciones</option>
                    <?php foreach ($facciones as $f): ?>
                        <option value="<?php echo $f['id']; ?>" <?php echo $faccionFiltro == $f['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($f['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="busquedaCliente">Buscar:</label>
                <input type="text" name="busqueda" id="busquedaCliente" placeholder="Nombre, usuario o email..." value="<?php echo htmlspecialchars($busqueda); ?>">
            </div>
        </form>

        <?php if (empty($clientes)): ?>
            <div class="clientes-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <p>No se encontraron clientes.</p>
            </div>
        <?php else: ?>

            <!-- Vista Grid -->
            <div class="clientes-grid" id="vistaGrid" style="<?php echo $vista === 'lista' ? 'display:none;' : ''; ?>">
                <?php foreach ($clientes as $cliente):
                    $faccionClass = getFaccionClass($cliente['faccion_nombre'] ?? '');
                    $avatarPath = !empty($cliente['avatar']) 
                        ? '/nexus_stellar_shipyards/assets/images/avatars/' . htmlspecialchars($cliente['avatar'])
                        : '';
                ?>
                    <div class="cliente-card">
                        <div class="cliente-card-faction-bar <?php echo $faccionClass; ?>"></div>
                        <div class="cliente-card-status <?php echo $cliente['activo'] ? 'activo' : 'inactivo'; ?>"></div>
                        
                        <div class="cliente-card-header">
                            <?php if ($avatarPath): ?>
                                <img src="<?php echo $avatarPath; ?>" 
                                     alt="<?php echo htmlspecialchars($cliente['nombre']); ?>" 
                                     class="cliente-avatar <?php echo $faccionClass; ?>"
                                     onerror="this.src='/nexus_stellar_shipyards/assets/images/avatars/default_avatar.png'">
                            <?php else: ?>
                                <div class="cliente-avatar <?php echo $faccionClass; ?>" style="display:flex;align-items:center;justify-content:center;background:var(--bg-header);color:var(--text-secondary);font-size:20px;font-weight:700;">
                                    <?php echo strtoupper(substr($cliente['nombre'], 0, 2)); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="cliente-card-info">
                                <div class="cliente-card-name"><?php echo htmlspecialchars($cliente['nombre']); ?></div>
                                <div class="cliente-card-username">@<?php echo htmlspecialchars($cliente['username']); ?></div>
                                <div class="cliente-card-faction <?php echo $faccionClass; ?>">
                                    <span class="cliente-card-faction-dot" style="background:<?php echo htmlspecialchars($cliente['faccion_color'] ?? '#888'); ?>"></span>
                                    <?php echo htmlspecialchars($cliente['faccion_nombre'] ?? 'Sin facción'); ?>
                                </div>
                            </div>
                        </div>

                        <div class="cliente-card-stats">
                            <div class="cliente-card-stat">
                                <span class="cliente-card-stat-value"><?php echo number_format($cliente['total_naves']); ?></span>
                                <span class="cliente-card-stat-label">Naves</span>
                            </div>
                            <div class="cliente-card-stat">
                                <span class="cliente-card-stat-value"><?php echo number_format($cliente['total_ordenes']); ?></span>
                                <span class="cliente-card-stat-label">Órdenes</span>
                            </div>
                            <div class="cliente-card-stat">
                                <span class="cliente-card-stat-value"><?php echo number_format($cliente['reparaciones_activas']); ?></span>
                                <span class="cliente-card-stat-label">Reparaciones</span>
                            </div>
                        </div>

                        <div class="cliente-card-saldo">
                            <span class="cliente-card-saldo-label">Saldo</span>
                            <span class="cliente-card-saldo-value"><?php echo formatCredits($cliente['saldo']); ?></span>
                        </div>

                        <div class="cliente-card-actions">
                            <button type="button" class="btn-icon" onclick="verCliente(<?php echo $cliente['id']; ?>)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                Ver
                            </button>
                            <button type="button" class="btn-icon" onclick="editarCliente(<?php echo $cliente['id']; ?>)">
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

            <!-- Vista Lista -->
            <div class="clientes-list <?php echo $vista === 'lista' ? 'active' : ''; ?>" id="vistaLista">
                <div class="ordenes-table-container">
                    <table class="ordenes-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Usuario</th>
                                <th>Facción</th>
                                <th>Naves</th>
                                <th>Órdenes</th>
                                <th>Reparaciones</th>
                                <th>Saldo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente):
                                $faccionClass = getFaccionClass($cliente['faccion_nombre'] ?? '');
                                $avatarPath = !empty($cliente['avatar']) 
                                    ? '/nexus_stellar_shipyards/assets/images/avatars/' . htmlspecialchars($cliente['avatar'])
                                    : '';
                            ?>
                                <tr>
                                    <td>
                                        <div class="ordenes-avatar-info">
                                            <?php if ($avatarPath): ?>
                                                <img src="<?php echo $avatarPath; ?>" 
                                                     alt="" 
                                                     class="ordenes-avatar-img <?php echo $faccionClass; ?>"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="ordenes-avatar" style="display:none;">
                                                    <?php echo strtoupper(substr($cliente['nombre'], 0, 2)); ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="ordenes-avatar">
                                                    <?php echo strtoupper(substr($cliente['nombre'], 0, 2)); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="name"><?php echo htmlspecialchars($cliente['nombre']); ?></div>
                                        </div>
                                    </td>
                                    <td class="ordenes-id">@<?php echo htmlspecialchars($cliente['username']); ?></td>
                                    <td>
                                        <span class="cliente-card-faction <?php echo $faccionClass; ?>">
                                            <span class="cliente-card-faction-dot" style="background:<?php echo htmlspecialchars($cliente['faccion_color'] ?? '#888'); ?>"></span>
                                            <?php echo htmlspecialchars($cliente['faccion_nombre'] ?? '—'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($cliente['total_naves']); ?></td>
                                    <td><?php echo number_format($cliente['total_ordenes']); ?></td>
                                    <td><?php echo number_format($cliente['reparaciones_activas']); ?></td>
                                    <td class="ordenes-id"><?php echo formatCredits($cliente['saldo']); ?></td>
                                    <td>
                                        <span class="ordenes-estado <?php echo $cliente['activo'] ? 'completada' : 'cancelada'; ?>">
                                            <?php echo $cliente['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="ordenes-acciones">
                                            <button type="button" class="btn-icon" onclick="verCliente(<?php echo $cliente['id']; ?>)" title="Ver">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                    <circle cx="12" cy="12" r="3"/>
                                                </svg>
                                            </button>
                                            <button type="button" class="btn-icon" onclick="editarCliente(<?php echo $cliente['id']; ?>)" title="Editar">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginación -->
            <?php if ($totalPaginas > 1): ?>
                <div class="clientes-pagination">
                    <button <?php echo $pagina <= 1 ? 'disabled' : ''; ?> onclick="location.href='?pagina=<?php echo $pagina - 1; ?>&vista=<?php echo $vista; ?>&faccion=<?php echo urlencode($faccionFiltro); ?>&busqueda=<?php echo urlencode($busqueda); ?>'">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                    </button>
                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <button class="<?php echo $i === $pagina ? 'active' : ''; ?>" onclick="location.href='?pagina=<?php echo $i; ?>&vista=<?php echo $vista; ?>&faccion=<?php echo urlencode($faccionFiltro); ?>&busqueda=<?php echo urlencode($busqueda); ?>'">
                            <?php echo $i; ?>
                        </button>
                    <?php endfor; ?>
                    <button <?php echo $pagina >= $totalPaginas ? 'disabled' : ''; ?> onclick="location.href='?pagina=<?php echo $pagina + 1; ?>&vista=<?php echo $vista; ?>&faccion=<?php echo urlencode($faccionFiltro); ?>&busqueda=<?php echo urlencode($busqueda); ?>'">
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
    function verCliente(id) {
        window.location.href = 'clientes_ver.php?id=' + id;
    }

    function editarCliente(id) {
        window.location.href = 'clientes_editar.php?id=' + id;
    }

    document.getElementById('btnNuevoCliente').addEventListener('click', function() {
        window.location.href = 'clientes_nuevo.php';
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
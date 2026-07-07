<?php
/**
 * NEXUS STELLAR SHIPYARDS — Órdenes de Trabajo
 */
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'Órdenes de Trabajo';

// Estadísticas
$stats = [
    'pendiente'        => contarOrdenesPorEstado('pendiente'),
    'en_reparacion'    => contarOrdenesPorEstado('en_reparacion'),
    'esperando_piezas' => contarOrdenesPorEstado('esperando_piezas'),
    'diagnostico'      => contarOrdenesPorEstado('diagnostico'),
    'en_pruebas'       => contarOrdenesPorEstado('en_pruebas'),
    'completada'       => contarOrdenesPorEstado('completada'),
    'cancelada'        => contarOrdenesPorEstado('cancelada'),
];

// Filtros
$estadoFiltro = $_GET['estado'] ?? '';
$busqueda     = $_GET['busqueda'] ?? '';
$pagina       = max(1, intval($_GET['pagina'] ?? 1));
$porPagina    = 15;

$ordenes = getOrdenes([
    'estado'   => $estadoFiltro,
    'busqueda' => $busqueda,
    'pagina'   => $pagina,
    'limite'   => $porPagina,
]);

$totalPaginas = ceil($ordenes['total'] / $porPagina);

// Helper para clase de facción
function getFaccionClass($faccion) {
    if (stripos($faccion, 'solaris') !== false) return 'faccion-solaris';
    if (stripos($faccion, 'umbra') !== false) return 'faccion-umbra';
    if (stripos($faccion, 'corporaci') !== false || stripos($faccion, 'independ') !== false) return 'faccion-corp';
    if (stripos($faccion, 'mercenario') !== false) return 'faccion-merc';
    if (stripos($faccion, 'liga') !== false) return 'faccion-liga';
    return '';
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_admin.php';
?>

<link rel="stylesheet" href="/nexus_stellar_shipyards/assets/css/ordenes.css">

<main class="main-content">
    <div class="content-wrapper">

        <!-- Header -->
        <div class="ordenes-header">
            <h1>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
                Órdenes de Trabajo
            </h1>
            <div class="ordenes-actions">
                <button type="button" class="ordenes-btn-nueva" id="btnNuevaOrden">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Nueva Orden
                </button>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="ordenes-stats">
            <div class="ordenes-stat-card">
                <div class="ordenes-stat-icon pendiente">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <div class="ordenes-stat-info">
                    <div class="ordenes-stat-value"><?php echo number_format($stats['pendiente']); ?></div>
                    <div class="ordenes-stat-label">Pendientes</div>
                </div>
            </div>
            <div class="ordenes-stat-card">
                <div class="ordenes-stat-icon en-proceso">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                    </svg>
                </div>
                <div class="ordenes-stat-info">
                    <div class="ordenes-stat-value"><?php echo number_format($stats['en_reparacion'] + $stats['diagnostico'] + $stats['en_pruebas'] + $stats['esperando_piezas']); ?></div>
                    <div class="ordenes-stat-label">En Proceso</div>
                </div>
            </div>
            <div class="ordenes-stat-card">
                <div class="ordenes-stat-icon completada">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div class="ordenes-stat-info">
                    <div class="ordenes-stat-value"><?php echo number_format($stats['completada']); ?></div>
                    <div class="ordenes-stat-label">Completadas</div>
                </div>
            </div>
            <div class="ordenes-stat-card">
                <div class="ordenes-stat-icon cancelada">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                </div>
                <div class="ordenes-stat-info">
                    <div class="ordenes-stat-value"><?php echo number_format($stats['cancelada']); ?></div>
                    <div class="ordenes-stat-label">Canceladas</div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <form method="GET" class="ordenes-filters">
            <div class="filter-group">
                <label for="filtroEstado">Estado:</label>
                <select name="estado" id="filtroEstado" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <option value="pendiente" <?php echo $estadoFiltro === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="en_reparacion" <?php echo $estadoFiltro === 'en_reparacion' ? 'selected' : ''; ?>>En Reparación</option>
                    <option value="esperando_piezas" <?php echo $estadoFiltro === 'esperando_piezas' ? 'selected' : ''; ?>>Esperando Piezas</option>
                    <option value="diagnostico" <?php echo $estadoFiltro === 'diagnostico' ? 'selected' : ''; ?>>Diagnóstico</option>
                    <option value="en_pruebas" <?php echo $estadoFiltro === 'en_pruebas' ? 'selected' : ''; ?>>En Pruebas</option>
                    <option value="completada" <?php echo $estadoFiltro === 'completada' ? 'selected' : ''; ?>>Completada</option>
                    <option value="cancelada" <?php echo $estadoFiltro === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="busquedaOrden">Buscar:</label>
                <input type="text" name="busqueda" id="busquedaOrden" placeholder="Código, cliente o nave..." value="<?php echo htmlspecialchars($busqueda); ?>">
            </div>
        </form>

        <!-- Tabla -->
        <div class="ordenes-table-container">
            <table class="ordenes-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Nave</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ordenes['data'])): ?>
                        <tr>
                            <td colspan="7" class="ordenes-empty">
                                No se encontraron órdenes de trabajo.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ordenes['data'] as $orden): 
                            $faccionClass = getFaccionClass($orden['cliente_faccion'] ?? '');
                            $avatarPath = !empty($orden['cliente_avatar']) 
                                ? '/nexus_stellar_shipyards/assets/images/avatars/' . htmlspecialchars($orden['cliente_avatar'])
                                : '';
                        ?>
                            <tr>
                                <td>
                                    <span class="ordenes-id"><?php echo htmlspecialchars($orden['codigo']); ?></span>
                                </td>
                                <td>
                                    <div class="ordenes-avatar-info">
                                        <?php if ($avatarPath): ?>
                                            <img src="<?php echo $avatarPath; ?>" 
                                                 alt="<?php echo htmlspecialchars($orden['cliente_nombre']); ?>" 
                                                 class="ordenes-avatar-img <?php echo $faccionClass; ?>"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="ordenes-avatar" style="display:none;">
                                                <?php echo strtoupper(substr($orden['cliente_nombre'], 0, 2)); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="ordenes-avatar">
                                                <?php echo strtoupper(substr($orden['cliente_nombre'], 0, 2)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="name"><?php echo htmlspecialchars($orden['cliente_nombre']); ?></div>
                                            <div class="meta"><?php echo htmlspecialchars($orden['cliente_faccion'] ?? 'Sin facción'); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($orden['nave_nombre'] ?? '—'); ?></td>
                                <td>
                                    <span class="ordenes-prioridad <?php echo $orden['prioridad']; ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                        </svg>
                                        <?php echo ucfirst($orden['prioridad']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="ordenes-estado <?php echo str_replace('_', '-', $orden['estado']); ?>">
                                        <?php echo str_replace('_', ' ', ucfirst($orden['estado'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($orden['fecha_creacion'])); ?></td>
                                <td>
                                    <div class="ordenes-acciones">
                                        <button type="button" class="btn-icon" title="Ver detalle" onclick="verOrden(<?php echo $orden['id']; ?>)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                            </svg>
                                        </button>
                                        <button type="button" class="btn-icon" title="Editar" onclick="editarOrden(<?php echo $orden['id']; ?>)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($totalPaginas > 1): ?>
            <div class="ordenes-pagination">
                <button <?php echo $pagina <= 1 ? 'disabled' : ''; ?> onclick="location.href='?pagina=<?php echo $pagina - 1; ?>&estado=<?php echo urlencode($estadoFiltro); ?>&busqueda=<?php echo urlencode($busqueda); ?>'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                </button>
                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <button class="<?php echo $i === $pagina ? 'active' : ''; ?>" onclick="location.href='?pagina=<?php echo $i; ?>&estado=<?php echo urlencode($estadoFiltro); ?>&busqueda=<?php echo urlencode($busqueda); ?>'">
                        <?php echo $i; ?>
                    </button>
                <?php endfor; ?>
                <button <?php echo $pagina >= $totalPaginas ? 'disabled' : ''; ?> onclick="location.href='?pagina=<?php echo $pagina + 1; ?>&estado=<?php echo urlencode($estadoFiltro); ?>&busqueda=<?php echo urlencode($busqueda); ?>'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </button>
            </div>
        <?php endif; ?>

    </div>
</main>

<!-- Modal Nueva Orden -->
<div class="ordenes-modal-overlay" id="modalNuevaOrden">
    <div class="ordenes-modal">
        <div class="ordenes-modal-header">
            <h2>Nueva Orden de Trabajo</h2>
            <button type="button" class="ordenes-modal-close" id="btnCerrarModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <form action="ordenes_guardar.php" method="POST" id="formNuevaOrden">
            <div class="ordenes-modal-body">
                <div class="ordenes-form-row">
                    <div class="ordenes-form-group">
                        <label for="clienteId">Cliente</label>
                        <select name="cliente_id" id="clienteId" required>
                            <option value="">Seleccionar cliente...</option>
                            <?php foreach (getClientesActivos() as $cliente): ?>
                                <option value="<?php echo $cliente['id']; ?>"><?php echo htmlspecialchars($cliente['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ordenes-form-group">
                        <label for="naveId">Nave (opcional)</label>
                        <select name="nave_id" id="naveId">
                            <option value="">Seleccionar nave...</option>
                            <?php foreach (getNavesDisponibles() as $nave): ?>
                                <option value="<?php echo $nave['id']; ?>"><?php echo htmlspecialchars($nave['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="ordenes-form-row">
                    <div class="ordenes-form-group">
                        <label for="estadoOrden">Estado Inicial</label>
                        <select name="estado" id="estadoOrden" required>
                            <option value="pendiente" selected>Pendiente</option>
                            <option value="en_reparacion">En Reparación</option>
                            <option value="diagnostico">Diagnóstico</option>
                            <option value="esperando_piezas">Esperando Piezas</option>
                            <option value="en_pruebas">En Pruebas</option>
                            <option value="completada">Completada</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                    </div>
                    <div class="ordenes-form-group">
                        <label for="prioridad">Prioridad</label>
                        <select name="prioridad" id="prioridad" required>
                            <option value="baja">Baja</option>
                            <option value="media" selected>Media</option>
                            <option value="alta">Alta</option>
                            <option value="critica">Crítica</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="ordenes-modal-footer">
                <button type="button" class="ordenes-btn-secondary" id="btnCancelarModal">Cancelar</button>
                <button type="submit" class="ordenes-btn-primary">Crear Orden</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('modalNuevaOrden');
    const btnNueva = document.getElementById('btnNuevaOrden');
    const btnCerrar = document.getElementById('btnCerrarModal');
    const btnCancelar = document.getElementById('btnCancelarModal');

    btnNueva.addEventListener('click', () => modal.classList.add('active'));
    btnCerrar.addEventListener('click', () => modal.classList.remove('active'));
    btnCancelar.addEventListener('click', () => modal.classList.remove('active'));
    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.classList.remove('active');
    });

    function verOrden(id) {
        window.location.href = 'ordenes_ver.php?id=' + id;
    }

    function editarOrden(id) {
        window.location.href = 'ordenes_editar.php?id=' + id;
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
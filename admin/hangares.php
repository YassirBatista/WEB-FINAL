<?php
// NEXUS STELLAR SHIPYARDS — Hangares Espaciales
require_once '../includes/functions.php';
requireAdmin();

$pageTitle = 'Hangares';

// Hangares con ocupación REAL desde la BD
$sql = "SELECT 
    h.id,
    h.nivel,
    h.nombre,
    h.tipo_naves,
    h.capacidad_total,
    COUNT(n.id) as ocupacion_real,
    h.imagen,
    h.estado_operativo,
    ROUND((COUNT(n.id) / h.capacidad_total) * 100, 0) as porcentaje_ocupacion
FROM hangares h
LEFT JOIN naves n ON h.nivel = n.hangar_nivel AND n.estado != 'destruida'
GROUP BY h.id, h.nivel
ORDER BY h.nivel DESC";

$hangares = $pdo->query($sql)->fetchAll();

// Hangar activo
$hangarActivo = isset($_GET['hangar']) ? (int)$_GET['hangar'] : ($hangares[0]['nivel'] ?? 0);

$infoHangar = null;
foreach ($hangares as $h) {
    if ((int)$h['nivel'] === $hangarActivo) {
        $infoHangar = $h;
        break;
    }
}

// Paginación de naves
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$navesPorPagina = 8;
$offset = ($pagina - 1) * $navesPorPagina;

$totalNaves = 0;
$totalPaginas = 0;
$naves = [];

if ($hangarActivo > 0) {
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM naves WHERE hangar_nivel = ? AND estado != 'destruida'");
    $stmtCount->execute([$hangarActivo]);
    $totalNaves = (int)$stmtCount->fetchColumn();
    $totalPaginas = max(1, ceil($totalNaves / $navesPorPagina));

    if ($pagina > $totalPaginas) $pagina = $totalPaginas;
    $offset = ($pagina - 1) * $navesPorPagina;

    $stmtNaves = $pdo->prepare("SELECT 
        n.id, n.nombre, n.tipo, n.estado, n.imagen, n.codigo,
        u.nombre as cliente_nombre, u.faccion_id,
        f.nombre as faccion_nombre, f.color as faccion_color
    FROM naves n
    LEFT JOIN usuarios u ON n.cliente_id = u.id
    LEFT JOIN facciones f ON u.faccion_id = f.id
    WHERE n.hangar_nivel = ? AND n.estado != 'destruida'
    ORDER BY n.created_at DESC
    LIMIT ? OFFSET ?");
    $stmtNaves->execute([$hangarActivo, $navesPorPagina, $offset]);
    $naves = $stmtNaves->fetchAll();
}

$usuario = getUsuarioActual();
require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <header class="top-header">
        <div class="header-title">HANGARES ESPACIALES</div>
        <div class="header-right">
            <div class="header-stat">
                <div class="label">HANGARES ACTIVOS</div>
                <div class="value"><?php echo count($hangares); ?> / 3</div>
            </div>
            <div class="header-stat">
                <div class="label">CAPACIDAD TOTAL</div>
                <div class="value"><?php echo array_sum(array_column($hangares, 'capacidad_total')); ?> NAVES</div>
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
        
        <!-- HEADER DE PÁGINA -->
        <div class="page-header" style="margin-bottom: 28px;">
            <h1 style="font-family:'Orbitron',sans-serif;font-size:22px;color:var(--tech-white);text-transform:uppercase;letter-spacing:3px;margin-bottom:6px;">
                CENTRO DE HANGARES
            </h1>
            <div class="breadcrumb" style="font-size:12px;color:var(--tech-white-dim);letter-spacing:1px;">
                <span style="color:var(--neon-cyan)">●</span> SECTOR ORBITAL NEXUS &nbsp;|&nbsp; 
                AÑO 2926 &nbsp;|&nbsp; 
                <span id="hangar-activo-label">HANGAR NIVEL <?php echo $hangarActivo; ?> ACTIVO</span>
            </div>
        </div>

        <!-- GRID DE HANGARES - CLASES VIEJAS -->
        <div class="hangares-grid">
            <?php foreach($hangares as $h): 
                $pct = (int)$h['porcentaje_ocupacion'];
                $ocupadas = (int)$h['ocupacion_real'];
                $capacidad = (int)$h['capacidad_total'];
                $isActive = ((int)$h['nivel'] === $hangarActivo);
                
                $colorPct = $pct >= 90 ? '#FF4D5A' : ($pct >= 70 ? '#FFB800' : ($pct >= 40 ? '#00D9FF' : '#00FF9C'));
                $bgImg = "../assets/images/backgrounds/hangar{$h['nivel']}.jpg";
            ?>
            <a href="?hangar=<?php echo (int)$h['nivel']; ?>" 
               class="hangar-card <?php echo $isActive ? 'active' : ''; ?>">
                
                <!-- Imagen de fondo -->
                <div class="bg-image" style="background-image: url('<?php echo $bgImg; ?>')"></div>
                <div class="overlay"></div>
                
                <!-- Contenido -->
                <div class="content">
                    <div>
                        <div class="nivel">NIVEL <?php echo (int)$h['nivel']; ?></div>
                        <div class="tipo"><?php 
                            $tipos = explode(',', $h['tipo_naves']);
                            echo htmlspecialchars(trim($tipos[0] ?? 'VARIOS'));
                            if (count($tipos) > 1) echo ' <span style="opacity:0.5">+' . (count($tipos)-1) . '</span>';
                        ?></div>
                    </div>
                    
                    <div>
                        <div class="ocupacion-label">OCUPACIÓN</div>
                        <div class="ocupacion-valor" style="color:<?php echo $colorPct; ?>"><?php echo $pct; ?>%</div>
                        <div class="progress-bar">
                            <div class="fill" style="width:<?php echo $pct; ?>%;background:<?php echo $colorPct; ?>"></div>
                        </div>
                        <div class="capacidad">
                            <span><?php echo $ocupadas; ?></span> / <?php echo $capacidad; ?> NAVES
                        </div>
                    </div>
                    
                    <div class="btn-nexus" style="text-align:center">VER HANGAR</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- SEPARADOR ESTILO NEXUS -->
        <div class="nexus-separator">
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

        <!-- NAVES DEL HANGAR SELECCIONADO -->
        <?php if($infoHangar): ?>
        <div class="hangar-naves-section">
            <div class="hangar-naves-header">
                <div>
                    <h2 class="hangar-naves-title">
                        NAVES EN HANGAR NIVEL <?php echo (int)$infoHangar['nivel']; ?>
                    </h2>
                    <p class="hangar-naves-subtitle">
                        <?php echo htmlspecialchars($infoHangar['tipo_naves']); ?>
                    </p>
                </div>
                <div class="hangar-naves-stats">
                    <div class="hangar-naves-stat">
                        <span class="hangar-naves-stat-label">OCUPACIÓN</span>
                        <span class="hangar-naves-stat-value" style="color:<?php echo $colorPct; ?>">
                            <?php echo (int)$infoHangar['porcentaje_ocupacion']; ?>%
                        </span>
                    </div>
                    <div class="hangar-naves-stat">
                        <span class="hangar-naves-stat-label">NAVES</span>
                        <span class="hangar-naves-stat-value">
                            <?php echo (int)$infoHangar['ocupacion_real']; ?> / <?php echo (int)$infoHangar['capacidad_total']; ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if(count($naves) > 0): ?>
            <div class="naves-grid">
                <?php foreach($naves as $n): 
                    $estado = getEstadoColor($n['estado']);
                    $imgNave = !empty($n['imagen']) ? $n['imagen'] : 'default_ship.jpg';
                ?>
                <div class="nave-card">
                    <div class="nave-image" style="background-image:url('../assets/images/ships/<?php echo htmlspecialchars($imgNave); ?>')">
                        <div class="nave-image-overlay"></div>
                        <span class="nave-estado-badge <?php echo $estado['class']; ?>">
                            <?php echo $estado['text']; ?>
                        </span>
                    </div>
                    <div class="nave-info">
                        <h4 class="nave-name"><?php echo htmlspecialchars($n['nombre']); ?></h4>
                        <span class="nave-type"><?php echo strtoupper($n['tipo']); ?></span>
                        
                        <div class="nave-meta-grid">
                            <div class="nave-meta-item">
                                <span class="nave-meta-label">CLIENTE</span>
                                <span class="nave-meta-value">
                                    <?php if($n['cliente_nombre']): ?>
                                        <span style="display:inline-flex;align-items:center;gap:5px">
                                            <span style="width:7px;height:7px;border-radius:50%;background:<?php echo $n['faccion_color'] ?? 'var(--neon-cyan)'; ?>"></span>
                                            <?php echo htmlspecialchars($n['cliente_nombre']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="opacity:0.5">Sin asignar</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="nave-meta-item">
                                <span class="nave-meta-label">CÓDIGO</span>
                                <span class="nave-meta-value" style="font-family:'Rajdhani',monospace;font-weight:700;letter-spacing:1px">
                                    <?php echo htmlspecialchars($n['codigo']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <a href="naves.php?ver=<?php echo (int)$n['id']; ?>" class="btn-nexus" style="width:100%;margin-top:12px">
                            VER DETALLES
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Paginación -->
            <?php if($totalPaginas > 1): ?>
            <div class="nexus-pagination">
                <?php if($pagina > 1): ?>
                    <a href="?hangar=<?php echo $hangarActivo; ?>&pagina=<?php echo $pagina-1; ?>" class="nexus-page-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    </a>
                <?php else: ?>
                    <span class="nexus-page-btn disabled"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg></span>
                <?php endif; ?>

                <?php for($i = 1; $i <= $totalPaginas; $i++): ?>
                    <a href="?hangar=<?php echo $hangarActivo; ?>&pagina=<?php echo $i; ?>" 
                       class="nexus-page-btn <?php echo $i == $pagina ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if($pagina < $totalPaginas): ?>
                    <a href="?hangar=<?php echo $hangarActivo; ?>&pagina=<?php echo $pagina+1; ?>" class="nexus-page-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                <?php else: ?>
                    <span class="nexus-page-btn disabled"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="empty-state-hangar">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--neon-cyan-dim)" stroke-width="1" style="margin-bottom:16px;opacity:0.3">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <h3>HANGAR VACÍO</h3>
                <p>No hay naves asignadas a este hangar.<br>El espacio está disponible para nuevas asignaciones.</p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
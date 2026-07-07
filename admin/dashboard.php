<?php
// NEXUS STELLAR SHIPYARDS — Dashboard Administrador (Centro de Mando)
require_once '../includes/functions.php';
requireAdmin();

$pageTitle = 'Centro de Mando';
$stats = getDashboardStats();
$alertas = getAlertas(null, 5);
$ordenes = getOrdenesRecientes(5);
$naves = getNavesEnTaller(5);
$tecnicos = getTecnicosActivos(7);
$piezasTop = getPiezasSolicitadas(5);
$distribucion = getDistribucionNaves();
$ingresos = getIngresos30Dias();
$usuario = getUsuarioActual();

require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <header class="top-header">
        <div class="header-title">SISTEMA DE GESTIÓN DE TALLER</div>
        <div class="header-right">
            <div class="header-stat">
                <div class="label">SALDO</div>
                <div class="value"><?php echo formatCredits($stats['creditos_totales'] ?? 0); ?></div>
            </div>
            <button class="header-icon-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <span class="badge badge-critical">3</span>
            </button>
            <button class="header-icon-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                <span class="badge badge-warning">7</span>
            </button>
            <div class="user-profile">
                <img src="../assets/images/avatars/<?php echo $usuario['avatar'] ?? 'admin_vasquez.jpg'; ?>" alt="Admin">
                <div class="info">
                    <div class="name"><?php echo htmlspecialchars($usuario['nombre'] ?? 'Comandante'); ?></div>
                    <div class="role">ADMINISTRADOR</div>
                </div>
            </div>
        </div>
    </header>

    <div class="dashboard-content">
        <!-- COLUMNA PRINCIPAL -->
        <div class="main-column">

            <!-- BANNER BIENVENIDA -->
            <div class="welcome-banner">
                <div class="bg" style="background-image:url('../assets/images/backgrounds/shipyard.jpg')"></div>
                <div class="overlay"></div>
                <div class="content">
                    <h1>BIENVENIDO DE NUEVO, ADMINISTRADOR</h1>
                    <div class="subtitle">NEXUS STELLAR SHIPYARDS</div>
                    <div class="desc">Líderes en reparación, mantenimiento y mejoras de naves espaciales.</div>
                </div>
            </div>

            <!-- HANGARES -->
            <div class="panel-card" style="margin-bottom:24px">
                <div class="panel-header">
                    <div class="panel-title">HANGARES</div>
                    <a href="hangares.php" class="panel-link">Ver Todos</a>
                </div>
                <div class="panel-body">
                    <div class="hangares-grid">
                        <?php
                        $hangares = $pdo->query("SELECT * FROM hangares ORDER BY nivel DESC");
                        while ($h = $hangares->fetch()):
                            $pct = round(($h['ocupacion_actual'] / $h['capacidad_total']) * 100);
                            $nivelLabel = match((int)$h['nivel']) {
                                3 => 'Naves Pequeñas',
                                2 => 'Naves Medianas',
                                default => 'Naves Capitales'
                            };
                        ?>
                        <div class="hangar-card">
                            <div class="bg-image" style="background-image:url('../assets/images/backgrounds/hangar<?php echo (int)$h['nivel']; ?>.jpg')"></div>
                            <div class="overlay"></div>
                            <div class="content">
                                <div>
                                    <div class="nivel">NIVEL <?php echo (int)$h['nivel']; ?></div>
                                    <div class="tipo"><?php echo $nivelLabel; ?></div>
                                </div>
                                <div>
                                    <div class="ocupacion-label">OCUPACIÓN</div>
                                    <div class="ocupacion-valor"><?php echo $pct; ?>%</div>
                                    <div class="progress-bar">
                                        <div class="fill" style="width:<?php echo $pct; ?>%"></div>
                                    </div>
                                    <div class="capacidad">
                                        <span><?php echo (int)$h['ocupacion_actual']; ?></span> / <?php echo (int)$h['capacidad_total']; ?>
                                    </div>
                                </div>
                                <a href="hangares.php?nivel=<?php echo (int)$h['nivel']; ?>" class="btn-nexus" style="text-align:center">VER HANGAR</a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- NAVES EN TALLER -->
            <div class="panel-card" style="margin-bottom:24px">
                <div class="panel-header">
                    <div class="panel-title">NAVES EN TALLER</div>
                    <a href="naves.php" class="panel-link">Ver Todas</a>
                </div>
                <div class="panel-body">
                    <div class="naves-carousel">
                        <?php foreach ($naves as $nave): 
                            $estado = getEstadoColor($nave['estado']);
                            // Asegurar imagen válida
                            $imgNave = !empty($nave['imagen']) && file_exists("../assets/images/ships/{$nave['imagen']}") 
                                ? $nave['imagen'] 
                                : 'default_ship.jpg';
                        ?>
                        <div class="nave-card">
                            <div class="nave-header">
                                <div>
                                    <div class="nave-name"><?php echo htmlspecialchars($nave['nombre']); ?></div>
                                    <div class="nave-type"><?php echo strtoupper($nave['tipo']); ?></div>
                                </div>
                                <button style="background:none;border:none;color:var(--tech-white-dim);cursor:pointer">✕</button>
                            </div>
                            <div class="nave-image" style="background-image:url('../assets/images/ships/<?php echo $imgNave; ?>')"></div>
                            <div class="nave-info">
                                <span class="estado-badge <?php echo $estado['class']; ?>" style="color:<?php echo $estado['color']; ?>;border-color:<?php echo $estado['color']; ?>;background:<?php echo $estado['color']; ?>20">
                                    <?php echo $estado['text']; ?>
                                </span>
                                <div class="nave-meta">
                                    <div class="nave-meta-row">
                                        <span class="label">HANGAR</span>
                                        <span class="value">NIVEL <?php echo (int)$nave['hangar_nivel']; ?></span>
                                    </div>
                                    <div class="nave-meta-row">
                                        <span class="label">TIEMPO RESTANTE</span>
                                        <span class="timer" data-seconds="<?php echo (int)$nave['tiempo_restante']; ?>">
                                            <?php echo formatTime($nave['tiempo_restante']); ?>
                                        </span>
                                    </div>
                                </div>
                                <a href="reparaciones.php?nave=<?php echo (int)$nave['id']; ?>" class="btn-nexus" style="width:100%">VER DETALLES</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ESTADÍSTICAS INFERIORES -->
            <div class="bottom-stats">
                <div class="panel-card">
                    <div class="panel-header">
                        <div class="panel-title">INGRESOS (30 DÍAS)</div>
                    </div>
                    <div class="panel-body">
                        <div class="ingresos-total">6,750,250 Cr</div>
                        <div class="ingresos-tendencia">↗ +12.5%</div>
                        <div class="chart-container">
                            <canvas id="ingresosChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="panel-card">
                    <div class="panel-header">
                        <div class="panel-title">DISTRIBUCIÓN DE NAVES</div>
                    </div>
                    <div class="panel-body">
                        <div class="chart-container">
                            <canvas id="distribucionChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="panel-card">
                    <div class="panel-header">
                        <div class="panel-title">PIEZAS MÁS SOLICITADAS</div>
                    </div>
                    <div class="panel-body">
                        <div class="piezas-list">
                            <?php $rank = 1; foreach ($piezasTop as $p): ?>
                            <div class="pieza-item">
                                <span class="rank"><?php echo $rank++; ?></span>
                                <span class="name"><?php echo htmlspecialchars($p['nombre']); ?></span>
                                <span class="count"><?php echo (int)$p['solicitudes']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="panel-card">
                    <div class="panel-header">
                        <div class="panel-title">TÉCNICOS ACTIVOS</div>
                        <a href="tecnicos.php" class="panel-link">Ver Todos</a>
                    </div>
                    <div class="panel-body">
                        <div class="tecnicos-avatars">
                            <?php foreach ($tecnicos as $t): 
                                $imgTech = !empty($t['avatar']) && file_exists("../assets/images/avatars/{$t['avatar']}") 
                                    ? $t['avatar'] 
                                    : 'default_avatar.jpg';
                            ?>
                            <img src="../assets/images/avatars/<?php echo $imgTech; ?>" alt="<?php echo htmlspecialchars($t['nombre']); ?>" title="<?php echo htmlspecialchars($t['nombre']); ?> — <?php echo $t['estado']; ?>">
                            <?php endforeach; ?>
                        </div>
                        <div class="tecnicos-count">
                            <?php echo count($tecnicos); ?> <span>/ 40</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- COLUMNA DERECHA -->
        <div class="side-column">

            <!-- RESUMEN GENERAL -->
            <div class="panel-card" style="margin-bottom:24px">
                <div class="panel-header">
                    <div class="panel-title">RESUMEN GENERAL</div>
                </div>
                <div class="panel-body">
                    <div class="stats-grid">
                        <div class="stat-row">
                            <span class="label">ÓRDENES ACTIVAS</span>
                            <span class="value"><?php echo (int)($stats['ordenes_activas'] ?? 0); ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="label">REPARACIONES</span>
                            <span class="value"><?php echo (int)($stats['reparaciones_proceso'] ?? 0); ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="label">NAVES EN TALLER</span>
                            <span class="value"><?php echo (int)($stats['naves_taller'] ?? 0); ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="label">PIEZAS INVENTARIO</span>
                            <span class="value"><?php echo number_format($stats['piezas_inventario'] ?? 0); ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="label">TÉCNICOS ACTIVOS</span>
                            <span class="value"><?php echo (int)($stats['tecnicos_activos'] ?? 0); ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="label">CRÉDITOS TOTALES</span>
                            <span class="value"><?php echo formatCredits($stats['creditos_totales'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ÓRDENES RECIENTES -->
            <div class="panel-card" style="margin-bottom:24px">
                <div class="panel-header">
                    <div class="panel-title">ÓRDENES RECIENTES</div>
                    <a href="ordenes.php" class="panel-link">Ver Todas</a>
                </div>
                <div class="panel-body">
                    <div class="ordenes-list">
                        <?php foreach ($ordenes as $orden): 
                            $estadoOrd = getEstadoColor($orden['estado']);
                        ?>
                        <div class="orden-item">
                            <div class="orden-info">
                                <div class="orden-dot" style="background:<?php echo $estadoOrd['color']; ?>"></div>
                                <div>
                                    <div class="orden-id"><?php echo htmlspecialchars($orden['codigo']); ?></div>
                                    <div class="orden-nave"><?php echo htmlspecialchars($orden['nave_nombre'] ?? 'Sin nave'); ?></div>
                                </div>
                            </div>
                            <span class="orden-estado" style="color:<?php echo $estadoOrd['color']; ?>"><?php echo $estadoOrd['text']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ALERTAS -->
            <div class="panel-card">
                <div class="panel-header">
                    <div class="panel-title">ALERTAS DEL SISTEMA</div>
                </div>
                <div class="panel-body">
                    <div class="alertas-list">
                        <?php foreach ($alertas as $alerta): 
                            $alertClass = $alerta['nivel'];
                            $alertColor = match($alerta['nivel']) {
                                'critico' => '#FF4D5A',
                                'advertencia' => '#FFB800',
                                default => '#00D9FF'
                            };
                        ?>
                        <div class="alerta-item <?php echo $alertClass; ?>">
                            <svg class="alerta-icon" viewBox="0 0 24 24" fill="none" stroke="<?php echo $alertColor; ?>" stroke-width="2">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            <div class="alerta-text"><?php echo htmlspecialchars($alerta['mensaje']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="reportes.php" class="btn-nexus" style="width:100%;margin-top:12px">VER TODAS LAS ALERTAS</a>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ingresosCtx = document.getElementById('ingresosChart').getContext('2d');
new Chart(ingresosCtx, {
    type: 'line',
    data: {
        labels: ['S1','S2','S3','S4','S5','S6','S7','S8','S9','S10'],
        datasets: [{
            data: [1200000,980000,1450000,1100000,1600000,1350000,1870000,1500000,1750000,1900000],
            borderColor: '#00D9FF',
            backgroundColor: 'rgba(0,217,255,0.05)',
            borderWidth: 2,
            tension: 0.4,
            pointRadius: 0,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { display: false }, y: { display: false } }
    }
});

const distCtx = document.getElementById('distribucionChart').getContext('2d');
new Chart(distCtx, {
    type: 'doughnut',
    data: {
        labels: ['Nodrizas','Acorazados','Fragatas','Comerciales','Transportes','Cazas'],
        datasets: [{
            data: [2,6,12,10,8,5],
            backgroundColor: ['#FF4D5A','#9D4DFF','#00D9FF','#FFB800','#00FF9C','#E8EDF5'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        cutout: '65%'
    }
});

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
<?php
// NEXUS STELLAR SHIPYARDS - Sidebar Administrador
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$sidebarStats = getDashboardStats();
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo-icon">
            <img src="../assets/images/backgrounds/favicon-1.svg" alt="Nexus">
        </div>
        <div class="logo-text">
            <div class="brand">NEXUS</div>
            <div class="subbrand">Stellar Shipyards</div>
        </div>
    </div>

    <div class="sidebar-info">
        <div class="year">AÑO 2926</div>
        <div class="war-status">Guerra Galáctica</div>

        <div class="faction-power">
            <div class="name">
                <div class="dot" style="background:#FF4D5A"></div>
                <span>Imperio Solaris</span>
            </div>
            <div class="percent">78%</div>
        </div>
        <div class="faction-power">
            <div class="name">
                <div class="dot" style="background:#9D4DFF"></div>
                <span>Confederación Umbra</span>
            </div>
            <div class="percent">65%</div>
        </div>

        <div class="conflict-badge">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
            Conflicto Activo
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Centro de Mando</div>

        <a href="/nexus_stellar_shipyards/admin/dashboard.php" class="nav-item <?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Escritorio
        </a>
        <a href="/nexus_stellar_shipyards/admin/hangares.php" class="nav-item <?php echo $currentPage == 'hangares' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Hangares
        </a>
        <a href="/nexus_stellar_shipyards/admin/naves.php" class="nav-item <?php echo $currentPage == 'naves' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
            Naves
        </a>
        <a href="/nexus_stellar_shipyards/admin/reparaciones.php" class="nav-item <?php echo $currentPage == 'reparaciones' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            Reparaciones
        </a>
        <a href="/nexus_stellar_shipyards/admin/piezas.php" class="nav-item <?php echo $currentPage == 'piezas' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Piezas
        </a>
        <a href="/nexus_stellar_shipyards/admin/inventario.php" class="nav-item <?php echo $currentPage == 'inventario' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            Inventario
        </a>
        <a href="/nexus_stellar_shipyards/admin/ordenes.php" class="nav-item <?php echo $currentPage == 'ordenes' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Órdenes
        </a>
        <a href="/nexus_stellar_shipyards/admin/clientes.php" class="nav-item <?php echo $currentPage == 'clientes' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Clientes
        </a>
        <a href="/nexus_stellar_shipyards/admin/tecnicos.php" class="nav-item <?php echo $currentPage == 'tecnicos' ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Técnicos
        </a>

        <a href="/nexus_stellar_shipyards/auth/logout.php" class="nav-item logout">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Cerrar Sesión
        </a>
    </nav>
</aside>
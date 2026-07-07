<?php
// NEXUS STELLAR SHIPYARDS - Header Superior
$usuario = getUsuarioActual();
$esAdmin = ($_SESSION['rol'] ?? '') === 'admin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'NEXUS STELLAR SHIPYARDS'; ?> — Sistema de Gestión de Taller</title>
    <link rel="stylesheet" href="/nexus_stellar_shipyards/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="/nexus_stellar_shipyards/assets/images/backgrounds/favicon.jpg">
    <link rel="apple-touch-icon" href="/nexus_stellar_shipyards/assets/images/backgrounds/favicon.jpg">
    
    
</head>
<body class="scanlines">
<div class="bg-space"></div>
<div class="app-container">

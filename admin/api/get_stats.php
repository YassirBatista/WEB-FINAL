<?php
// NEXUS STELLAR SHIPYARDS — API: Estadísticas
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$stats = getDashboardStats();
echo json_encode($stats);

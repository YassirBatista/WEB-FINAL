<?php
// NEXUS STELLAR SHIPYARDS — API: Alertas
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$usuario_id = $_SESSION['rol'] === 'cliente' ? $_SESSION['user_id'] : null;
$alertas = getAlertas($usuario_id, 10);
echo json_encode($alertas);

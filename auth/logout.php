<?php
// NEXUS STELLAR SHIPYARDS — Cierre de Sesión
session_start();
session_destroy();
header('Location: login.php');
exit;
?>

<?php
// ============================================
// ROULEZ.TN - API Test
// ============================================
header('Content-Type: application/json');

echo json_encode([
    'status' => 'ok',
    'message' => 'API est accessible',
    'php_version' => phpversion(),
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
]);
?>

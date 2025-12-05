<?php
// Disable error display to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../db.php';
    require_once __DIR__ . '/../includes/functions.php';

    $lat = floatval($_GET['lat'] ?? 0);
    $lng = floatval($_GET['lng'] ?? 0);

    if ($lat === 0 || $lng === 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid coordinates'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Try to get real data from Overpass API or Nominatim
    $centers = getNearbyFitnessCenters($lat, $lng, 25, 5);
    
    // If no centers found, return empty array (frontend will show message)
    echo json_encode([
        'success' => true,
        'centers' => $centers,
        'count' => count($centers),
        'source' => !empty($centers) ? 'api' : 'none'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
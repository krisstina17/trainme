<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../db.php';

header('Content-Type: application/json');

$search = sanitize($_GET['search'] ?? '');
$specializacija = intval($_GET['specializacija'] ?? 0);

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(p.naziv LIKE ? OR p.opis LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($specializacija > 0) {
    $where[] = "u.tk_specializacija = ?";
    $params[] = $specializacija;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$query = "
    SELECT p.*, 
           u.ime as trener_ime, 
           u.priimek as trener_priimek,
           s.naziv_specializacije
    FROM programi p
    JOIN uporabniki u ON p.tk_trener = u.id_uporabnik
    LEFT JOIN specializacije s ON u.tk_specializacija = s.id_specializacija
    $whereClause
    ORDER BY p.naziv ASC
    LIMIT 20
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$programi = $stmt->fetchAll();

echo json_encode($programi);
?>


<?php
require_once '../includes/config.php';
require_once '../db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireTrainer();

$user = getCurrentUser();
$programId = intval($_GET['id'] ?? 0);

if ($programId === 0) {
    header('Location: /trainer/dashboard.php?error=invalid_id');
    exit;
}

// Check if program exists and belongs to trainer
$stmt = $pdo->prepare("SELECT * FROM programi WHERE id_program = ? AND tk_trener = ?");
$stmt->execute([$programId, $user['id_uporabnik']]);
$program = $stmt->fetch();

if (!$program) {
    header('Location: /trainer/dashboard.php?error=not_found');
    exit;
}

// Check if program has active subscriptions
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM narocnine n
    JOIN status_narocnine s ON n.tk_status = s.id_status
    WHERE n.tk_program = ? AND s.naziv_statusa = 'Aktivna'
");
$stmt->execute([$programId]);
$activeSubscriptions = $stmt->fetch()['count'];

if ($activeSubscriptions > 0) {
    header('Location: /trainer/dashboard.php?error=has_subscriptions&count=' . $activeSubscriptions);
    exit;
}

// Delete program (cascade will handle exercises and other related data)
try {
    // First delete exercises
    $stmt = $pdo->prepare("DELETE FROM vaje WHERE tk_program = ?");
    $stmt->execute([$programId]);
    
    // Delete program
    $stmt = $pdo->prepare("DELETE FROM programi WHERE id_program = ? AND tk_trener = ?");
    $stmt->execute([$programId, $user['id_uporabnik']]);
    
    if ($stmt->rowCount() > 0) {
        header('Location: /trainer/dashboard.php?success=deleted&name=' . urlencode($program['naziv']));
    } else {
        header('Location: /trainer/dashboard.php?error=delete_failed');
    }
    exit;
} catch (PDOException $e) {
    header('Location: /trainer/dashboard.php?error=delete_error');
    exit;
}
?>


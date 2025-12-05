<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../db.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM uporabniki WHERE id_uporabnik = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Check if user is trainer
 */
function isTrainer() {
    global $pdo;
    $user = getCurrentUser();
    if (!$user) return false;
    
    $stmt = $pdo->prepare("SELECT naziv_vloge FROM vloge WHERE id_vloga = ?");
    $stmt->execute([$user['tk_vloga']]);
    $vloga = $stmt->fetch();
    
    return $vloga && $vloga['naziv_vloge'] === 'trener';
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

/**
 * Require trainer role
 */
function requireTrainer() {
    requireLogin();
    if (!isTrainer()) {
        header('Location: /index.php');
        exit;
    }
}

/**
 * Login user
 */
function loginUser($userId) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['login_time'] = time();
}

/**
 * Logout user
 */
function logoutUser() {
    session_unset();
    session_destroy();
    header('Location: /index.php');
    exit;
}

/**
 * Check if user has active subscription for program
 */
function hasActiveSubscription($userId, $programId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT n.* 
        FROM narocnine n
        JOIN status_narocnine s ON n.tk_status = s.id_status
        WHERE n.tk_uporabnik = ? 
        AND n.tk_program = ?
        AND s.naziv_statusa = 'Aktivna'
        AND n.konec >= CURDATE()
    ");
    $stmt->execute([$userId, $programId]);
    return $stmt->fetch() !== false;
}

/**
 * Get user's active subscriptions
 */
function getUserActiveSubscriptions($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT n.*, p.naziv as program_naziv, p.cena, p.trajanje_dni
        FROM narocnine n
        JOIN programi p ON n.tk_program = p.id_program
        JOIN status_narocnine s ON n.tk_status = s.id_status
        WHERE n.tk_uporabnik = ? 
        AND s.naziv_statusa = 'Aktivna'
        AND n.konec >= CURDATE()
        ORDER BY n.konec DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

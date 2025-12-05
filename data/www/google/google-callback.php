<?php
/**
 * Google OAuth Callback Handler
 * 
 * This file handles the callback from Google OAuth
 */

// Suppress deprecated warnings for curl_close (PHP 8.5+)
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Verify state token (CSRF protection)
if (!isset($_GET['state']) || !isset($_SESSION['google_oauth_state']) || 
    $_GET['state'] !== $_SESSION['google_oauth_state']) {
    $_SESSION['error'] = 'Nepravilna zahteva. Prosimo, poskusite znova.';
    header('Location: /login.php');
    exit;
}

unset($_SESSION['google_oauth_state']);

// Check for error from Google
if (isset($_GET['error'])) {
    $_SESSION['error'] = 'Google prijava ni uspela: ' . htmlspecialchars($_GET['error']);
    header('Location: /login.php');
    exit;
}

// Get authorization code
$code = $_GET['code'] ?? null;

if (!$code) {
    $_SESSION['error'] = 'Nepravilna zahteva. Prosimo, poskusite znova.';
    header('Location: /login.php');
    exit;
}

// Exchange authorization code for access token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

$tokenResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
// Remove curl_close() - not needed in PHP 8.0+, automatically closed
unset($ch);

if ($httpCode !== 200) {
    $_SESSION['error'] = 'Napaka pri pridobivanju dostopnega žetona. Prosimo, poskusite znova.';
    header('Location: /login.php');
    exit;
}

$tokenData = json_decode($tokenResponse, true);

if (!isset($tokenData['access_token'])) {
    $_SESSION['error'] = 'Napaka pri pridobivanju dostopnega žetona. Prosimo, poskusite znova.';
    header('Location: /login.php');
    exit;
}

// Get user info from Google
$userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $tokenData['access_token'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenData['access_token']]);

$userInfoResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
// Remove curl_close() - not needed in PHP 8.0+, automatically closed
unset($ch);

if ($httpCode !== 200) {
    $_SESSION['error'] = 'Napaka pri pridobivanju podatkov uporabnika. Prosimo, poskusite znova.';
    header('Location: /login.php');
    exit;
}

$userInfo = json_decode($userInfoResponse, true);

if (!isset($userInfo['email'])) {
    $_SESSION['error'] = 'Google račun nima e-poštnega naslova.';
    header('Location: /login.php');
    exit;
}

// Check if user exists
$stmt = $pdo->prepare("SELECT * FROM uporabniki WHERE email = ?");
$stmt->execute([$userInfo['email']]);
$existingUser = $stmt->fetch();

if ($existingUser) {
    // User exists, log them in
    loginUser($existingUser['id_uporabnik']);
    $_SESSION['success'] = 'Uspešno prijavljeni z Google računom!';
    
    // Update Google ID if not set
    if (empty($existingUser['google_id'])) {
        $stmt = $pdo->prepare("UPDATE uporabniki SET google_id = ? WHERE id_uporabnik = ?");
        $stmt->execute([$userInfo['id'], $existingUser['id_uporabnik']]);
    }
    
    // Update profile picture if available and not set
    if (!empty($userInfo['picture']) && empty($existingUser['slika_profila'])) {
        // Download and save profile picture
        $imageData = file_get_contents($userInfo['picture']);
        if ($imageData) {
            $extension = 'jpg'; // Google profile pictures are usually JPG
            $filename = 'profile_' . $existingUser['id_uporabnik'] . '_google_' . time() . '.' . $extension;
            $targetPath = UPLOAD_DIR . $filename;
            
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }
            
            if (file_put_contents($targetPath, $imageData)) {
                $stmt = $pdo->prepare("UPDATE uporabniki SET slika_profila = ? WHERE id_uporabnik = ?");
                $stmt->execute([$filename, $existingUser['id_uporabnik']]);
            }
        }
    }
    
    // Redirect based on user role
    $user = getCurrentUser();
    if (isTrainer()) {
        $redirect = '/trainer/dashboard.php';
    } else {
        $redirect = $_SESSION['redirect_after_login'] ?? '/programi.php';
    }
    unset($_SESSION['redirect_after_login']);
    header('Location: ' . $redirect);
    exit;
} else {
    // New user, create account
    $ime = $userInfo['given_name'] ?? '';
    $priimek = $userInfo['family_name'] ?? '';
    $email = $userInfo['email'];
    $googleId = $userInfo['id'];
    
    // Generate a random password (user won't need it, but required by DB)
    $password = bin2hex(random_bytes(16));
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        // Try with geslo_hash first, fallback to geslo
        // Default to regular user (vloga = 1), not trainer
        $stmt = $pdo->prepare("
            INSERT INTO uporabniki (ime, priimek, email, geslo_hash, google_id, tk_vloga, tip_prijave)
            VALUES (?, ?, ?, ?, ?, 1, 'google')
        ");
        $stmt->execute([$ime, $priimek, $email, $hashedPassword, $googleId]);
        
        $userId = $pdo->lastInsertId();
        
        // Download and save profile picture
        if (!empty($userInfo['picture'])) {
            $imageData = file_get_contents($userInfo['picture']);
            if ($imageData) {
                $extension = 'jpg';
                $filename = 'profile_' . $userId . '_google_' . time() . '.' . $extension;
                $targetPath = UPLOAD_DIR . $filename;
                
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }
                
                if (file_put_contents($targetPath, $imageData)) {
                    $stmt = $pdo->prepare("UPDATE uporabniki SET slika_profila = ? WHERE id_uporabnik = ?");
                    $stmt->execute([$filename, $userId]);
                }
            }
        }
        
        // Log user in
        loginUser($userId);
        $_SESSION['success'] = 'Uspešno registrirani in prijavljeni z Google računom!';
        
        // Redirect based on user role (new users are always regular users, vloga = 1)
        $redirect = '/programi.php';
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . $redirect);
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Napaka pri ustvarjanju računa. Prosimo, poskusite znova.';
        header('Location: /login.php');
        exit;
    }
}
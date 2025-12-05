<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    http_response_code(401);
    exit;
}

$programId = intval($_POST['program_id'] ?? 0);
if ($programId === 0) {
    echo json_encode(['error' => 'Invalid program ID']);
    http_response_code(400);
    exit;
}

// Get program
$stmt = $pdo->prepare("SELECT * FROM programi WHERE id_program = ?");
$stmt->execute([$programId]);
$program = $stmt->fetch();

if (!$program) {
    echo json_encode(['error' => 'Program not found']);
    http_response_code(404);
    exit;
}

// Check if Stripe is configured
if (!defined('STRIPE_SECRET_KEY') || STRIPE_SECRET_KEY === 'sk_test_YOUR_KEY' || empty(STRIPE_SECRET_KEY)) {
    echo json_encode(['error' => 'Stripe ni pravilno konfiguriran. Prosimo, kontaktirajte administratorja.']);
    http_response_code(500);
    exit;
}

// Convert EUR to cents (Stripe uses smallest currency unit)
$amount = (int)($program['cena'] * 100);

if ($amount <= 0) {
    echo json_encode(['error' => 'Neveljavna cena programa.']);
    http_response_code(400);
    exit;
}

// Create Payment Intent via Stripe API
$ch = curl_init('https://api.stripe.com/v1/payment_intents');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . STRIPE_SECRET_KEY,
    'Content-Type: application/x-www-form-urlencoded',
]);
// Build metadata as string (Stripe requires metadata as key-value pairs)
$metadata = [
    'program_id' => (string)$programId,
    'user_id' => (string)$_SESSION['user_id'],
    'program_name' => $program['naziv']
];

$postData = [
    'amount' => $amount,
    'currency' => 'eur',
];

// Add metadata fields individually
foreach ($metadata as $key => $value) {
    $postData['metadata[' . $key . ']'] = $value;
}

curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
unset($ch);

if ($error) {
    error_log("Stripe API cURL error: " . $error);
    echo json_encode(['error' => 'Napaka pri povezavi s Stripe. Prosimo, poskusite znova.']);
    http_response_code(500);
    exit;
}

if ($httpCode !== 200) {
    $errorData = json_decode($response, true);
    $errorMessage = $errorData['error']['message'] ?? 'Payment creation failed';
    error_log("Stripe API error (HTTP $httpCode): " . $response);
    echo json_encode(['error' => $errorMessage]);
    http_response_code($httpCode);
    exit;
}

$paymentIntent = json_decode($response, true);

if (!isset($paymentIntent['client_secret'])) {
    error_log("Stripe API response missing client_secret: " . $response);
    echo json_encode(['error' => 'Napaka pri ustvarjanju plačila. Prosimo, poskusite znova.']);
    http_response_code(500);
    exit;
}

echo json_encode(['clientSecret' => $paymentIntent['client_secret']]);
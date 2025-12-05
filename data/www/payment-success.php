<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'db.php';

requireLogin();

$programId = intval($_GET['program_id'] ?? 0);
$paymentIntentId = $_GET['payment_intent'] ?? '';

$paymentSuccess = false;
$errorMessage = '';

if ($programId === 0) {
    header('Location: /programi.php');
    exit;
}

// Get program
$stmt = $pdo->prepare("SELECT * FROM programi WHERE id_program = ?");
$stmt->execute([$programId]);
$program = $stmt->fetch();

if (!$program) {
    header('Location: /programi.php');
    exit;
}

// Verify payment with Stripe
if (!empty($paymentIntentId)) {
    // Check if Stripe is configured
    if (!defined('STRIPE_SECRET_KEY') || STRIPE_SECRET_KEY === 'sk_test_YOUR_KEY' || empty(STRIPE_SECRET_KEY)) {
        $errorMessage = 'Stripe ni pravilno konfiguriran. Prosimo, kontaktirajte podporo.';
    } else {
        $ch = curl_init('https://api.stripe.com/v1/payment_intents/' . urlencode($paymentIntentId));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . STRIPE_SECRET_KEY,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        unset($ch);
        
        if ($curlError) {
            error_log("Stripe API cURL error: " . $curlError);
            $errorMessage = 'Napaka pri preverjanju plačila. Prosimo, kontaktirajte podporo.';
        } elseif ($httpCode === 200) {
            $paymentIntent = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Stripe API JSON decode error: " . json_last_error_msg());
                $errorMessage = 'Napaka pri obdelavi odgovora. Prosimo, kontaktirajte podporo.';
            } elseif (isset($paymentIntent['status']) && $paymentIntent['status'] === 'succeeded') {
            // Check if subscription already exists
            if (!hasActiveSubscription($_SESSION['user_id'], $programId)) {
                try {
                    // Create subscription
                    $zacetek = date('Y-m-d');
                    $konec = date('Y-m-d', strtotime("+{$program['trajanje_dni']} days"));
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO narocnine (tk_uporabnik, tk_program, zacetek, konec, tk_status)
                        VALUES (?, ?, ?, ?, 1)
                    ");
                    $stmt->execute([$_SESSION['user_id'], $programId, $zacetek, $konec]);
                    $narocninaId = $pdo->lastInsertId();
                    
                    // Get Stripe payment method ID
                    $stmt = $pdo->query("SELECT id_nacin FROM nacin_placila WHERE naziv_nacina = 'Stripe' LIMIT 1");
                    $nacinPlacila = $stmt->fetch();
                    $nacinId = $nacinPlacila['id_nacin'] ?? 1;
                    
                    // Create payment record
                    $stmt = $pdo->prepare("
                        INSERT INTO placila (tk_narocnina, tk_uporabnik, znesek, tk_nacin, transakcija, STATUS)
                        VALUES (?, ?, ?, ?, ?, 'uspesno')
                    ");
                    $stmt->execute([$narocninaId, $_SESSION['user_id'], $program['cena'], $nacinId, $paymentIntentId]);
                    
                    // Send confirmation email
                    $user = getCurrentUser();
                    sendEmail($user['email'], 'Potrditev naročnine - ' . $program['naziv'], 
                        "<h2>Naročnina potrjena!</h2><p>Uspešno ste se naročili na program: {$program['naziv']}</p><p>Trajanje: {$program['trajanje_dni']} dni</p>");
                    
                    $paymentSuccess = true;
                } catch (Exception $e) {
                    $errorMessage = 'Napaka pri shranjevanju naročnine: ' . $e->getMessage();
                }
            } else {
                $paymentSuccess = true; // Payment succeeded, but subscription already exists
            }
            } elseif (isset($paymentIntent['status'])) {
                $errorMessage = 'Plačilo ni bilo uspešno. Status: ' . htmlspecialchars($paymentIntent['status']);
            } else {
                $errorMessage = 'Nepričakovan odgovor od Stripe. Prosimo, kontaktirajte podporo.';
            }
        } else {
            $errorData = json_decode($response, true);
            $stripeError = isset($errorData['error']['message']) ? $errorData['error']['message'] : 'Unknown error';
            error_log("Stripe API error (HTTP $httpCode): " . $response);
            $errorMessage = 'Napaka pri preverjanju plačila: ' . htmlspecialchars($stripeError);
        }
    }
} else {
    $errorMessage = 'Manjka ID plačila.';
}

include 'header.php';
?>

<section class="payment-result-section">
    <div class="container">
        <div class="payment-result-card">
            <?php if ($paymentSuccess): ?>
                <div class="payment-success">
                    <div class="success-icon">✅</div>
                    <h1>Plačilo uspešno!</h1>
                    <p class="success-message">
                        Vaše plačilo v višini <strong>€<?php echo number_format($program['cena'], 2); ?></strong> 
                        za program <strong><?php echo htmlspecialchars($program['naziv']); ?></strong> je bilo uspešno obdelano.
                    </p>
                    <p class="transaction-id">
                        ID transakcije: <code><?php echo htmlspecialchars($paymentIntentId); ?></code>
                    </p>
                    <div class="action-buttons">
                        <a href="/moj-program.php?id=<?php echo $programId; ?>" class="btn btn-primary btn-large">
                            Pojdi na program
                        </a>
                        <a href="/moji-programi.php" class="btn btn-secondary">
                            Moji programi
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="payment-error">
                    <div class="error-icon">❌</div>
                    <h1>Plačilo ni bilo uspešno</h1>
                    <p class="error-message">
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </p>
                    <div class="action-buttons">
                        <a href="/checkout.php?id_program=<?php echo $programId; ?>" class="btn btn-primary btn-large">
                            Poskusi znova
                        </a>
                        <a href="/programi.php" class="btn btn-secondary">
                            Nazaj na programe
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
    .payment-result-section {
        padding: 4rem 0;
        min-height: 60vh;
    }
    
    .payment-result-card {
        max-width: 600px;
        margin: 0 auto;
        background: white;
        border-radius: 12px;
        padding: 3rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        text-align: center;
    }
    
    .success-icon, .error-icon {
        font-size: 4rem;
        margin-bottom: 1.5rem;
    }
    
    .payment-success h1, .payment-error h1 {
        color: #28a745;
        margin-bottom: 1rem;
    }
    
    .payment-error h1 {
        color: #dc3545;
    }
    
    .success-message, .error-message {
        font-size: 1.1rem;
        margin-bottom: 1.5rem;
        color: #666;
    }
    
    .transaction-id {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 2rem;
        font-size: 0.9rem;
    }
    
    .transaction-id code {
        color: #007bff;
        font-weight: bold;
    }
    
    .action-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .btn-large {
        padding: 0.75rem 2rem;
        font-size: 1.1rem;
    }
</style>

<?php include 'footer.php'; ?>
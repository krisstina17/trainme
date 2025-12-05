<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'db.php';

requireLogin();

$programId = intval($_GET['id_program'] ?? 0);

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

// Check if already has active subscription
if (hasActiveSubscription($_SESSION['user_id'], $programId)) {
    header('Location: /moj-program.php?id=' . $programId);
    exit;
}

// Get payment methods
$stmt = $pdo->query("SELECT * FROM nacin_placila ORDER BY id_nacin");
$paymentMethods = $stmt->fetchAll();

$errors = [];
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nacinPlacila = intval($_POST['nacin_placila'] ?? 0);
    
    if ($nacinPlacila === 0) {
        $errors[] = "Izberite način plačila.";
    } else {
        // Create subscription
        $zacetek = date('Y-m-d');
        $konec = date('Y-m-d', strtotime("+{$program['trajanje_dni']} days"));
        
        $stmt = $pdo->prepare("
            INSERT INTO narocnine (tk_uporabnik, tk_program, zacetek, konec, tk_status)
            VALUES (?, ?, ?, ?, 1)
        ");
        $stmt->execute([$_SESSION['user_id'], $programId, $zacetek, $konec]);
        $narocninaId = $pdo->lastInsertId();
        
        // Create payment record
        $transakcija = 'tx_' . time() . '_' . rand(1000, 9999);
        $stmt = $pdo->prepare("
            INSERT INTO placila (tk_narocnina, tk_uporabnik, znesek, tk_nacin, transakcija, STATUS)
            VALUES (?, ?, ?, ?, ?, 'uspesno')
        ");
        $stmt->execute([$narocninaId, $_SESSION['user_id'], $program['cena'], $nacinPlacila, $transakcija]);
        
        // Send confirmation email
        $user = getCurrentUser();
        sendEmail($user['email'], 'Potrditev naročnine - ' . $program['naziv'], 
            "<h2>Naročnina potrjena!</h2><p>Uspešno ste se naročili na program: {$program['naziv']}</p><p>Trajanje: {$program['trajanje_dni']} dni</p>");
        
        $success = true;
        header('Location: /moj-program.php?id=' . $programId);
        exit;
    }
}

include 'header.php';
?>

<section class="checkout-section">
    <div class="container">
        <h1 class="page-title">Naročilo</h1>
        
        <div class="checkout-container">
            <div class="checkout-summary">
                <h2>Povzetek naročila</h2>
                <div class="summary-item">
                    <h3><?php echo htmlspecialchars($program['naziv']); ?></h3>
                    <p><?php echo htmlspecialchars($program['opis'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></p>
                    <div class="summary-meta">
                        <span>Trajanje: <?php echo $program['trajanje_dni']; ?> dni</span>
                    </div>
                </div>
                <div class="summary-total">
                    <span>Skupaj:</span>
                    <span class="total-price">€<?php echo number_format($program['cena'], 2); ?></span>
                </div>
            </div>

            <div class="checkout-form-card">
                <?php 
                if (!empty($errors)) {
                    foreach ($errors as $e) {
                        showToast($e, 'error');
                    }
                }
                ?>

                <form method="POST" class="checkout-form">
                    <h2>Izberite način plačila</h2>
                    
                    <div class="payment-methods">
                        <?php foreach ($paymentMethods as $method): ?>
                            <label class="payment-method-option">
                                <input type="radio" name="nacin_placila" 
                                       value="<?php echo $method['id_nacin']; ?>" required>
                                <div class="payment-method-card">
                                    <span class="payment-method-name"><?php echo htmlspecialchars($method['naziv_nacina']); ?></span>
                                    <?php if ($method['naziv_nacina'] === 'Stripe'): ?>
                                        <span class="payment-method-icon">💳</span>
                                    <?php elseif ($method['naziv_nacina'] === 'PayPal'): ?>
                                        <span class="payment-method-icon">🅿️</span>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-large">
                        Plačaj €<?php echo number_format($program['cena'], 2); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>


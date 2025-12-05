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

// Check if Stripe is configured
$stripeConfigured = defined('STRIPE_PUBLIC_KEY') && 
                    STRIPE_PUBLIC_KEY !== 'pk_test_YOUR_KEY' && 
                    !empty(STRIPE_PUBLIC_KEY);

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
                <?php if (!$stripeConfigured): ?>
                    <div class="alert alert-warning">
                        <strong>Stripe ni konfiguriran!</strong> Prosimo, dodajte STRIPE_PUBLIC_KEY in STRIPE_SECRET_KEY v .env datoteko.
                    </div>
                <?php endif; ?>

                <h2>Plačilo s kartico</h2>
                <p class="text-muted">Vnesite podatke o kartici za plačilo</p>

                <form id="payment-form" class="checkout-form">
                    <input type="hidden" id="program_id" value="<?php echo $programId; ?>">
                    
                    <!-- Stripe Elements will mount here -->
                    <div id="card-element" class="stripe-card-element">
                        <!-- Stripe Elements will create form elements here -->
                    </div>
                    
                    <!-- Display form errors -->
                    <div id="card-errors" role="alert" class="error-message" style="display: none;"></div>
                    
                    <button type="submit" id="submit-button" class="btn btn-primary btn-block btn-large" <?php echo !$stripeConfigured ? 'disabled' : ''; ?>>
                        <span id="button-text">Plačaj €<?php echo number_format($program['cena'], 2); ?></span>
                        <span id="spinner" class="spinner" style="display: none;">⏳</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php if ($stripeConfigured): ?>
<!-- Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>
<script>
    const stripe = Stripe('<?php echo htmlspecialchars(STRIPE_PUBLIC_KEY, ENT_QUOTES, 'UTF-8'); ?>');
    const elements = stripe.elements();
    
    // Create card element
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#32325d',
                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                '::placeholder': {
                    color: '#aab7c4',
                },
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a',
            },
        },
    });
    
    // Mount card element
    const cardElementContainer = document.getElementById('card-element');
    if (cardElementContainer) {
        cardElement.mount('#card-element');
    }
    
    // Handle form submission
    const form = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit-button');
    const buttonText = document.getElementById('button-text');
    const spinner = document.getElementById('spinner');
    const cardErrors = document.getElementById('card-errors');
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Disable submit button
        submitButton.disabled = true;
        buttonText.style.display = 'none';
        spinner.style.display = 'inline-block';
        cardErrors.style.display = 'none';
        
        const programId = document.getElementById('program_id').value;
        
        try {
            // Create payment intent
            const response = await fetch('/api/create-payment-intent.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    program_id: programId
                })
            });
            
            if (!response.ok) {
                throw new Error('Napaka pri povezavi s strežnikom.');
            }
            
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            if (!data.clientSecret) {
                throw new Error('Napaka pri ustvarjanju plačila.');
            }
            
            // Confirm payment with Stripe
            const {error, paymentIntent} = await stripe.confirmCardPayment(data.clientSecret, {
                payment_method: {
                    card: cardElement,
                }
            });
            
            if (error) {
                // Show error to user
                cardErrors.textContent = error.message;
                cardErrors.style.display = 'block';
                submitButton.disabled = false;
                buttonText.style.display = 'inline';
                spinner.style.display = 'none';
            } else if (paymentIntent && paymentIntent.status === 'succeeded') {
                // Payment succeeded - redirect to success page
                window.location.href = '/payment-success.php?program_id=' + programId + '&payment_intent=' + paymentIntent.id;
            } else {
                throw new Error('Nepričakovan status plačila.');
            }
        } catch (error) {
            console.error('Error:', error);
            cardErrors.textContent = error.message || 'Napaka pri obdelavi plačila. Prosimo, poskusite znova.';
            cardErrors.style.display = 'block';
            submitButton.disabled = false;
            buttonText.style.display = 'inline';
            spinner.style.display = 'none';
        }
    });
    
    // Display real-time validation errors from the card Element
    cardElement.on('change', ({error}) => {
        if (error) {
            cardErrors.textContent = error.message;
            cardErrors.style.display = 'block';
        } else {
            cardErrors.style.display = 'none';
        }
    });
</script>
<?php endif; ?>

<style>
    .stripe-card-element {
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        margin-bottom: 1rem;
        background: white;
    }
    
    .error-message {
        color: #dc3545;
        font-size: 14px;
        margin-top: 0.5rem;
        padding: 0.5rem;
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        border-radius: 4px;
    }
    
    .spinner {
        display: inline-block;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    #submit-button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
</style>

<?php include 'footer.php'; ?>


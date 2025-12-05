<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'db.php';

$errors = [];
$success = false;

if (isLoggedIn()) {
    header('Location: /programi.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $ime = trim($_POST["ime"] ?? '');
    $priimek = trim($_POST["priimek"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $geslo = $_POST["geslo"] ?? '';

    // VALIDACIJA
    if (empty($ime)) $errors[] = "Ime je obvezno.";
    if (empty($priimek)) $errors[] = "Priimek je obvezen.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email ni veljaven.";
    if (strlen($geslo) < 6) $errors[] = "Geslo mora imeti vsaj 6 znakov.";

    // preveri, če email že obstaja
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id_uporabnik FROM uporabniki WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $errors[] = "Ta email je že registriran.";
        }
    }

    // Če ni napak → vstavi novega uporabnika
    if (empty($errors)) {
        $hash = password_hash($geslo, PASSWORD_BCRYPT);
        
        // Določi vlogo: 1 = uporabnik, 2 = trener
        $vloga = isset($_POST['vloga']) && $_POST['vloga'] === 'trener' ? 2 : 1;

        $stmt = $pdo->prepare("
            INSERT INTO uporabniki 
            (ime, priimek, email, geslo_hash, tk_vloga, tip_prijave)
            VALUES (?, ?, ?, ?, ?, 'klasicna')
        ");

        $stmt->execute([$ime, $priimek, $email, $hash, $vloga]);

        // Send welcome email
        $vlogaText = $vloga == 2 ? 'trener' : 'uporabnik';
        $vlogaMessage = $vloga == 2 
            ? '<p>Vaša registracija kot trener je bila uspešna. Pojdite na <a href="/trainer/dashboard.php">Dashboard</a> in začnite z ustvarjanjem programov!</p>'
            : '<p>Vaša registracija je bila uspešna. Začnite z izbiro programa!</p>';
        
        sendEmail($email, 'Dobrodošli v TrainMe!', 
            "<h2>Dobrodošli, $ime!</h2><p>Registrirani ste kot $vlogaText.</p>$vlogaMessage");

        $success = true;
    }
}

include "header.php";
?>

<section class="auth-section">
    <div class="auth-container">
        <div class="auth-card">
            <h2>Registracija</h2>

            <?php 
            if (!empty($errors)) {
                foreach ($errors as $e) {
                    showToast($e, 'error');
                }
            }
            if ($success) {
                showToast('Registracija uspešna! Prosimo prijavite se.', 'success');
            }
            ?>

            <form method="POST" class="auth-form" id="registerForm">
                <div class="form-group">
                    <label for="ime">Ime</label>
                    <input type="text" id="ime" name="ime" required placeholder="Vaše ime" 
                           value="<?php echo htmlspecialchars($_POST['ime'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="priimek">Priimek</label>
                    <input type="text" id="priimek" name="priimek" required placeholder="Vaš priimek"
                           value="<?php echo htmlspecialchars($_POST['priimek'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="vas@email.com"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="geslo">Geslo</label>
                    <input type="password" id="geslo" name="geslo" required placeholder="Najmanj 6 znakov">
                </div>

                <div class="form-group">
                    <label>Registriraj se kot:</label>
                    <div class="role-selection">
                        <label class="role-option">
                            <input type="radio" name="vloga" value="uporabnik" checked>
                            <span>Uporabnik</span>
                            <small>Naročanje na programe</small>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="vloga" value="trener">
                            <span>Trener</span>
                            <small>Ustvarjanje programov</small>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Registriraj se</button>
            </form>

            <div class="auth-divider">
                <span>ali</span>
            </div>

            <a href="/google/google-login.php" class="btn btn-google btn-block">
                <svg width="18" height="18" viewBox="0 0 18 18">
                    <path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/>
                    <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.96-2.184l-2.908-2.258c-.806.54-1.837.86-3.052.86-2.347 0-4.33-1.584-5.04-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z"/>
                    <path fill="#FBBC05" d="M3.96 10.707c-.18-.54-.282-1.117-.282-1.707s.102-1.167.282-1.707V4.961H.957C.348 6.175 0 7.55 0 9s.348 2.825.957 4.039l3.003-2.332z"/>
                    <path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.961L3.96 7.293C4.67 5.158 6.653 3.58 9 3.58z"/>
                </svg>
                Registriraj se z Google
            </a>

            <p class="auth-footer">
                Že imate račun? <a href="/login.php">Prijavite se</a>
            </p>
        </div>
    </div>
</section>

<script>
// Store registration data in localStorage
document.getElementById('registerForm').addEventListener('submit', function() {
    const formData = {
        email: document.getElementById('email').value,
        timestamp: new Date().toISOString()
    };
    localStorage.setItem('registrationData', JSON.stringify(formData));
});
</script>

<?php include "footer.php"; ?>

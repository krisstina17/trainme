<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'db.php';

$errors = [];

if (isLoggedIn()) {
    $user = getCurrentUser();
    if (isTrainer()) {
        header('Location: /trainer/dashboard.php');
    } else {
        header('Location: /programi.php');
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? '');
    $geslo = $_POST["geslo"] ?? '';

    if (empty($email) || empty($geslo)) {
        $errors[] = "Email in geslo sta obvezna.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM uporabniki WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Check both geslo_hash and geslo columns for compatibility
        $passwordHash = $user['geslo_hash'] ?? $user['geslo'] ?? null;
        if ($user && $passwordHash && password_verify($geslo, $passwordHash)) {
            loginUser($user['id_uporabnik']);
            
            // Refresh user data after login
            $user = getCurrentUser();
            
            // Store in localStorage via JavaScript
            if (isTrainer()) {
                header('Location: /trainer/dashboard.php');
            } else {
                header('Location: /programi.php');
            }
            exit;
        } else {
            $errors[] = "Napačen email ali geslo.";
        }
    }
}

include 'header.php';
?>

<section class="auth-section">
    <div class="auth-container">
        <div class="auth-card">
            <h2>Prijava</h2>
            
            <?php 
            if (!empty($errors)) {
                foreach ($errors as $e) {
                    showToast($e, 'error');
                }
            }
            ?>

            <form method="POST" class="auth-form" id="loginForm">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="vas@email.com" autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="geslo">Geslo</label>
                    <input type="password" id="geslo" name="geslo" required 
                           placeholder="Vaše geslo" autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-primary btn-block">Prijavi se</button>
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
                Prijavi se z Google
            </a>

            <p class="auth-footer">
                Nimate računa? <a href="/register.php">Registrirajte se</a>
            </p>
        </div>
    </div>
</section>

<script>
// Store login attempt in localStorage
document.getElementById('loginForm').addEventListener('submit', function() {
    localStorage.setItem('lastLoginAttempt', new Date().toISOString());
});
</script>

<?php include 'footer.php'; ?>


<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/get-avatar.php';
require_once 'db.php';

requireLogin();

if (isTrainer()) {
    header('Location: /index.php');
    exit;
}

$trenerId = intval($_GET['trener'] ?? 0);

if ($trenerId === 0) {
    header('Location: /programi.php');
    exit;
}

// Check if trainer exists
$stmt = $pdo->prepare("SELECT * FROM uporabniki WHERE id_uporabnik = ? AND tk_vloga = 2");
$stmt->execute([$trenerId]);
$trener = $stmt->fetch();

if (!$trener) {
    header('Location: /programi.php');
    exit;
}

// Check if already rated
$stmt = $pdo->prepare("SELECT * FROM ocene WHERE tk_uporabnik = ? AND tk_trener = ?");
$stmt->execute([$_SESSION['user_id'], $trenerId]);
$existingRating = $stmt->fetch();

$errors = [];
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $ocena = intval($_POST['ocena'] ?? 0);
    $komentar = trim($_POST['komentar'] ?? '');

    if ($ocena < 1 || $ocena > 5) {
        $errors[] = "Ocena mora biti med 1 in 5.";
    }

    if (empty($errors)) {
        if ($existingRating) {
            // Update existing rating
            $stmt = $pdo->prepare("
                UPDATE ocene 
                SET ocena = ?, komentar = ?
                WHERE tk_uporabnik = ? AND tk_trener = ?
            ");
            $stmt->execute([$ocena, $komentar, $_SESSION['user_id'], $trenerId]);
        } else {
            // Insert new rating
            $stmt = $pdo->prepare("
                INSERT INTO ocene (tk_uporabnik, tk_trener, ocena, komentar)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $trenerId, $ocena, $komentar]);
        }
        
        $success = true;
        header('Location: /trener.php?id=' . $trenerId);
        exit;
    }
}

// Pre-fill if updating
$ocena = $existingRating ? $existingRating['ocena'] : 0;
$komentar = $existingRating ? $existingRating['komentar'] : '';

include 'header.php';
?>

<section class="rate-section">
    <div class="container">
        <div class="rate-card">
            <h1>Oceni trenerja</h1>
            <p class="trainer-name"><?php echo htmlspecialchars($trener['ime'] . ' ' . $trener['priimek']); ?></p>

            <?php 
            if (!empty($errors)) {
                foreach ($errors as $e) {
                    showToast($e, 'error');
                }
            }
            ?>

            <form method="POST" class="rate-form">
                <div class="form-group">
                    <label>Ocena</label>
                    <div class="rating-input" id="ratingInput">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label class="star-label" data-rating="<?php echo $i; ?>">
                                <input type="radio" name="ocena" value="<?php echo $i; ?>" 
                                       required <?php echo $ocena == $i ? 'checked' : ''; ?>>
                                <span class="star">★</span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="komentar">Komentar</label>
                    <textarea id="komentar" name="komentar" rows="5" 
                              placeholder="Delite svoje izkušnje..."><?php echo htmlspecialchars($komentar); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Shrani oceno</button>
                    <a href="/trener.php?id=<?php echo $trenerId; ?>" class="btn btn-secondary">Prekliči</a>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
// Star rating interaction
const ratingInput = document.getElementById('ratingInput');
const starLabels = ratingInput.querySelectorAll('.star-label');
const radioInputs = ratingInput.querySelectorAll('input[type="radio"]');

// Highlight stars on hover
starLabels.forEach((label, index) => {
    label.addEventListener('mouseenter', function() {
        const rating = parseInt(this.dataset.rating);
        highlightStars(rating);
    });
});

ratingInput.addEventListener('mouseleave', function() {
    const checked = ratingInput.querySelector('input[type="radio"]:checked');
    if (checked) {
        highlightStars(parseInt(checked.value));
    } else {
        clearStars();
    }
});

// Highlight stars when clicked
radioInputs.forEach(input => {
    input.addEventListener('change', function() {
        highlightStars(parseInt(this.value));
    });
});

function highlightStars(rating) {
    starLabels.forEach((label, index) => {
        const labelRating = parseInt(label.dataset.rating);
        if (labelRating <= rating) {
            label.classList.add('active');
            label.querySelector('.star').style.color = '#ffc107';
        } else {
            label.classList.remove('active');
            label.querySelector('.star').style.color = '#ddd';
        }
    });
}

function clearStars() {
    starLabels.forEach(label => {
        label.classList.remove('active');
        label.querySelector('.star').style.color = '#ddd';
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const checked = ratingInput.querySelector('input[type="radio"]:checked');
    if (checked) {
        highlightStars(parseInt(checked.value));
    }
});
</script>

<?php include 'footer.php'; ?>

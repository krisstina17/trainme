<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/get-avatar.php';
require_once 'db.php';

requireLogin();

$user = getCurrentUser();
$errors = [];
$success = false;

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_profile'])) {
    $ime = trim($_POST['ime'] ?? '');
    $priimek = trim($_POST['priimek'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    if (empty($ime) || empty($priimek)) {
        $errors[] = "Ime in priimek sta obvezna.";
    } else {
        $stmt = $pdo->prepare("
            UPDATE uporabniki 
            SET ime = ?, priimek = ?, bio = ?
            WHERE id_uporabnik = ?
        ");
        $stmt->execute([$ime, $priimek, $bio, $user['id_uporabnik']]);
        $success = true;
        $user = getCurrentUser(); // Refresh user data
    }
}

// Handle image upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['slika_profila'])) {
    $result = uploadProfileImage($_FILES['slika_profila'], $user['id_uporabnik']);
    if ($result['success']) {
        $stmt = $pdo->prepare("UPDATE uporabniki SET slika_profila = ? WHERE id_uporabnik = ?");
        $stmt->execute([$result['filename'], $user['id_uporabnik']]);
        $user = getCurrentUser();
        $success = true;
    } else {
        $errors[] = $result['error'];
    }
}

// Get user's subscriptions
$subscriptions = getUserActiveSubscriptions($user['id_uporabnik']);

include 'header.php';
?>

<section class="profile-section">
    <div class="container">
        <h1 class="page-title">Moj profil</h1>

        <div class="profile-container">
            <div class="profile-card">
                <h2>Osebni podatki</h2>
                
                <?php 
                if ($success) {
                    showToast('Profil uspešno posodobljen!', 'success');
                }
                if (!empty($errors)) {
                    foreach ($errors as $e) {
                        showToast($e, 'error');
                    }
                }
                ?>

                <div class="profile-image-section">
                    <?php 
                    $profileImage = getUserProfileImage($user, 300);
                    ?>
                    <img src="<?php echo $profileImage; ?>" 
                         alt="Profilna slika" class="profile-image-large" 
                         loading="lazy" decoding="async">
                    <form method="POST" enctype="multipart/form-data" class="image-upload-form">
                        <input type="file" name="slika_profila" accept="image/*" id="imageInput" style="display: none;">
                        <label for="imageInput" class="btn btn-secondary">Spremeni sliko</label>
                        <button type="submit" class="btn btn-sm btn-primary" style="display: none;" id="uploadBtn">Naloži</button>
                    </form>
                </div>

                <form method="POST" class="profile-form">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label for="ime">Ime</label>
                        <input type="text" id="ime" name="ime" required
                               value="<?php echo htmlspecialchars($user['ime']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="priimek">Priimek</label>
                        <input type="text" id="priimek" name="priimek" required
                               value="<?php echo htmlspecialchars($user['priimek']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        <small class="text-muted">Email ni mogoče spremeniti</small>
                    </div>

                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" rows="4" 
                                  placeholder="Napišite nekaj o sebi..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Shrani spremembe</button>
                </form>
            </div>

            <div class="profile-subscriptions">
                <h2>Moje naročnine</h2>
                <?php if (empty($subscriptions)): ?>
                    <div class="no-data">
                        <p>Nimate aktivnih naročnin.</p>
                        <a href="/programi.php" class="btn btn-primary">Poglej programe</a>
                    </div>
                <?php else: ?>
                    <div class="subscriptions-list">
                        <?php foreach ($subscriptions as $sub): ?>
                            <div class="subscription-item">
                                <h3><?php echo htmlspecialchars($sub['program_naziv']); ?></h3>
                                <p>Do: <?php echo formatDate($sub['konec']); ?> (<?php echo daysRemaining($sub['konec']); ?> dni)</p>
                                <a href="/moj-program.php?id=<?php echo $sub['tk_program']; ?>" 
                                   class="btn btn-sm btn-primary">Odpri program</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
.profile-section {
    padding: 2rem 0;
}

.profile-container {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 2rem;
}

.profile-card,
.profile-subscriptions {
    background: var(--white);
    border-radius: var(--radius);
    padding: 2rem;
    box-shadow: var(--shadow);
}

.profile-image-section {
    text-align: center;
    margin-bottom: 2rem;
}

.profile-image-large {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 1rem;
}

.image-upload-form {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: center;
}

.subscription-item {
    padding: 1.5rem;
    background: var(--light-bg);
    border-radius: var(--radius-sm);
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .profile-container {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.getElementById('imageInput')?.addEventListener('change', function() {
    document.getElementById('uploadBtn').style.display = 'inline-block';
});
</script>

<?php include 'footer.php'; ?>


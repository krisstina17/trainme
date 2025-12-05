<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/get-avatar.php';
require_once 'db.php';

$trenerId = intval($_GET['id'] ?? 0);

if ($trenerId === 0) {
    header('Location: /programi.php');
    exit;
}

// Get trainer info
$stmt = $pdo->prepare("
    SELECT u.*, s.naziv_specializacije
    FROM uporabniki u
    LEFT JOIN specializacije s ON u.tk_specializacija = s.id_specializacija
    WHERE u.id_uporabnik = ? AND u.tk_vloga = 2
");
$stmt->execute([$trenerId]);
$trener = $stmt->fetch();

if (!$trener) {
    header('Location: /programi.php');
    exit;
}

// Get trainer programs
$stmt = $pdo->prepare("
    SELECT * FROM programi 
    WHERE tk_trener = ? 
    ORDER BY naziv ASC
");
$stmt->execute([$trenerId]);
$programi = $stmt->fetchAll();

// Get ratings
$rating = getTrainerRating($trenerId);

$stmt = $pdo->prepare("
    SELECT o.*, u.ime, u.priimek
    FROM ocene o
    JOIN uporabniki u ON o.tk_uporabnik = u.id_uporabnik
    WHERE o.tk_trener = ?
    ORDER BY o.id_ocena DESC
    LIMIT 10
");
$stmt->execute([$trenerId]);
$ocene = $stmt->fetchAll();

include 'header.php';
?>

<section class="trainer-profile-section">
    <div class="container">
        <div class="trainer-profile">
            <div class="trainer-header">
                <?php 
                $trenerImage = getUserProfileImage($trener, 400);
                ?>
                <img src="<?php echo $trenerImage; ?>" 
                     alt="<?php echo htmlspecialchars($trener['ime']); ?>" 
                     class="trainer-profile-image" 
                     loading="lazy" decoding="async">
                <div class="trainer-info">
                    <h1><?php echo htmlspecialchars($trener['ime'] . ' ' . $trener['priimek']); ?></h1>
                    <p class="trainer-specialization-large"><?php echo htmlspecialchars($trener['naziv_specializacije'] ?? 'N/A'); ?></p>
                    
                    <?php if ($rating['count'] > 0): ?>
                        <div class="trainer-rating-large">
                            <span class="stars-large"><?php echo str_repeat('★', max(1, round((float)$rating['average']))); ?></span>
                            <span class="rating-value-large"><?php echo number_format($rating['average'], 1); ?></span>
                            <span class="rating-count-large">(<?php echo $rating['count']; ?> ocen)</span>
                        </div>
                    <?php else: ?>
                        <p class="no-ratings">Še ni ocen</p>
                    <?php endif; ?>

                    <?php if ($trener['bio']): ?>
                        <p class="trainer-bio-full"><?php echo nl2br(htmlspecialchars($trener['bio'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="trainer-content">
                <div class="trainer-programs">
                    <h2>Programi</h2>
                    <?php if (empty($programi)): ?>
                        <p>Trener še nima programov.</p>
                    <?php else: ?>
                        <div class="programs-grid-mini">
                            <?php foreach ($programi as $program): ?>
                                <div class="program-card-mini">
                                    <h3><?php echo htmlspecialchars($program['naziv']); ?></h3>
                                    <p><?php echo htmlspecialchars(substr($program['opis'], 0, 100), ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>...</p>
                                    <div class="program-meta-mini">
                                        <span>€<?php echo number_format($program['cena'], 2); ?></span>
                                        <span><?php echo $program['trajanje_dni']; ?> dni</span>
                                    </div>
                                    <a href="/program.php?id=<?php echo $program['id_program']; ?>" 
                                       class="btn btn-sm btn-primary">Poglej več</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="trainer-ratings">
                    <h2>Ocene in komentarji</h2>
                    
                    <?php if (isLoggedIn() && !isTrainer()): ?>
                        <div class="rate-trainer-section">
                            <h3>Oceni trenerja</h3>
                            <a href="/oceni.php?trener=<?php echo $trenerId; ?>" 
                               class="btn btn-primary">Dodaj oceno</a>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($ocene)): ?>
                        <p>Še ni ocen.</p>
                    <?php else: ?>
                        <div class="ratings-list">
                            <?php foreach ($ocene as $ocena): ?>
                                <div class="rating-item">
                                    <div class="rating-header">
                                        <strong><?php echo htmlspecialchars($ocena['ime'] . ' ' . $ocena['priimek']); ?></strong>
                                        <span class="rating-stars"><?php echo str_repeat('★', $ocena['ocena']); ?></span>
                                    </div>
                                    <?php if ($ocena['komentar']): ?>
                                        <p class="rating-comment"><?php echo nl2br(htmlspecialchars($ocena['komentar'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>


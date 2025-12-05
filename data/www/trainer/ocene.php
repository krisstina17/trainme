<?php
require_once '../includes/config.php';
require_once '../db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireTrainer();

$user = getCurrentUser();

// Get all ratings
$stmt = $pdo->prepare("
    SELECT o.*, u.ime, u.priimek, u.email
    FROM ocene o
    JOIN uporabniki u ON o.tk_uporabnik = u.id_uporabnik
    WHERE o.tk_trener = ?
    ORDER BY o.id_ocena DESC
");
$stmt->execute([$user['id_uporabnik']]);
$ocene = $stmt->fetchAll();

// Get average rating
$rating = getTrainerRating($user['id_uporabnik']);

include '../header.php';
?>

<section class="trainer-ratings-section">
    <div class="container">
        <h1 class="page-title">Ocene uporabnikov</h1>
        
        <div class="ratings-summary">
            <div class="summary-card">
                <h2>Povprečna ocena</h2>
                <div class="average-rating-large">
                    <?php if ($rating['count'] > 0): ?>
                        <span class="stars-large"><?php echo str_repeat('★', max(1, round((float)$rating['average']))); ?></span>
                        <span class="rating-number"><?php echo number_format($rating['average'], 1); ?></span>
                        <span class="rating-count">(<?php echo $rating['count']; ?> ocen)</span>
                    <?php else: ?>
                        <p>Še ni ocen</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="ratings-list-full">
            <?php if (empty($ocene)): ?>
                <div class="no-ratings">
                    <p>Še nimate ocen.</p>
                </div>
            <?php else: ?>
                <?php foreach ($ocene as $ocena): ?>
                    <div class="rating-item-full">
                        <div class="rating-header-full">
                            <div>
                                <strong><?php echo htmlspecialchars($ocena['ime'] . ' ' . $ocena['priimek']); ?></strong>
                                <span class="rating-date"><?php echo formatDate($ocena['id_ocena']); ?></span>
                            </div>
                            <span class="rating-stars"><?php echo str_repeat('★', $ocena['ocena']); ?></span>
                        </div>
                        <?php if ($ocena['komentar']): ?>
                            <p class="rating-comment-full"><?php echo nl2br(htmlspecialchars($ocena['komentar'])); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include '../footer.php'; ?>


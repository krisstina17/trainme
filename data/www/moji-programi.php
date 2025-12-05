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

// Get user's active subscriptions
$stmt = $pdo->prepare("
    SELECT p.*, 
           n.zacetek, 
           n.konec, 
           n.tk_status,
           s.naziv_statusa,
           u.ime as trener_ime, 
           u.priimek as trener_priimek,
           u.slika_profila as trener_slika,
           sp.naziv_specializacije,
           (SELECT AVG(ocena) FROM ocene WHERE tk_trener = u.id_uporabnik) as avg_rating
    FROM narocnine n
    JOIN programi p ON n.tk_program = p.id_program
    JOIN uporabniki u ON p.tk_trener = u.id_uporabnik
    JOIN status_narocnine s ON n.tk_status = s.id_status
    LEFT JOIN specializacije sp ON u.tk_specializacija = sp.id_specializacija
    WHERE n.tk_uporabnik = ? AND s.naziv_statusa = 'Aktivna'
    ORDER BY n.konec DESC
");
$stmt->execute([$_SESSION['user_id']]);
$mojiProgrami = $stmt->fetchAll();

$pageTitle = 'Moji programi';
include 'header.php';
?>

<section class="programs-section">
    <div class="container">
        <h1 class="page-title">Moji programi</h1>
        
        <?php if (empty($mojiProgrami)): ?>
            <div class="no-programs">
                <p>Nimate aktivnih naročnin.</p>
                <a href="/programi.php" class="btn btn-primary">Poglej programe</a>
            </div>
        <?php else: ?>
            <div class="programs-grid">
                <?php 
                foreach ($mojiProgrami as $index => $program): 
                    $programImage = getProgramImageUrl($program['naziv']);
                ?>
                    <div class="program-card" data-program-id="<?php echo $program['id_program']; ?>">
                        <div class="program-image">
                            <img src="<?php echo $programImage; ?>" 
                                 alt="<?php echo htmlspecialchars($program['naziv']); ?>"
                                 loading="lazy" decoding="async">
                        </div>
                        <div class="program-content">
                            <div class="program-header">
                                <h3><?php echo htmlspecialchars($program['naziv']); ?></h3>
                                <span class="program-price">€<?php echo number_format($program['cena'], 2); ?></span>
                            </div>
                            
                            <p class="program-description">
                                <?php echo htmlspecialchars(substr($program['opis'], 0, 100), ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>...
                            </p>

                            <div class="program-meta">
                                <div class="trainer-info">
                                    <?php 
                                    $trenerData = ['id_uporabnik' => $program['tk_trener'] ?? 0, 'slika_profila' => $program['trener_slika'] ?? null];
                                    $trenerImage = getUserProfileImage($trenerData, 100);
                                    ?>
                                    <img src="<?php echo $trenerImage; ?>" 
                                         alt="Trener" class="trainer-avatar" 
                                         loading="lazy" decoding="async">
                                    <span><?php echo htmlspecialchars($program['trener_ime'] . ' ' . $program['trener_priimek']); ?></span>
                                </div>
                                <div class="program-footer">
                                    <span><?php echo $program['trajanje_dni']; ?> dni</span>
                                    <span><?php echo htmlspecialchars($program['naziv_specializacije'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                            
                            <div class="subscription-info" style="margin-top: 1rem; padding: 0.75rem; background: #e8f5e9; border-radius: 8px;">
                                <p style="margin: 0; font-size: 0.9rem; color: #2e7d32;">
                                    <strong>Aktivna do:</strong> <?php echo formatDate($program['konec']); ?> 
                                    (<?php echo daysRemaining($program['konec']); ?> dni)
                                </p>
                            </div>
                            
                            <a href="/moj-program.php?id=<?php echo $program['id_program']; ?>" 
                               class="btn btn-primary btn-block" style="margin-top: 1rem;">
                                Odpri program
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'footer.php'; ?>


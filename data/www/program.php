<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/get-avatar.php';
require_once 'db.php';

$programId = intval($_GET['id'] ?? 0);

if ($programId === 0) {
    header('Location: /programi.php');
    exit;
}

// Get program details
$stmt = $pdo->prepare("
    SELECT p.*, 
           u.id_uporabnik as trener_id,
           u.ime as trener_ime, 
           u.priimek as trener_priimek,
           u.bio as trener_bio,
           u.slika_profila as trener_slika,
           s.naziv_specializacije
    FROM programi p
    JOIN uporabniki u ON p.tk_trener = u.id_uporabnik
    LEFT JOIN specializacije s ON u.tk_specializacija = s.id_specializacija
    WHERE p.id_program = ?
");
$stmt->execute([$programId]);
$program = $stmt->fetch();

if (!$program) {
    header('Location: /programi.php');
    exit;
}

// Get exercises
$stmt = $pdo->prepare("
    SELECT * FROM vaje 
    WHERE tk_program = ? 
    ORDER BY zaporedje ASC
");
$stmt->execute([$programId]);
$vaje = $stmt->fetchAll();

// Get trainer rating
$rating = getTrainerRating($program['trener_id']);

// Check if user has active subscription
$hasAccess = false;
if (isLoggedIn()) {
    $hasAccess = hasActiveSubscription($_SESSION['user_id'], $programId);
}

include 'header.php';
?>

<section class="program-detail-section">
    <div class="container">
        <div class="program-detail">
            <div class="program-detail-main">
                <div class="program-hero">
                    <img src="<?php echo getProgramImageUrl($program['naziv']); ?>" 
                         alt="<?php echo htmlspecialchars($program['naziv']); ?>"
                         loading="lazy" class="program-hero-image">
                    <div class="program-hero-overlay"></div>
                    <div class="program-hero-content">
                        <h1><?php echo htmlspecialchars($program['naziv']); ?></h1>
                        <div class="program-price-large">€<?php echo number_format($program['cena'], 2); ?></div>
                        <p class="program-duration-large">📅 Trajanje: <?php echo $program['trajanje_dni']; ?> dni | 💪 <?php echo count($vaje); ?> vaj</p>
                    </div>
                </div>

                <div class="program-description-full">
                    <h2>Opis programa</h2>
                    <p><?php echo nl2br(htmlspecialchars($program['opis'], ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?></p>
                    
                    <div class="program-details-grid">
                        <div class="detail-item">
                            <span class="detail-icon">📅</span>
                            <div>
                                <strong>Trajanje</strong>
                                <p><?php echo $program['trajanje_dni']; ?> dni</p>
                            </div>
                        </div>
                        <div class="detail-item">
                            <span class="detail-icon">💪</span>
                            <div>
                                <strong>Število vaj</strong>
                                <p><?php echo count($vaje); ?> vaj</p>
                            </div>
                        </div>
                        <div class="detail-item">
                            <span class="detail-icon">🎯</span>
                            <div>
                                <strong>Nivo</strong>
                                <p><?php 
                                    if (stripos($program['naziv'], 'začet') !== false || stripos($program['naziv'], 'beginner') !== false) {
                                        echo 'Začetni';
                                    } elseif (stripos($program['naziv'], 'napred') !== false || stripos($program['naziv'], 'advanced') !== false) {
                                        echo 'Napreden';
                                    } else {
                                        echo 'Srednji';
                                    }
                                ?></p>
                            </div>
                        </div>
                        <div class="detail-item">
                            <span class="detail-icon">⏱️</span>
                            <div>
                                <strong>Čas na dan</strong>
                                <p>20-30 min</p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($hasAccess): ?>
                    <?php if (!empty($vaje)): ?>
                        <div class="program-exercises">
                            <h2>Vaje v programu</h2>
                            <p class="exercises-hint">Kliknite na "Označi kot opravljeno" po opravljeni vaji.</p>
                            <div class="exercises-list">
                                <?php 
                                // Get completed exercises from localStorage (will be handled by JS)
                                $completedExercises = []; // This will be populated by JavaScript
                                foreach ($vaje as $vaja): 
                                ?>
                                    <div class="exercise-item" data-exercise-id="<?php echo $vaja['id_vaja']; ?>">
                                        <div class="exercise-number"><?php echo $vaja['zaporedje']; ?></div>
                                        <div class="exercise-content">
                                            <h3><?php echo htmlspecialchars($vaja['naziv']); ?></h3>
                                            <p><?php echo nl2br(htmlspecialchars($vaja['opis'], ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?></p>
                                            <?php 
                                            $videoId = getYouTubeVideoId($vaja['video_url'] ?? '');
                                            if ($videoId): 
                                            ?>
                                                <div class="exercise-video-embed">
                                                    <iframe 
                                                        width="100%" 
                                                        height="315" 
                                                        src="https://www.youtube.com/embed/<?php echo htmlspecialchars($videoId); ?>?rel=0" 
                                                        frameborder="0" 
                                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                        allowfullscreen
                                                        loading="lazy"
                                                        style="border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                                    </iframe>
                                                </div>
                                            <?php else: ?>
                                                <div class="exercise-image-embed">
                                                    <img 
                                                        src="<?php echo getExerciseImageUrl($vaja['naziv']); ?>" 
                                                        alt="<?php echo htmlspecialchars($vaja['naziv']); ?>"
                                                        loading="lazy"
                                                        decoding="async"
                                                        style="width: 100%; height: auto; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); object-fit: cover; min-height: 300px;">
                                                </div>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-success mark-complete-btn" 
                                                    data-exercise-id="<?php echo $vaja['id_vaja']; ?>">
                                                ✓ Označi kot opravljeno
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="program-exercises-locked">
                        <div class="locked-content">
                            <h2>🔒 Vaje v programu</h2>
                            <p>Za dostop do vaj se morate naročiti na ta program.</p>
                            <p class="exercises-preview">Program vsebuje <strong><?php echo count($vaje); ?> vaj</strong>, ki vas bodo vodile skozi celoten trening.</p>
                            <a href="<?php echo isLoggedIn() ? '/checkout.php?id_program=' . $programId : '/login.php?redirect=' . urlencode('/program.php?id=' . $programId); ?>" 
                               class="btn btn-primary btn-large">
                                <?php echo isLoggedIn() ? 'Naroči se zdaj' : 'Prijavite se za naročilo'; ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="program-detail-sidebar">
                <div class="trainer-card">
                    <?php 
                    $trenerData = ['id_uporabnik' => $program['trener_id'], 'slika_profila' => $program['trener_slika'] ?? null];
                    $trenerImage = getUserProfileImage($trenerData, 200);
                    ?>
                    <img src="<?php echo $trenerImage; ?>" 
                         alt="Trener" class="trainer-card-image" 
                         loading="lazy" decoding="async">
                    <h3><?php echo htmlspecialchars($program['trener_ime'] . ' ' . $program['trener_priimek']); ?></h3>
                    <p class="trainer-specialization"><?php echo htmlspecialchars($program['naziv_specializacije'] ?? 'N/A'); ?></p>
                    
                    <?php if ($rating['count'] > 0): ?>
                        <div class="trainer-rating">
                            <span class="stars"><?php echo str_repeat('★', max(1, round((float)$rating['average']))); ?></span>
                            <span><?php echo number_format($rating['average'], 1); ?> (<?php echo $rating['count']; ?> ocen)</span>
                        </div>
                    <?php endif; ?>

                    <?php if ($program['trener_bio']): ?>
                        <p class="trainer-bio"><?php echo htmlspecialchars($program['trener_bio']); ?></p>
                    <?php endif; ?>

                    <a href="/trener.php?id=<?php echo $program['trener_id']; ?>" 
                       class="btn btn-secondary btn-block">Poglej profil trenerja</a>
                </div>

                <div class="program-actions">
                    <?php if ($hasAccess): ?>
                        <div class="access-granted">
                            <p>✅ Imate dostop do tega programa</p>
                            <a href="/moj-program.php?id=<?php echo $programId; ?>" 
                               class="btn btn-primary btn-block">Odpri program</a>
                            
                            <?php
                            // Generate QR code for program access
                            $qrData = SITE_URL . "/moj-program.php?id=" . $programId . "&user=" . $_SESSION['user_id'];
                            $qrCodeUrl = generateQRCode($qrData);
                            ?>
                            <div class="qr-code-section">
                                <h4>QR koda za dostop</h4>
                                <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code" class="qr-code">
                                <p class="qr-hint">Skenirajte za hiter dostop</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="purchase-section">
                            <div class="price-box">
                                <span class="price-label">Cena</span>
                                <span class="price-value">€<?php echo number_format($program['cena'], 2); ?></span>
                            </div>
                            
                            <?php if (isLoggedIn()): ?>
                                <a href="/checkout.php?id_program=<?php echo $programId; ?>" 
                                   class="btn btn-primary btn-block btn-large">
                                    Naroči se
                                </a>
                            <?php else: ?>
                                <a href="/login.php?redirect=<?php echo urlencode('/program.php?id=' . $programId); ?>" 
                                   class="btn btn-primary btn-block btn-large">
                                    Prijavite se za naročilo
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>


<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'db.php';

requireLogin();

$programId = intval($_GET['id'] ?? 0);

if ($programId === 0) {
    header('Location: /programi.php');
    exit;
}

// Check access
if (!hasActiveSubscription($_SESSION['user_id'], $programId)) {
    header('Location: /program.php?id=' . $programId);
    exit;
}

// Get program
$stmt = $pdo->prepare("SELECT * FROM programi WHERE id_program = ?");
$stmt->execute([$programId]);
$program = $stmt->fetch();

// Get subscription details
$stmt = $pdo->prepare("
    SELECT n.*, s.naziv_statusa
    FROM narocnine n
    JOIN status_narocnine s ON n.tk_status = s.id_status
    WHERE n.tk_uporabnik = ? AND n.tk_program = ? AND s.naziv_statusa = 'Aktivna'
    ORDER BY n.konec DESC
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id'], $programId]);
$narocnina = $stmt->fetch();

// Get exercises
$stmt = $pdo->prepare("
    SELECT * FROM vaje 
    WHERE tk_program = ? 
    ORDER BY zaporedje ASC
");
$stmt->execute([$programId]);
$vaje = $stmt->fetchAll();

// Generate QR code
$qrData = SITE_URL . "/moj-program.php?id=" . $programId . "&user=" . $_SESSION['user_id'];
$qrCodeUrl = generateQRCode($qrData);

include 'header.php';
?>

<section class="my-program-section">
    <div class="container">
        <div class="my-program-header">
            <h1><?php echo htmlspecialchars($program['naziv']); ?></h1>
            <?php if ($narocnina): ?>
                <div class="subscription-status">
                    <span class="status-badge status-active">Aktivna naročnina</span>
                    <span class="subscription-dates">
                        Do: <?php echo formatDate($narocnina['konec']); ?> 
                        (<?php echo daysRemaining($narocnina['konec']); ?> dni preostalo)
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <div class="my-program-content">
            <div class="program-exercises-full">
                <div class="progress-header">
                    <h2>Vaje</h2>
                    <div class="progress-indicator">
                        <div class="progress-bar-container">
                            <div class="progress-bar" id="progressBar" style="width: 0%"></div>
                        </div>
                        <span class="progress-text" id="progressText">0% dokončano</span>
                    </div>
                </div>
                <div class="exercises-list">
                    <?php foreach ($vaje as $index => $vaja): ?>
                        <div class="exercise-item-full" data-exercise-id="<?php echo $vaja['id_vaja']; ?>">
                            <div class="exercise-header">
                                <span class="exercise-number-large"><?php echo $vaja['zaporedje']; ?></span>
                                <h3><?php echo htmlspecialchars($vaja['naziv']); ?></h3>
                                <span class="exercise-status" data-exercise-id="<?php echo $vaja['id_vaja']; ?>"></span>
                            </div>
                            <div class="exercise-description">
                                <p><?php echo nl2br(htmlspecialchars($vaja['opis'], ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?></p>
                            </div>
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
                            <div class="exercise-actions">
                                <button class="btn btn-sm btn-success mark-complete" 
                                        data-exercise-id="<?php echo $vaja['id_vaja']; ?>">
                                    ✓ Označi kot opravljeno
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="program-sidebar">
                <div class="qr-code-card">
                    <h3>QR koda za dostop</h3>
                    <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code" class="qr-code-large" 
                         loading="lazy" decoding="async">
                    <p>Skenirajte za hiter dostop do programa</p>
                </div>

                <div class="program-info-card">
                    <h3>Informacije o programu</h3>
                    <ul>
                        <li><strong>Trajanje:</strong> <?php echo $program['trajanje_dni']; ?> dni</li>
                        <li><strong>Cena:</strong> €<?php echo number_format($program['cena'], 2); ?></li>
                        <?php if ($narocnina): ?>
                            <li><strong>Začetek:</strong> <?php echo formatDate($narocnina['zacetek']); ?></li>
                            <li><strong>Konec:</strong> <?php echo formatDate($narocnina['konec']); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Progress tracking
function updateProgress() {
    const totalExercises = document.querySelectorAll('.exercise-item-full').length;
    
    // Get all exercise IDs that are actually on this page (part of current program)
    const exerciseIdsOnPage = Array.from(document.querySelectorAll('.exercise-item-full')).map(
        exercise => exercise.dataset.exerciseId
    );
    
    // Get completed exercises from localStorage
    const allCompleted = JSON.parse(localStorage.getItem('completedExercises') || '[]');
    
    // Filter: only count exercises that are part of the current program
    const completed = allCompleted.filter(id => exerciseIdsOnPage.includes(id));
    const completedCount = completed.length;
    
    // Calculate percentage, but cap it at 100%
    const percentage = totalExercises > 0 ? Math.min(100, Math.round((completedCount / totalExercises) * 100)) : 0;
    
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    
    if (progressBar) {
        progressBar.style.width = percentage + '%';
    }
    if (progressText) {
        progressText.textContent = percentage + '% dokončano (' + completedCount + '/' + totalExercises + ')';
    }
    
    // Update exercise status indicators
    document.querySelectorAll('.exercise-item-full').forEach(exercise => {
        const exerciseId = exercise.dataset.exerciseId;
        const statusSpan = exercise.querySelector('.exercise-status');
        const btn = exercise.querySelector('.mark-complete');
        
        if (completed.includes(exerciseId)) {
            exercise.classList.add('completed');
            if (statusSpan) {
                statusSpan.innerHTML = '<span class="status-badge completed">✓ Opravljeno</span>';
            }
            if (btn) {
                btn.classList.add('completed');
                btn.textContent = '✓ Opravljeno';
            }
        } else {
            exercise.classList.remove('completed');
            if (statusSpan) {
                statusSpan.innerHTML = '';
            }
            if (btn) {
                btn.classList.remove('completed');
                btn.textContent = '✓ Označi kot opravljeno';
            }
        }
    });
}

// Mark exercise as complete (store in localStorage)
document.querySelectorAll('.mark-complete').forEach(btn => {
    btn.addEventListener('click', function() {
        const exerciseId = this.dataset.exerciseId;
        
        // Verify that this exercise is actually part of the current program
        const exerciseIdsOnPage = Array.from(document.querySelectorAll('.exercise-item-full')).map(
            exercise => exercise.dataset.exerciseId
        );
        
        if (!exerciseIdsOnPage.includes(exerciseId)) {
            console.warn('Exercise ID not found in current program:', exerciseId);
            return;
        }
        
        const completed = JSON.parse(localStorage.getItem('completedExercises') || '[]');
        if (!completed.includes(exerciseId)) {
            completed.push(exerciseId);
            localStorage.setItem('completedExercises', JSON.stringify(completed));
            updateProgress();
        }
    });
});

// Load completed exercises on page load
window.addEventListener('DOMContentLoaded', function() {
    // Clean up localStorage: remove exercise IDs that are not part of current program
    const exerciseIdsOnPage = Array.from(document.querySelectorAll('.exercise-item-full')).map(
        exercise => exercise.dataset.exerciseId
    );
    const allCompleted = JSON.parse(localStorage.getItem('completedExercises') || '[]');
    const validCompleted = allCompleted.filter(id => exerciseIdsOnPage.includes(id));
    
    // Only update localStorage if we removed some invalid entries
    if (validCompleted.length !== allCompleted.length) {
        localStorage.setItem('completedExercises', JSON.stringify(validCompleted));
    }
    
    updateProgress();
});
</script>

<?php include 'footer.php'; ?>


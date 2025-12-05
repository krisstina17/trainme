<?php
require_once '../includes/config.php';
require_once '../db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireTrainer();

$user = getCurrentUser();

// Get trainer's programs
$stmt = $pdo->prepare("
    SELECT p.*, 
           COUNT(DISTINCT n.id_narocnina) as subscriber_count,
           COUNT(DISTINCT o.id_ocena) as rating_count,
           AVG(o.ocena) as avg_rating
    FROM programi p
    LEFT JOIN narocnine n ON p.id_program = n.tk_program AND n.tk_status = 1
    LEFT JOIN ocene o ON o.tk_trener = ?
    WHERE p.tk_trener = ?
    GROUP BY p.id_program
    ORDER BY p.naziv ASC
");
$stmt->execute([$user['id_uporabnik'], $user['id_uporabnik']]);
$programi = $stmt->fetchAll();

// Get overall stats
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT n.id_narocnina) as total_subscriptions,
        COUNT(DISTINCT n.tk_uporabnik) as unique_users,
        AVG(o.ocena) as avg_rating,
        COUNT(DISTINCT o.id_ocena) as total_ratings
    FROM uporabniki u
    LEFT JOIN programi p ON p.tk_trener = u.id_uporabnik
    LEFT JOIN narocnine n ON n.tk_program = p.id_program AND n.tk_status = 1
    LEFT JOIN ocene o ON o.tk_trener = u.id_uporabnik
    WHERE u.id_uporabnik = ?
");
$stmt->execute([$user['id_uporabnik']]);
$stats = $stmt->fetch();

include '../header.php';
?>

<section class="trainer-dashboard-section">
    <div class="container">
        <div class="dashboard-header">
            <h1>Dashboard trenerja</h1>
            <p>Dobrodošli, <?php echo htmlspecialchars($user['ime']); ?>!</p>
        </div>

        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_subscriptions'] ?? 0; ?></h3>
                    <p>Aktivne naročnine</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['unique_users'] ?? 0; ?></h3>
                    <p>Unikatni uporabniki</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?></h3>
                    <p>Povprečna ocena</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_ratings'] ?? 0; ?></h3>
                    <p>Ocen</p>
                </div>
            </div>
        </div>

        <div class="dashboard-actions">
            <a href="/trainer/uredi-program.php" class="btn btn-primary">+ Dodaj nov program</a>
            <a href="/trainer/ocene.php" class="btn btn-secondary">Poglej ocene</a>
        </div>

        <div class="dashboard-programs">
            <h2>Moji programi</h2>
            
            <?php 
            // Show success/error messages
            if (isset($_GET['success']) && $_GET['success'] === 'deleted') {
                $programName = isset($_GET['name']) ? htmlspecialchars(urldecode($_GET['name'])) : 'Program';
                showToast("Program '$programName' je bil uspešno izbrisan.", 'success');
            }
            if (isset($_GET['error'])) {
                $errorMessages = [
                    'has_subscriptions' => 'Programa ni mogoče izbrisati, ker ima aktivne naročnine.',
                    'not_found' => 'Program ni najden.',
                    'invalid_id' => 'Neveljaven ID programa.',
                    'delete_failed' => 'Napaka pri brisanju programa.',
                    'delete_error' => 'Napaka pri brisanju programa.'
                ];
                $errorMsg = $errorMessages[$_GET['error']] ?? 'Neznana napaka.';
                if ($_GET['error'] === 'has_subscriptions' && isset($_GET['count'])) {
                    $errorMsg = "Programa ni mogoče izbrisati, ker ima {$_GET['count']} aktivnih naročnin.";
                }
                showToast($errorMsg, 'error');
            }
            ?>
            
            <?php if (empty($programi)): ?>
                <div class="no-programs">
                    <p>Še nimate programov. <a href="/trainer/uredi-program.php">Dodajte prvi program</a>.</p>
                </div>
            <?php else: ?>
                <div class="programs-table-container">
                    <table class="programs-table">
                        <thead>
                            <tr>
                                <th>Naziv</th>
                                <th>Cena</th>
                                <th>Trajanje</th>
                                <th>Naročnine</th>
                                <th>Ocena</th>
                                <th>Akcije</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($programi as $program): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($program['naziv']); ?></strong></td>
                                    <td>€<?php echo number_format($program['cena'], 2); ?></td>
                                    <td><?php echo $program['trajanje_dni']; ?> dni</td>
                                    <td><?php echo $program['subscriber_count']; ?></td>
                                    <td>
                                        <?php if ($program['avg_rating'] && $program['rating_count'] > 0): ?>
                                            <div class="rating-display">
                                                <span class="rating-stars">
                                                    <?php 
                                                    $rating = (float)$program['avg_rating'];
                                                    $fullStars = floor($rating);
                                                    $hasHalfStar = ($rating - $fullStars) >= 0.5;
                                                    for ($i = 0; $i < $fullStars; $i++): ?>
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="star-icon star-filled">
                                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                                        </svg>
                                                    <?php endfor; ?>
                                                    <?php if ($hasHalfStar): ?>
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="star-icon star-half">
                                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                                        </svg>
                                                    <?php endif; ?>
                                                    <?php for ($i = $fullStars + ($hasHalfStar ? 1 : 0); $i < 5; $i++): ?>
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="star-icon star-empty">
                                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                                        </svg>
                                                    <?php endfor; ?>
                                                </span>
                                                <span class="rating-value"><?php echo number_format($rating, 1); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="no-rating">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                                            <a href="/trainer/uredi-program.php?id=<?php echo $program['id_program']; ?>" 
                                               class="btn btn-sm btn-primary">Uredi</a>
                                            <?php if ($program['subscriber_count'] == 0): ?>
                                                <a href="/trainer/izbrisi-program.php?id=<?php echo $program['id_program']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Ali ste prepričani, da želite izbrisati program \'<?php echo htmlspecialchars($program['naziv']); ?>\'? To dejanje ni mogoče razveljaviti.');">
                                                    Izbriši
                                                </a>
                                            <?php else: ?>
                                                <span class="btn btn-sm btn-secondary" 
                                                      style="cursor: not-allowed; opacity: 0.6;"
                                                      title="Programa ni mogoče izbrisati, ker ima <?php echo $program['subscriber_count']; ?> aktivnih naročnin.">
                                                    Izbriši
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include '../footer.php'; ?>


<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'db.php';

// Get featured programs
$stmt = $pdo->query("
    SELECT p.*, 
           u.ime as trener_ime, 
           u.priimek as trener_priimek,
           s.naziv_specializacije,
           (SELECT AVG(ocena) FROM ocene WHERE tk_trener = u.id_uporabnik) as avg_rating
    FROM programi p
    JOIN uporabniki u ON p.tk_trener = u.id_uporabnik
    LEFT JOIN specializacije s ON u.tk_specializacija = s.id_specializacija
    ORDER BY p.id_program DESC
    LIMIT 6
");
$featuredPrograms = $stmt->fetchAll();

$pageTitle = 'Domov';
include 'header.php';
?>

<section class="hero" style="background: linear-gradient(135deg, rgba(45, 108, 223, 0.85), rgba(30, 78, 189, 0.85)), url('/assets/img/hero.png'); background-size: cover; background-position: center;">
    <div>
        <h1>Postani najboljša verzija sebe</h1>
        <p>Pridruži se našim programom vadbe, vodi napredek in treniraj kjerkoli.</p>
        <a href="/programi.php" class="btn btn-primary btn-large">Poglej programe</a>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2>Zakaj TrainMe?</h2>

        <div class="cards">
            <div class="card">
                <div style="font-size: 3rem; margin-bottom: 1rem;">💪</div>
                <h3>Profesionalni trenerji</h3>
                <p>Programi so vodeni s strani izkušenih strokovnjakov.</p>
            </div>

            <div class="card">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
                <h3>Spremljanje napredka</h3>
                <p>Shranjuj svojo težo in si oglej graf napredka.</p>
            </div>

            <div class="card">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🌍</div>
                <h3>Dostop od kjerkoli</h3>
                <p>Treniraj doma, v naravi ali v fitnesu — vsebina je vedno dostopna.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2>Priljubljeni programi</h2>

        <div class="programs-grid">
            <?php 
            foreach ($featuredPrograms as $index => $program): 
                $programImage = getProgramImageUrl($program['naziv']);
            ?>
                <div class="program-card">
                    <div class="program-image">
                        <img src="<?php echo $programImage; ?>" 
                             alt="<?php echo htmlspecialchars($program['naziv']); ?>"
                             loading="lazy">
                    </div>
                    <div class="program-content">
                        <div class="program-header">
                            <h3><?php echo htmlspecialchars($program['naziv']); ?></h3>
                            <span class="program-price">€<?php echo number_format($program['cena'], 2); ?></span>
                        </div>
                        <p class="program-description">
                            <?php echo htmlspecialchars(substr($program['opis'], 0, 100), ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>...
                        </p>
                        <div class="program-footer">
                            <span><?php echo $program['trajanje_dni']; ?> dni</span>
                            <span><?php echo htmlspecialchars($program['naziv_specializacije'] ?? 'N/A'); ?></span>
                        </div>
                        <a href="/program.php?id=<?php echo $program['id_program']; ?>" 
                           class="btn btn-primary btn-block" style="margin-top: 1rem;">
                            Poglej več
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="/programi.php" class="btn btn-secondary btn-large">Poglej vse programe</a>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>

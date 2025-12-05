<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/get-avatar.php';
require_once 'db.php';

// Get search and filter parameters
$search = sanitize($_GET['search'] ?? '');
$specializacija = intval($_GET['specializacija'] ?? 0);
$sort = sanitize($_GET['sort'] ?? 'naziv');
$order = sanitize($_GET['order'] ?? 'ASC');

// Build query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(p.naziv LIKE ? OR p.opis LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($specializacija > 0) {
    $where[] = "u.tk_specializacija = ?";
    $params[] = $specializacija;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Validate sort column
$allowedSorts = ['naziv', 'cena', 'trajanje_dni'];
$sort = in_array($sort, $allowedSorts) ? $sort : 'naziv';
$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

$query = "
    SELECT p.*, 
           u.ime as trener_ime, 
           u.priimek as trener_priimek,
           u.slika_profila as trener_slika,
           s.naziv_specializacije,
           (SELECT AVG(ocena) FROM ocene WHERE tk_trener = u.id_uporabnik) as avg_rating,
           (SELECT COUNT(*) FROM ocene WHERE tk_trener = u.id_uporabnik) as rating_count
    FROM programi p
    JOIN uporabniki u ON p.tk_trener = u.id_uporabnik
    LEFT JOIN specializacije s ON u.tk_specializacija = s.id_specializacija
    $whereClause
    ORDER BY p.$sort $order
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$programi = $stmt->fetchAll();

// Get all specializations for filter
$stmt = $pdo->query("SELECT * FROM specializacije ORDER BY naziv_specializacije");
$specializacije = $stmt->fetchAll();

include 'header.php';
?>

<section class="programs-section">
    <div class="container">
        <div class="programs-layout">
            <!-- Sidebar Filters -->
            <aside class="filters-sidebar">
                <div class="filters-card">
                    <h3>Filtriraj programe</h3>
                    <form method="GET" class="filters-form" id="filterForm">
                        <div class="filter-group">
                            <label for="search">🔍 Iskanje</label>
                            <div class="input-wrapper">
                                <input type="text" id="search" name="search" 
                                       placeholder="Iščite programe..." 
                                       value="<?php echo htmlspecialchars($search); ?>"
                                       class="modern-input">
                            </div>
                        </div>

                        <div class="filter-group">
                            <label for="specializacija">📋 Specializacija</label>
                            <div class="select-wrapper">
                                <select id="specializacija" name="specializacija" class="modern-select">
                                    <option value="0">Vse specializacije</option>
                                    <?php foreach ($specializacije as $spec): ?>
                                        <option value="<?php echo $spec['id_specializacija']; ?>" 
                                                <?php echo $specializacija == $spec['id_specializacija'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($spec['naziv_specializacije']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="filter-group">
                            <label for="sort">🔀 Sortiraj po</label>
                            <div class="select-wrapper">
                                <select id="sort" name="sort" class="modern-select">
                                    <option value="naziv" <?php echo $sort === 'naziv' ? 'selected' : ''; ?>>Ime</option>
                                    <option value="cena" <?php echo $sort === 'cena' ? 'selected' : ''; ?>>Cena</option>
                                    <option value="trajanje_dni" <?php echo $sort === 'trajanje_dni' ? 'selected' : ''; ?>>Trajanje</option>
                                </select>
                            </div>
                        </div>

                        <div class="filter-group">
                            <label for="order">📊 Vrstni red</label>
                            <div class="select-wrapper">
                                <select id="order" name="order" class="modern-select">
                                    <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Naraščajoče</option>
                                    <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Padajoče</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Filtriraj</button>
                        <a href="/programi.php" class="btn btn-secondary btn-block">Počisti filtre</a>
                    </form>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="programs-main">
                <h1 class="page-title">Fitnes Programi</h1>
                <p class="results-count">Najdenih: <strong><?php echo count($programi); ?></strong> programov</p>

                <!-- Programs Grid -->
                <div class="programs-grid" id="programsGrid">
            <?php if (empty($programi)): ?>
                <div class="no-results">
                    <p>Ni najdenih programov.</p>
                </div>
            <?php else: ?>
                <?php 
                foreach ($programi as $index => $program): 
                    $programImage = getProgramImageUrl($program['naziv']);
                ?>
                    <div class="program-card" data-program-id="<?php echo $program['id_program']; ?>">
                        <div class="program-image">
                        <img src="<?php echo $programImage; ?>" 
                             alt="<?php echo htmlspecialchars($program['naziv']); ?>"
                             loading="lazy"
                             decoding="async">
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
                                         alt="Trener" class="trainer-avatar" loading="lazy">
                                    <span><?php echo htmlspecialchars($program['trener_ime'] . ' ' . $program['trener_priimek']); ?></span>
                                </div>
                                
                                <?php if ($program['avg_rating'] && $program['rating_count'] > 0): ?>
                                    <div class="rating">
                                        <span class="stars"><?php echo str_repeat('★', max(1, round((float)$program['avg_rating']))); ?></span>
                                        <span class="rating-value"><?php echo number_format((float)$program['avg_rating'], 1); ?></span>
                                        <span class="rating-count">(<?php echo $program['rating_count']; ?>)</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="program-footer">
                                <span class="program-duration"><?php echo $program['trajanje_dni']; ?> dni</span>
                                <span class="program-specialization"><?php echo htmlspecialchars($program['naziv_specializacije'] ?? 'N/A'); ?></span>
                            </div>

                            <a href="/program.php?id=<?php echo $program['id_program']; ?>" 
                               class="btn btn-primary btn-block">Poglej več</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>


<?php include 'footer.php'; ?>


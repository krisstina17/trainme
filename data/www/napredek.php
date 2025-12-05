<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'db.php';

requireLogin();

$errors = [];
$success = false;

// Add progress entry
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_progress'])) {
    $datum = $_POST['datum'] ?? '';
    $teza = floatval($_POST['teza'] ?? 0);
    $opombe = trim($_POST['opombe'] ?? '');

    if (empty($datum) || $teza <= 0) {
        $errors[] = "Datum in teža sta obvezna.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO napredek (tk_uporabnik, datum, teza, opombe)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $datum, $teza, $opombe]);
        $success = true;
    }
}

// Get user progress
$stmt = $pdo->prepare("
    SELECT * FROM napredek 
    WHERE tk_uporabnik = ? 
    ORDER BY datum ASC
");
$stmt->execute([$_SESSION['user_id']]);
$napredek = $stmt->fetchAll();

// Prepare data for chart
$chartLabels = [];
$chartData = [];
foreach ($napredek as $entry) {
    $chartLabels[] = formatDate($entry['datum']);
    $chartData[] = floatval($entry['teza']);
}

include 'header.php';
?>

<section class="progress-section">
    <div class="container">
        <h1 class="page-title">Moj napredek</h1>

        <div class="progress-container">
            <div class="progress-form-card">
                <h2>Dodaj meritev</h2>
                
                <?php 
                if ($success) {
                    showToast('Meritev uspešno dodana!', 'success');
                }
                if (!empty($errors)) {
                    foreach ($errors as $e) {
                        showToast($e, 'error');
                    }
                }
                ?>

                <form method="POST" class="progress-form">
                    <div class="form-group">
                        <label for="datum">Datum</label>
                        <input type="date" id="datum" name="datum" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="teza">Teža (kg)</label>
                        <input type="number" id="teza" name="teza" 
                               step="0.1" min="0" required placeholder="70.5">
                    </div>

                    <div class="form-group">
                        <label for="opombe">Opombe</label>
                        <textarea id="opombe" name="opombe" rows="3" 
                                  placeholder="Kako se počutite? Kakšen je bil trening?"></textarea>
                    </div>

                    <button type="submit" name="add_progress" class="btn btn-primary btn-block">
                        Dodaj meritev
                    </button>
                </form>
            </div>

            <div class="progress-chart-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2 style="margin: 0;">Graf napredka</h2>
                    <?php if (!empty($napredek)): ?>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="/export-progress.php?format=pdf" class="btn btn-sm btn-secondary">📄 PDF</a>
                            <a href="/export-progress.php?format=excel" class="btn btn-sm btn-secondary">📊 Excel</a>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (empty($napredek)): ?>
                    <div class="no-data">
                        <p>Še nimate vpisanih meritev. Dodajte prvo meritev za prikaz grafa.</p>
                    </div>
                <?php else: ?>
                    <canvas id="progressChart"></canvas>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($napredek)): ?>
            <div class="progress-history">
                <h2>Zgodovina meritev</h2>
                <div class="history-table-container">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Datum</th>
                                <th>Teža (kg)</th>
                                <th>Opombe</th>
                                <th>Razlika</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $previousWeight = null;
                            foreach (array_reverse($napredek) as $entry): 
                                $currentWeight = floatval($entry['teza']);
                                $difference = $previousWeight !== null ? $currentWeight - $previousWeight : null;
                                $previousWeight = $currentWeight;
                            ?>
                                <tr>
                                    <td><?php echo formatDate($entry['datum']); ?></td>
                                    <td><strong><?php echo number_format($currentWeight, 2); ?> kg</strong></td>
                                    <td><?php echo htmlspecialchars($entry['opombe'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($difference !== null): ?>
                                            <span class="<?php echo $difference < 0 ? 'weight-loss' : ($difference > 0 ? 'weight-gain' : ''); ?>">
                                                <?php echo $difference > 0 ? '+' : ''; ?><?php echo number_format($difference, 2); ?> kg
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($napredek)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('progressChart').getContext('2d');
const progressChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chartLabels); ?>,
        datasets: [{
            label: 'Teža (kg)',
            data: <?php echo json_encode($chartData); ?>,
            borderColor: '#2d6cdf',
            backgroundColor: 'rgba(45, 108, 223, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointHoverRadius: 7,
            pointBackgroundColor: '#2d6cdf',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                mode: 'index',
                intersect: false
            }
        },
        scales: {
            y: {
                beginAtZero: false,
                title: {
                    display: true,
                    text: 'Teža (kg)'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Datum'
                }
            }
        },
        interaction: {
            mode: 'nearest',
            axis: 'x',
            intersect: false
        }
    }
});

// Store chart data in localStorage
localStorage.setItem('progressChartData', JSON.stringify({
    labels: <?php echo json_encode($chartLabels); ?>,
    data: <?php echo json_encode($chartData); ?>
}));
</script>
<?php endif; ?>

<?php include 'footer.php'; ?>


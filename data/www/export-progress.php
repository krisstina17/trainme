<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'db.php';

requireLogin();

$format = $_GET['format'] ?? 'pdf';

// Get user progress
$stmt = $pdo->prepare("
    SELECT * FROM napredek 
    WHERE tk_uporabnik = ? 
    ORDER BY datum ASC
");
$stmt->execute([$_SESSION['user_id']]);
$napredek = $stmt->fetchAll();

$user = getCurrentUser();

if ($format === 'pdf') {
    // Use JavaScript to generate PDF
    header('Content-Type: text/html; charset=UTF-8');
    
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Graf napredka</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h1 { color: #2d6cdf; }
        .info-box { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #2d6cdf; color: white; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body>
    <div id="content">
        <h1>Graf napredka - ' . htmlspecialchars($user['ime'] . ' ' . $user['priimek'], ENT_QUOTES, 'UTF-8') . '</h1>
        <div class="info-box">
            <p><strong>Število meritev:</strong> ' . count($napredek) . '</p>
            <p><strong>Prva meritev:</strong> ' . (!empty($napredek) ? formatDate($napredek[0]['datum']) : '-') . '</p>
            <p><strong>Zadnja meritev:</strong> ' . (!empty($napredek) ? formatDate($napredek[count($napredek)-1]['datum']) : '-') . '</p>
            <p><strong>Začetna teža:</strong> ' . (!empty($napredek) ? number_format(floatval($napredek[0]['teza']), 2) . ' kg' : '-') . '</p>
            <p><strong>Trenutna teža:</strong> ' . (!empty($napredek) ? number_format(floatval($napredek[count($napredek)-1]['teza']), 2) . ' kg' : '-') . '</p>';
    
    if (!empty($napredek) && count($napredek) > 1) {
        $change = floatval($napredek[count($napredek)-1]['teza']) - floatval($napredek[0]['teza']);
        echo '<p><strong>Skupna sprememba:</strong> ' . ($change > 0 ? '+' : '') . number_format($change, 2) . ' kg</p>';
    }
    
    echo '</div>
        <table>
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Teža (kg)</th>
                    <th>Opombe</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($napredek as $entry) {
        echo '<tr>
            <td>' . formatDate($entry['datum']) . '</td>
            <td>' . number_format(floatval($entry['teza']), 2) . '</td>
            <td>' . htmlspecialchars($entry['opombe'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>
        </tr>';
    }
    
    echo '</tbody></table>
    </div>
    <script>
        window.onload = function() {
            const { jsPDF } = window.jspdf;
            html2canvas(document.getElementById("content")).then(canvas => {
                const imgData = canvas.toDataURL("image/png");
                const pdf = new jsPDF("p", "mm", "a4");
                const imgWidth = 210;
                const pageHeight = 295;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                let heightLeft = imgHeight;
                let position = 0;
                
                pdf.addImage(imgData, "PNG", 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
                
                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, "PNG", 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }
                
                pdf.save("napredek_' . date('Y-m-d') . '.pdf");
            });
        };
        </script>
</body>
</html>';
    exit;
    
} elseif ($format === 'excel') {
    // Export as CSV (Excel compatible) with proper formatting
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="napredek_' . date('Y-m-d') . '.csv"');
    
    // Disable error display to prevent output corruption
    error_reporting(0);
    ini_set('display_errors', 0);
    
    // Add BOM for UTF-8 (Excel compatibility)
    echo "\xEF\xBB\xBF";
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Helper function to clean data for CSV
    $cleanData = function($data) {
        // Remove HTML tags
        $data = strip_tags($data);
        // Remove line breaks and replace with space
        $data = str_replace(["\r\n", "\r", "\n"], ' ', $data);
        // Trim whitespace
        $data = trim($data);
        return $data;
    };
    
    // Add headers with user info
    fputcsv($output, ['Uporabnik', $cleanData($user['ime'] . ' ' . $user['priimek'])], ';', '"', '\\');
    fputcsv($output, [], ';', '"', '\\'); // Empty row
    fputcsv($output, ['Datum', 'Teža (kg)', 'Opombe'], ';', '"', '\\');
    
    // Add data
    foreach ($napredek as $entry) {
        $opombe = $entry['opombe'] ?? '-';
        // Clean opombe - remove HTML and special characters
        $opombe = $cleanData($opombe);
        if (empty($opombe)) {
            $opombe = '-';
        }
        
        fputcsv($output, [
            formatDate($entry['datum']),
            number_format(floatval($entry['teza']), 2),
            $opombe
        ], ';', '"', '\\');
    }
    
    fclose($output);
    exit;
}

header('Location: /napredek.php');
exit;

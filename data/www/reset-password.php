<?php
/**
 * Skripta za ponastavitev gesla uporabnika
 * Uporaba: Odprite v brskalniku in sledite navodilom
 */

require_once 'db.php';

// Preveri, ali je zahteva POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $newPassword = $_POST['password'] ?? '';
    
    if (empty($email) || empty($newPassword)) {
        $error = "Email in geslo sta obvezna.";
    } else {
        // Hash gesla
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        
        // Posodobi geslo v bazi
        $stmt = $pdo->prepare("UPDATE uporabniki SET geslo_hash = ? WHERE email = ?");
        $stmt->execute([$hash, $email]);
        
        if ($stmt->rowCount() > 0) {
            $success = "Geslo je bilo uspešno posodobljeno!";
        } else {
            $error = "Uporabnik s tem emailom ni bil najden.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ponastavitev gesla - TrainMe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2d6cdf;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            background: #2d6cdf;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background: #1e4ebd;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Ponastavitev gesla</h1>
        
        <?php if (isset($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email uporabnika:</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? 'luka@example.com'); ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="password">Novo geslo:</label>
                <input type="password" id="password" name="password" 
                       value="<?php echo htmlspecialchars($_POST['password'] ?? 'trener123'); ?>" 
                       required>
            </div>
            
            <button type="submit">Ponastavi geslo</button>
        </form>
        
        <p style="margin-top: 20px; color: #666; font-size: 14px;">
            <strong>Opomba:</strong> Po ponastavitvi gesla se lahko prijavite z novim geslom.
        </p>
    </div>
</body>
</html>


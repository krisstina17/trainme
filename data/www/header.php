<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/toast.php';

$currentUser = isLoggedIn() ? getCurrentUser() : null;
$isTrainer = isLoggedIn() && isTrainer();
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>TrainMe</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
<?php require_once __DIR__ . '/includes/toast.php'; ?>

<nav class="navbar">
    <div class="navbar-container">
        <a href="/index.php" class="logo">
            <img src="/assets/img/logo.svg" alt="TrainMe" class="logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
            <span class="logo-text">TrainMe</span>
        </a>
        <button class="mobile-menu-toggle" id="mobileMenuToggle">☰</button>
        <ul class="nav-menu" id="navMenu">
            <li><a href="/index.php">Domov</a></li>
            <li><a href="/programi.php">Programi</a></li>
            <?php if (isLoggedIn()): ?>
                <?php if ($isTrainer): ?>
                    <li><a href="/trainer/dashboard.php">Dashboard</a></li>
                    <li><a href="/trainer/dashboard.php">Moji programi</a></li>
                <?php else: ?>
                    <li><a href="/napredek.php">Napredek</a></li>
                    <li><a href="/moji-programi.php">Moji programi</a></li>
                <?php endif; ?>
                <li class="nav-user">
                    <a href="#" class="user-menu-toggle">
                        <?php if ($currentUser): 
                            $userAvatar = $currentUser['slika_profila'] 
                                ? '/uploads/' . $currentUser['slika_profila'] 
                                : getAvatarUrl($currentUser['id_uporabnik']);
                        ?>
                            <img src="<?php echo htmlspecialchars($userAvatar); ?>" 
                                 alt="Profil" 
                                 class="user-avatar-small"
                                 loading="lazy" decoding="async">
                        <?php endif; ?>
                        <span><?php echo htmlspecialchars($currentUser['ime'] ?? 'Uporabnik'); ?></span>
                        <?php if ($isTrainer): ?>
                            <span class="user-badge trainer-badge" title="Trener">👨‍🏫</span>
                        <?php endif; ?>
                        <span class="dropdown-arrow">▼</span>
                    </a>
                    <ul class="user-dropdown">
                        <li><a href="/profil.php">Moj profil</a></li>
                        <?php if (!$isTrainer): ?>
                            <li><a href="/napredek.php">Napredek</a></li>
                        <?php endif; ?>
                        <li><a href="/logout.php">Odjava</a></li>
                    </ul>
                </li>
            <?php else: ?>
                <li><a href="/login.php">Prijava</a></li>
                <li><a href="/register.php" class="btn-nav">Registracija</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

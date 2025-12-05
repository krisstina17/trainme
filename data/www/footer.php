<footer class="footer">
    <div class="footer-container">
        <div class="footer-section">
            <h3>TrainMe</h3>
            <p>Vaša platforma za fitnes programe in treninge.</p>
        </div>
        <div class="footer-section">
            <h4>Povezave</h4>
            <ul>
                <li><a href="/index.php">Domov</a></li>
                <li><a href="/programi.php">Programi</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="/napredek.php">Napredek</a></li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="footer-section">
            <h4>Kontakt</h4>
            <p>Email: info@trainme.com</p>
            <p>Telefon: +386 1 234 5678</p>
        </div>
        <div class="footer-section">
            <h4>Bližnji fitnes centri</h4>
            <a href="/fitnes-centri.php" class="btn btn-sm btn-secondary">Poglej na karti</a>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?php echo date("Y"); ?> TrainMe – Vse pravice pridržane.
    </div>
</footer>

<script>
// Mobile menu toggle
document.getElementById('mobileMenuToggle')?.addEventListener('click', function() {
    document.getElementById('navMenu')?.classList.toggle('active');
});

// User dropdown
document.querySelector('.user-menu-toggle')?.addEventListener('click', function(e) {
    e.preventDefault();
    this.nextElementSibling?.classList.toggle('active');
});

// Close dropdowns on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.nav-user')) {
        document.querySelector('.user-dropdown')?.classList.remove('active');
    }
});
</script>

</body>
</html>

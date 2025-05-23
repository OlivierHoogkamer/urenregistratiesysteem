<div class="sidebar d-flex flex-column">
    <h4 class="mb-4">👮 Admin Paneel</h4>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="admin_home.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'admin_home.php') ? 'active' : '' ?>">🏠 Dashboard</a>
        </li>
        <li class="nav-item">
            <a href="uren.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'uren.php') ? 'active' : '' ?>">🕒 Urenregistratie</a>
        </li>
        <li class="nav-item">
            <a href="werknemers.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'werknemers.php') ? 'active' : '' ?>">👥 Werknemers</a>
        </li>
        <li class="nav-item">
            <a href="instellingen.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'instellingen.php') ? 'active' : '' ?>">⚙️ Instellingen</a>
        </li>
        <li class="nav-item mt-4">
            <a href="logout.php" class="nav-link text-danger">🚪 Uitloggen</a>
        </li>
    </ul>
</div>
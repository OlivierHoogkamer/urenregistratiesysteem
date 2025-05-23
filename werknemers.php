<?php
session_start();
$breadcrumb = [
    ['label' => 'Home', 'url' => 'admin_home.php'],
    ['label' => 'Werknemers']
];
// Check admin
if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] != 1) {
    header("Location: index.php");
    exit;
}

require 'includes/db.php';

// Zoekfilter
$zoek = $_GET['zoek'] ?? '';

// Query met filter
if ($zoek) {
    $stmt = $pdo->prepare("SELECT id, naam, email, is_admin FROM users WHERE naam LIKE ? OR email LIKE ? ORDER BY naam");
    $zoekParam = '%' . $zoek . '%';
    $stmt->execute([$zoekParam, $zoekParam]);
} else {
    $stmt = $pdo->query("SELECT id, naam, email, is_admin FROM users ORDER BY naam");
}
$werknemers = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8" />
    <title>Werknemers – Urenregistratie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />
</head>
<body>
<header class="topbar">
   <?php include('includes/header.php') ?>
</header>

<?php include('includes/sidebar.php') ?>

<div class="main">
    <h2 class="mb-4">👥 Werknemers</h2>

    <!-- Zoekfilter -->
    <form method="GET" class="mb-4 row g-3 align-items-center" id="zoekForm" novalidate>
        <div class="col-md-6">
            <input
                type="text"
                name="zoek"
                id="zoek"
                class="form-control"
                placeholder="Zoek op naam of e-mail"
                value="<?= htmlspecialchars($zoek) ?>"
                autocomplete="off"
            />
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Zoeken</button>
            <a href="werknemers.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <!-- Tabel werknemers -->
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Naam</th>
                    <th>E-mail</th>
                    <th>Rol</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($werknemers) > 0): ?>
                    <?php foreach ($werknemers as $w): ?>
                        <tr data-href="werknemer_detail.php?id=<?= (int)$w['id'] ?>" style="cursor:pointer;">
                            <td><?= htmlspecialchars($w['naam']) ?></td>
                            <td><?= htmlspecialchars($w['email']) ?></td>
                            <td>
                                <?php if ($w['is_admin'] == 1): ?>
                                    <span class="badge bg-success">Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Werknemer</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="werknemer_verwijderen_bevestigen.php?id=<?= (int)$w['id'] ?>" 
                                   class="btn btn-outline-danger btn-sm" 
                                   title="Verwijderen"
                                   onclick="event.stopPropagation();">
                                   🗑️
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">Geen werknemers gevonden</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('tr[data-href]').forEach(function(row) {
        row.addEventListener('click', function() {
            window.location.href = this.dataset.href;
        });
    });
});
</script>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] != 1) {
    header("Location: index.php");
    exit;
}

require 'includes/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: werknemers.php');
    exit;
}

// Data ophalen om naam te tonen
$stmt = $pdo->prepare("SELECT naam FROM users WHERE id = ?");
$stmt->execute([$id]);
$werknemer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$werknemer) {
    header('Location: werknemers.php');
    exit;
}

// Verwijderen na bevestiging
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Voorkom verwijderen van jezelf
    if ($id == $_SESSION['user']['id']) {
        header('Location: werknemers.php?error=Je+kan+jezelf+niet+verwijderen');
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    header('Location: werknemers.php?success=Werknemer+verwijderd');
    exit;
}
$breadcrumb = [
    ['label' => 'Home', 'url' => 'admin_home.php'],
    ['label' => 'Werknemers', 'url' => 'werknemers.php'],
    ['label' => $werknemer ? $werknemer['naam'] : 'Onbekende werknemer', 'url' => $werknemer ? 'werknemer_detail.php?id=' . $id : '#'],
    ['label' => 'Werknemer verwijderen', 'url' => 'werknemers.php'],

];
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8" />
    <title>Bevestig Verwijderen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />
</head>
<body>
<header class="topbar">
    <?php include('includes/header.php') ?>
</header>

<?php include('includes/sidebar.php') ?>

<div class="main">
    <h2>Bevestig verwijderen</h2>

    <p>Weet je zeker dat je werknemer <strong><?= htmlspecialchars($werknemer['naam']) ?></strong> wilt verwijderen? Dit kan niet ongedaan gemaakt worden.</p>

    <form method="POST">
        <button type="submit" class="btn btn-danger">Ja, verwijderen</button>
        <a href="werknemers.php" class="btn btn-secondary">Annuleren</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

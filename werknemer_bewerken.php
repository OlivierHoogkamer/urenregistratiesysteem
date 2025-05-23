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

// Data ophalen
$stmt = $pdo->prepare("SELECT id, naam, email, is_admin FROM users WHERE id = ?");
$stmt->execute([$id]);
$werknemer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$werknemer) {
    header('Location: werknemers.php');
    exit;
}

// Form verstuurd?
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $naam = trim($_POST['naam'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    if ($naam === '') $errors[] = "Naam is verplicht.";
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Ongeldig e-mailadres.";

    if (empty($errors)) {
        $update = $pdo->prepare("UPDATE users SET naam = ?, email = ?, is_admin = ? WHERE id = ?");
        $update->execute([$naam, $email, $is_admin, $id]);
        header('Location: werknemers.php');
        exit;
    }
}
$breadcrumb = [
    ['label' => 'Home', 'url' => 'admin_home.php'],
    ['label' => 'Werknemers', 'url' => 'werknemers.php'],
    ['label' => $werknemer ? $werknemer['naam'] : 'Onbekende werknemer', 'url' => $werknemer ? 'werknemer_detail.php?id=' . $id : '#'],
    ['label' => 'Werknemer bewerken', 'url' => 'werknemers.php']
];
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8" />
    <title>Werknemer Bewerken</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />
</head>
<body>
<header class="topbar">
    <?php include('includes/header.php') ?>
</header>

<?php include('includes/sidebar.php') ?>

<div class="main">
    <h2>Werknemer Bewerken</h2>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach($errors as $fout): ?>
                    <li><?= htmlspecialchars($fout) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="mb-4">
        <div class="mb-3">
            <label for="naam" class="form-label">Naam</label>
            <input type="text" name="naam" id="naam" class="form-control" value="<?= htmlspecialchars($_POST['naam'] ?? $werknemer['naam']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? $werknemer['email']) ?>" required>
        </div>
        <div class="form-check mb-3">
            <input type="checkbox" name="is_admin" id="is_admin" class="form-check-input" <?= ((isset($_POST['is_admin']) ? $_POST['is_admin'] : $werknemer['is_admin']) == 1) ? 'checked' : '' ?>>
            <label for="is_admin" class="form-check-label">Admin-rechten</label>
        </div>
        <button type="submit" class="btn btn-primary">Opslaan</button>
        <a href="werknemers.php" class="btn btn-secondary">Annuleren</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

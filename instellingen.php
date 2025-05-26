<?php
session_start();
require 'includes/db.php';

// Check admin access
if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] != 1) {
    header("Location: index.php");
    exit;
}

$message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $theme = $_POST['theme'] ?? 'light';
    $language = $_POST['language'] ?? 'nl';
    $notifications = isset($_POST['notifications']) ? 1 : 0;
    $avatar = $_FILES['avatar'] ?? null;

    // File upload handling for avatar
    if ($avatar && $avatar['size'] > 0) {
        $avatarPath = 'uploads/' . time() . '_' . basename($avatar['name']);
        move_uploaded_file($avatar['tmp_name'], $avatarPath);
    } else {
        $avatarPath = $_SESSION['user']['avatar'] ?? 'default-avatar.png';
    }

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET email = ?, password = ?, theme = ?, language = ?, notifications = ?, avatar = ? WHERE id = ?");
        $stmt->execute([$email, $hashed_password, $theme, $language, $notifications, $avatarPath, $_SESSION['user']['id']]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET email = ?, theme = ?, language = ?, notifications = ?, avatar = ? WHERE id = ?");
        $stmt->execute([$email, $theme, $language, $notifications, $avatarPath, $_SESSION['user']['id']]);
    }

    $message = "Instellingen bijgewerkt!";
}

// Fetch current settings
$stmt = $pdo->prepare("SELECT email, theme, language, notifications, avatar FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user']['id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Instellingen – Urenregistratie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar">
    <?php include('includes/header.php') ?>
</header>

<?php include('includes/sidebar.php') ?>

<div class="main">
    <h2 class="mb-4">⚙️ Instellingen</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" class="mb-4" enctype="multipart/form-data">
        <!-- Profile Picture -->
        <div class="mb-3">
            <label class="form-label">Profielfoto</label><br>
            <img src="<?= htmlspecialchars($user['avatar']) ?>" class="rounded-circle" width="100"><br>
            <input type="file" name="avatar" class="form-control mt-2">
        </div>

        <!-- Email -->
        <div class="mb-3">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($user['email']) ?>">
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label class="form-label">Nieuw wachtwoord (optioneel)</label>
            <input type="password" name="password" class="form-control">
        </div>

        <!-- Theme -->
        <div class="mb-3">
            <label class="form-label">Thema</label>
            <select name="theme" class="form-select">
                <option value="light" <?= $user['theme'] === 'light' ? 'selected' : '' ?>>Licht</option>
                <option value="dark" <?= $user['theme'] === 'dark' ? 'selected' : '' ?>>Donker</option>
            </select>
        </div>

        <!-- Language -->
        <div class="mb-3">
            <label class="form-label">Taal</label>
            <select name="language" class="form-select">
                <option value="nl" <?= $user['language'] === 'nl' ? 'selected' : '' ?>>Nederlands</option>
                <option value="en" <?= $user['language'] === 'en' ? 'selected' : '' ?>>Engels</option>
            </select>
        </div>

        <!-- Notifications -->
        <div class="mb-3 form-check">
            <input type="checkbox" id="notifications" name="notifications" class="form-check-input" <?= $user['notifications'] ? 'checked' : '' ?>>
            <label class="form-check-label">Meldingen ontvangen</label>
        </div>

        <button type="submit" class="btn btn-primary">Instellingen opslaan</button>
    </form>

    <!-- Backup Database -->
    <h3 class="mt-4">🛠️ Database Backup</h3>
    <a href="backup.php" class="btn btn-outline-secondary">Maak een back-up</a>

    <!-- Enable 2FA -->
    <h3 class="mt-4">🔒 Twee-factor authenticatie</h3>
    <p>Verhoog je accountbeveiliging door 2FA in te schakelen.</p>
    <a href="enable_2fa.php" class="btn btn-outline-success">Schakel 2FA in</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

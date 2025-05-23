<?php
session_start();
require_once 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pin = trim($_POST['pin'] ?? '');

    if ($email && $pin) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND pin = ? LIMIT 1");
        $stmt->execute([$email, $pin]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user'] = $user;
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Ongeldige combinatie van e-mail en pincode.";
        }
    } else {
        $error = "Vul alle velden in.";
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Inloggen</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
  <div class="card shadow p-4" style="width: 100%; max-width: 400px;">
    <h4 class="mb-3 text-center">Urenregistratie</h4>
    
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="index.php">
      <div class="mb-3">
        <label for="email" class="form-label">E-mailadres</label>
        <input type="email" class="form-control" name="email" id="email" required>
      </div>
      <div class="mb-3">
        <label for="pin" class="form-label">Pincode</label>
        <input type="password" class="form-control" name="pin" id="pin" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Inloggen</button>
    </form>
  </div>
</body>
</html>

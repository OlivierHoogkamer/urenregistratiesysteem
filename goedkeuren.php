<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] != 1) {
  header("Location: index.php");
  exit;
}

require 'includes/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
  header("Location: admin.php");
  exit;
}

// Haal de shift op
$stmt = $pdo->prepare("SELECT shifts.*, users.naam FROM shifts JOIN users ON shifts.user_id = users.id WHERE shifts.id = ?");
$stmt->execute([$id]);
$shift = $stmt->fetch();

if (!$shift) {
  echo "Shift niet gevonden.";
  exit;
}

// Verwerk formulier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $start = $_POST['start_time'] ?? '';
  $end = $_POST['end_time'] ?? '';

  $stmt = $pdo->prepare("UPDATE shifts SET start_time = ?, end_time = ?, approved = 1 WHERE id = ?");
  $stmt->execute([$start, $end, $id]);

  header("Location: uren.php");
  exit;
}

?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <title>Shift goedkeuren</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3>👁 Goedkeuren shift van <?= htmlspecialchars($shift['naam']) ?></h3>
      <a href="admin.php" class="btn btn-secondary">↩️ Terug</a>
    </div>

    <form method="POST" class="card p-4 shadow-sm">
      <div class="mb-3">
        <label for="start_time" class="form-label">Starttijd</label>
        <input type="datetime-local" name="start_time" class="form-control" required
               value="<?= date('Y-m-d\TH:i', strtotime($shift['start_time'])) ?>">
      </div>
      <div class="mb-3">
        <label for="end_time" class="form-label">Eindtijd</label>
        <input type="datetime-local" name="end_time" class="form-control" required
               value="<?= $shift['end_time'] ? date('Y-m-d\TH:i', strtotime($shift['end_time'])) : '' ?>">
      </div>
      <button type="submit" class="btn btn-success">✅ Goedkeuren en opslaan</button>
    </form>
  </div>
</body>
</html>

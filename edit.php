<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: index.php");
  exit;
}

require 'includes/db.php';

$user_id = $_SESSION['user']['id'];
$shift_id = $_GET['id'] ?? null;
$melding = null;

// Haal de shift op
$stmt = $pdo->prepare("SELECT * FROM shifts WHERE id = ? AND user_id = ?");
$stmt->execute([$shift_id, $user_id]);
$shift = $stmt->fetch();

if (!$shift) {
  die("Shift niet gevonden of geen toegang.");
}

// Als er een POST is, werk de shift bij
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $start = $_POST['start_time'] ?? null;
  $end = $_POST['end_time'] ?? null;

  // Validatie (optioneel: meer checks op datumformaat)
  if (!$start) {
    $melding = "Starttijd is verplicht.";
  } else {
    $query = "UPDATE shifts SET start_time = ?, end_time = ?, approved = 0 WHERE id = ? AND user_id = ?";
    $pdo->prepare($query)->execute([$start, $end ?: null, $shift_id, $user_id]);

    header("Location: dashboard.php");
    exit;
  }
}

function formatForInput($datetime) {
  return $datetime ? date('Y-m-d\TH:i', strtotime($datetime)) : '';
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <title>Shift aanpassen</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="mb-4">
      <a href="dashboard.php" class="btn btn-secondary">⬅️ Terug naar dashboard</a>
    </div>

    <h3>Shift aanpassen</h3>

    <?php if ($melding): ?>
      <div class="alert alert-warning"><?= htmlspecialchars($melding) ?></div>
    <?php endif; ?>

    <form method="POST" class="card p-4 shadow-sm">
      <div class="mb-3">
        <label for="start_time" class="form-label">Starttijd</label>
        <input type="datetime-local" name="start_time" id="start_time" class="form-control" required
          value="<?= formatForInput($shift['start_time']) ?>">
      </div>

      <div class="mb-3">
        <label for="end_time" class="form-label">Eindtijd</label>
        <input type="datetime-local" name="end_time" id="end_time" class="form-control"
          value="<?= formatForInput($shift['end_time']) ?>">
      </div>

      <button type="submit" class="btn btn-primary">💾 Opslaan</button>
    </form>
  </div>
</body>
</html>

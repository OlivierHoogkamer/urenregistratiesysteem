<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: index.php");
  exit;
}
require 'includes/db.php';

$user_id = $_SESSION['user']['id'];
$naam = $_SESSION['user']['naam'] ?? 'Gebruiker';

$melding = null;
$breadcrumb = [
    ['label' => 'Home', 'url' => 'admin_home.php'],
];
// Start/stop registratie
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['start'])) {
    $check = $pdo->prepare("SELECT COUNT(*) FROM shifts WHERE user_id = ? AND end_time IS NULL");
    $check->execute([$user_id]);
    $openShiftCount = $check->fetchColumn();

    if ($openShiftCount == 0) {
      $stmt = $pdo->prepare("INSERT INTO shifts (user_id, start_time) VALUES (?, NOW())");
      $stmt->execute([$user_id]);
    } else {
      $melding = "Je hebt al een actieve shift lopen. Stop deze eerst.";
    }
  }

  if (isset($_POST['stop'])) {
    $stmt = $pdo->prepare("UPDATE shifts SET end_time = NOW() WHERE user_id = ? AND end_time IS NULL ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id]);
  }
}

// Haal geselecteerde maand op
$selectedMonth = $_GET['month'] ?? date('Y-m');
$startOfMonth = $selectedMonth . '-01 00:00:00';
$endOfMonth = date('Y-m-t 23:59:59', strtotime($startOfMonth));

// Query alle shifts in die maand
$stmt = $pdo->prepare("
  SELECT * FROM shifts 
  WHERE user_id = ? AND start_time BETWEEN ? AND ? 
  ORDER BY start_time DESC
");
$stmt->execute([$user_id, $startOfMonth, $endOfMonth]);
$shifts = $stmt->fetchAll();

// Bereken totaaluren
$totaalUren = 0;
$totaalMinuten = 0;
foreach ($shifts as $shift) {
  if (!empty($shift['end_time'])) {
    $start = new DateTime($shift['start_time']);
    $end = new DateTime($shift['end_time']);
    $diff = $start->diff($end);
    $totaalUren += $diff->h;
    $totaalMinuten += $diff->i;
  }
}
$totaalUren += floor($totaalMinuten / 60);
$totaalMinuten = $totaalMinuten % 60;

// Maandnaam in NL
$maandnamen = [
  '01' => 'januari', '02' => 'februari', '03' => 'maart',
  '04' => 'april', '05' => 'mei', '06' => 'juni',
  '07' => 'juli', '08' => 'augustus', '09' => 'september',
  '10' => 'oktober', '11' => 'november', '12' => 'december'
];
$maandnummer = substr($selectedMonth, 5, 2);
$jaar = substr($selectedMonth, 0, 4);
$maandNaamNL = $maandnamen[$maandnummer] . " " . $jaar;
?>

<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3>Welkom, <?= htmlspecialchars($naam) ?></h3>
      <a href="logout.php" class="btn btn-outline-secondary">Uitloggen</a>
    </div>

    <?php if ($melding): ?>
      <div class="alert alert-warning"><?= htmlspecialchars($melding) ?></div>
    <?php endif; ?>

    <?php if (!empty($shifts[0]) && empty($shifts[0]['end_time'])): ?>
      <div class="alert alert-success">
        🟢 Actieve shift sinds <?= (new DateTime($shifts[0]['start_time']))->format('H:i') ?>
      </div>
    <?php endif; ?>

    <div class="card mb-4 shadow-sm">
      <div class="card-body text-center">
        <h5 class="card-title">Shift bediening</h5>
        <form method="POST">
          <button type="submit" name="start" class="btn btn-success m-2">
            ✅ Start shift
          </button>
          <button type="submit" name="stop" class="btn btn-danger m-2">
            🛑 Stop shift
          </button>
        </form>
      </div>
    </div>

  <div class="alert alert-info d-flex justify-content-between align-items-center">
  <div>
    🧮 Totaal gewerkt in <?= htmlspecialchars($maandNaamNL) ?>: <strong><?= $totaalUren ?>u <?= $totaalMinuten ?>m</strong>
  </div>
  <a href="loonstrook.php?month=<?= urlencode($selectedMonth) ?>" class="btn btn-outline-primary btn-sm">
    📄 Bekijk loonstrook
  </a>
</div>


    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Shifts deze maand</h5>

        <div class="d-flex flex-wrap align-items-center mb-3">
          <a href="?month=<?= date('Y-m', strtotime($selectedMonth . ' -1 month')) ?>" class="btn btn-secondary me-2 mb-2">Vorige maand</a>
          <a href="?month=<?= date('Y-m', strtotime($selectedMonth . ' +1 month')) ?>" class="btn btn-secondary mb-2">Volgende maand</a>

          <form method="GET" class="ms-auto" style="max-width: 300px;">
            <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($selectedMonth) ?>" onchange="this.form.submit()">
          </form>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Datum</th>
                <th>Start</th>
                <th>Eind</th>
                <th>Duur</th>
                <th>Status</th>
                <th>Acties</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($shifts as $shift): ?>
                <?php
                  $start = new DateTime($shift['start_time']);
                  $eind = $shift['end_time'] ? new DateTime($shift['end_time']) : null;
                  $duur = $eind ? $start->diff($eind) : null;

                  if (is_null($shift['end_time'])) {
                    $statusClass = 'bg-secondary';
                    $statusText = 'Actief';
                  } else {
                    $statusClass = $shift['approved'] == 1 ? 'bg-success' : 'bg-warning';
                    $statusText = $shift['approved'] == 1 ? 'Goedgekeurd' : 'In afwachting';
                  }
                ?>
                <tr>
                  <td><?= $start->format('d-m-Y') ?></td>
                  <td><?= $start->format('H:i') ?></td>
                  <td><?= $eind ? $eind->format('H:i') : '-' ?></td>
                  <td><?= $duur ? $duur->h . 'u ' . $duur->i . 'm' : '-' ?></td>
                  <td><span class="badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                  <td>
                    <?php if (empty($shift['end_time'])): ?>
                      <a href="edit.php?id=<?= $shift['id'] ?>" class="btn btn-sm btn-outline-warning">
                        ⏱
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if (count($shifts) === 0): ?>
                <tr>
                  <td colspan="6" class="text-muted text-center">Geen shifts deze maand</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</body>
</html>

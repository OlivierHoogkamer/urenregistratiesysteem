<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: index.php");
  exit;
}
require 'includes/db.php';

$user_id = $_SESSION['user']['id'];
$naam = $_SESSION['user']['naam'] ?? 'Gebruiker';

$selectedMonth = $_GET['month'] ?? date('Y-m');
$startOfMonth = $selectedMonth . '-01 00:00:00';
$endOfMonth = date('Y-m-t 23:59:59', strtotime($startOfMonth));

$stmt = $pdo->prepare("
  SELECT * FROM shifts 
  WHERE user_id = ? AND start_time BETWEEN ? AND ? AND end_time IS NOT NULL
  ORDER BY start_time ASC
");
$stmt->execute([$user_id, $startOfMonth, $endOfMonth]);
$shifts = $stmt->fetchAll();

$totaalUren = 0;
$totaalMinuten = 0;

$uurloon = 12.50; // vast uurloon
$shiftsDetail = [];

foreach ($shifts as $shift) {
  $start = new DateTime($shift['start_time']);
  $end = new DateTime($shift['end_time']);
  $diff = $start->diff($end);
  $uren = $diff->h;
  $minuten = $diff->i;

  $totaalUren += $uren;
  $totaalMinuten += $minuten;

  $totaalUrenDec = $uren + ($minuten / 60);
  $dagloon = $totaalUrenDec * $uurloon;

  $shiftsDetail[] = [
    'datum' => $start->format('d-m-Y'),
    'start' => $start->format('H:i'),
    'eind' => $end->format('H:i'),
    'uren' => $uren,
    'minuten' => $minuten,
    'loon' => $dagloon
  ];
}

$totaalUren += floor($totaalMinuten / 60);
$totaalMinuten = $totaalMinuten % 60;
$totaalUrenDecimaal = $totaalUren + ($totaalMinuten / 60);
$brutoLoon = $totaalUrenDecimaal * $uurloon;

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
  <title>Loonstrook - <?= htmlspecialchars($maandNaamNL) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <h3 class="mb-4">📄 Verwachte loonstrook - <?= htmlspecialchars($maandNaamNL) ?></h3>

    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <p><strong>Naam:</strong> <?= htmlspecialchars($naam) ?></p>
        <p><strong>Gewerkte tijd:</strong> <?= $totaalUren ?>u <?= $totaalMinuten ?>m</p>
        <p><strong>Uurloon:</strong> €<?= number_format($uurloon, 2, ',', '.') ?></p>
        <hr>
        <h5>💰 Totaal bruto loon: <span class="text-success">€<?= number_format($brutoLoon, 2, ',', '.') ?></span></h5>
      </div>
    </div>

    <?php if (count($shiftsDetail) > 0): ?>
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h5 class="mb-3">📆 Overzicht van alle shifts:</h5>
        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th>Datum</th>
                <th>Start</th>
                <th>Eind</th>
                <th>Duur</th>
                <th>Verdiend</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($shiftsDetail as $shift): ?>
                <tr>
                  <td><?= $shift['datum'] ?></td>
                  <td><?= $shift['start'] ?></td>
                  <td><?= $shift['eind'] ?></td>
                  <td><?= $shift['uren'] ?>u <?= $shift['minuten'] ?>m</td>
                  <td>€<?= number_format($shift['loon'], 2, ',', '.') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php else: ?>
      <div class="alert alert-info">Er zijn geen gewerkte shifts in deze maand.</div>
    <?php endif; ?>

    <a href="dashboard.php?month=<?= urlencode($selectedMonth) ?>" class="btn btn-secondary">← Terug naar dashboard</a>
  </div>
</body>
</html>

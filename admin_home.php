<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] != 1) {
  header("Location: index.php");
  exit;
}

require 'includes/db.php';

$totaalShifts = $pdo->query("SELECT COUNT(*) FROM shifts")->fetchColumn();
$totaalGebruikers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$goedTeKeuren = $pdo->query("SELECT COUNT(*) FROM shifts WHERE approved = 0 AND end_time IS NOT NULL")->fetchColumn();
$incompleet = $pdo->query("SELECT COUNT(*) FROM shifts WHERE end_time IS NULL")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <title>Admin Hoofddashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css" />

</head>
<body>
<header class="topbar">
   <?php include('includes/header.php')?>
</header>

<!-- Sidebar -->
<?php include('includes/sidebar.php') ?>

<!-- Main content -->
<div class="main">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Welkom terug, <?= htmlspecialchars($_SESSION['user']['naam']) ?> 👋</h2>
  </div>

  <div class="row g-4">
    <div class="col-md-3">
      <div class="card border-start border-primary border-4 shadow-sm">
        <div class="card-body">
          <h6>📋 Totaal shifts</h6>
          <h3><?= $totaalShifts ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-start border-warning border-4 shadow-sm">
        <div class="card-body">
          <h6>⏳ Goed te keuren</h6>
          <h3><?= $goedTeKeuren ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-start border-danger border-4 shadow-sm">
        <div class="card-body">
          <h6>🛑 Incompleet</h6>
          <h3><?= $incompleet ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-start border-success border-4 shadow-sm">
        <div class="card-body">
          <h6>👥 Aantal werknemers</h6>
          <h3><?= $totaalGebruikers ?></h3>
        </div>
      </div>
    </div>
  </div>

  <!-- Placeholder voor later -->
  <div class="card mt-5">
    <div class="card-body text-muted text-center">
      📈 Rapportages en grafieken komen hier binnenkort!
    </div>
  </div>
</div>

</body>
</html>

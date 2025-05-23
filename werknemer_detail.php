<?php
session_start();

// Check admin login
if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] != 1) {
    header("Location: index.php");
    exit;
}

require 'includes/db.php';
function safeOutput($value) {
    if (is_null($value) || $value === '' || (is_numeric($value) && $value == 0)) {
        return '-';
    }
    return htmlspecialchars($value);
}
// Werknemer ID uit GET
if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: werknemers.php");
    exit;
}
$userId = (int)$_GET['id'];

// Werknemer gegevens ophalen
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$werknemer = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$werknemer) {
    header("Location: werknemers.php");
    exit;
}

// Statistieken ophalen over shifts van deze werknemer
// Huidige maand (kan je aanpassen of via GET)
$maand = $_GET['maand'] ?? date('Y-m');

// Totaal uren gewerkt (alle gewerkte shifts met eindtijd)
$stmtTotaalUren = $pdo->prepare("
    SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time))/60, 0) AS totaal_uren
    FROM shifts
    WHERE user_id = ? AND end_time IS NOT NULL
");
$stmtTotaalUren->execute([$userId]);
$totaalUren = (float)$stmtTotaalUren->fetchColumn();

// Te keuren uren (eindtijd niet null en approved = 0)
$stmtTeKeuren = $pdo->prepare("
    SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time))/60, 0) AS te_keuren_uren
    FROM shifts
    WHERE user_id = ? AND approved = 0 AND end_time IS NOT NULL
");
$stmtTeKeuren->execute([$userId]);
$teKeurenUren = (float)$stmtTeKeuren->fetchColumn();

// Goedgekeurde uren
$stmtGoedgekeurd = $pdo->prepare("
    SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time))/60, 0) AS goedgekeurde_uren
    FROM shifts
    WHERE user_id = ? AND approved = 1
");
$stmtGoedgekeurd->execute([$userId]);
$goedgekeurdUren = (float)$stmtGoedgekeurd->fetchColumn();

// Incomplete shifts (end_time is null)
$stmtIncompleet = $pdo->prepare("
    SELECT COUNT(*) FROM shifts WHERE user_id = ? AND end_time IS NULL
");
$stmtIncompleet->execute([$userId]);
$incompleteShifts = (int)$stmtIncompleet->fetchColumn();

// Recente shifts ophalen (laatste 10)
$stmtRecent = $pdo->prepare("
    SELECT * FROM shifts WHERE user_id = ? ORDER BY start_time DESC LIMIT 4
");
$stmtRecent->execute([$userId]);
$recenteShifts = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
$breadcrumb = [
    ['label' => 'Home', 'url' => 'admin_home.php'],
    ['label' => 'Werknemers', 'url' => 'werknemers.php'],
    ['label' => $werknemer ? $werknemer['naam'] : 'Onbekende werknemer']
];
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8" />
    <title>Werknemer Detail - <?= htmlspecialchars($werknemer['naam']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />
</head>
<body>
<header class="topbar">
    <?php include('includes/header.php') ?>
</header>

<?php include('includes/sidebar.php') ?>

<div class="main container my-4">
    <a href="werknemers.php" class="btn btn-outline-primary mb-4">← Terug naar werknemers</a>

    <h1 class="mb-3"><?= htmlspecialchars($werknemer['naam']) ?></h1>
    <p class="text-muted mb-4">Functie: <?= htmlspecialchars($werknemer['functie'] ?? 'Onbekend') ?></p>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">Persoonlijke gegevens</div>
                <div class="card-body">
                    <p><strong>Naam:</strong> <?= safeOutput($werknemer['naam']) ?></p>
                    <p><strong>E-mail:</strong> <?= safeOutput($werknemer['email']) ?></p>
                    <p><strong>Telefoon:</strong> <?= safeOutput($werknemer['telefoon']) ?></p>
                    <p><strong>Startdatum:</strong> <?= $werknemer['startdatum'] ? date('d-m-Y', strtotime($werknemer['startdatum'])) : '-' ?></p>
                    <p><strong>Opmerkingen:</strong><br /><?= nl2br(safeOutput($werknemer['opmerkingen'])) ?></p>

                        <?= nl2br(htmlspecialchars($werknemer['opmerkingen'] ?? '-')) ?></p>
                    <a href="werknemer_bewerken.php?id=<?= $userId ?>" class="btn btn-outline-primary btn-sm mt-3">✏️ Bewerken</a>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-start border-primary border-4 shadow-sm">
                        <div class="card-body">
                            <h6>Totaal gewerkt</h6>
                            <h3><?= number_format($totaalUren, 2, ',', '') ?> uur</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-start border-warning border-4 shadow-sm">
                        <div class="card-body">
                            <h6>Te keuren</h6>
                            <h3><?= number_format($teKeurenUren, 2, ',', '') ?> uur</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-start border-success border-4 shadow-sm">
                        <div class="card-body">
                            <h6>Goedgekeurd</h6>
                            <h3><?= number_format($goedgekeurdUren, 2, ',', '') ?> uur</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-start border-danger border-4 shadow-sm">
                        <div class="card-body">
                            <h6>Incomplete shifts</h6>
                            <h3><?= $incompleteShifts ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <h4>Recente shifts</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Datum</th>
                            <th>Start</th>
                            <th>Eind</th>
                            <th>Status</th>
                            <th>Actie</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recenteShifts) > 0): ?>
                            <?php foreach ($recenteShifts as $shift): 
                                $start = new DateTime($shift['start_time']);
                                $eind = $shift['end_time'] ? new DateTime($shift['end_time']) : null;
                            ?>
                                <tr>
                                    <td><?= $start->format('d-m-Y') ?></td>
                                    <td><?= $start->format('H:i') ?></td>
                                    <td><?= $eind ? $eind->format('H:i') : '-' ?></td>
                                    <td>
                                        <?php if (!$shift['end_time']): ?>
                                            <span class="badge bg-danger">Incompleet</span>
                                        <?php elseif ($shift['approved'] == 0): ?>
                                            <span class="badge bg-warning text-dark">Afwachten</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Goedgekeurd</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="goedkeuren.php?id=<?= (int)$shift['id'] ?>" class="btn btn-outline-secondary btn-sm" title="Goedkeuren">
                                            ✔️
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">Geen recente shifts gevonden</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <a href="shift_toevoegen.php?user_id=<?= $userId ?>" class="btn btn-primary mt-3">➕ Nieuwe shift toevoegen</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

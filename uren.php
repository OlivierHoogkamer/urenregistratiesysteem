<?php
session_start();
$breadcrumb = [
    ['label' => 'Home', 'url' => 'admin_home.php'],
    ['label' => 'Urenregistratie']
];
// Redirect als gebruiker niet ingelogd is of geen admin is
if (!isset($_SESSION['user']) || $_SESSION['user']['is_admin'] != 1) {
    header("Location: index.php");
    exit;
}

// Huidige maand als standaard filter voor maand
if (empty($_GET['maand'])) {
    $_GET['maand'] = date('Y-m');
}

require 'includes/db.php';

// Statistieken ophalen
$teGoedkeuren = $pdo->query("SELECT COUNT(*) FROM shifts WHERE approved = 0 AND end_time IS NOT NULL")->fetchColumn();
$goedgekeurd = $pdo->query("SELECT COUNT(*) FROM shifts WHERE approved = 1")->fetchColumn();
$incompleet = $pdo->query("SELECT COUNT(*) FROM shifts WHERE end_time IS NULL")->fetchColumn();

// Filters voorbereiden
$where = [];
$params = [];

// Zoekfilter: naam of datum
if (!empty($_GET['zoek'])) {
    $where[] = "(users.naam LIKE ? OR DATE(shifts.start_time) = ?)";
    $zoek = '%' . $_GET['zoek'] . '%';
    $params[] = $zoek;
    $params[] = $_GET['zoek'];
}

// Status filter
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $where[] = "shifts.approved = ?";
    $params[] = $_GET['status'];
}

// Maand filter
if (!empty($_GET['maand'])) {
    $where[] = "DATE_FORMAT(shifts.start_time, '%Y-%m') = ?";
    $params[] = $_GET['maand'];
}

// WHERE clause samenstellen
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Shifts ophalen met filters
$sql = "
    SELECT shifts.*, users.naam 
    FROM shifts
    JOIN users ON shifts.user_id = users.id
    $where_sql
    ORDER BY shifts.start_time DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totaal aantal shifts in geselecteerde maand (of totaal)
$sqlTotaalShifts = "SELECT COUNT(*) FROM shifts";
$paramsTotaalShifts = [];

if (!empty($_GET['maand'])) {
    $sqlTotaalShifts .= " WHERE DATE_FORMAT(start_time, '%Y-%m') = ?";
    $paramsTotaalShifts[] = $_GET['maand'];
}

$totaalShiftsStmt = $pdo->prepare($sqlTotaalShifts);
$totaalShiftsStmt->execute($paramsTotaalShifts);
$totaalShifts = $totaalShiftsStmt->fetchColumn();

// Maandoverzicht per werknemer
$sqlMaandoverzicht = "
    SELECT
        users.naam,
        COALESCE(SUM(TIMESTAMPDIFF(MINUTE, shifts.start_time, shifts.end_time)) / 60, 0) AS totaal_uren,
        COALESCE(SUM(CASE WHEN shifts.approved = 1 THEN TIMESTAMPDIFF(MINUTE, shifts.start_time, shifts.end_time) ELSE 0 END) / 60, 0) AS goedgekeurde_uren
    FROM shifts
    JOIN users ON shifts.user_id = users.id
    WHERE shifts.end_time IS NOT NULL
    AND DATE_FORMAT(shifts.start_time, '%Y-%m') = ?
    GROUP BY users.id
    ORDER BY users.naam
";
$stmtOverzicht = $pdo->prepare($sqlMaandoverzicht);
$stmtOverzicht->execute([$_GET['maand']]);
$overzicht = $stmtOverzicht->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8" />
    <title>Admin – Urenregistratie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />
</head>
<body>
<header class="topbar">
   <?php include('includes/header.php')?>
</header>

<?php include('includes/sidebar.php') ?>

<div class="main">
    <!-- Statistiekkaarten -->
    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="card border-start border-primary border-4 shadow-sm">
                <div class="card-body">
                    <h6>📅 Totaal shifts</h6>
                    <h3><?= (int)$totaalShifts ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-warning border-4 shadow-sm">
                <div class="card-body">
                    <h6>⏳ Te keuren</h6>
                    <h3><?= (int)$teGoedkeuren ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-success border-4 shadow-sm">
                <div class="card-body">
                    <h6>✅ Goedgekeurd</h6>
                    <h3><?= (int)$goedgekeurd ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-danger border-4 shadow-sm">
                <div class="card-body">
                    <h6>🛑 Incompleet</h6>
                    <h3><?= (int)$incompleet ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Maandoverzicht per werknemer (accordion) -->
    <div class="accordion mt-5" id="maandOverzichtAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingOverzicht">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOverzicht" aria-expanded="false" aria-controls="collapseOverzicht">
                    📊 Toon maandoverzicht per werknemer
                </button>
            </h2>
            <div id="collapseOverzicht" class="accordion-collapse collapse" aria-labelledby="headingOverzicht" data-bs-parent="#maandOverzichtAccordion">
                <div class="accordion-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>👤 Naam</th>
                                    <th>⏱️ Totaal uren</th>
                                    <th>✅ Goedgekeurde uren</th>
                                    <th>⛔ Niet-goedgekeurde uren</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($overzicht) > 0): ?>
                                    <?php foreach ($overzicht as $rij): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($rij['naam']) ?></td>
                                            <td><?= number_format($rij['totaal_uren'], 2, ',', '') ?> uur</td>
                                            <td><?= number_format($rij['goedgekeurde_uren'], 2, ',', '') ?> uur</td>
                                            <td><?= number_format($rij['totaal_uren'] - $rij['goedgekeurde_uren'], 2, ',', '') ?> uur</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Geen gegevens voor deze maand</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filterformulier -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end" id="filterForm" novalidate>
                <div class="col-md-4">
                    <label for="zoek" class="form-label">🔍 Zoekterm</label>
                    <input
                        type="text"
                        name="zoek"
                        id="zoek"
                        class="form-control"
                        placeholder="Naam of datum"
                        value="<?= htmlspecialchars($_GET['zoek'] ?? '') ?>"
                        autocomplete="off"
                    />
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">🛠 Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Alle statussen</option>
                        <option value="0" <?= (isset($_GET['status']) && $_GET['status'] === '0') ? 'selected' : '' ?>>Afwachten</option>
                        <option value="1" <?= (isset($_GET['status']) && $_GET['status'] === '1') ? 'selected' : '' ?>>Goedgekeurd</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="maand" class="form-label">📆 Maand</label>
                    <input
                        type="month"
                        name="maand"
                        id="maand"
                        class="form-control"
                        value="<?= htmlspecialchars($_GET['maand'] ?? '') ?>"
                    />
                </div>
                <div class="col-md-2 d-grid">
                    <button type="button" id="resetFilters" class="btn btn-outline-secondary" title="Filters wissen">✖ Filters wissen</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel met shifts -->
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">📋 Alle shifts</h5>
            <div class="table-responsive">
                <table id="shiftsTable" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>👤 Naam</th>
                            <th>📅 Datum</th>
                            <th>🕒 Start</th>
                            <th>🕔 Eind</th>
                            <th>📌 Status</th>
                            <th>⚙️ Actie</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($shifts) > 0): ?>
                            <?php foreach ($shifts as $shift): ?>
                                <?php
                                    $start = new DateTime($shift['start_time']);
                                    $eind = $shift['end_time'] ? new DateTime($shift['end_time']) : null;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($shift['naam']) ?></td>
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
                                <td colspan="6" class="text-center text-muted">Geen shifts gevonden</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#shiftsTable').DataTable({
        order: [[1, 'desc']],
        pageLength: 10,
        language: {
            search: "Zoeken:",
            lengthMenu: "Toon _MENU_ entries",
            info: "Toon _START_ tot _END_ van _TOTAL_ entries",
            paginate: {
                previous: "Vorige",
                next: "Volgende"
            },
            zeroRecords: "Geen overeenkomende records gevonden",
            infoEmpty: "Geen records beschikbaar",
            infoFiltered: "(gefilterd uit _MAX_ totaal)",
        }
    });

    // Filter reset knop
    $('#resetFilters').click(function() {
        $('#filterForm')[0].reset();
        $('#filterForm').submit();
    });
});
</script>
</body>
</html>

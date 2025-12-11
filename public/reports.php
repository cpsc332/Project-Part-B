<?php
require_once __DIR__ . '/../includes/init.php';
require_role('admin');

$topStmt = $pdo->query("
    SELECT movieid, movie_name, tickets_sold
    FROM vw_top_movies_last_30_days
    ORDER BY tickets_sold DESC
    LIMIT 10
");
$topMovies = $topStmt->fetchAll(PDO::FETCH_ASSOC);

$utilStmt = $pdo->query("
    SELECT theatre_name, starttime, pct_sold
    FROM vw_theatre_utilization_next_7_days
    ORDER BY starttime
");
$utilRows = $utilStmt->fetchAll(PDO::FETCH_ASSOC);

$topLabels = [];
$topData   = [];
foreach ($topMovies as $row) {
    $topLabels[] = $row['movie_name'];
    $topData[]   = (int)$row['tickets_sold'];
}

$utilLabels = [];
$utilData   = [];
foreach ($utilRows as $row) {
    $label = date('m/d H:i', strtotime($row['starttime'])) . ' - ' . $row['theatre_name'];
    $utilLabels[] = $label;
    $utilData[]   = (float)$row['pct_sold'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo esc(t('reports_heading')); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<h1><?php echo esc(t('reports_heading')); ?></h1>

<h2><?php echo esc(t('top_movies_chart')); ?></h2>
<canvas id="topMoviesChart" width="600" height="300"></canvas>

<h2><?php echo esc(t('util_chart')); ?></h2>
<canvas id="utilChart" width="600" height="300"></canvas>

<script>
const topMovieLabels = <?php echo json_encode($topLabels); ?>;
const topMovieData   = <?php echo json_encode($topData); ?>;

const ctx1 = document.getElementById('topMoviesChart').getContext('2d');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: topMovieLabels,
        datasets: [{
            label: 'Tickets sold',
            data: topMovieData
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

const utilLabels = <?php echo json_encode($utilLabels); ?>;
const utilData   = <?php echo json_encode($utilData); ?>;

const ctx2 = document.getElementById('utilChart').getContext('2d');
new Chart(ctx2, {
    type: 'line',
    data: {
        labels: utilLabels,
        datasets: [{
            label: '% seats sold',
            data: utilData
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                max: 100
            }
        }
    }
});
</script>

</body>
</html>

<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Get basic table counts using PDO from db.php
$stats = [];
$tables = ['theatre', 'auditorium', 'seat', 'movie', 'showtime', 'customer', 'ticket'];

foreach ($tables as $table) {
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM {$table}");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats[$table] = (int)($row['c'] ?? 0);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Theatre Booking – Home</title>
</head>
<body>

<h1>Theatre Booking – Part C</h1>

<h2>Navigation</h2>
<ul>
    <li><a href="movies.php">Browse Movies</a></li>
    <li><a href="showtimes.php">Showtime Finder</a></li>
    <li><a href="my_tickets.php">My Tickets</a></li>
    <li><a href="reports.php">Reports</a></li>
</ul>

<hr>

<h2>Database Status</h2>
<table border="1" cellpadding="5">
    <tr>
        <th>Table</th>
        <th>Rows</th>
    </tr>
    <tr>
        <td>theatre</td>
        <td><?php echo $stats['theatre']; ?></td>
    </tr>
    <tr>
        <td>auditorium</td>
        <td><?php echo $stats['auditorium']; ?></td>
    </tr>
    <tr>
        <td>seat</td>
        <td><?php echo $stats['seat']; ?></td>
    </tr>
    <tr>
        <td>movie</td>
        <td><?php echo $stats['movie']; ?></td>
    </tr>
    <tr>
        <td>showtime</td>
        <td><?php echo $stats['showtime']; ?></td>
    </tr>
    <tr>
        <td>customer</td>
        <td><?php echo $stats['customer']; ?></td>
    </tr>
    <tr>
        <td>ticket</td>
        <td><?php echo $stats['ticket']; ?></td>
    </tr>
</table>

</body>
</html>

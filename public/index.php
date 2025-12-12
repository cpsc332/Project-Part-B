<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Get basic table counts using PDO from db.php
$stats = [];
$tables = ['theatre', 'auditorium', 'seat', 'movie', 'showtime', 'customer', 'ticket'];

foreach ($tables as $table) {
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM {$table}");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats[$table] = (int) ($row['c'] ?? 0);

}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Theatre Booking – Home</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
    /* General page style */
    body {
        font-family: Arial, sans-serif;
        padding: 50px;

    }

    button {
        padding: 8px 20px;
        background: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    /* Navigation links */
    ul {
        padding: 0;
        margin-bottom: 20px;
    }

    ul li {
        margin: 8px 0;
    }
    h1 {
        color: black;
        text-align: center;
    }
    ul li a {
        text-decoration: none;
        color: #ffffffff;
        font-weight: bold;
        padding: 6px 10px;
        border-radius: 5px;
        transition: 0.2s;
        background-color: #4da3ffff;
        display: inline-block;
    }

    ul li a:hover {
        background-color: #007bff;
    }

    /* Table styling */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background: white;
        border-radius: 6px;
        overflow: hidden;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    th {
        background-color: #4da3ffff;
        color: #ffffffff;
        padding: 12px;
        text-align: left;
    }

    td {
        padding: 10px;
        border-bottom: 1px solid #ddd;
    }

    tr:hover {
        background-color: #f0f8ff;
    }

    /* Links inside table */
    table a {
        color: #2a4d7c;
        font-weight: bold;
        text-decoration: none;
    }

    table a:hover {
        text-decoration: underline;
    }
</style>

</head>
<body>
    <?php
        require_once __DIR__ . '/../includes/header.php';
        echo theatre_header();
    ?>

<!-- <h1>Welcome to Theatre Booking System</h1> -->

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

    <?php
    require_once __DIR__ . '/../includes/footer.php';
echo theatre_footer();
?>
</body>
</html>

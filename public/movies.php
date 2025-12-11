<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// get ?q= from the URL
$search = param('q', '', 'GET');

if ($search === '') {
    $stmt = $pdo->query("
        SELECT MovieID, Name, MPAA, Runtime, ReleaseDate
        FROM movie
        ORDER BY Name
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT MovieID, Name, MPAA, Runtime, ReleaseDate
        FROM movie
        WHERE Name LIKE :search
        ORDER BY Name
    ");
    $stmt->execute(['search' => '%' . $search . '%']);
}

$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Browse Movies</title>

 <style>
    /* General page style */
    body {
        font-family: Arial, sans-serif;
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
        background-color: #007bffff;
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
        color: #464648ff;
        font-weight: bold;
        text-decoration: none;
    }

    table a:hover {
        text-decoration: underline;
    }
</style>

</head>
<body>

<h1>Browse Movies</h1>

<form method="get" action="movies.php">
    <label>Search by Title:</label>
    <input type="text" name="q" value="<?php echo esc($search); ?>">
    <button type="submit">Search</button>
</form>

<hr>

<table border="1" cellpadding="5">
    <tr>
        <th>Movie ID</th>
        <th>Title (Name)</th>
        <th>MPAA Rating</th>
        <th>Runtime (min)</th>
        <th>Release Date</th>
    </tr>

    <?php if ($movies): ?>
        <?php foreach ($movies as $movie): ?>
            <tr>
                <td><?php echo $movie['MovieID']; ?></td>
               <td>
                    <a href="showtimes.php?movie_id=<?php echo urlencode($movie['MovieID']); ?>">
                        <?php echo esc($movie['Name']); ?>
                    </a>
                </td>
                <td><?php echo esc($movie['MPAA']); ?></td>
                <td><?php echo $movie['Runtime']; ?></td>
                <td><?php echo $movie['ReleaseDate']; ?></td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="5">No movies found.</td></tr>
    <?php endif; ?>
</table>

<hr>

<a href="index.php">Back to Home</a>

</body>
</html>

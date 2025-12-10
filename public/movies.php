<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Safe way to get ?q= from the URL
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
                <td><?php echo esc($movie['Name']); ?></td>
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

<?php
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

$q = trim(param('q', '', 'GET'));
if ($q === '' || strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT MovieID, Name
    FROM movie
    WHERE Name LIKE :search
    ORDER BY Name
    LIMIT 5
");
$stmt->execute([':search' => '%' . $q . '%']);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = [];
foreach ($rows as $r) {
    $result[] = [
        'id'   => (int)$r['MovieID'],
        'name' => $r['Name'],
    ];
}

echo json_encode($result);

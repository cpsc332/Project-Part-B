<?php
require_once __DIR__ . '/../includes/init.php';
require_login();

$showtime_id = (int)param('showtime_id', 0);
$seatsParam  = trim(param('seats', '', 'GET'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $showtime_id = (int)param('showtime_id', 0, 'POST');
    $seatsParam  = trim(param('seats', '', 'POST'));
}

if ($showtime_id <= 0 || $seatsParam === '') {
    header('Location: movies.php');
    exit;
}

$seatIds = array_filter(array_map('intval', explode(',', $seatsParam)));
$seatIds = array_values($seatIds);

if (empty($seatIds)) {
    header('Location: movies.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT s.ShowtimeID, s.StartTime, s.BasePrice,
           m.Name AS MovieName,
           a.AuditoriumID,
           t.Name AS TheatreName
    FROM showtime s
    JOIN movie m ON s.MovieID = m.MovieID
    JOIN auditorium a ON s.AuditoriumID = a.AuditoriumID
    JOIN theatre t ON a.TheatreID = t.TheatreID
    WHERE s.ShowtimeID = :id
");
$stmt->execute([':id' => $showtime_id]);
$showtime = $stmt->fetch();

if (!$showtime) {
    header('Location: movies.php');
    exit;
}

$in = implode(',', array_fill(0, count($seatIds), '?'));
$sql = "
    SELECT SeatID, RowNumber, SeatNumber, SeatType
    FROM seat
    WHERE AuditoriumID = :aud_id AND SeatID IN ($in)
    ORDER BY RowNumber, SeatNumber
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':aud_id', $showtime['AuditoriumID'], PDO::PARAM_INT);
foreach ($seatIds as $i => $sid) {
    $stmt->bindValue($i + 1, $sid, PDO::PARAM_INT);
}
$stmt->execute();
$seatRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($seatRows) !== count($seatIds)) {
    die('One or more seats do not belong to this showtime auditorium.');
}

$dynamicMult = dynamic_price_multiplier($showtime['StartTime']);

function compute_line_price(array $seatRow, float $basePrice, float $dynamicMult): float {
    $price = $basePrice;
    $st = strtolower($seatRow['SeatType']);
    if ($st === 'premium') {
        $price *= 1.20;
    } elseif ($st === 'ada') {
        $price *= 0.90;
    }
    $price *= $dynamicMult;
    return $price;
}

$error = '';
$successTicketIds = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $giftCode = trim(param('gift_code', '', 'POST'));

    $discountMult = 1.0;

    try {
        $pdo->beginTransaction();

        $takenStmt = $pdo->prepare("
            SELECT SeatID
            FROM ticket
            WHERE ShowtimeID = :shid
              AND SeatID IN ($in)
              AND Status IN ('RESERVED', 'PURCHASED', 'USED')
        ");
        $takenStmt->bindValue(':shid', $showtime_id, PDO::PARAM_INT);
        foreach ($seatIds as $i => $sid) {
            $takenStmt->bindValue($i + 1, $sid, PDO::PARAM_INT);
        }
        $takenStmt->execute();
        $already = $takenStmt->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($already)) {
            throw new Exception('Some seats are already sold or reserved.');
        }

        if ($giftCode !== '') {
            $gcStmt = $pdo->prepare("
                SELECT *
                FROM gift_code
                WHERE Code = :code AND Active = 1
                LIMIT 1
            ");
            $gcStmt->execute([':code' => $giftCode]);
            $gift = $gcStmt->fetch();

            if (!$gift) {
                throw new Exception('Invalid or inactive gift code.');
            }

            $minTickets = (int)$gift['BundleMinTickets'];
            if (count($seatIds) < $minTickets) {
                throw new Exception("This gift code requires at least {$minTickets} tickets.");
            }

            if (!is_null($gift['MaxUses']) && $gift['Uses'] >= $gift['MaxUses']) {
                throw new Exception('This gift code has been fully used.');
            }

            $discountMult = max(0.0, 1.0 - ((float)$gift['DiscountPercent'] / 100.0));

            $upd = $pdo->prepare("
                UPDATE gift_code
                SET Uses = Uses + :cnt
                WHERE GiftCodeID = :id
            ");
            $upd->execute([
                ':cnt' => count($seatIds),
                ':id'  => $gift['GiftCodeID'],
            ]);
        }

        $user = current_user();
        $customerId = (int)$user['id'];

        // Insert tickets
        $insert = $pdo->prepare("
            INSERT INTO ticket (ShowtimeID, SeatID, CustomerID, Price, DiscountType, Status)
            VALUES (:showtime, :seat, :cust, :price, :discount, 'purchased')
        ");

        foreach ($seatRows as $seatRow) {
            $linePrice = compute_line_price($seatRow, (float)$showtime['BasePrice'], $dynamicMult);
            $linePrice *= $discountMult;
            $linePrice = round($linePrice, 2);

            $insert->execute([
                ':showtime' => $showtime_id,
                ':seat'     => $seatRow['SeatID'],
                ':cust'     => $customerId,
                ':price'    => $linePrice,
                ':discount' => ($giftCode !== '' ? $giftCode : null),
            ]);

            $successTicketIds[] = (int)$pdo->lastInsertId();
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<h1>Checkout</h1>

<p>
    <strong>Movie:</strong> <?php echo esc($showtime['MovieName']); ?><br>
    <strong>Theatre:</strong> <?php echo esc($showtime['TheatreName']); ?><br>
    <strong>Showtime:</strong>
    <?php echo date('l, F j, Y g:i A', strtotime($showtime['StartTime'])); ?>
</p>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error): ?>

    <h2>Purchase Complete</h2>
    <p>Your tickets have been purchased.</p>
    <p><strong>Ticket IDs:</strong>
        <?php echo implode(', ', array_map('intval', $successTicketIds)); ?>
    </p>

<?php else: ?>

    <?php if ($error): ?>
        <p style="color:red;"><?php echo esc($error); ?></p>
    <?php endif; ?>

    <h2>Selected Seats</h2>
    <table border="1" cellpadding="5">
        <tr>
            <th>Seat</th>
            <th>Type</th>
            <th>Price (before gift code)</th>
        </tr>
        <?php
        $subTotal = 0.0;
        foreach ($seatRows as $seatRow):
            $linePrice = compute_line_price($seatRow, (float)$showtime['BasePrice'], $dynamicMult);
            $subTotal += $linePrice;
        ?>
            <tr>
                <td>Row <?php echo (int)$seatRow['RowNumber']; ?> Seat <?php echo (int)$seatRow['SeatNumber']; ?></td>
                <td><?php echo esc($seatRow['SeatType']); ?></td>
                <td>$<?php echo number_format($linePrice, 2); ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="2" align="right"><strong>Subtotal:</strong></td>
            <td>$<?php echo number_format($subTotal, 2); ?></td>
        </tr>
    </table>

    <p>
        Dynamic pricing multiplier:
        <?php echo number_format($dynamicMult, 2); ?>
        <?php if ($dynamicMult > 1.0): ?>
            (peak)
        <?php elseif ($dynamicMult < 1.0): ?>
            (off-peak)
        <?php else: ?>
            (standard)
        <?php endif; ?>
    </p>

    <form method="post" action="purchase.php">
        <input type="hidden" name="showtime_id" value="<?php echo (int)$showtime_id; ?>">
        <input type="hidden" name="seats" value="<?php echo esc($seatsParam); ?>">

        <div>
            <label>Gift / discount code (optional):</label>
            <input type="text" name="gift_code" value="<?php echo esc(param('gift_code', '', 'POST')); ?>">
        </div>

        <button type="submit">Confirm Purchase</button>
    </form>

<?php endif; ?>

</body>
</html>

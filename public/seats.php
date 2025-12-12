<?php
session_start();

// Database connections
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get showtime_id from URL
$showtime_id = param('showtime_id', null, 'GET');

// Grab showtime details with movie, auditorium, and theatre info
$stmt = $pdo->prepare("
    SELECT
        s.ShowtimeID,
        s.StartTime,
        s.Format,
        s.Language,
        s.BasePrice,
        m.MovieID,
        m.Name AS MovieName,
        m.Runtime,
        m.MPAA,
        a.AuditoriumID,
        a.Name AS AuditoriumName,
        a.RowCount,
        a.SeatCount,
        t.Name AS TheatreName,
        t.City
    FROM showtime s
    JOIN movie m ON s.MovieID = m.MovieID
    JOIN auditorium a ON s.AuditoriumID = a.AuditoriumID
    JOIN theatre t ON a.TheatreID = t.TheatreID
    WHERE s.ShowtimeID = :showtime_id
");

$stmt->execute([':showtime_id' => $showtime_id]);
$showtime = $stmt->fetch();

// If showtime not found, redirect back
if (!$showtime) {
    header('Location: movies.php');
    exit;
}

// Fetch all seats for this auditorium
$stmt = $pdo->prepare("
    SELECT SeatID, RowNumber, SeatNumber, SeatType
    FROM seat
    WHERE AuditoriumID = :auditorium_id
    ORDER BY RowNumber, SeatNumber
");
$stmt->execute([':auditorium_id' => $showtime['AuditoriumID']]);
$seats = $stmt->fetchAll();

// Fetch seats already taken for this showtime
$stmt = $pdo->prepare("
    SELECT SeatID
    FROM ticket
    WHERE ShowtimeID = :showtime_id
    AND Status IN ('RESERVED', 'PURCHASED', 'USED')
");
$stmt->execute([':showtime_id' => $showtime_id]);
$taken_seats = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Organize seats by row for display
$seat_map = [];
foreach ($seats as $seat) {
    $seat_map[$seat['RowNumber']][] = $seat;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Select Seats - <?php echo esc($showtime['MovieName']); ?></title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
    /* Temporary styling if we have to move it to a styles.css sheet */
    body { font-family: Arial, sans-serif; margin: 0 auto; padding: 20px; background: #fafafa; }
    .back-link { color: #007bff; text-decoration: none; }

    /* Showtime info header */
    .showtime-info { background: #fff; padding: 20px; margin: 20px 0; border-radius: 5px; }
    .showtime-info h1 { margin: 0 0 10px 0; }
    .showtime-info p { margin: 5px 0; color: #555; }

    /* Screen */
    .screen { background: #333; color: #fff; text-align: center; padding: 10px; margin: 30px auto 20px; width: 70%; border-radius: 5px; }

    /* Seat map */
    .seat-map { text-align: center; margin: 20px 0; }
    .row { display: flex; justify-content: center; align-items: center; margin: 5px 0; }
    .row-label { width: 30px; font-weight: bold; color: #666; }

    /* Seat buttons */
    .seat {
        width: 32px; height: 32px; margin: 2px;
        border: none; border-radius: 5px;
        cursor: pointer; font-size: 11px; font-weight: bold;
    }
    .seat.standard { background: #28a745; color: white; }
    .seat.premium { background: #ffc107; color: #333; }
    .seat.ada { background: #17a2b8; color: white; }
    .seat.taken { background: #ff0019ff; color: white; cursor: not-allowed; }
    .seat.selected { background: #007bff !important; color: white; }
    .seat:hover:not(.taken):not(.selected) { opacity: 0.8; }

    /* Legend */
    .legend { display: flex; justify-content: center; gap: 20px; margin: 25px 0; flex-wrap: wrap; }
    .legend-item { display: flex; align-items: center; gap: 5px; font-size: 14px; }
    .legend-box { width: 20px; height: 20px; border-radius: 4px; }
    .legend-box.standard { background: #28a745; }
    .legend-box.premium { background: #ffc107; }
    .legend-box.ada { background: #17a2b8; }
    .legend-box.selected { background: #007bff; }
    .legend-box.taken { background: #dc3545; }

    /* Summary */
    .summary { background: #fff; padding: 20px; border-radius: 5px; margin-top: 20px; }
    .summary h3 { margin-top: 0; }
    .checkout-btn {
        padding: 12px 30px; background: #28a745; color: white;
        border: none; border-radius: 5px; font-size: 16px; cursor: pointer;
        margin-top: 10px;
    }
    .checkout-btn:disabled { background: #ccc; cursor: not-allowed; }
    .checkout-btn:not(:disabled):hover { background: #218838; }
</style>
</head>
<body>
    <?php
        require_once __DIR__ . '/../includes/header.php';
echo theatre_header();
?>
    <a href="showtimes.php?movie_id=<?php echo esc($showtime['MovieID']); ?>" class="back-link">← Back to Showtimes</a>

    <!-- Showtime Info Header -->
    <div class="showtime-info">
        <h1><?php echo esc($showtime['MovieName']); ?></h1>
        <p>
            <strong><?php echo esc($showtime['TheatreName']); ?></strong> - <?php echo esc($showtime['AuditoriumName']); ?>
        </p>
        <p>
            <?php echo date('l, F j, Y', strtotime($showtime['StartTime'])); ?> at
            <strong><?php echo date('g:i A', strtotime($showtime['StartTime'])); ?></strong>
        </p>
        <p>
            <?php echo esc($showtime['Runtime']); ?> min |
            <?php echo strtoupper(esc($showtime['MPAA'])); ?> |
            <?php echo strtoupper(esc($showtime['Format'])); ?> |
            Base Price: $<?php echo number_format($showtime['BasePrice'], 2); ?>
        </p>
    </div>

    <!-- Screen indicator -->
    <div class="screen">SCREEN</div>

    <!-- Seat Map -->
    <div class="seat-map">
        <?php foreach ($seat_map as $row_num => $row_seats): ?>
            <div class="row">
                <span class="row-label"><?php echo $row_num; ?></span>
                <?php foreach ($row_seats as $seat):
                    $is_taken = in_array($seat['SeatID'], $taken_seats);
                    $type_class = strtolower($seat['SeatType']);
                    ?>
                    <button
                        class="seat <?php echo $type_class; ?> <?php echo $is_taken ? 'taken' : ''; ?>"
                        data-seat-id="<?php echo esc($seat['SeatID']); ?>"
                        data-seat-type="<?php echo esc($seat['SeatType']); ?>"
                        data-row="<?php echo esc($seat['RowNumber']); ?>"
                        data-number="<?php echo esc($seat['SeatNumber']); ?>"
                        <?php echo $is_taken ? 'disabled' : ''; ?>
                        title="Row <?php echo $seat['RowNumber']; ?>, Seat <?php echo $seat['SeatNumber']; ?> (<?php echo ucfirst($seat['SeatType']); ?>)">
                        <?php echo $seat['SeatNumber']; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Legend -->
    <div class="legend">
        <div class="legend-item"><div class="legend-box standard"></div> Standard</div>
        <div class="legend-item"><div class="legend-box premium"></div> Premium (+20%)</div>
        <div class="legend-item"><div class="legend-box ada"></div> ADA (-10%)</div>
        <div class="legend-item"><div class="legend-box selected"></div> Selected</div>
        <div class="legend-item"><div class="legend-box taken"></div> Taken</div>
    </div>

    <!-- Selection Summary -->
    <div class="summary">
        <h3>Your Selection</h3>
        <p><strong>Seats:</strong> <span id="selected-seats">None</span></p>
        <p><strong>Total:</strong> $<span id="total-price">0.00</span></p>

        <form id="checkout-form" method="POST" action="purchasee.php">
            <input type="hidden" name="showtime_id" value="<?php echo (int)$showtime_id; ?>">
            <input type="hidden" name="seats" id="seats-input" value="">
            <button type="submit" id="checkout-btn" class="checkout-btn" disabled>Proceed to Checkout</button>
        </form>
    </div>
    <!-- JavaScript for seat selection -->
    <script>
        const basePrice = <?php echo $showtime['BasePrice']; ?>;
        let selectedSeats = [];

        // Add click event to all available seats
        document.querySelectorAll('.seat:not(.taken)').forEach(seat => {
            seat.addEventListener('click', function() {
                const seatId = this.dataset.seatId;
                const seatType = this.dataset.seatType;
                const row = this.dataset.row;
                const number = this.dataset.number;

                if (this.classList.contains('selected')) {
                    // Deselect seat
                    this.classList.remove('selected');
                    selectedSeats = selectedSeats.filter(s => s.id !== seatId);
                } else {
                    // Select seat
                    this.classList.add('selected');
                    selectedSeats.push({
                        id: seatId,
                        type: seatType,
                        row: row,
                        number: number,
                        label: 'Row: ' + row + ' Seat: ' + number
                    });
                }

                updateSummary();
            });
        });

        function updateSummary() {
            const seatsDisplay = document.getElementById('selected-seats');
            const priceDisplay = document.getElementById('total-price');
            const checkoutBtn = document.getElementById('checkout-btn');

            if (selectedSeats.length === 0) {
                seatsDisplay.textContent = 'None';
                priceDisplay.textContent = '0.00';
                checkoutBtn.disabled = true;
            } else {
                // Display selected seats
                seatsDisplay.textContent = selectedSeats.map(s => s.label).join(', ');

                // Calculate total price
                let total = 0;
                selectedSeats.forEach(s => {
                    let price = basePrice;
                    // Apply seat type pricing
                    if (s.type.toLowerCase() === 'premium') {
                        price *= 1.20;  // +20%
                    } else if (s.type.toLowerCase() === 'ada') {
                        price *= 0.90;  // -10%
                    }
                    total += price;
                });

                priceDisplay.textContent = total.toFixed(2);
                checkoutBtn.disabled = false;
            }
        }

        // Checkout button click
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            if (selectedSeats.length === 0) {
              e.preventDefault();
              return;
            }

            // Set hidden input value before form submission
            document.getElementById('seats-input').value = selectedSeats.map(s => s.id).join(',');
        });
    </script>

    <?php
        require_once __DIR__ . '/../includes/footer.php';
echo theatre_footer();
?>
</body>
</html>

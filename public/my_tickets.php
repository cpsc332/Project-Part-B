<?php
session_start();
$page_title = 'My Tickets';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

$search_email = param('email', '', 'GET');
$search_ticket_id = param('ticket_id', '', 'GET');

$tickets = [];
$searched = false;

if (!empty($search_email) || !empty($search_ticket_id)) {
    $searched = true;

    try {
        if (!empty($search_email)) {
            if (!filter_var($search_email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['flash_message'] = 'Please enter a valid email address.';
                $_SESSION['flash_type'] = 'error';
            } else {
                $stmt = $pdo->prepare("
                    SELECT
                        t.ticketid,
                        t.price,
                        t.discounttype,
                        t.status,
                        s.starttime,
                        m.name AS movie_name,
                        m.runtime,
                        m.mpaa,
                        th.name AS theatre_name,
                        th.street,
                        th.city,
                        th.state,
                        a.name AS auditorium_name,
                        se.rownumber,
                        se.seatnumber,
                        se.seattype,
                        c.name AS customer_name,
                        c.email AS customer_email
                    FROM ticket t
                    JOIN customer c ON t.customerid = c.customerid
                    JOIN showtime s ON t.showtimeid = s.showtimeid
                    JOIN movie m ON s.movieid = m.movieid
                    JOIN auditorium a ON s.auditoriumid = a.auditoriumid
                    JOIN theatre th ON a.theatreid = th.theatreid
                    JOIN seat se ON t.seatid = se.seatid
                    WHERE c.email = ?
                    ORDER BY s.starttime DESC
                ");
                $stmt->execute([$search_email]);
                $tickets = $stmt->fetchAll();
            }
        } elseif (!empty($search_ticket_id) && is_numeric($search_ticket_id)) {
            $stmt = $pdo->prepare("
                SELECT
                    t.ticketid,
                    t.price,
                    t.discounttype,
                    t.status,
                    s.starttime,
                    m.name AS movie_name,
                    m.runtime,
                    m.mpaa,
                    th.name AS theatre_name,
                    th.street,
                    th.city,
                    th.state,
                    a.name AS auditorium_name,
                    se.rownumber,
                    se.seatnumber,
                    se.seattype,
                    c.name AS customer_name,
                    c.email AS customer_email
                FROM ticket t
                JOIN customer c ON t.customerid = c.customerid
                JOIN showtime s ON t.showtimeid = s.showtimeid
                JOIN movie m ON s.movieid = m.movieid
                JOIN auditorium a ON s.auditoriumid = a.auditoriumid
                JOIN theatre th ON a.theatreid = th.theatreid
                JOIN seat se ON t.seatid = se.seatid
                WHERE t.ticketid = ?
            ");
            $stmt->execute([$search_ticket_id]);
            $ticket = $stmt->fetch();
            if ($ticket) {
                $tickets = [$ticket];
            }
        }
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = 'An error occurred while searching for tickets.';
        $_SESSION['flash_type'] = 'error';
    }
}

$flash_message = null;
if (isset($_SESSION['flash_message'])) {
    $flash_message = [
        'text' => $_SESSION['flash_message'],
        'type' => $_SESSION['flash_type'] ?? 'info',
    ];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}
?>

<html>
    <head>
            <link rel="stylesheet" href="assets/styles.css">
    </head>

    <style>
    body { font-family: Arial, sans-serif; background: #f4f4f4; }
    .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .page-header { margin-bottom: 2rem; }
    .page-header h1 { font-size: 2rem; color: #2c3e50; }
    .search-section { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem; }
    .form-row { display: flex; gap: 2rem; align-items: flex-end; flex-wrap: wrap; }
    .form-group { flex: 1; min-width: 250px; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
    .form-control { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; }
    .form-divider { text-align: center; padding: 0 1rem; margin-bottom: 1.5rem; font-weight: bold; color: #7f8c8d; }
    .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
    .btn-primary { background: #3498db; color: white; }
    .btn-primary:hover { background: #2980b9; }
    .btn-danger { background: #e74c3c; color: white; }
    .btn-danger:hover { background: #c0392b; }
    .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
    .alert-success { background: #d4edda; color: #155724; }
    .alert-error { background: #f8d7da; color: #721c24; }
    .alert-info { background: #d1ecf1; color: #0c5460; }
    .results-section { margin-top: 2rem; }
    .tickets-list { display: grid; gap: 1.5rem; }
    .ticket-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden; }
    .ticket-card:hover { box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
    .ticket-header { background: #34495e; color: white; padding: 1rem; display: flex; justify-content: space-between; align-items: center; }
    .ticket-id { font-weight: bold; font-size: 1.1rem; }
    .ticket-status { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: bold; text-transform: uppercase; }
    .status-purchased { background: #27ae60; color: white; }
    .status-refunded { background: #95a5a6; color: white; }
    .ticket-body { padding: 1.5rem; }
    .movie-title { font-size: 1.5rem; margin-bottom: 1rem; color: #2c3e50; }
    .ticket-details { margin-bottom: 1.5rem; }
    .detail-row { display: flex; padding: 0.5rem 0; border-bottom: 1px solid #ecf0f1; }
    .detail-row:last-child { border-bottom: none; }
    .detail-row .label { font-weight: bold; width: 150px; color: #7f8c8d; }
    .detail-row .value { flex: 1; color: #2c3e50; }
    .discount-badge { background: #e74c3c; color: white; padding: 0.15rem 0.5rem; border-radius: 3px; font-size: 0.75rem; margin-left: 0.5rem; }
    .ticket-actions { margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #ecf0f1; }
    </style>

    <body>
            <?php
                require_once __DIR__ . '/../includes/header.php';
echo theatre_header();
?>
        <div class="container">

            <?php if ($flash_message): ?>
                <div class="alert alert-<?php echo esc($flash_message['type']); ?>">
                    <?php echo esc($flash_message['text']); ?>
                </div>
            <?php endif; ?>

            <div class="page-header">
                <h1>My Tickets</h1>
                <p>Look up your tickets by email or ticket ID</p>
            </div>

            <div class="search-section">
                <form method="GET" action="my_tickets.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Search by Email:</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo esc($search_email); ?>" placeholder="your.email@example.com">
                        </div>

                        <div class="form-divider">
                            <span>OR</span>
                        </div>

                        <div class="form-group">
                            <label for="ticket_id">Search by Ticket ID:</label>
                            <input type="text" id="ticket_id" name="ticket_id" class="form-control" value="<?php echo esc($search_ticket_id); ?>" placeholder="123456">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Search Tickets</button>
                </form>
            </div>

            <?php if ($searched): ?>
                <div class="results-section">
                    <?php if (empty($tickets)): ?>
                        <div class="alert alert-info">
                            <p>No tickets found. Please check your email or ticket ID and try again.</p>
                        </div>
                    <?php else: ?>
                        <h2>Your Tickets (<?php echo count($tickets); ?> found)</h2>

                        <div class="tickets-list">
                            <?php foreach ($tickets as $ticket): ?>
                                <div class="ticket-card">
                                    <div class="ticket-header">
                                        <div class="ticket-id">Ticket #<?php echo esc($ticket['ticketid']); ?></div>
                                        <div class="ticket-status status-<?php echo strtolower(esc($ticket['status'])); ?>"><?php echo esc($ticket['status']); ?></div>
                                    </div>

                                    <div class="ticket-body">
                                        <h3 class="movie-title"><?php echo esc($ticket['movie_name']); ?></h3>

                                        <div class="ticket-details">
                                            <div class="detail-row">
                                                <span class="label">Theatre:</span>
                                                <span class="value"><?php echo esc($ticket['theatre_name']); ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="label">Location:</span>
                                                <span class="value"><?php echo esc($ticket['street']); ?>, <?php echo esc($ticket['city']); ?>, <?php echo esc($ticket['state']); ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="label">Auditorium:</span>
                                                <span class="value"><?php echo esc($ticket['auditorium_name']); ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="label">Showtime:</span>
                                                <span class="value"><?php echo date('l, F j, Y \a\t g:i A', strtotime($ticket['starttime'])); ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="label">Seat:</span>
                                                <span class="value">Row <?php echo esc($ticket['rownumber']); ?>, Seat <?php echo esc($ticket['seatnumber']); ?><?php if ($ticket['seattype'] !== 'standard'): ?> (<?php echo esc(strtoupper($ticket['seattype'])); ?>)<?php endif; ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="label">Price:</span>
                                                <span class="value">
                                                    $<?php echo number_format($ticket['price'], 2); ?>
                                                    <?php if ($ticket['discounttype'] && strtoupper($ticket['discounttype']) !== 'NONE'): ?>
                                                        <span class="discount-badge"><?php echo esc($ticket['discounttype']); ?></span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>

                                        <?php if ($ticket['status'] === 'PURCHASED' || $ticket['status'] === 'RESERVED'): ?>
                                            <div class="ticket-actions">
                                                <form method="POST" action="refund.php">
                                                    <input type="hidden" name="ticket_id" value="<?php echo esc($ticket['ticketid']); ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to refund this ticket?');">Request Refund</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
            <?php
    require_once __DIR__ . '/../includes/footer.php';
echo theatre_footer();
?>
    </body>
</html>

<?php require_once '../includes/footer.php'; ?>
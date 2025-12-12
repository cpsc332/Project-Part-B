<?php

declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: my_tickets.php");
    exit();
}

session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

$csrf_token = param('csrf_token', '', 'POST');
if (!check_token($csrf_token)) {
    $_SESSION['flash_message'] = 'Invalid security token. Please try again.';
    $_SESSION['flash_type'] = 'error';
    header("Location: my_tickets.php");
    exit();
}

$ticket_id = param('ticket_id', 0, 'POST');

if (empty($ticket_id) || !is_numeric($ticket_id)) {
    $_SESSION['flash_message'] = 'Invalid ticket ID.';
    $_SESSION['flash_type'] = 'error';
    header("Location: my_tickets.php");
    exit();
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT t.ticketid, t.status, t.customerid, c.email, s.starttime, m.name AS movie_name FROM ticket t JOIN customer c ON t.customerid = c.customerid JOIN showtime s ON t.showtimeid = s.showtimeid JOIN movie m ON s.movieid = m.movieid WHERE t.ticketid = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        $pdo->rollBack();
        $_SESSION['flash_message'] = 'Ticket not found.';
        $_SESSION['flash_type'] = 'error';
        header("Location: my_tickets.php");
        exit();
    }

    if ($ticket['status'] === 'REFUNDED') {
        $pdo->rollBack();
        $_SESSION['flash_message'] = 'This ticket has already been refunded.';
        $_SESSION['flash_type'] = 'info';
        header("Location: my_tickets.php?email=" . urlencode($ticket['email']));
        exit();
    }

    if ($ticket['status'] === 'USED') {
        $pdo->rollBack();
        $_SESSION['flash_message'] = 'Cannot refund a ticket that has already been used.';
        $_SESSION['flash_type'] = 'error';
        header("Location: my_tickets.php?email=" . urlencode($ticket['email']));
        exit();
    }

    $showtime = strtotime($ticket['starttime']);
    $current_time = time();

    if ($current_time > $showtime) {
        $pdo->rollBack();
        $_SESSION['flash_message'] = 'Cannot refund tickets for showtimes that have already passed.';
        $_SESSION['flash_type'] = 'error';
        header("Location: my_tickets.php?email=" . urlencode($ticket['email']));
        exit();
    }

    $hours_before_showtime = ($showtime - $current_time) / 3600;
    if ($hours_before_showtime < 2) {
        $pdo->rollBack();
        $_SESSION['flash_message'] = 'Refunds must be requested at least 2 hours before the showtime.';
        $_SESSION['flash_type'] = 'error';
        header("Location: my_tickets.php?email=" . urlencode($ticket['email']));
        exit();
    }

    $stmt = $pdo->prepare("UPDATE ticket SET status = 'REFUNDED' WHERE ticketid = ?");
    $stmt->execute([$ticket_id]);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        $_SESSION['flash_message'] = 'Failed to process refund. Please try again.';
        $_SESSION['flash_type'] = 'error';
        header("Location: my_tickets.php?email=" . urlencode($ticket['email']));
        exit();
    }

    $pdo->commit();

    $_SESSION['flash_message'] = 'Ticket #' . $ticket_id . ' for "' . $ticket['movie_name'] . '" has been successfully refunded.';
    $_SESSION['flash_type'] = 'success';

    header("Location: my_tickets.php?email=" . urlencode($ticket['email']));
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Refund error for ticket $ticket_id: " . $e->getMessage());
    $_SESSION['flash_message'] = 'An error occurred while processing the refund. Please try again or contact support.';
    $_SESSION['flash_type'] = 'error';
    header("Location: my_tickets.php");
    exit();
}

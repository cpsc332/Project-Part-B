<?php

declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: showtimes.php");
    exit();
}

session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

$csrf_token = param('csrf_token', '', 'POST');
if (!check_token($csrf_token)) {
    $_SESSION['flash_message'] = 'Invalid security token. Please try again.';
    $_SESSION['flash_type'] = 'error';
    header("Location: seats.php?showtime_id=" . param('showtime_id', 0, 'POST'));
    exit();
}

$showtime_id = param('showtime_id', 0, 'POST');
$seat_id = param('seat_id', 0, 'POST');
$customer_name = param('customer_name', '', 'POST');
$customer_email = param('customer_email', '', 'POST');
$discount_code = param('discount_code', null, 'POST');

$errors = [];

if (empty($showtime_id) || !is_numeric($showtime_id)) {
    $errors[] = 'Invalid showtime selected.';
}

if (empty($seat_id) || !is_numeric($seat_id)) {
    $errors[] = 'Invalid seat selected.';
}

if (empty($customer_name)) {
    $errors[] = 'Customer name is required.';
}

if (empty($customer_email) || !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email address is required.';
}

if (!empty($errors)) {
    $_SESSION['flash_message'] = implode(' ', $errors);
    $_SESSION['flash_type'] = 'error';
    header("Location: seats.php?showtime_id=" . $showtime_id);
    exit();
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT customerid FROM customer WHERE email = ?");
    $stmt->execute([$customer_email]);
    $customer = $stmt->fetch();

    if ($customer) {
        $customer_id = $customer['customerid'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO customer (name, email, guestflag) VALUES (?, ?, 0)");
        $stmt->execute([$customer_name, $customer_email]);
        $customer_id = $pdo->lastInsertId();
    }

    $stmt = $pdo->prepare("CALL sell_ticket(?, ?, ?, ?, @ticket_id)");
    $stmt->execute([$showtime_id, $seat_id, $customer_id, $discount_code]);

    $result = $pdo->query("SELECT @ticket_id AS ticket_id")->fetch();
    $ticket_id = $result['ticket_id'];

    $pdo->commit();

    $_SESSION['flash_message'] = 'Ticket purchased successfully! Ticket ID: #' . $ticket_id;
    $_SESSION['flash_type'] = 'success';
    header("Location: my_tickets.php?email=" . urlencode($customer_email));
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();

    $error_message = $e->getMessage();
    if (str_contains($error_message, 'Seat already sold')) {
        $_SESSION['flash_message'] = 'Sorry, this seat has already been taken. Please select another seat.';
    } elseif (str_contains($error_message, 'does not belong')) {
        $_SESSION['flash_message'] = 'Invalid seat selection for this showtime.';
    } else {
        $_SESSION['flash_message'] = 'An error occurred while processing your purchase. Please try again.';
    }
    $_SESSION['flash_type'] = 'error';

    header("Location: seats.php?showtime_id=" . $showtime_id);
    exit();
}

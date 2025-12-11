<?php

function login_user(array $userRow): void
{
    $_SESSION['user'] = [
        'id'    => (int)$userRow['CustomerID'],
        'name'  => $userRow['Name'],
        'email' => $userRow['Email'],
        'role'  => $userRow['Role'] ?? 'customer',
    ];
    session_regenerate_id(true);
}

function logout_user(): void
{
    unset($_SESSION['user']);
    session_regenerate_id(true);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: login.php');
        exit;
    }
}

function require_role(string $role): void
{
    $user = current_user();
    if (!$user || $user['role'] !== $role) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

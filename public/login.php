<?php
require_once __DIR__ . '/../includes/init.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim(param('email', '', 'POST'));
    $password = param('password', '', 'POST');

    if ($email === '' || $password === '') {
        $error = 'Please enter email and password.';
    } else {
        $stmt = $pdo->prepare("
            SELECT CustomerID, Name, Email, PasswordHash, Role
            FROM customer
            WHERE Email = :email
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $hashInput = hash('sha256', $password);
            if (strcasecmp($hashInput, $user['PasswordHash']) === 0) {
                login_user($user);
                header('Location: index.php');
                exit;
            }
        }
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html>
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
<head>
    <meta charset="UTF-8">
    <title><?php echo esc(t('login_heading')); ?></title>
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<h1><?php echo esc(t('login_heading')); ?></h1>

<?php if ($error): ?>
    <p style="color:red;"><?php echo esc($error); ?></p>
<?php endif; ?>

<form method="post" action="login.php">
    <div>
        <label>Email:</label>
        <input type="email" name="email" required>
    </div>
    <div>
        <label>Password:</label>
        <input type="password" name="password" required>
    </div>
    <button type="submit">Log in</button>
</form>

</body>
</html>

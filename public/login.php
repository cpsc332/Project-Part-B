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

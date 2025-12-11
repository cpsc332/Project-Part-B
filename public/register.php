<?php
require_once __DIR__ . '/../includes/init.php';

$error = '';
$ok    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim(param('name', '', 'POST'));
    $email    = trim(param('email', '', 'POST'));
    $password = param('password', '', 'POST');
    $confirm  = param('confirm', '', 'POST');

    if ($name === '' || $email === '' || $password === '' || $confirm === '') {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer WHERE Email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'That email is already registered.';
        } else {
            $hash = hash('sha256', $password);
            $stmt = $pdo->prepare("
                INSERT INTO customer (Name, Email, GuestFlag, PasswordHash, Role)
                VALUES (:name, :email, 0, :hash, 'customer')
            ");
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':hash' => $hash,
            ]);

            $id = (int)$pdo->lastInsertId();
            $userRow = [
                'CustomerID'  => $id,
                'Name'        => $name,
                'Email'       => $email,
                'PasswordHash'=> $hash,
                'Role'        => 'customer',
            ];
            login_user($userRow);
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo esc(t('register_heading')); ?></title>
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<h1><?php echo esc(t('register_heading')); ?></h1>

<?php if ($error): ?>
    <p style="color:red;"><?php echo esc($error); ?></p>
<?php endif; ?>

<form method="post" action="register.php">
    <div>
        <label>Name:</label>
        <input type="text" name="name" required value="<?php echo isset($name) ? esc($name) : ''; ?>">
    </div>
    <div>
        <label>Email:</label>
        <input type="email" name="email" required value="<?php echo isset($email) ? esc($email) : ''; ?>">
    </div>
    <div>
        <label>Password:</label>
        <input type="password" name="password" required>
    </div>
    <div>
        <label>Confirm Password:</label>
        <input type="password" name="confirm" required>
    </div>
    <button type="submit">Register</button>
</form>

</body>
</html>

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

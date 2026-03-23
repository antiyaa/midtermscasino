<?php
session_start();

$valid_username = 'admin';
$valid_password = 'password123';

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: Homepage.php');
    exit;
}

if (isset($_SESSION['exited']) && $_SESSION['exited'] === true) {
    ?>
    <!DOCTYPE html>
    <html>
    <body>
        <h2>System Exited</h2>
        <p>Too many failed login attempts. The system has exited.</p>
        <p>Please <strong>close your browser</strong> and reopen it to try again.</p>
    </body>
    </html>
    <?php
    exit;
}

if (!isset($_SESSION['attempts'])) {
    $_SESSION['attempts'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === $valid_username && $password === $valid_password) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['balance'] = 100;        // Starting balance
        $_SESSION['attempts'] = 0;
        unset($_SESSION['exited']);
        header('Location: Homepage.php');
        exit;
    } else {
        $_SESSION['attempts']++;
        if ($_SESSION['attempts'] >= 3) {
            $_SESSION['exited'] = true;
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid credentials. Attempts: ' . $_SESSION['attempts'] . '/3';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<body>
    <h2>Login</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="post">
        Username: <input type="text" name="username" required><br><br>
        Password: <input type="password" name="password" required><br><br>
        <input type="submit" value="Login">
    </form>
    <p>Attempts remaining: <?php echo 3 - $_SESSION['attempts']; ?></p>
</body>
</html>
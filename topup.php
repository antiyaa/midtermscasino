<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (int)($_POST['amount'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($amount < 100) {
        $message = "Amount must be at least 100 credits.";
    } elseif ($action === 'deposit') {
        // Top up
        $_SESSION['balance'] += $amount;
        $message = "Successfully deposited $amount credits. New balance: {$_SESSION['balance']}.";
    } elseif ($action === 'withdraw') {
        // Withdraw
        if ($amount > $_SESSION['balance']) {
            $message = "Insufficient balance. You have {$_SESSION['balance']} credits.";
        } else {
            $_SESSION['balance'] -= $amount;
            $message = "Successfully withdrew $amount credits. New balance: {$_SESSION['balance']}.";
        }
    } else {
        $message = "Please select an action.";
    }
}
?>
<!DOCTYPE html>
<html>
<body>
    <h2>Manage Balance</h2>
    <p>Your current balance: <strong><?php echo $_SESSION['balance']; ?> credits</strong></p>

    <?php if ($message) echo "<p>$message</p>"; ?>

    <form method="post">
        Amount (minimum 100): <input type="number" name="amount" min="100" required><br><br>
        <input type="radio" name="action" value="deposit" required> Deposit (Top Up)<br>
        <input type="radio" name="action" value="withdraw" required> Withdraw<br><br>
        <input type="submit" value="Submit">
    </form>

    <br><button onclick="location.href='Homepage.php'">Back to Homepage</button>
</body>
</html>
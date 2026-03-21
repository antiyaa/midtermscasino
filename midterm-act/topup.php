<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (int)($_POST['amount'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($amount < 100) {
        $message = "Amount must be at least 100 credits.";
        $messageType = 'error';
    } elseif ($action === 'deposit') {
        $_SESSION['balance'] += $amount;
        $message = "Successfully deposited " . number_format($amount) . " credits! New balance: " . number_format($_SESSION['balance']) . " credits.";
        $messageType = 'success';
    } elseif ($action === 'withdraw') {
        if ($amount > $_SESSION['balance']) {
            $message = "Insufficient balance. You have " . number_format($_SESSION['balance']) . " credits.";
            $messageType = 'error';
        } else {
            $_SESSION['balance'] -= $amount;
            $message = "Successfully withdrew " . number_format($amount) . " credits! New balance: " . number_format($_SESSION['balance']) . " credits.";
            $messageType = 'success';
        }
    } else {
        $message = "Please select an action.";
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Balance</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background: white;
            max-width: 450px;
            width: 100%;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .balance {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .balance-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .balance-amount {
            font-size: 28px;
            font-weight: bold;
            color: #28a745;
        }

        .message {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .radio-group {
            margin: 10px 0;
        }

        .radio-group label {
            display: inline-block;
            margin-right: 20px;
            font-weight: normal;
            cursor: pointer;
        }

        .radio-group input {
            margin-right: 5px;
        }

        button, .back-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
        }

        button {
            background: #007bff;
            color: white;
        }

        button:hover {
            background: #0056b3;
        }

        .back-btn {
            background: #6c757d;
            color: white;
            text-align: center;
            display: inline-block;
            text-decoration: none;
        }

        .back-btn:hover {
            background: #5a6268;
        }

        .info {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>💰 Manage Balance</h2>

        <div class="balance">
            <div class="balance-label">Current Balance</div>
            <div class="balance-amount"><?php echo number_format($_SESSION['balance']); ?> credits</div>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Amount (minimum 100)</label>
                <input type="number" name="amount" min="100" step="100" required>
            </div>

            <div class="form-group">
                <label>Action</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="action" value="deposit" required> 💰 Deposit
                    </label>
                    <label>
                        <input type="radio" name="action" value="withdraw" required> 💸 Withdraw
                    </label>
                </div>
            </div>

            <button type="submit">Submit</button>
        </form>

        <button class="back-btn" onclick="location.href='Homepage.php'">← Back to Homepage</button>

        <div class="info">
            Minimum transaction: 100 credits
        </div>
    </div>
</body>
</html>
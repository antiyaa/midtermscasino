<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// Initialize balance if not set
if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 0;
}

// Flash message handling
$message = '';
$messageType = '';

if (isset($_SESSION['flash_msg'])) {
    $message = $_SESSION['flash_msg'];
    $messageType = $_SESSION['flash_type'];
    unset($_SESSION['flash_msg']);
    unset($_SESSION['flash_type']);
}

// Cancel pending transaction
if (isset($_GET['cancel_pending'])) {
    unset($_SESSION['pending_amount']);
    unset($_SESSION['pending_action']);
    unset($_SESSION['pending_cp']);
    unset($_SESSION['pending_code']);
    unset($_SESSION['pending_resend_count']);
    $_SESSION['flash_msg'] = "Pending transaction cancelled.";
    $_SESSION['flash_type'] = "info";
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Resend verification code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_code']) && isset($_SESSION['pending_code'])) {
    $resendCount = $_SESSION['pending_resend_count'] ?? 0;
    if ($resendCount < 3) {
        $newCode = random_int(100000, 999999);
        $_SESSION['pending_code'] = $newCode;
        $_SESSION['pending_resend_count'] = $resendCount + 1;
        
        $_SESSION['flash_msg'] = "New verification code: <strong>{$newCode}</strong>. You have " . (3 - ($resendCount + 1)) . " resend(s) left.";
        $_SESSION['flash_type'] = "info";
    } else {
        $_SESSION['flash_msg'] = "You have reached the maximum of 3 resend attempts. Please cancel and start over.";
        $_SESSION['flash_type'] = "error";
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Process verification code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verification_code'])) {
    $enteredCode = trim($_POST['verification_code']);
    
    if (isset($_SESSION['pending_code']) && $enteredCode == $_SESSION['pending_code']) {
        $amount = (int)$_SESSION['pending_amount'];
        $action = $_SESSION['pending_action'];
        $cpNumber = $_SESSION['pending_cp'] ?? '';
        
        // Validate again before processing
        $valid = true;
        
        // Check divisibility by 100 (same for both actions)
        if ($amount % 100 != 0) {
            $valid = false;
            $_SESSION['flash_msg'] = "Amount must be divisible by 100.";
            $_SESSION['flash_type'] = "error";
        }
        
        if ($valid) {
            if ($action === 'deposit') {
                if ($amount < 100 || $amount > 1000000) {
                    $valid = false;
                    $_SESSION['flash_msg'] = "Deposit amount must be between 100 and 1,000,000 credits.";
                    $_SESSION['flash_type'] = "error";
                } elseif (empty($cpNumber)) {
                    $valid = false;
                    $_SESSION['flash_msg'] = "CP Number is required for deposit.";
                    $_SESSION['flash_type'] = "error";
                }
            } elseif ($action === 'withdraw') {
                if ($amount < 100) {
                    $valid = false;
                    $_SESSION['flash_msg'] = "Withdrawal amount must be at least 100 credits.";
                    $_SESSION['flash_type'] = "error";
                } elseif ($amount > $_SESSION['balance']) {
                    $valid = false;
                    $_SESSION['flash_msg'] = "Insufficient balance for withdrawal.";
                    $_SESSION['flash_type'] = "error";
                } elseif (empty($cpNumber)) {
                    $valid = false;
                    $_SESSION['flash_msg'] = "CP Number is required for withdrawal.";
                    $_SESSION['flash_type'] = "error";
                }
            } else {
                $valid = false;
                $_SESSION['flash_msg'] = "Invalid action.";
                $_SESSION['flash_type'] = "error";
            }
        }
        
        if ($valid) {
            if ($action === 'deposit') {
                $_SESSION['balance'] += $amount;
                $_SESSION['flash_msg'] = "Successfully deposited " . number_format($amount) . " credits (CP: " . htmlspecialchars($cpNumber) . ")! New balance: " . number_format($_SESSION['balance']) . " credits.";
                $_SESSION['flash_type'] = "success";
            } elseif ($action === 'withdraw') {
                $_SESSION['balance'] -= $amount;
                $_SESSION['flash_msg'] = "Successfully withdrew " . number_format($amount) . " credits (CP: " . htmlspecialchars($cpNumber) . ")! New balance: " . number_format($_SESSION['balance']) . " credits.";
                $_SESSION['flash_type'] = "success";
            }
            
            // Clear pending data after successful transaction
            unset($_SESSION['pending_amount']);
            unset($_SESSION['pending_action']);
            unset($_SESSION['pending_cp']);
            unset($_SESSION['pending_code']);
            unset($_SESSION['pending_resend_count']);
        } else {
            // Clear pending data on validation failure
            unset($_SESSION['pending_amount']);
            unset($_SESSION['pending_action']);
            unset($_SESSION['pending_cp']);
            unset($_SESSION['pending_code']);
            unset($_SESSION['pending_resend_count']);
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $_SESSION['flash_msg'] = "Invalid verification code. Please try again.";
        $_SESSION['flash_type'] = "error";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Process new transaction request (first step: generate verification code)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount']) && isset($_POST['action']) && !isset($_POST['verification_code'])) {
    $amount = (int)($_POST['amount'] ?? 0);
    $action = $_POST['action'] ?? '';
    $cpNumber = trim($_POST['cp_number'] ?? '');
    
    $error = null;
    
    // Validate CP Number: exactly 11 digits
    if (!preg_match('/^\d{11}$/', $cpNumber)) {
        $error = "CP Number must be exactly 11 digits (0-9 only).";
    }
    // Validate amount is divisible by 100
    elseif ($amount % 100 != 0) {
        $error = "Amount must be divisible by 100 (e.g., 100, 200, 500, etc.).";
    }
    // Validate amount and balance based on action
    elseif ($action === 'deposit') {
        if ($amount < 100) {
            $error = "Minimum deposit amount is 100 credits.";
        } elseif ($amount > 1000000) {
            $error = "Maximum deposit amount is 1,000,000 credits.";
        }
    } elseif ($action === 'withdraw') {
        if ($amount < 100) {
            $error = "Minimum withdrawal amount is 100 credits.";
        } elseif ($amount > $_SESSION['balance']) {
            $error = "Insufficient balance. You have " . number_format($_SESSION['balance']) . " credits.";
        }
    } else {
        $error = "Please select a valid action (Deposit or Withdraw).";
    }
    
    if ($error) {
        $_SESSION['flash_msg'] = $error;
        $_SESSION['flash_type'] = "error";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Generate 6-digit verification code
    $verificationCode = random_int(100000, 999999);
    
    // Store pending transaction in session
    $_SESSION['pending_amount'] = $amount;
    $_SESSION['pending_action'] = $action;
    $_SESSION['pending_cp'] = $cpNumber;
    $_SESSION['pending_code'] = $verificationCode;
    $_SESSION['pending_resend_count'] = 0;
    
    // Show flash message (still useful for info)
    $_SESSION['flash_msg'] = "Verification required. Please check the alert for your 6-digit code.";
    $_SESSION['flash_type'] = "info";
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Determine if we are in pending verification state
$hasPending = isset($_SESSION['pending_code']) && isset($_SESSION['pending_amount']) && isset($_SESSION['pending_action']);
$pendingDetails = null;
$resendCount = 0;
$resendRemaining = 0;
$pendingCode = null;
if ($hasPending) {
    $pendingDetails = [
        'amount' => $_SESSION['pending_amount'],
        'action' => $_SESSION['pending_action'],
        'cp' => $_SESSION['pending_cp'] ?? null
    ];
    $resendCount = $_SESSION['pending_resend_count'] ?? 0;
    $resendRemaining = max(0, 3 - $resendCount);
    $pendingCode = $_SESSION['pending_code']; // store for possible display
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Balance</title>
    <style>
        .resend-btn { margin-left: 10px; }
        .resend-info { font-size: 0.9em; margin-top: 5px; }
        .form-group { margin-bottom: 15px; }
        .message { margin: 15px 0; padding: 10px; border-radius: 4px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .message.info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .balance { margin: 20px 0; font-size: 1.2em; }
        button, .cancel-btn { padding: 8px 15px; cursor: pointer; }
        .cancel-btn { background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px; padding: 8px 15px; }
        .back-btn { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Manage Balance</h2>
        
        <div class="balance">
            <div class="balance-label">Current Balance</div>
            <div class="balance-amount"><?php echo number_format($_SESSION['balance']); ?> credits</div>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($messageType); ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($hasPending): ?>
            <!-- PENDING VERIFICATION STEP -->
            <div class="pending-section">
                <h3>Confirm Transaction</h3>
                <div class="pending-details">
                    <p><strong>Action:</strong> <?php echo ucfirst(htmlspecialchars($pendingDetails['action'])); ?></p>
                    <p><strong>Amount:</strong> <?php echo number_format($pendingDetails['amount']); ?> credits</p>
                    <p><strong>CP Number:</strong> <?php echo htmlspecialchars($pendingDetails['cp']); ?></p>
                </div>
                <form method="post">
                    <div class="form-group">
                        <label>Verification Code (6 digits)</label>
                        <input type="text" name="verification_code" pattern="[0-9]{6}" maxlength="6" required placeholder="Enter 6-digit code">
                    </div>
                    <button type="submit">Confirm Transaction</button>
                    <a href="?cancel_pending=1" class="cancel-btn">Cancel</a>
                </form>
                
                <div class="resend-section" style="margin-top: 15px;">
                    <?php if ($resendRemaining > 0): ?>
                        <form method="post" style="display: inline;">
                            <button type="submit" name="resend_code" class="resend-btn">Resend Code (<?php echo $resendRemaining; ?> left)</button>
                        </form>
                    <?php else: ?>
                        <p class="resend-info" style="color: red;">You have used all 3 resend attempts. Please cancel and start a new transaction.</p>
                    <?php endif; ?>
                    <p class="resend-info">If you didn't receive the code, you can request a new one (max 3 times).</p>
                </div>
            </div>
        <?php else: ?>
            <!-- MAIN FORM: DEPOSIT / WITHDRAW (NO PENDING) -->
            <form method="post">
                <div class="form-group">
                    <label>Amount (minimum 100, multiples of 100)</label>
                    <input type="number" name="amount" min="100" step="100" required>
                    <small class="amount-hint">Amount must be divisible by 100 (e.g., 100, 200, 500, etc.)</small>
                </div>
                
                <div class="form-group">
                    <label>Action</label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="action" value="deposit" required> Deposit (Top-up)
                        </label>
                        <label>
                            <input type="radio" name="action" value="withdraw" required> Withdraw
                        </label>
                    </div>
                </div>
                
                <!-- CP Number field: exactly 11 digits -->
                <div class="form-group">
                    <label>CP Number (11 digits)</label>
                    <input type="text" name="cp_number" placeholder="e.g., 09171234567" pattern="[0-9]{11}" maxlength="11" required>
                    <small>Must be exactly 11 digits (0-9 only)</small>
                </div>
                
                <button type="submit">Proceed with Verification</button>
            </form>
            
            <div class="info" style="margin-top: 15px;">
                <strong>Note:</strong> After clicking "Proceed", you will see an alert with your 6‑digit verification code. Enter it below to complete the transaction. You can resend the code up to 3 times if needed.
            </div>
        <?php endif; ?>
        
        <button class="back-btn" onclick="location.href='Homepage.php'">Back to Homepage</button>
        
        <div class="info" style="margin-top: 15px;">
            Minimum transaction: 100 credits | Maximum deposit: 1,000,000 credits | Amount must be multiples of 100.
        </div>
    </div>
</body>
</html>

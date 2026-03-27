<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 0;
}

$message = '';
$messageType = '';

if (isset($_SESSION['flash_msg'])) {
    $message = $_SESSION['flash_msg'];
    $messageType = $_SESSION['flash_type'];
    unset($_SESSION['flash_msg']);
    unset($_SESSION['flash_type']);
}

if (isset($_GET['cancel_pending'])) {
    unset($_SESSION['pending_amount'], $_SESSION['pending_action'],
          $_SESSION['pending_cp'], $_SESSION['pending_code'], $_SESSION['pending_resend_count']);
    unset($_SESSION['show_alert']); // clean up alert flag
    $_SESSION['flash_msg']  = "Pending transaction cancelled.";
    $_SESSION['flash_type'] = "info";
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_code']) && isset($_SESSION['pending_code'])) {
    $resendCount = $_SESSION['pending_resend_count'] ?? 0;
    if ($resendCount < 3) {
        $newCode = random_int(100000, 999999);
        $_SESSION['pending_code']         = $newCode;
        $_SESSION['pending_resend_count'] = $resendCount + 1;
        $_SESSION['flash_msg']  = "New verification code generated. Check the alert box.";
        $_SESSION['flash_type'] = "info";
        // Set flag to show alert with the new code
        $_SESSION['show_alert'] = true;
    } else {
        $_SESSION['flash_msg']  = "Maximum of 3 resend attempts reached. Please cancel and start over.";
        $_SESSION['flash_type'] = "error";
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verification_code'])) {
    $enteredCode = trim($_POST['verification_code']);
    if (isset($_SESSION['pending_code']) && $enteredCode == $_SESSION['pending_code']) {
        $amount   = (int)$_SESSION['pending_amount'];
        $action   = $_SESSION['pending_action'];
        $cpNumber = $_SESSION['pending_cp'] ?? '';
        $valid    = true;

        if ($amount % 100 != 0) {
            $valid = false;
            $_SESSION['flash_msg']  = "Amount must be divisible by 100.";
            $_SESSION['flash_type'] = "error";
        }

        if ($valid) {
            if ($action === 'deposit') {
                if ($amount < 100 || $amount > 1000000) { $valid = false; $_SESSION['flash_msg'] = "Deposit must be between ₱100 and ₱1,000,000."; $_SESSION['flash_type'] = "error"; }
                elseif (empty($cpNumber))               { $valid = false; $_SESSION['flash_msg'] = "CP Number is required."; $_SESSION['flash_type'] = "error"; }
            } elseif ($action === 'withdraw') {
                if ($amount < 100)                       { $valid = false; $_SESSION['flash_msg'] = "Minimum withdrawal is ₱100."; $_SESSION['flash_type'] = "error"; }
                elseif ($amount > $_SESSION['balance'])  { $valid = false; $_SESSION['flash_msg'] = "Insufficient balance."; $_SESSION['flash_type'] = "error"; }
                elseif (empty($cpNumber))               { $valid = false; $_SESSION['flash_msg'] = "CP Number is required."; $_SESSION['flash_type'] = "error"; }
            } else {
                $valid = false; $_SESSION['flash_msg'] = "Invalid action."; $_SESSION['flash_type'] = "error";
            }
        }

        if ($valid) {
            if ($action === 'deposit')  $_SESSION['balance'] += $amount;
            else                        $_SESSION['balance'] -= $amount;
            $_SESSION['flash_msg']  = "Successfully " . ($action === 'deposit' ? 'deposited' : 'withdrew') . " ₱" . number_format($amount) . " (CP: " . htmlspecialchars($cpNumber) . "). New balance: ₱" . number_format($_SESSION['balance']) . ".";
            $_SESSION['flash_type'] = "success";
        }

        unset($_SESSION['pending_amount'], $_SESSION['pending_action'],
              $_SESSION['pending_cp'], $_SESSION['pending_code'], $_SESSION['pending_resend_count']);
        unset($_SESSION['show_alert']);
    } else {
        $_SESSION['flash_msg']  = "Invalid verification code. Please try again.";
        $_SESSION['flash_type'] = "error";
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'], $_POST['action']) && !isset($_POST['verification_code'])) {
    $amount   = (int)($_POST['amount'] ?? 0);
    $action   = $_POST['action'] ?? '';
    $cpNumber = trim($_POST['cp_number'] ?? '');
    $error    = null;

    if (!preg_match('/^\d{11}$/', $cpNumber))        $error = "CP Number must be exactly 11 digits.";
    elseif ($amount % 100 != 0)                      $error = "Amount must be divisible by 100.";
    elseif ($action === 'deposit') {
        if ($amount < 100)        $error = "Minimum deposit is ₱100.";
        elseif ($amount > 1000000) $error = "Maximum deposit is ₱1,000,000.";
    } elseif ($action === 'withdraw') {
        if ($amount < 100)                    $error = "Minimum withdrawal is ₱100.";
        elseif ($amount > $_SESSION['balance']) $error = "Insufficient balance. You have ₱" . number_format($_SESSION['balance']) . ".";
    } else {
        $error = "Please select Deposit or Withdraw.";
    }

    if ($error) {
        $_SESSION['flash_msg']  = $error;
        $_SESSION['flash_type'] = "error";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $verificationCode = random_int(100000, 999999);
    $_SESSION['pending_amount']       = $amount;
    $_SESSION['pending_action']       = $action;
    $_SESSION['pending_cp']           = $cpNumber;
    $_SESSION['pending_code']         = $verificationCode;
    $_SESSION['pending_resend_count'] = 0;
    $_SESSION['show_alert']           = true;  // flag to trigger JavaScript alert

    $_SESSION['flash_msg']  = "Verification required. Check the alert box for your 6-digit code.";
    $_SESSION['flash_type'] = "info";
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$hasPending      = isset($_SESSION['pending_code'], $_SESSION['pending_amount'], $_SESSION['pending_action']);
$pendingDetails  = null;
$resendRemaining = 0;
$pendingCode     = null;
if ($hasPending) {
    $pendingDetails  = ['amount' => $_SESSION['pending_amount'], 'action' => $_SESSION['pending_action'], 'cp' => $_SESSION['pending_cp'] ?? ''];
    $resendRemaining = max(0, 3 - ($_SESSION['pending_resend_count'] ?? 0));
    $pendingCode     = $_SESSION['pending_code'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Casino Ni Lady – Manage Balance</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --mahogany:       #0E0500;
            --imperial-red:   #C0152A;
            --burnished-gold: #D4A017;
            --warm-champagne: #F5DFA0;
            --deep-burgundy:  #1C0A08;
            --gold-dim:       rgba(212,160,23,0.18);
            --gold-glow:      rgba(212,160,23,0.32);
            --text-muted:     rgba(245,223,160,0.45);
            --sans:           'Arial Narrow', Arial, sans-serif;
        }

        body {
            background: radial-gradient(circle at top, #1C0A08, #0E0500);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: var(--warm-champagne);
            font-family: var(--sans);
            padding: 0;
        }

        /* ── HEADER (same as game pages) ── */
        header {
            background: linear-gradient(90deg, #0E0500, #1C0A08);
            padding: 12px 32px;
            border-bottom: 2px solid var(--burnished-gold);
            border-top: 2px solid var(--burnished-gold);
            box-shadow: 0 0 24px var(--gold-glow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .header-logo {
            width: 56px;
            height: 56px;
            object-fit: contain;
            border-radius: 8px;
            filter: drop-shadow(0 0 6px rgba(212,160,23,0.4));
        }
        header h1 {
            color: var(--burnished-gold);
            font-size: 2.2rem;
            letter-spacing: 4px;
            font-family: 'Times New Roman', serif;
            text-shadow: 0 0 14px rgba(212,160,23,0.5);
            margin: 0;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .header-divider {
            width: 1px;
            height: 44px;
            background: linear-gradient(to bottom, transparent, var(--burnished-gold), transparent);
            opacity: 0.5;
            flex-shrink: 0;
        }
        .header-balance {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 2px;
        }
        .balance-label {
            color: var(--text-muted);
            font-size: 0.72rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        .balance-amount {
            color: var(--burnished-gold);
            font-size: 1.45rem;
            font-weight: bold;
            letter-spacing: 1px;
            text-shadow: 0 0 8px rgba(212,160,23,0.4);
        }
        .btn-deposit {
             background: linear-gradient(45deg, #1C0A08, #2a100a);
                border: 1px solid rgba(212,160,23,0.35);
                padding: 8px 12px;
                cursor: pointer;
                border-radius: 2px;
                transition: 0.3s;
                display: inline-flex;
                align-items: center;
                justify-content: center;
        }
        .btn-deposit img {
    width: 24px;
    height: 24px;
    display: block;
    filter: brightness(0) invert(1); /* makes the icon gold/champagne colored */
    transition: filter 0.3s, transform 0.2s;
}
        .btn-deposit:hover {
                background: linear-gradient(45deg, #2a0c08, #3e1610);
    border-color: var(--burnished-gold);
    box-shadow: 0 0 14px rgba(212,160,23,0.5);
    transform: scale(1.02);
        }


        /* ── CARD (centered) ── */
        .card {
            background: #1C0A08;
            border: 1px solid var(--gold-dim);
            border-radius: 14px;
            padding: 36px 38px;
            width: 100%;
            max-width: 460px;
            margin: 30px auto;
            box-shadow: 0 0 40px rgba(0,0,0,0.6), 0 0 0 1px rgba(212,160,23,0.06);
            position: relative;
        }

        .card-title {
            font-family: 'Times New Roman', serif;
            font-size: 1.8rem;
            color: var(--burnished-gold);
            letter-spacing: 4px;
            text-align: center;
            text-shadow: 0 0 14px rgba(212,160,23,0.4);
            margin-bottom: 6px;
        }
        .card-title::after {
            content: '';
            display: block;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--burnished-gold), transparent);
            margin: 10px auto 24px;
            width: 70%;
        }

        /* ── BALANCE DISPLAY ── */
        .balance-box {
            background: rgba(212,160,23,0.06);
            border: 1px solid var(--gold-dim);
            border-radius: 10px;
            padding: 18px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .balance-label {
            font-family: var(--sans);
            font-size: 0.7rem;
            letter-spacing: 3px;
            color: var(--text-muted);
            text-transform: uppercase;
        }
        .balance-amount {
            font-family: 'Times New Roman', serif;
            font-size: 1.6rem;
            font-weight: bold;
            color: var(--burnished-gold);
            text-shadow: 0 0 8px rgba(212,160,23,0.35);
        }

        /* ── FLASH MESSAGE ── */
        .flash {
            border-radius: 7px;
            padding: 11px 16px;
            margin-bottom: 20px;
            font-family: var(--sans);
            font-size: 0.88rem;
            letter-spacing: 0.3px;
            border-left: 3px solid;
        }
        .flash.success { background: rgba(74,222,128,0.07);  border-color: #4ade80; color: #86efac; }
        .flash.error   { background: rgba(248,113,113,0.08); border-color: #f87171; color: #fca5a5; }
        .flash.info    { background: rgba(212,160,23,0.08);  border-color: var(--burnished-gold); color: var(--warm-champagne); }

        /* ── FORM ELEMENTS ── */
        .field { margin-bottom: 20px; }
        .field label {
            display: block;
            font-family: var(--sans);
            font-size: 0.7rem;
            letter-spacing: 2.5px;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .field small {
            display: block;
            margin-top: 6px;
            font-family: var(--sans);
            font-size: 0.72rem;
            color: var(--text-muted);
            letter-spacing: 0.5px;
        }

        input[type="number"],
        input[type="text"] {
            width: 100%;
            background: #0E0500;
            border: 1px solid rgba(212,160,23,0.3);
            border-radius: 7px;
            color: var(--burnished-gold);
            font-size: 1rem;
            font-family: var(--sans);
            padding: 10px 14px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            letter-spacing: 0.5px;
        }
        input[type="number"]:focus,
        input[type="text"]:focus {
            border-color: var(--burnished-gold);
            box-shadow: 0 0 0 2px var(--gold-glow);
        }
        input::placeholder { color: rgba(212,160,23,0.25); }

        /* radio action toggle */
        .action-toggle {
            display: flex;
            gap: 10px;
        }
        .action-toggle input { display: none; }
        .action-toggle label {
            flex: 1;
            text-align: center;
            padding: 10px;
            border: 1px solid var(--gold-dim);
            border-radius: 7px;
            background: rgba(212,160,23,0.04);
            color: var(--warm-champagne);
            font-family: var(--sans);
            font-size: 0.88rem;
            font-weight: bold;
            letter-spacing: 1.5px;
            cursor: pointer;
            transition: 0.18s;
            text-transform: uppercase;
            margin-bottom: 0;
        }
        .action-toggle input:checked + label {
            border-color: var(--burnished-gold);
            background: rgba(212,160,23,0.12);
            color: var(--burnished-gold);
            box-shadow: 0 0 10px var(--gold-glow);
        }
        .action-toggle label:hover {
            border-color: rgba(212,160,23,0.45);
        }

        /* gold separator */
        .gold-sep {
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold-dim), transparent);
            margin: 8px 0 20px;
        }

        /* ── BUTTONS ── */
        .btn-primary {
            width: 100%;
            background: rgba(212,160,23,0.08);
            border: 1px solid var(--burnished-gold);
            color: var(--burnished-gold);
            padding: 12px;
            font-family: 'Times New Roman', serif;
            font-size: 1.15rem;
            font-weight: bold;
            letter-spacing: 2px;
            cursor: pointer;
            border-radius: 8px;
            transition: 0.2s;
            margin-top: 4px;
        }
        .btn-primary:hover {
            background: rgba(212,160,23,0.17);
            box-shadow: 0 0 14px var(--gold-glow);
        }

        .btn-secondary {
            width: 100%;
            background: rgba(212,160,23,0.04);
            border: 1px solid var(--gold-dim);
            color: var(--warm-champagne);
            padding: 11px;
            font-family: var(--sans);
            font-size: 0.9rem;
            font-weight: bold;
            letter-spacing: 1.5px;
            cursor: pointer;
            border-radius: 8px;
            transition: 0.2s;
            margin-top: 10px;
            text-align: center;
            display: block;
            text-decoration: none;
        }
        .btn-secondary:hover {
            border-color: var(--burnished-gold);
            color: var(--burnished-gold);
            background: rgba(212,160,23,0.10);
        }

        .btn-danger {
            width: 100%;
            background: rgba(192,21,42,0.07);
            border: 1px solid rgba(192,21,42,0.35);
            color: #f87171;
            padding: 11px;
            font-family: var(--sans);
            font-size: 0.9rem;
            font-weight: bold;
            letter-spacing: 1.5px;
            cursor: pointer;
            border-radius: 8px;
            transition: 0.2s;
            margin-top: 10px;
            text-align: center;
            display: block;
            text-decoration: none;
        }
        .btn-danger:hover {
            background: rgba(192,21,42,0.14);
            border-color: #C0152A;
        }

        .btn-resend {
            background: rgba(212,160,23,0.06);
            border: 1px solid var(--gold-dim);
            color: var(--warm-champagne);
            padding: 9px 20px;
            font-family: var(--sans);
            font-size: 0.85rem;
            font-weight: bold;
            letter-spacing: 1px;
            cursor: pointer;
            border-radius: 7px;
            transition: 0.2s;
        }
        .btn-resend:hover {
            border-color: var(--burnished-gold);
            color: var(--burnished-gold);
            background: rgba(212,160,23,0.12);
        }

        /* ── PENDING DETAIL BOX ── */
        .pending-box {
            background: rgba(212,160,23,0.05);
            border: 1px solid var(--gold-dim);
            border-radius: 9px;
            padding: 16px 20px;
            margin-bottom: 22px;
        }
        .pending-box-title {
            font-family: 'Times New Roman', serif;
            font-size: 1rem;
            color: var(--burnished-gold);
            letter-spacing: 2px;
            margin-bottom: 12px;
        }
        .pending-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: var(--sans);
            font-size: 0.88rem;
            padding: 4px 0;
            border-bottom: 1px solid rgba(212,160,23,0.07);
        }
        .pending-row:last-child { border-bottom: none; }
        .pending-key   { color: var(--text-muted); letter-spacing: 1px; font-size: 0.75rem; text-transform: uppercase; }
        .pending-value { color: var(--warm-champagne); font-weight: bold; }

        /* resend row */
        .resend-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 14px;
        }
        .resend-note {
            font-family: var(--sans);
            font-size: 0.75rem;
            color: var(--text-muted);
            letter-spacing: 0.3px;
        }

        /* footer note */
        .footer-note {
            margin-top: 22px;
            text-align: center;
            font-family: var(--sans);
            font-size: 0.72rem;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            line-height: 1.6;
        }
    </style>
    <script>
        // Show alert with verification code if flag is set
        <?php if (isset($_SESSION['show_alert']) && $_SESSION['show_alert'] && isset($_SESSION['pending_code'])): ?>
            alert("Your verification code is: <?php echo $_SESSION['pending_code']; ?>");
            <?php unset($_SESSION['show_alert']); ?>
        <?php endif; ?>

        // Sync max amount for deposit/withdraw
        window.addEventListener('DOMContentLoaded', function () {
            const depositRadio = document.querySelector('input[name="action"][value="deposit"]');
            const amountInput  = document.querySelector('input[name="amount"]');
            if (depositRadio && amountInput) {
                function syncMax() {
                    amountInput.max = depositRadio.checked ? 1000000 : '';
                }
                document.querySelectorAll('input[name="action"]').forEach(r => r.addEventListener('change', syncMax));
                syncMax();
            }
        });
    </script>
</head>
<body>

    <header>
        <div class="header-left">
            <img src="logo.png" alt="Casino Ni Lady Logo" class="header-logo">
            <h1>AURUM</h1>
        </div>
        <div class="header-right">
            <div class="header-divider"></div>
            <div class="header-balance">
                <span class="balance-label">Your Balance</span>
                <span class="balance-amount">₱<?php echo number_format($_SESSION['balance']); ?></span>
            </div>
            <button class="btn-deposit" onclick="location.href='Homepage.php'">
                <img src="home.png" alt="home">
            </button>
        </div>
    </header>

    <div class="card">
        <div class="card-title">MANAGE BALANCE</div>

        <!-- Flash message -->
        <?php if ($message): ?>
            <div class="flash <?php echo htmlspecialchars($messageType); ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($hasPending): ?>
        <!-- VERIFICATION STEP -->
            <div class="pending-box">
                <div class="pending-box-title">TRANSACTION SUMMARY</div>
                <div class="pending-row">
                    <span class="pending-key">Action</span>
                    <span class="pending-value"><?php echo strtoupper($pendingDetails['action']); ?></span>
                </div>
                <div class="pending-row">
                    <span class="pending-key">Amount</span>
                    <span class="pending-value">₱<?php echo number_format($pendingDetails['amount']); ?></span>
                </div>
                <div class="pending-row">
                    <span class="pending-key">CP Number</span>
                    <span class="pending-value"><?php echo htmlspecialchars($pendingDetails['cp']); ?></span>
                </div>
            </div>

            <form method="post">
                <div class="field">
                    <label>Enter 6-Digit Code</label>
                    <input type="text" name="verification_code" pattern="[0-9]{6}" maxlength="6"
                           required placeholder="— — — — — —" autofocus>
                </div>
                <button type="submit" class="btn-primary">CONFIRM TRANSACTION</button>
            </form>

            <div class="resend-row">
                <?php if ($resendRemaining > 0): ?>
                    <form method="post">
                        <button type="submit" name="resend_code" class="btn-resend">
                            RESEND CODE (<?php echo $resendRemaining; ?> left)
                        </button>
                    </form>
                    <span class="resend-note">Request a new code if you didn't receive one.</span>
                <?php else: ?>
                    <span class="resend-note" style="color:#f87171;">All resend attempts used. Cancel and start over.</span>
                <?php endif; ?>
            </div>

            <a href="?cancel_pending=1" class="btn-danger">CANCEL TRANSACTION</a>

        <?php else: ?>
        <!-- MAIN FORM -->
            <form method="post">
                <div class="field">
                    <label>Amount</label>
                    <input type="number" name="amount" min="100" step="100" required placeholder="e.g. 500">
                    <small>Minimum ₱100 · Must be a multiple of 100</small>
                </div>

                <div class="field">
                    <label>Action</label>
                    <div class="action-toggle">
                        <input type="radio" name="action" value="deposit"  id="act-dep" required>
                        <label for="act-dep">Deposit</label>
                        <input type="radio" name="action" value="withdraw" id="act-wd">
                        <label for="act-wd">Withdraw</label>
                    </div>
                </div>

                <div class="field">
                    <label>CP Number</label>
                    <input type="text" name="cp_number" placeholder="09171234567"
                           pattern="[0-9]{11}" maxlength="11" required>
                    <small>Exactly 11 digits, numbers only</small>
                </div>

                <div class="gold-sep"></div>

                <button type="submit" class="btn-primary">PROCEED WITH VERIFICATION</button>
            </form>

            <div class="footer-note">
                After clicking Proceed, your 6-digit verification code will appear in a pop‑up.<br>
                Enter it on the next screen to complete the transaction. You may resend up to 3 times.
            </div>
        <?php endif; ?>

        <div class="footer-note" style="margin-top:14px;">
            Min transaction: ₱100 &nbsp;·&nbsp; Max deposit: ₱1,000,000 &nbsp;·&nbsp; Amounts must be multiples of ₱100
        </div>
    </div>

</body>
</html>
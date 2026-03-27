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
        $message = "Amount must be at least ₱100.";
        $messageType = 'error';
    } elseif ($action === 'deposit') {
        $_SESSION['balance'] += $amount;
        $message = "Successfully deposited ₱" . number_format($amount) . ". New balance: ₱" . number_format($_SESSION['balance']) . ".";
        $messageType = 'success';
    } elseif ($action === 'withdraw') {
        if ($amount > $_SESSION['balance']) {
            $message = "Insufficient balance. You currently have ₱" . number_format($_SESSION['balance']) . ".";
            $messageType = 'error';
        } else {
            $_SESSION['balance'] -= $amount;
            $message = "Successfully withdrew ₱" . number_format($amount) . ". New balance: ₱" . number_format($_SESSION['balance']) . ".";
            $messageType = 'success';
        }
    } else {
        $message = "Please select an action.";
        $messageType = 'error';
    }
}
 
$balance_display = number_format($_SESSION['balance']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aurum — Top Up</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Barlow+Condensed:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            margin: 0; padding: 0; box-sizing: border-box;
        }
 
        :root {
            --mahogany:       #0E0500;
            --imperial-red:   #C0152A;
            --burnished-gold: #D4A017;
            --warm-champagne: #F5DFA0;
            --deep-burgundy:  #1C0A08;
            --text-muted:     rgba(245,223,160,0.45);
            --gold-dim:       rgba(212,160,23,0.15);
            --gold-glow:      rgba(212,160,23,0.28);
            --sans:           'Barlow Condensed', 'Arial Narrow', Arial, sans-serif;
            --serif:          'Cormorant Garamond', 'Times New Roman', serif;
        }
 
        body {
            background: radial-gradient(ellipse at top, #1C0A08 0%, #0E0500 70%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: var(--sans);
            color: var(--warm-champagne);
            overflow-x: hidden;
            position: relative;
        }
 
        /* Grid texture */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                repeating-linear-gradient(0deg, transparent, transparent 59px, rgba(212,160,23,0.025) 60px),
                repeating-linear-gradient(90deg, transparent, transparent 59px, rgba(212,160,23,0.025) 60px);
            pointer-events: none;
            z-index: 0;
        }
 
        /* ── HEADER (matches Homepage.php exactly) ── */
        header {
            background: linear-gradient(90deg, #0E0500, #1C0A08);
            padding: 12px 32px;
            border-bottom: 2px solid var(--burnished-gold);
            border-top: 2px solid var(--burnished-gold);
            box-shadow: 0 0 24px var(--gold-glow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 10;
        }
 
        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
 
        .header-logo {
            width: 56px; height: 56px;
            object-fit: contain;
            border-radius: 8px;
            filter: drop-shadow(0 0 6px rgba(212,160,23,0.4));
        }
 
        header h1 {
            color: var(--burnished-gold);
            font-size: 2.2rem;
            letter-spacing: 4px;
            font-family: var(--serif);
            font-weight: 600;
        }
 
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
 
        .header-divider {
            width: 1px; height: 44px;
            background: linear-gradient(to bottom, transparent, var(--burnished-gold), transparent);
            opacity: 0.5;
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
            transition: all 0.4s ease;
        }
 
        .btn-back {
            background: linear-gradient(45deg, #1C0A08, #2a100a);
            color: var(--warm-champagne);
            border: 1px solid rgba(212,160,23,0.35);
            padding: 11px 22px;
            font-weight: 600;
            font-size: 0.78rem;
            letter-spacing: 1.5px;
            cursor: pointer;
            border-radius: 2px;
            transition: 0.3s;
            white-space: nowrap;
            font-family: var(--sans);
            text-decoration: none;
            display: inline-block;
        }
        .btn-back:hover {
            border-color: rgba(212,160,23,0.65);
            box-shadow: 0 0 14px rgba(212,160,23,0.15);
            transform: scale(1.03);
        }
 
        /* ── MAIN ── */
        main {
            flex: 1;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 50px 20px 60px;
            position: relative;
            z-index: 1;
        }
 
        /* ── CARD ── */
        .topup-card {
            width: 100%;
            max-width: 460px;
            background: linear-gradient(160deg, #1C0A08 0%, #0E0500 100%);
            border: 1px solid rgba(212,160,23,0.38);
            padding: 46px 44px 40px;
            position: relative;
            animation: fadeUp 0.45s ease both;
        }
 
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
 
        /* Corner brackets */
        .topup-card::before,
        .topup-card::after,
        .topup-card .corners::before,
        .topup-card .corners::after {
            content: '';
            position: absolute;
            width: 14px; height: 14px;
            border-color: var(--burnished-gold);
            border-style: solid;
            opacity: 0.6;
        }
        .topup-card::before          { top:-1px;    left:-1px;  border-width: 2px 0 0 2px; }
        .topup-card::after           { top:-1px;    right:-1px; border-width: 2px 2px 0 0; }
        .topup-card .corners::before { bottom:-1px; left:-1px;  border-width: 0 0 2px 2px; }
        .topup-card .corners::after  { bottom:-1px; right:-1px; border-width: 0 2px 2px 0; }
 
        /* ── CARD HEADER ── */
        .card-title-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 4px;
        }
        .suit {
            font-size: 18px;
            color: var(--burnished-gold);
            opacity: 0.5;
            line-height: 1;
        }
        .card-title {
            font-family: var(--serif);
            font-size: 1.75rem;
            letter-spacing: 5px;
            color: var(--burnished-gold);
            font-weight: 600;
            line-height: 1;
        }
        .card-tagline {
            text-align: center;
            font-size: 0.67rem;
            letter-spacing: 3px;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 26px;
        }
        .gold-rule {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(212,160,23,0.4), transparent);
            margin-bottom: 28px;
        }
 
        /* ── BALANCE DISPLAY ── */
        .balance-display {
            background: rgba(14,5,0,0.6);
            border: 1px solid rgba(212,160,23,0.22);
            border-radius: 2px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }
        .bd-label {
            font-size: 0.67rem;
            letter-spacing: 2.5px;
            color: var(--text-muted);
            text-transform: uppercase;
        }
        .bd-amount {
            font-family: var(--serif);
            font-size: 1.65rem;
            color: var(--burnished-gold);
            font-weight: 600;
            letter-spacing: 1px;
        }
        .bd-pip {
            width: 5px; height: 5px;
            border-radius: 50%;
            background: rgba(212,160,23,0.3);
        }
 
        /* ── MESSAGES ── */
        .msg {
            font-size: 0.75rem;
            letter-spacing: 1px;
            font-family: var(--sans);
            padding: 11px 16px;
            border-radius: 2px;
            margin-bottom: 22px;
            line-height: 1.65;
        }
        .msg-error {
            color: #f08080;
            background: rgba(192,21,42,0.14);
            border: 1px solid rgba(192,21,42,0.28);
        }
        .msg-success {
            color: #a8d8a0;
            background: rgba(40,100,30,0.14);
            border: 1px solid rgba(60,160,40,0.25);
        }
 
        /* ── FIELDS ── */
        .field {
            margin-bottom: 20px;
        }
        .field > label {
            display: block;
            font-size: 0.67rem;
            letter-spacing: 2.5px;
            color: rgba(212,160,23,0.5);
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .field input[type="number"] {
            width: 100%;
            background: rgba(14,5,0,0.75);
            border: 1px solid rgba(212,160,23,0.28);
            color: var(--warm-champagne);
            padding: 12px 16px;
            font-size: 1.05rem;
            font-family: var(--sans);
            font-weight: 300;
            letter-spacing: 2px;
            outline: none;
            border-radius: 2px;
            transition: border-color 0.2s, box-shadow 0.2s;
            -webkit-appearance: none;
            -moz-appearance: textfield;
        }
        .field input[type="number"]::-webkit-inner-spin-button,
        .field input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; }
        .field input[type="number"]::placeholder {
            color: rgba(245,223,160,0.18);
            font-size: 0.85rem;
        }
        .field input[type="number"]:focus {
            border-color: rgba(212,160,23,0.6);
            box-shadow: 0 0 0 3px rgba(212,160,23,0.06);
        }
 
        /* ── QUICK AMOUNTS ── */
        .quick-amounts {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .quick-btn {
            background: rgba(14,5,0,0.6);
            border: 1px solid rgba(212,160,23,0.22);
            color: rgba(245,223,160,0.55);
            padding: 6px 14px;
            font-family: var(--sans);
            font-size: 0.72rem;
            letter-spacing: 1.5px;
            cursor: pointer;
            border-radius: 2px;
            transition: all 0.2s;
        }
        .quick-btn:hover {
            border-color: rgba(212,160,23,0.55);
            color: var(--burnished-gold);
            background: rgba(212,160,23,0.07);
        }
 
        /* ── ACTION TOGGLE ── */
        .action-toggle {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 2px;
        }
        .action-option {
            position: relative;
        }
        .action-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0; height: 0;
        }
        .action-option label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 13px 16px;
            background: rgba(14,5,0,0.6);
            border: 1px solid rgba(212,160,23,0.22);
            border-radius: 2px;
            cursor: pointer;
            font-size: 0.78rem;
            letter-spacing: 2.5px;
            color: rgba(245,223,160,0.45);
            text-transform: uppercase;
            transition: all 0.2s;
            user-select: none;
        }
        .action-option label .icon {
            font-size: 1rem;
            opacity: 0.6;
        }
        .action-option input[type="radio"]:checked + label {
            border-color: rgba(212,160,23,0.65);
            color: var(--burnished-gold);
            background: rgba(212,160,23,0.08);
            box-shadow: inset 0 0 18px rgba(212,160,23,0.05);
        }
        .action-option label:hover {
            border-color: rgba(212,160,23,0.45);
            color: rgba(245,223,160,0.7);
        }
        /* Deposit gets a green tint when selected */
        .action-option.deposit input[type="radio"]:checked + label {
            border-color: rgba(60,160,40,0.55);
            color: #a8d8a0;
            background: rgba(40,100,30,0.1);
        }
        /* Withdraw gets a red tint when selected */
        .action-option.withdraw input[type="radio"]:checked + label {
            border-color: rgba(192,21,42,0.55);
            color: #f08080;
            background: rgba(192,21,42,0.09);
        }
 
        /* ── SUBMIT BUTTON ── */
        .btn-submit {
            width: 100%;
            background: linear-gradient(180deg, #C0152A 0%, #8f0e1f 100%);
            color: var(--warm-champagne);
            border: 1px solid rgba(192,21,42,0.35);
            padding: 14px 20px;
            font-family: var(--sans);
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 3.5px;
            text-transform: uppercase;
            cursor: pointer;
            border-radius: 2px;
            margin-top: 6px;
            transition: filter 0.2s, transform 0.15s, box-shadow 0.2s;
        }
        .btn-submit:hover {
            filter: brightness(1.14);
            transform: translateY(-1px);
            box-shadow: 0 6px 22px rgba(192,21,42,0.3);
        }
        .btn-submit:active { transform: translateY(0); filter: brightness(0.95); }
 
        /* ── CARD FOOTER ── */
        .card-foot {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid rgba(212,160,23,0.1);
        }
        .pip {
            width: 4px; height: 4px;
            border-radius: 50%;
            background: rgba(212,160,23,0.22);
        }
        .foot-text {
            font-size: 0.62rem;
            letter-spacing: 2px;
            color: rgba(245,223,160,0.22);
            text-transform: uppercase;
        }
    </style>
</head>
<body>
 
    <!-- Header (mirrors Homepage.php) -->
    <header>
        <div class="header-left">
            <img src="logo.png" alt="Aurum Logo" class="header-logo"
                 onerror="this.style.display='none'">
            <h1>AURUM</h1>
        </div>
        <div class="header-right">
            <div class="header-divider"></div>
            <div class="header-balance">
                <span class="balance-label">Your Balance</span>
                <span class="balance-amount" id="headerBalance">₱<?php echo htmlspecialchars($balance_display); ?></span>
            </div>
            <a href="Homepage.php" class="btn-back">Home Page</a>
        </div>
    </header>
 
    <main>
        <div class="topup-card">
            <div class="corners"></div>
 
            <!-- Card title -->
            <div class="card-title-row">
                <span class="suit">&#9824;</span>
                <div class="card-title">TOP UP</div>
                <span class="suit">&#9827;</span>
            </div>
            <p class="card-tagline">Deposit &nbsp;&middot;&nbsp; Withdraw</p>
            <div class="gold-rule"></div>
 
            <!-- Balance display -->
            <div class="balance-display">
                <span class="bd-label">Current Balance</span>
                <div class="bd-pip"></div>
                <span class="bd-amount">₱<?php echo htmlspecialchars($balance_display); ?></span>
            </div>
 
            <!-- Message -->
            <?php if ($message): ?>
                <div class="msg msg-<?php echo htmlspecialchars($messageType); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
 
            <!-- Form -->
            <form method="post" id="topupForm">
 
                <div class="field">
                    <label for="amount">Amount (minimum ₱100)</label>
                    <input
                        type="number"
                        id="amount"
                        name="amount"
                        min="100"
                        step="100"
                        placeholder="Enter amount"
                        required>
                    <!-- Quick amount buttons -->
                    <div class="quick-amounts">
                        <button type="button" class="quick-btn" onclick="setAmount(100)">₱100</button>
                        <button type="button" class="quick-btn" onclick="setAmount(500)">₱500</button>
                        <button type="button" class="quick-btn" onclick="setAmount(1000)">₱1,000</button>
                        <button type="button" class="quick-btn" onclick="setAmount(5000)">₱5,000</button>
                        <button type="button" class="quick-btn" onclick="setAmount(10000)">₱10,000</button>
                    </div>
                </div>
 
                <div class="field">
                    <label>Action</label>
                    <div class="action-toggle">
                        <div class="action-option deposit">
                            <input type="radio" name="action" id="deposit" value="deposit" required
                                <?php echo (($_POST['action'] ?? '') === 'deposit') ? 'checked' : ''; ?>>
                            <label for="deposit">
                                <span class="icon">&#43;</span> Deposit
                            </label>
                        </div>
                        <div class="action-option withdraw">
                            <input type="radio" name="action" id="withdraw" value="withdraw"
                                <?php echo (($_POST['action'] ?? '') === 'withdraw') ? 'checked' : ''; ?>>
                            <label for="withdraw">
                                <span class="icon">&#8722;</span> Withdraw
                            </label>
                        </div>
                    </div>
                </div>
 
                <button type="submit" class="btn-submit">Confirm Transaction</button>
            </form>
 
            <div class="card-foot">
                <div class="pip"></div>
                <span class="foot-text">Minimum ₱100 per transaction</span>
                <div class="pip"></div>
            </div>
        </div>
    </main>
 
    <script>
        function setAmount(val) {
            document.getElementById('amount').value = val;
        }
    </script>
 
</body>
</html>
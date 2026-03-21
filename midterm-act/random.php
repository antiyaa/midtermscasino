<?php
session_start();

if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 99999;
}
if (!isset($_SESSION['current_bet'])) {
    $_SESSION['current_bet'] = 10;
}

$message = '';
$selected_option = null;
$random_number = null;
$win = false;
$payout_multiplier = 0;

$display_number = "?";
$result_status = "";
$result_amount = "";
$result_class = "";
$confirm_message = "";
$confirm_error = "";

// --- Handle bet adjustment (preserves selected pattern) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust'])) {
    // Store the selected pattern if present (so it doesn't reset)
    if (isset($_POST['option']) && in_array($_POST['option'], ['odd', 'even', 'lucky7', '1-15', '16-30'])) {
        $_SESSION['selected_option'] = $_POST['option'];
    }

    $adjust = $_POST['adjust'];
    $current_bet = $_SESSION['current_bet'];
    $balance = $_SESSION['balance'];

    switch ($adjust) {
        case '-100':
            $new_bet = $current_bet - 100;
            break;
        case '-10':
            $new_bet = $current_bet - 10;
            break;
        case 'allin':
            $new_bet = $balance;
            break;
        case '+10':
            $new_bet = $current_bet + 10;
            break;
        case '+100':
            $new_bet = $current_bet + 100;
            break;
        default:
            $new_bet = $current_bet;
    }

    // Clamp bet to allowed range
    $new_bet = max(10, min(5000, $new_bet));
    $_SESSION['current_bet'] = $new_bet;
    // Clearing confirmed bet because the amount changed
    unset($_SESSION['confirmed_bet']);
}

// --- Handle bet confirmation ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_bet'])) {
    // Store the selected pattern (if any)
    if (isset($_POST['option']) && in_array($_POST['option'], ['odd', 'even', 'lucky7', '1-15', '16-30'])) {
        $_SESSION['selected_option'] = $_POST['option'];
    }

    $bet = $_SESSION['current_bet'];

    if ($bet < 10 || $bet > 5000) {
        $confirm_error = "Bet must be between 10 and 5000.";
    } elseif ($bet > $_SESSION['balance']) {
        $confirm_error = "Insufficient balance.";
    } else {
        $_SESSION['confirmed_bet'] = $bet;
        $confirm_message = "Bet confirmed: $bet. You can now select a pattern and press START.";
    }
}

// --- Handle game play ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['play'])) {
    // Store pattern from selection
    if (isset($_POST['option']) && in_array($_POST['option'], ['odd', 'even', 'lucky7', '1-15', '16-30'])) {
        $_SESSION['selected_option'] = $_POST['option'];
    }

    if (!isset($_SESSION['confirmed_bet'])) {
        $message = "Please confirm your bet amount first.";
    } else {
        $bet = $_SESSION['confirmed_bet'];
        $selected_option = $_SESSION['selected_option'] ?? '';

        if (!in_array($selected_option, ['odd', 'even', 'lucky7', '1-15', '16-30'])) {
            $message = "Please select a valid betting pattern.";
        } else {
            $random_number = rand(1, 30);
            $display_number = $random_number;

            switch ($selected_option) {
                case 'odd': $win = ($random_number % 2 == 1); $payout_multiplier = 2; break;
                case 'even': $win = ($random_number % 2 == 0); $payout_multiplier = 2; break;
                case 'lucky7': $win = ($random_number == 7); $payout_multiplier = 15; break;
                case '1-15': $win = ($random_number >= 1 && $random_number <= 15); $payout_multiplier = 2; break;
                case '16-30': $win = ($random_number >= 16 && $random_number <= 30); $payout_multiplier = 2; break;
            }

            $_SESSION['balance'] -= $bet;

            if ($win) {
                $winnings = $bet * $payout_multiplier;
                $_SESSION['balance'] += $winnings;
                $profit = $winnings - $bet;
                $result_status = "You Win";
                $result_amount = "+$profit MILLION FUCKING DOLLARS";
                $result_class = "win";
            } else {
                $result_status = "You Lose";
                $result_amount = "-$bet MILLION FUCKING DOLLARS";
                $result_class = "lose";
            }

            unset($_SESSION['confirmed_bet']);
        }
    }
}

// Prepare values for display
$confirmed_bet = $_SESSION['confirmed_bet'] ?? null;
$has_confirmed_bet = ($confirmed_bet !== null);
$selected_option = $_SESSION['selected_option'] ?? null;
$current_bet = $_SESSION['current_bet'];

// Build selection text based on stored pattern
$selection_messages = [
    'odd' => 'PINILI MO ANG ODD NUMBERS BITCH',
    'even' => 'PINILI MO ANG EVEN NUMBERS BITCH',
    'lucky7' => 'PINILI MO ANG 7 BITCH',
    '1-15' => 'PINILI MO ANG 1-15 BITCH',
    '16-30' => 'PINILI MO ANG 16-30 BITCH',
];
$selection_text = $selected_option ? ($selection_messages[$selected_option] ?? '') : '';

// Get username from session (fallback)
$username = htmlspecialchars($_SESSION['username'] ?? 'KpopIdol');
$balance_display = number_format($_SESSION['balance']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Casino Ni Lady - Random Number</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Arial Narrow', Arial, sans-serif; }
        
        body { background-color: #fff; min-height: 100vh; display: flex; flex-direction: column; align-items: center; }

        /* --- Header (identical height to homepage) --- */
        header { 
            width: 100%;
            border-top: 5px solid #c5a059; 
            background-color: #121826; 
            border-bottom: 5px solid #c5a059; 
            padding: 15px 0;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-left, .header-right {
            width: 150px;
            padding: 0 20px;
            color: white;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            line-height: 3rem; /* matches h1 line height for consistent height */
        }
        .header-left {
            justify-content: flex-start;
        }
        .header-right {
            justify-content: flex-end;
        }
        .user-icon {
            background-color: #7b818a;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .balance-amount {
            font-weight: bold;
        }
        header h1 { 
            color: #d4af37; 
            font-family: 'Times New Roman', Times, serif; 
            font-size: 3rem;
            letter-spacing: 2px; 
            font-weight: normal; 
            margin: 0;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            line-height: 3rem; /* ensures the title's height matches side containers */
        }

        /* --- Main Layout --- */
        .container { width: 95%; max-width: 1200px; margin-top: 20px; }
        
        .back-btn { 
            border: 2px solid black; 
            padding: 5px 25px; 
            background: white; 
            cursor: pointer; 
            font-size: 1.2rem; 
            margin-bottom: 20px;
            display: inline-block;
            text-decoration: none;
            color: black;
        }

        form { display: flex; width: 100%; gap: 40px; }

        .left-panel, .right-panel { flex: 1; display: flex; flex-direction: column; }

        /* --- Betting Section --- */
        .box { border: 2px solid black; border-radius: 8px; padding: 30px; position: relative; margin-bottom: 20px; }
        .box-title { font-family: Georgia, serif; font-size: 2.2rem; text-align: center; margin-bottom: 20px; }
        .help-icon { position: absolute; top: 15px; right: 15px; border: 1px solid black; border-radius: 50%; width: 25px; height: 25px; display: flex; justify-content: center; align-items: center; }

        .pattern-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; }
        .pattern-radio { display: none; }
        .pattern-label { 
            border: 2px solid black; border-radius: 10px; padding: 10px 25px; 
            font-size: 2rem; font-family: Georgia, serif; font-weight: bold; 
            cursor: pointer; transition: 0.2s; min-width: 120px; text-align: center;
        }
        .pattern-radio:checked + .pattern-label { border-color: #d4af37; color: #d4af37; }

        .selection-text { text-align: center; margin-top: 20px; font-family: Georgia, serif; font-size: 1rem; text-transform: uppercase; min-height: 1.2rem; }

        /* --- Bet Controls --- */
        .bet-label { font-weight: bold; margin-bottom: 10px; display: block; }
        .bet-info-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .balance-display { display: flex; align-items: center; gap: 10px; font-weight: bold; font-size: 1.1rem; }
        .bet-input { border: 2px solid black; padding: 5px; width: 120px; text-align: center; font-weight: bold; font-size: 1.1rem; background: #f9f9f9; }
        
        /* Adjustment buttons */
        .adjust-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .adjust-btn {
            border: 2px solid black;
            background: white;
            padding: 8px 15px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 5px;
            font-size: 1rem;
            transition: 0.2s;
        }
        .adjust-btn:hover {
            background: #f0f0f0;
        }

        .confirm-message { margin-top: 10px; font-size: 0.9rem; color: #2e7d32; text-align: center; }
        .error-message { color: red; text-align: center; margin-top: 10px; }

        /* --- Confirm Button --- */
        .btn-confirm { 
            width: 100%;
            border: 2px solid black; 
            background: white; 
            padding: 12px; 
            font-family: Georgia, serif; 
            font-size: 1.5rem; 
            font-weight: bold; 
            cursor: pointer; 
            border-radius: 5px; 
            transition: 0.2s;
            margin-top: 15px;
        }
        .btn-confirm:hover { background: #f9f9f9; }

        /* --- Right Panel: Results & Start Button --- */
        .result-box { border: 2px solid black; flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; text-align: center; margin-bottom: 20px; }
        .res-header { font-family: Georgia, serif; font-size: 3.5rem; color: #d4af37; margin-bottom: 20px; }
        .circle { 
            width: 220px; height: 220px; border: 6px solid #d4af37; border-radius: 50%; 
            display: flex; justify-content: center; align-items: center; 
            font-size: 6rem; font-family: Georgia, serif; margin-bottom: 20px;
        }
        .status { font-family: Georgia, serif; font-size: 3rem; margin-bottom: 10px; }
        .amount { font-family: Georgia, serif; font-size: 1.5rem; font-weight: bold; letter-spacing: 1px; }
        .win { color: #2e7d32; }
        .lose { color: red; }
        
        .btn-start { 
            width: 100%; 
            border: 2px solid black; 
            background: #f0f0f0; 
            padding: 15px; 
            font-family: Georgia, serif; 
            font-size: 2.5rem; 
            font-weight: bold; 
            cursor: pointer; 
            border-radius: 5px; 
            transition: 0.2s;
        }
        .btn-start.enabled {
            background: white;
            cursor: pointer;
        }
        .btn-start.enabled:hover { background: #f9f9f9; }
        .btn-start.disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.7;
        }
    </style>
</head>
<body>

    <header>
        <div class="header-left">
            <div class="user-icon">👤</div>
            <span><?php echo $username; ?></span>
        </div>
        <h1>CASINO NI LADY</h1>
        <div class="header-right">
            <span>💰</span>
            <span class="balance-amount">₱<?php echo $balance_display; ?></span>
        </div>
    </header>

    <div class="container">
        <a href="Homepage.php" class="back-btn">&lt;-</a>

        <form method="post" id="gameForm">
            <div class="left-panel">
                <div class="box">
                    <div class="help-icon">?</div>
                    <h2 class="box-title">Choose Your Betting Pattern</h2>
                    
                    <div class="pattern-grid">
                        <input type="radio" name="option" id="o1" value="odd" class="pattern-radio" <?php if($selected_option=='odd') echo 'checked'; ?>>
                        <label for="o1" class="pattern-label">ODD</label>
                        
                        <input type="radio" name="option" id="o2" value="even" class="pattern-radio" <?php if($selected_option=='even') echo 'checked'; ?>>
                        <label for="o2" class="pattern-label">EVEN</label>

                        <input type="radio" name="option" id="o3" value="lucky7" class="pattern-radio" <?php if($selected_option=='lucky7') echo 'checked'; ?>>
                        <label for="o3" class="pattern-label" style="font-size:3rem; padding: 0 30px;">7</label>

                        <input type="radio" name="option" id="o4" value="1-15" class="pattern-radio" <?php if($selected_option=='1-15') echo 'checked'; ?>>
                        <label for="o4" class="pattern-label">1-15</label>

                        <input type="radio" name="option" id="o5" value="16-30" class="pattern-radio" <?php if($selected_option=='16-30') echo 'checked'; ?>>
                        <label for="o5" class="pattern-label">16-30</label>
                    </div>

                    <div class="selection-text"><?php echo htmlspecialchars($selection_text); ?></div>
                </div>

                <span class="bet-label">YOUR BET</span>
                <div class="box">
                    <div class="bet-info-row">
                        <div class="balance-display">
                            <img src="chips_icon.png" width="30" alt=""> Balance: <?php echo $_SESSION['balance']; ?>
                        </div>
                        <input type="number" name="bet_display" class="bet-input" value="<?php echo $current_bet; ?>" readonly>
                    </div>

                    <!-- 5 adjustment buttons -->
                    <div class="adjust-buttons">
                        <button type="submit" name="adjust" value="-100" class="adjust-btn">-100</button>
                        <button type="submit" name="adjust" value="-10" class="adjust-btn">-10</button>
                        <button type="submit" name="adjust" value="allin" class="adjust-btn">ALL IN</button>
                        <button type="submit" name="adjust" value="+10" class="adjust-btn">+10</button>
                        <button type="submit" name="adjust" value="+100" class="adjust-btn">+100</button>
                    </div>

                    <div style="display:flex; justify-content:space-between; font-weight:bold; font-size:0.8rem; margin-top:10px;">
                        <span>MIN: 10</span>
                        <span>MAX: 5000</span>
                    </div>
                    
                    <!-- Messages -->
                    <?php if ($confirm_message): ?>
                        <div class="confirm-message"><?php echo $confirm_message; ?></div>
                    <?php endif; ?>
                    <?php if ($confirm_error): ?>
                        <div class="error-message"><?php echo $confirm_error; ?></div>
                    <?php endif; ?>
                    <?php if ($message): ?>
                        <div class="error-message"><?php echo $message; ?></div>
                    <?php endif; ?>
                    
                    <button type="submit" name="confirm_bet" class="btn-confirm">CONFIRM BET</button>
                </div>
            </div>

            <div class="right-panel">
                <div class="result-box">
                    <h2 class="res-header">RESULT</h2>
                    <div class="circle"><?php echo $display_number; ?></div>
                    <div class="status <?php echo $result_class; ?>"><?php echo $result_status; ?></div>
                    <div class="amount <?php echo $result_class; ?>"><?php echo $result_amount; ?></div>
                </div>
                <button type="submit" name="play" class="btn-start <?php echo ($has_confirmed_bet && $selected_option) ? 'enabled' : 'disabled'; ?>" <?php echo ($has_confirmed_bet && $selected_option) ? '' : 'disabled'; ?>>START</button>
            </div>
        </form>
    </div>

</body>
</html>
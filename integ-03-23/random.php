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
        $confirm_message = "Bet confirmed: $bet. PRESS START TO START THE GAME.";
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
                case 'lucky7': $win = ($random_number % 7 == 0); $payout_multiplier = 3; break;  
                case '1-15': $win = ($random_number >= 1 && $random_number <= 15); $payout_multiplier = 2; break;
                case '16-30': $win = ($random_number >= 16 && $random_number <= 30); $payout_multiplier = 2; break;
            }

            $_SESSION['balance'] -= $bet;

            if ($win) {
                $winnings = $bet * $payout_multiplier;
                $_SESSION['balance'] += $winnings;
                $profit = $winnings - $bet;
                $result_status = "You Win";
                $result_amount = "+$profit";
                $result_class = "win";
            } else {
                $result_status = "You Lose";
                $result_amount = "-$bet";
                $result_class = "lose";
            }

            unset($_SESSION['confirmed_bet']);
        }
    }
}

$confirmed_bet = $_SESSION['confirmed_bet'] ?? null;
$has_confirmed_bet = ($confirmed_bet !== null);
$selected_option = $_SESSION['selected_option'] ?? null;
$current_bet = $_SESSION['current_bet'];

$selection_messages = [
    'odd' => 'PINILI MO ODD NUMBERS',
    'even' => 'PINILI MO ANG EVEN NUMBERS',
    'lucky7' => 'PINILI MO ANG LUCKY 7 (MULTIPLES OF 7)',
    '1-15' => 'PINILI MO ANG LOW NUMBERS (1-15)',
    '16-30' => 'PINILI MO ANG HIGH NUMBERS (16-30)',
];
$selection_text = $selected_option ? ($selection_messages[$selected_option] ?? '') : '';

$username = htmlspecialchars($_SESSION['username'] ?? 'KpopIdol');
$balance_display = number_format($_SESSION['balance']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Casino Ni Lady - Random Number</title>
    <link rel="stylesheet" href="rng.css">
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
        <a href="Homepage.php" class="back-btn">Back to Homepage</a>

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
                        <input type="number" name="bet_display" class="bet-input" value="<?php echo $current_bet; ?>">
                    </div>

                    <div class="adjust-buttons">
                        <button type="submit" name="adjust" value="-100" class="adjust-btn">-100</button>
                        <button type="submit" name="adjust" value="-10" class="adjust-btn">-10</button>
                        <button type="submit" name="adjust" value="allin" class="adjust-btn">MAX</button>
                        <button type="submit" name="adjust" value="+10" class="adjust-btn">+10</button>
                        <button type="submit" name="adjust" value="+100" class="adjust-btn">+100</button>
                    </div>

                    <div style="display:flex; justify-content:space-between; font-weight:bold; font-size:0.8rem; margin-top:10px;">
                        <span>MIN: 10</span>
                        <span>MAX: 5000</span>
                    </div>

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
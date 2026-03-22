<?php
session_start();

if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 99999;
}
if (!isset($_SESSION['current_bet'])) {
    $_SESSION['current_bet'] = 10;
}

$colors = ['Red', 'Green', 'Blue', 'Yellow', 'Purple', 'Orange'];
$color_classes = [
    'Red'    => '#b91c1c',
    'Green'  => '#166534',
    'Blue'   => '#1d4ed8',
    'Yellow' => '#ca8a04',
    'Purple' => '#6b21a8',
    'Orange' => '#c2410f'
];

if (!isset($_SESSION['color_bet'])) $_SESSION['color_bet'] = null;
if (!isset($_SESSION['color_active'])) $_SESSION['color_active'] = false;
if (!isset($_SESSION['color_chosen'])) $_SESSION['color_chosen'] = null;

$message = '';
$die1 = null;
$die2 = null;
$match_count = 0;
$show_win_banner = false;
$show_loss_banner = false;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'exit') {
    unset($_SESSION['color_bet'], $_SESSION['color_chosen']);
    $_SESSION['color_active'] = false;
    header('Location: Homepage.php');
    exit;
}

if ($action === 'stop') {
    unset($_SESSION['color_bet'], $_SESSION['color_chosen']);
    $_SESSION['color_active'] = false;
    // Reinitialize to avoid undefined index warnings
    $_SESSION['color_chosen'] = null;
    $_SESSION['color_bet'] = null;
    $message = "Game stopped. You can place a new bet.";
}

if (isset($_POST['adjust'])) {
    if (isset($_POST['color']) && in_array($_POST['color'], $colors)) {
        $_SESSION['color_chosen'] = $_POST['color'];
    }
    $adjust = $_POST['adjust'];
    $current_bet = $_SESSION['current_bet'];
    switch ($adjust) {
        case '-100': $new_bet = $current_bet - 100; break;
        case '-10':  $new_bet = $current_bet - 10; break;
        case '+10':  $new_bet = $current_bet + 10; break;
        case '+100': $new_bet = $current_bet + 100; break;
        default:     $new_bet = $current_bet;
    }
    $new_bet = max(10, min(5000, $new_bet));
    $_SESSION['current_bet'] = $new_bet;
    if ($_SESSION['color_active']) {
        unset($_SESSION['color_bet'], $_SESSION['color_chosen']);
        $_SESSION['color_active'] = false;
        $_SESSION['color_chosen'] = null;
        $_SESSION['color_bet'] = null;
        $message = "Bet amount changed. Please confirm your new bet.";
    }
}

if ($action === 'confirm_bet') {
    $bet = (int)($_POST['bet'] ?? $_SESSION['current_bet']);
    $chosen_color = $_POST['color'] ?? '';
    if ($bet <= 0) {
        $message = "Bet must be positive.";
    } elseif ($bet > $_SESSION['balance']) {
        $message = "Insufficient balance. Your balance is {$_SESSION['balance']}.";
    } elseif (!in_array($chosen_color, $colors)) {
        $message = "Please select a valid color.";
    } else {
        $_SESSION['color_bet'] = $bet;
        $_SESSION['color_chosen'] = $chosen_color;
        $_SESSION['color_active'] = true;
        $message = "Bet confirmed: $bet credits on $chosen_color. Click ROLL to play.";
    }
}

if ($action === 'roll' && $_SESSION['color_active']) {
    $bet = $_SESSION['color_bet'];
    $chosen = $_SESSION['color_chosen'];
    if ($bet > $_SESSION['balance']) {
        $message = "Insufficient balance to continue. Please stop and try again with a lower bet.";
    } else {
        $die1 = $colors[array_rand($colors)];
        $die2 = $colors[array_rand($colors)];
        $match_count = 0;
        if ($die1 === $chosen) $match_count++;
        if ($die2 === $chosen) $match_count++;
        $_SESSION['balance'] -= $bet;
        if ($match_count == 0) {
            $message = "You rolled $die1 and $die2. No match. You lose $bet credits. New balance: {$_SESSION['balance']}.";
            $show_loss_banner = true;
        } elseif ($match_count == 1) {
            $winnings = $bet * 2;
            $_SESSION['balance'] += $winnings;
            $message = "You rolled $die1 and $die2. One match! You win $winnings credits (profit " . ($winnings - $bet) . "). New balance: {$_SESSION['balance']}.";
            $show_win_banner = true;
        } else {
            $winnings = $bet * 3;
            $_SESSION['balance'] += $winnings;
            $message = "You rolled $die1 and $die2. Both match! You win $winnings credits (profit " . ($winnings - $bet) . "). New balance: {$_SESSION['balance']}.";
            $show_win_banner = true;
        }
    }
}

$current_bet = $_SESSION['current_bet'];
$color_active = $_SESSION['color_active'];
// Use null coalescing to avoid undefined index warning
$chosen_color = $_SESSION['color_chosen'] ?? null;
$balance = $_SESSION['balance'];
$username = htmlspecialchars($_SESSION['username'] ?? 'KpopIdol');
$balance_display = number_format($balance);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Casino Ni Lady - Color Dice</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #0a0c15;
            color: #eee;
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* --- Header (matching other games) --- */
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
            line-height: 3rem;
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
            line-height: 3rem;
        }

        /* Main layout */
        .container {
            flex: 1;
            display: flex;
            gap: 25px;
            padding: 25px;
        }

        /* Left column */
        .left {
            flex: 1;
            background: rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #f5b04240;
        }

        .left h2 {
            color: #f5b042;
            margin-bottom: 15px;
        }

        /* Color radio buttons */
        .color-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin: 20px 0;
        }

        .color-option {
            display: block;
        }

        .color-option input {
            display: none;
        }

        .color-label {
            display: block;
            padding: 10px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.2s;
        }

        /* Disabled style for radio labels when game is active */
        .color-option input:disabled + .color-label {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .color-option input:checked + .color-label {
            box-shadow: 0 0 0 2px white, 0 0 0 4px #f5b042;
            transform: scale(1.02);
        }

        /* Bet controls */
        .bet-panel {
            background: #0f111a;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }

        .bet-amount {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .bet-amount span {
            font-weight: bold;
        }

        .bet-input {
            background: #1f2937;
            color: white;
            border: 1px solid #f5b042;
            padding: 5px 10px;
            border-radius: 5px;
            width: 100px;
            text-align: center;
            font-size: 1rem;
        }

        .bet-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin: 15px 0;
        }

        .bet-buttons button {
            background: #1e293b;
            border: 1px solid #f5b042;
            color: #f5b042;
            padding: 5px 12px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }

        .bet-buttons button:hover {
            background: #2d3a4e;
        }

        .minmax {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #aaa;
            margin-top: 8px;
        }

        .confirm-btn {
            width: 100%;
            background: #f5b042;
            color: #111;
            border: none;
            padding: 10px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 15px;
        }

        .confirm-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .confirm-btn:hover:not(:disabled) {
            background: #f5a52e;
        }

        /* Right column */
        .right {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* --- Enlarged dice area with result banner (updated) --- */
        .dice-area {
            flex: 1;
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid #f5b04240;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Banner styling copied from first program */
        .banner {
            text-align: center;
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 15px;
            animation: pulse 0.5s ease-in-out;
        }
        .win-banner {
            color: #d4af37;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .loss-banner {
            color: #dc3545;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        @keyframes pulse {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .dice-row {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .die {
            width: 180px;
            height: 200px;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            background: #1e293b;
            border: 2px solid #f5b042;
            font-size: 1.5rem;
        }

        .die-color {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            margin: 15px auto;
        }

        /* Responsive adjustments */
        @media (max-width: 800px) {
            .die {
                width: 140px;
                height: 160px;
            }
            .die-color {
                width: 70px;
                height: 70px;
            }
            .dice-row {
                gap: 20px;
            }
            .banner {
                font-size: 2rem;
            }
        }

        @media (max-width: 600px) {
            .die {
                width: 100px;
                height: 120px;
                font-size: 1rem;
            }
            .die-color {
                width: 50px;
                height: 50px;
            }
            .banner {
                font-size: 1.5rem;
            }
        }

        .message-box {
            background: #111;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            color: #ffd966;
            border-left: 4px solid #f5b042;
        }

        .action-buttons {  j
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .roll-btn {
            background: linear-gradient(to right, #f5b042, #f5a52e);
            border: none;
            padding: 12px 35px;
            font-size: 1.2rem;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
        }

        .roll-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .secondary-btn {
            background: #2d3748;
            border: 1px solid #f5b042;
            color: #f5b042;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
        }

        .secondary-btn:hover {
            background: #3b4559;
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
    <!-- Left Column -->
    <div class="left">
        <h2>COLOR DICE GAME</h2>
        <p>Pick a color:</p>
        <form method="post" id="gameForm">
            <div class="color-grid">
                <?php foreach ($colors as $color): ?>
                    <label class="color-option">
                        <input type="radio" name="color" value="<?php echo $color; ?>"
                               <?php echo ($chosen_color == $color) ? 'checked' : ''; ?>
                               <?php echo $color_active ? 'disabled' : ''; ?>>
                        <div class="color-label" style="background: <?php echo $color_classes[$color]; ?>;">
                            <?php echo $color; ?>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="bet-panel">
                <div class="bet-amount">
                    <span>Your Bet:</span>
                    <input type="text" name="bet_display" class="bet-input" value="<?php echo $current_bet; ?>"
                           <?php echo $color_active ? 'readonly' : ''; ?>>
                </div>

                <div class="bet-buttons">
                    <button type="submit" name="adjust" value="-100" <?php echo $color_active ? 'disabled' : ''; ?>>-100</button>
                    <button type="submit" name="adjust" value="-10" <?php echo $color_active ? 'disabled' : ''; ?>>-10</button>
                    <button type="submit" name="adjust" value="+10" <?php echo $color_active ? 'disabled' : ''; ?>>+10</button>
                    <button type="submit" name="adjust" value="+100" <?php echo $color_active ? 'disabled' : ''; ?>>+100</button>
                </div>
                <div class="minmax">
                    <span>MIN: 10</span>
                    <span>MAX: 5000</span>
                </div>
            </div>

            <button type="submit" name="action" value="confirm_bet" class="confirm-btn"
                    <?php echo $color_active ? 'disabled' : ''; ?>>CONFIRM BET</button>
        </form>
    </div>

    <!-- Right Column -->
    <div class="right">
        <div class="dice-area">
            <h3>DICE RESULTS</h3>
            <!-- New banner structure -->
            <?php if ($show_win_banner): ?>
                <div class="banner win-banner">YOU WIN</div>
            <?php elseif ($show_loss_banner): ?>
                <div class="banner loss-banner">YOU LOSE</div>
            <?php endif; ?>
            <div class="dice-row">
                <div class="die">
                    <?php if ($die1): ?>
                        <div><?php echo $die1; ?></div>
                        <div class="die-color" style="background: <?php echo $color_classes[$die1]; ?>;"></div>
                    <?php else: ?>
                        <div>?</div>
                        <div class="die-color" style="background: #333;"></div>
                    <?php endif; ?>
                </div>
                <div class="die">
                    <?php if ($die2): ?>
                        <div><?php echo $die2; ?></div>
                        <div class="die-color" style="background: <?php echo $color_classes[$die2]; ?>;"></div>
                    <?php else: ?>
                        <div>?</div>
                        <div class="die-color" style="background: #333;"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message-box"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="action-buttons">
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="roll">
                <button type="submit" class="roll-btn" <?php echo $color_active ? '' : 'disabled'; ?>>ROLL</button>
            </form>
            <?php if ($color_active): ?>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="stop">
                    <button type="submit" class="secondary-btn">STOP</button>
                </form>
            <?php endif; ?>
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="exit">
                <button type="submit" class="secondary-btn">EXIT</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
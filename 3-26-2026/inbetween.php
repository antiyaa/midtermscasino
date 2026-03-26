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

if (!isset($_SESSION['color_bet']))    $_SESSION['color_bet']    = null;
if (!isset($_SESSION['color_active'])) $_SESSION['color_active'] = false;
if (!isset($_SESSION['color_chosen'])) $_SESSION['color_chosen'] = null;

$message         = '';
$die1            = null;
$die2            = null;
$match_count     = 0;
$show_win_banner  = false;
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
    $_SESSION['color_chosen'] = null;
    $_SESSION['color_bet']    = null;
    $message = "Game stopped. You can place a new bet.";
}

if (isset($_POST['adjust'])) {
    if (isset($_POST['color']) && in_array($_POST['color'], $colors)) {
        $_SESSION['color_chosen'] = $_POST['color'];
    }
    $adjust      = $_POST['adjust'];
    $current_bet = $_SESSION['current_bet'];
    switch ($adjust) {
        case '-100': $new_bet = $current_bet - 100; break;
        case '-10':  $new_bet = $current_bet - 10;  break;
        case '+10':  $new_bet = $current_bet + 10;  break;
        case '+100': $new_bet = $current_bet + 100; break;
        default:     $new_bet = $current_bet;
    }
    $new_bet = max(10, min(5000, $new_bet));
    $_SESSION['current_bet'] = $new_bet;
    if ($_SESSION['color_active']) {
        unset($_SESSION['color_bet'], $_SESSION['color_chosen']);
        $_SESSION['color_active'] = false;
        $_SESSION['color_chosen'] = null;
        $_SESSION['color_bet']    = null;
        $message = "Bet amount changed. Please confirm your new bet.";
    }
}

if ($action === 'confirm_bet') {
    $bet          = (int)($_POST['bet'] ?? $_SESSION['current_bet']);
    $chosen_color = $_POST['color'] ?? '';
    if ($bet <= 0) {
        $message = "Bet must be positive.";
    } elseif ($bet > $_SESSION['balance']) {
        $message = "Insufficient balance. Your balance is {$_SESSION['balance']}.";
    } elseif (!in_array($chosen_color, $colors)) {
        $message = "Please select a valid color.";
    } else {
        $_SESSION['color_bet']    = $bet;
        $_SESSION['color_chosen'] = $chosen_color;
        $_SESSION['color_active'] = true;
        $message = "Bet confirmed: ₱$bet on $chosen_color. Click ROLL to play.";
    }
}

if ($action === 'roll' && $_SESSION['color_active']) {
    $bet    = $_SESSION['color_bet'];
    $chosen = $_SESSION['color_chosen'];
    if ($bet > $_SESSION['balance']) {
        $message = "Insufficient balance to continue. Please stop and try again with a lower bet.";
    } else {
        $die1        = $colors[array_rand($colors)];
        $die2        = $colors[array_rand($colors)];
        $match_count = 0;
        if ($die1 === $chosen) $match_count++;
        if ($die2 === $chosen) $match_count++;
        $_SESSION['balance'] -= $bet;
        if ($match_count == 0) {
            $message          = "You rolled $die1 and $die2. No match. You lose ₱$bet. New balance: ₱" . number_format($_SESSION['balance']) . ".";
            $show_loss_banner = true;
        } elseif ($match_count == 1) {
            $winnings             = $bet * 2;
            $_SESSION['balance'] += $winnings;
            $profit               = $winnings - $bet;
            $message              = "You rolled $die1 and $die2. One match! You win ₱$winnings (profit ₱$profit). New balance: ₱" . number_format($_SESSION['balance']) . ".";
            $show_win_banner      = true;
        } else {
            $winnings             = $bet * 3;
            $_SESSION['balance'] += $winnings;
            $profit               = $winnings - $bet;
            $message              = "You rolled $die1 and $die2. Both match! You win ₱$winnings (profit ₱$profit). New balance: ₱" . number_format($_SESSION['balance']) . ".";
            $show_win_banner      = true;
        }
    }
}

$current_bet     = $_SESSION['current_bet'];
$color_active    = $_SESSION['color_active'];
$chosen_color    = $_SESSION['color_chosen'] ?? null;
$balance         = $_SESSION['balance'];
$balance_display = number_format($balance);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Casino Ni Lady – Color Game</title>
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
            color: var(--warm-champagne);
            font-family: var(--sans);
        }

        /* --- HEADER (standard) --- */
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
                background: linear-gradient(45deg, #C0152A, #e0294a);
                color: var(--warm-champagne);
                border: none;
                padding: 11px 22px;
                font-weight: bold;
                font-size: 0.82rem;
                letter-spacing: 1.5px;
                cursor: pointer;
                border-radius: 8px;
                transition: 0.3s;
                white-space: nowrap;
                font-family: var(--sans);
        }

        .btn-deposit:hover {
            box-shadow: 0 0 18px rgba(192,21,42,0.65);
            transform: scale(1.04);
        }

        /* --- MAIN CONTAINER --- */
        .container {
            flex: 1;
            display: flex;
            gap: 22px;
            padding: 24px;
            min-height: 0;
        }

        .left {
            width: 340px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .panel {
            background: #1C0A08;
            border: 1px solid var(--gold-dim);
            border-radius: 12px;
            padding: 22px;
            position: relative;
            transition: box-shadow 0.2s;
        }
        .panel:hover { box-shadow: 0 0 18px var(--gold-glow); }

        .panel-title {
            font-family: 'Times New Roman', serif;
            font-size: 1.3rem;
            color: var(--burnished-gold);
            letter-spacing: 3px;
            text-align: center;
            margin-bottom: 6px;
        }
        .panel-title::after {
            content: '';
            display: block;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--burnished-gold), transparent);
            margin: 8px auto 16px;
            width: 80%;
        }

        .color-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .color-option input { display: none; }

        .color-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 8px;
            border-radius: 10px;
            cursor: pointer;
            font-family: var(--sans);
            font-size: 0.85rem;
            font-weight: bold;
            letter-spacing: 1px;
            border: 2px solid transparent;
            transition: 0.2s;
            position: relative;
        }
        .color-label:hover { border-color: rgba(212,160,23,0.5); transform: scale(1.03); }

        .color-dot {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.2);
            flex-shrink: 0;
        }

        .color-option input:checked + .color-label {
            border-color: var(--burnished-gold);
            box-shadow: 0 0 14px var(--gold-glow), inset 0 0 8px rgba(212,160,23,0.08);
            transform: scale(1.05);
        }
        .color-option input:disabled + .color-label {
            opacity: 0.45;
            cursor: not-allowed;
            transform: none;
        }

        .bet-label {
            display: block;
            font-family: var(--sans);
            font-size: 0.7rem;
            letter-spacing: 3px;
            color: var(--text-muted);
            margin-bottom: 10px;
        }
        .bet-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }
        .bet-balance {
            font-family: var(--sans);
            font-size: 0.88rem;
            color: var(--warm-champagne);
        }
        .bet-balance strong { color: var(--burnished-gold); }
        .bet-input {
            background: #0E0500;
            border: 1px solid var(--burnished-gold);
            border-radius: 7px;
            color: var(--burnished-gold);
            font-size: 1.1rem;
            font-weight: bold;
            font-family: var(--sans);
            width: 110px;
            text-align: center;
            padding: 6px 8px;
            outline: none;
        }
        .bet-input:focus { box-shadow: 0 0 0 2px var(--gold-glow); }

        .adjust-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .adjust-btn {
            border: 1px solid var(--gold-dim);
            background: rgba(212,160,23,0.05);
            color: var(--warm-champagne);
            padding: 6px 13px;
            border-radius: 6px;
            font-family: var(--sans);
            font-size: 0.88rem;
            font-weight: bold;
            cursor: pointer;
            transition: 0.15s;
        }
        .adjust-btn:hover:not(:disabled) {
            border-color: var(--burnished-gold);
            color: var(--burnished-gold);
            background: rgba(212,160,23,0.12);
        }
        .adjust-btn:disabled { opacity: 0.35; cursor: not-allowed; }

        .minmax-row {
            display: flex;
            justify-content: space-between;
            font-family: var(--sans);
            font-size: 11px;
            color: var(--text-muted);
            margin-bottom: 12px;
        }
        .gold-sep {
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold-dim), transparent);
            margin: 4px 0 12px;
        }

        .btn-confirm {
            width: 100%;
            background: rgba(212,160,23,0.08);
            border: 1px solid var(--burnished-gold);
            color: var(--burnished-gold);
            padding: 11px;
            font-family: 'Times New Roman', serif;
            font-size: 1.2rem;
            font-weight: bold;
            letter-spacing: 2px;
            cursor: pointer;
            border-radius: 8px;
            transition: 0.2s;
        }
        .btn-confirm:hover:not(:disabled) {
            background: rgba(212,160,23,0.17);
            box-shadow: 0 0 14px var(--gold-glow);
        }
        .btn-confirm:disabled { opacity: 0.35; cursor: not-allowed; }

        .payout-row {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .payout-pill {
            background: rgba(212,160,23,0.07);
            border: 1px solid var(--gold-dim);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 11px;
            font-family: var(--sans);
            color: var(--text-muted);
            letter-spacing: 1px;
        }
        .payout-pill strong { color: var(--burnished-gold); }

        .right {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-width: 0;
        }

        .dice-area {
            flex: 1;
            background: #1C0A08;
            border: 1px solid var(--gold-dim);
            border-radius: 12px;
            padding: 28px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 18px;
            transition: box-shadow 0.2s;
        }
        .dice-area:hover { box-shadow: 0 0 20px var(--gold-glow); }

        .dice-title {
            font-family: 'Times New Roman', serif;
            font-size: 1.5rem;
            color: var(--burnished-gold);
            letter-spacing: 4px;
            text-shadow: 0 0 12px var(--gold-glow);
        }
        .dice-title::after {
            content: '';
            display: block;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--burnished-gold), transparent);
            margin: 8px auto 0;
            width: 60%;
        }

        .banner {
            font-family: 'Times New Roman', serif;
            font-size: 3rem;
            font-weight: bold;
            letter-spacing: 5px;
            animation: popIn 0.4s ease-out;
        }
        .win-banner  { color: #4ade80; text-shadow: 0 0 20px rgba(74,222,128,0.4); }
        .loss-banner { color: #f87171; text-shadow: 0 0 20px rgba(248,113,113,0.4); }
        @keyframes popIn {
            0%   { transform: scale(0.7); opacity: 0; }
            70%  { transform: scale(1.08); }
            100% { transform: scale(1); opacity: 1; }
        }

        .dice-row {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
        }

        .die {
            width: 220px;
            background: #0E0500;
            border: 2px solid var(--burnished-gold);
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 28px 20px;
            gap: 16px;
            box-shadow: 0 0 16px var(--gold-glow);
            transition: transform 0.2s;
        }
        .die:hover { transform: translateY(-4px); }

        .die-label {
            font-family: 'Times New Roman', serif;
            font-size: 1.6rem;
            font-weight: bold;
            letter-spacing: 2px;
            color: var(--warm-champagne);
        }
        .die-label.placeholder { color: var(--text-muted); font-size: 2.5rem; }

        .die-circle {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.2);
            box-shadow: 0 0 18px rgba(0,0,0,0.5), inset 0 0 14px rgba(255,255,255,0.07);
        }

        .die-tag {
            font-family: var(--sans);
            font-size: 0.72rem;
            letter-spacing: 2px;
            color: var(--text-muted);
        }

        .message-box {
            background: #1C0A08;
            border: 1px solid var(--gold-dim);
            border-left: 3px solid var(--burnished-gold);
            border-radius: 8px;
            padding: 12px 18px;
            text-align: center;
            color: var(--warm-champagne);
            font-family: var(--sans);
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        .action-buttons {
            display: flex;
            gap: 14px;
            justify-content: center;
            align-items: center;
        }

        .roll-btn {
            background: linear-gradient(45deg, #C0152A, #e0294a);
            border: 1px solid rgba(212,160,23,0.35);
            color: var(--warm-champagne);
            padding: 14px 50px;
            font-family: 'Times New Roman', serif;
            font-size: 1.6rem;
            font-weight: bold;
            letter-spacing: 4px;
            border-radius: 9px;
            cursor: pointer;
            transition: 0.25s;
            box-shadow: 0 0 20px rgba(192,21,42,0.35);
        }
        .roll-btn:hover:not(:disabled) {
            box-shadow: 0 0 30px rgba(192,21,42,0.6);
            transform: scale(1.03);
        }
        .roll-btn:disabled {
            opacity: 0.35;
            cursor: not-allowed;
            box-shadow: none;
        }

        .secondary-btn {
            background: rgba(212,160,23,0.06);
            border: 1px solid var(--gold-dim);
            color: var(--warm-champagne);
            padding: 10px 24px;
            font-family: var(--sans);
            font-size: 0.9rem;
            font-weight: bold;
            letter-spacing: 1.5px;
            border-radius: 7px;
            cursor: pointer;
            transition: 0.2s;
        }
        .secondary-btn:hover {
            border-color: var(--burnished-gold);
            color: var(--burnished-gold);
            background: rgba(212,160,23,0.12);
        }
    </style>
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
                <span class="balance-amount">₱<?php echo htmlspecialchars($balance_display); ?></span>
            </div>
            <button class="btn-deposit" onclick="location.href='topup.php'">+ TOP UP</button>
        </div>
    </header>

    <div class="container">
        <div class="left">
            <div class="panel">
                <div class="panel-title">PICK YOUR COLOR</div>
                <form method="post" id="gameForm">
                    <div class="color-grid">
                        <?php foreach ($colors as $color): ?>
                            <label class="color-option">
                                <input type="radio" name="color" value="<?php echo $color; ?>"
                                    <?php echo ($chosen_color == $color) ? 'checked' : ''; ?>
                                    <?php echo $color_active ? 'disabled' : ''; ?>>
                                <div class="color-label">
                                    <div class="color-dot" style="background:<?php echo $color_classes[$color]; ?>;"></div>
                                    <span style="color:#fff; font-size:0.8rem; letter-spacing:1.5px;"><?php echo strtoupper($color); ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- PAYOUT PILLS -->
                    <div class="payout-row" style="margin-top:16px;">
                        <div class="payout-pill">1 match <strong>2×</strong></div>
                        <div class="payout-pill">2 match <strong>3×</strong></div>
                    </div>

                    <!-- BET SECTION -->
                    <div style="margin-top:18px;">
                        <span class="bet-label">YOUR BET</span>
                        <div class="bet-row">
                            <div class="bet-balance">Balance: <strong>&#8369;<?php echo number_format($_SESSION['balance']); ?></strong></div>
                            <input type="text" name="bet_display" class="bet-input"
                                   value="<?php echo $current_bet; ?>"
                                   <?php echo $color_active ? 'readonly' : ''; ?>>
                        </div>

                        <div class="adjust-buttons">
                            <button type="submit" name="adjust" value="-100" class="adjust-btn" <?php echo $color_active ? 'disabled' : ''; ?>>-100</button>
                            <button type="submit" name="adjust" value="-10"  class="adjust-btn" <?php echo $color_active ? 'disabled' : ''; ?>>-10</button>
                            <button type="submit" name="adjust" value="+10"  class="adjust-btn" <?php echo $color_active ? 'disabled' : ''; ?>>+10</button>
                            <button type="submit" name="adjust" value="+100" class="adjust-btn" <?php echo $color_active ? 'disabled' : ''; ?>>+100</button>
                        </div>

                        <div class="minmax-row">
                            <span>MIN: 10</span><span>MAX: 5000</span>
                        </div>

                        <div class="gold-sep"></div>

                        <button type="submit" name="action" value="confirm_bet" class="btn-confirm"
                                <?php echo $color_active ? 'disabled' : ''; ?>>CONFIRM BET</button>
                    </div>
                </form>
            </div>

        </div><!-- /left -->

        <!-- ============ RIGHT COLUMN ============ -->
        <div class="right">

            <!-- DICE AREA -->
            <div class="dice-area">
                <div class="dice-title">DICE RESULTS</div>

                <?php if ($show_win_banner): ?>
                    <div class="banner win-banner">YOU WIN!</div>
                <?php elseif ($show_loss_banner): ?>
                    <div class="banner loss-banner">YOU LOSE</div>
                <?php endif; ?>

                <div class="dice-row">
                    <!-- DIE 1 -->
                    <div class="die">
                        <?php if ($die1): ?>
                            <div class="die-label"><?php echo strtoupper($die1); ?></div>
                            <div class="die-circle" style="background:<?php echo $color_classes[$die1]; ?>;"></div>
                            <div class="die-tag">DIE 1</div>
                        <?php else: ?>
                            <div class="die-label placeholder">?</div>
                            <div class="die-circle" style="background:#1a0a04;border-color:rgba(212,160,23,0.15);"></div>
                            <div class="die-tag">DIE 1</div>
                        <?php endif; ?>
                    </div>

                    <!-- DIE 2 -->
                    <div class="die">
                        <?php if ($die2): ?>
                            <div class="die-label"><?php echo strtoupper($die2); ?></div>
                            <div class="die-circle" style="background:<?php echo $color_classes[$die2]; ?>;"></div>
                            <div class="die-tag">DIE 2</div>
                        <?php else: ?>
                            <div class="die-label placeholder">?</div>
                            <div class="die-circle" style="background:#1a0a04;border-color:rgba(212,160,23,0.15);"></div>
                            <div class="die-tag">DIE 2</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="message-box"><?php echo $message; ?></div>
            <?php endif; ?>

            <!-- ACTION BUTTONS -->
            <div class="action-buttons">
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="roll">
                    <button type="submit" class="roll-btn" <?php echo $color_active ? '' : 'disabled'; ?>>ROLL</button>
                </form>
                <?php if ($color_active): ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="stop">
                        <button type="submit" class="secondary-btn">STOP</button>
                    </form>
                <?php endif; ?>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="exit">
                    <button type="submit" class="secondary-btn">EXIT</button>
                </form>
            </div>

        </div><!-- /right -->

    </div><!-- /container -->

</body>
</html>
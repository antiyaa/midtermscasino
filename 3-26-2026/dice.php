<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['dice_bet']))    $_SESSION['dice_bet']    = null;
if (!isset($_SESSION['dice_active'])) $_SESSION['dice_active'] = false;

$message          = '';
$player_dice1     = null;
$player_dice2     = null;
$banker_dice1     = null;
$banker_dice2     = null;
$player_sum       = null;
$banker_sum       = null;
$show_win_banner  = false;
$show_loss_banner = false;
$show_tie_banner  = false;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'back') {
    unset($_SESSION['dice_bet']);
    $_SESSION['dice_active'] = false;
    header('Location: Homepage.php');
    exit;
}

if ($action === 'stop') {
    unset($_SESSION['dice_bet']);
    $_SESSION['dice_active'] = false;
    $message = "Game stopped. You can place a new bet.";
}

if ($action === 'confirm_bet') {
    $bet = (int)($_POST['bet'] ?? 0);
    if ($bet <= 0) {
        $message = "Bet must be positive.";
    } elseif ($bet > $_SESSION['balance']) {
        $message = "Insufficient balance. Your balance is {$_SESSION['balance']}.";
    } else {
        $_SESSION['dice_bet']    = $bet;
        $_SESSION['dice_active'] = true;
        $message = "Bet confirmed: ₱$bet. Click Roll Dice to play.";
    }
}

if ($action === 'roll' && $_SESSION['dice_active']) {
    $bet = $_SESSION['dice_bet'];
    if ($bet > $_SESSION['balance']) {
        $message = "Insufficient balance to continue. Please stop and try again with a lower bet.";
    } else {
        $player_dice1 = rand(1, 6);
        $player_dice2 = rand(1, 6);
        $player_sum   = $player_dice1 + $player_dice2;

        $banker_dice1 = rand(1, 6);
        $banker_dice2 = rand(1, 6);
        $banker_sum   = $banker_dice1 + $banker_dice2;

        $_SESSION['balance'] -= $bet;

        if ($player_sum > $banker_sum) {
            $_SESSION['balance'] += ($bet * 2);
            $message         = "You won! +&#8369;" . $bet . " profit. New balance: &#8369;" . number_format($_SESSION['balance']) . ".";
            $show_win_banner = true;
        } elseif ($player_sum < $banker_sum) {
            $message          = "Banker wins. You lost &#8369;$bet. New balance: &#8369;" . number_format($_SESSION['balance']) . ".";
            $show_loss_banner = true;
        } else {
            $_SESSION['balance'] += $bet;
            $message         = "It's a tie! Bet returned. Balance: &#8369;" . number_format($_SESSION['balance']) . ".";
            $show_tie_banner = true;
        }
    }
}

function getDiceImage($value) {
    if ($value === null) return "d1.png";
    return "d$value.png";
}

$balance_display = number_format($_SESSION['balance']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Casino Ni Lady – Dice Game</title>
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

        /* --- HEADER --- */
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

        /* --- BACK BUTTON --- */
        .back-container { padding: 18px 28px 0; }
        .back-btn {
            display: inline-flex; align-items: center; gap: 6px;
            background: linear-gradient(45deg, #C0152A, #e0294a);
            color: var(--warm-champagne);
            padding: 8px 20px; border-radius: 6px;
            font-weight: bold; border: none; cursor: pointer;
            font-size: 0.85rem; font-family: var(--sans); letter-spacing: 1.5px; transition: 0.2s;
        }
        .back-btn:hover { box-shadow: 0 0 16px rgba(192,21,42,0.55); transform: scale(1.03); }

        /* --- GAME WRAPPER --- */
        .game-wrapper {
            max-width: 1100px;
            width: 95%;
            margin: 22px auto;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 18px;
            padding-bottom: 40px;
        }

        .game-title {
            font-family: 'Times New Roman', serif;
            font-size: 1.6rem;
            text-align: center;
            color: var(--burnished-gold);
            letter-spacing: 5px;
            text-shadow: 0 0 14px var(--gold-glow);
        }
        .game-title::after {
            content: '';
            display: block; height: 1px;
            background: linear-gradient(90deg, transparent, var(--burnished-gold), transparent);
            margin: 10px auto 0; width: 50%;
        }

        /* --- TWO-COLUMN LAYOUT --- */
        .columns {
            display: flex;
            gap: 22px;
            align-items: stretch;
        }

        .col-left {
            width: 340px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .col-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-width: 0;
        }

        /* --- PANEL --- */
        .panel {
            background: #1C0A08;
            border: 1px solid var(--gold-dim);
            border-radius: 12px;
            padding: 24px 26px;
            position: relative;
            transition: box-shadow 0.2s;
            height: 100%;
        }
        .panel:hover { box-shadow: 0 0 18px var(--gold-glow); }

        .panel-title {
            font-family: 'Times New Roman', serif;
            font-size: 1.2rem;
            color: var(--burnished-gold);
            letter-spacing: 3px;
            text-align: center;
            margin-bottom: 6px;
        }
        .panel-title::after {
            content: '';
            display: block; height: 1px;
            background: linear-gradient(90deg, transparent, var(--burnished-gold), transparent);
            margin: 8px auto 20px; width: 70%;
        }

        /* --- PAYOUT PILLS --- */
        .payout-row {
            display: flex; gap: 8px; justify-content: center;
            flex-wrap: wrap; margin-bottom: 20px;
        }
        .payout-pill {
            background: rgba(212,160,23,0.07);
            border: 1px solid var(--gold-dim);
            border-radius: 20px; padding: 4px 13px;
            font-size: 11px; font-family: var(--sans);
            color: var(--text-muted); letter-spacing: 1px;
        }
        .payout-pill strong { color: var(--burnished-gold); }

        /* --- BET FORM --- */
        .bet-balance-row {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 16px; font-family: var(--sans); font-size: 0.9rem;
        }
        .bal-label { color: var(--text-muted); letter-spacing: 1px; font-size: 0.75rem; }
        .bal-value { color: var(--burnished-gold); font-weight: bold; font-size: 1.1rem; }

        .bet-input-wrap { text-align: center; margin-bottom: 10px; }
        .bet-number-input {
            background: #0E0500;
            border: 1px solid var(--burnished-gold);
            border-radius: 8px;
            color: var(--burnished-gold);
            font-size: 1.4rem; font-weight: bold;
            font-family: var(--sans);
            width: 100%; text-align: center; padding: 10px;
            outline: none;
        }
        .bet-number-input:focus { box-shadow: 0 0 0 2px var(--gold-glow); }

        .gold-sep {
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold-dim), transparent);
            margin: 4px 0 14px;
        }

        .btn-confirm {
            width: 100%;
            background: rgba(212,160,23,0.08);
            border: 1px solid var(--burnished-gold);
            color: var(--burnished-gold);
            padding: 12px;
            font-family: 'Times New Roman', serif;
            font-size: 1.2rem; font-weight: bold; letter-spacing: 2px;
            cursor: pointer; border-radius: 8px; transition: 0.2s;
        }
        .btn-confirm:hover { background: rgba(212,160,23,0.17); box-shadow: 0 0 14px var(--gold-glow); }

        /* active bet pills */
        .info-pills {
            display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;
        }
        .info-pill {
            background: rgba(212,160,23,0.07);
            border: 1px solid var(--gold-dim);
            border-radius: 8px; padding: 12px 16px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .pill-label { font-family: var(--sans); font-size: 0.7rem; letter-spacing: 2px; color: var(--text-muted); }
        .pill-value { font-family: var(--sans); font-size: 1.2rem; font-weight: bold; color: var(--burnished-gold); }

        .active-note {
            text-align: center; font-family: var(--sans);
            font-size: 0.8rem; color: var(--text-muted); letter-spacing: 1px;
            margin-top: 6px;
        }

        /* --- RESULT PANEL --- */
        .result-panel {
            background: #1C0A08;
            border: 1px solid var(--gold-dim);
            border-radius: 12px;
            padding: 28px 26px;
            flex: 1;
            display: flex;
            flex-direction: column;
            transition: box-shadow 0.2s;
        }
        .result-panel:hover { box-shadow: 0 0 18px var(--gold-glow); }

        /* banner */
        .banner {
            font-family: 'Times New Roman', serif;
            font-size: 2.8rem; font-weight: bold; letter-spacing: 6px;
            text-align: center; margin-bottom: 20px;
            animation: popIn 0.4s ease-out;
        }
        .win-banner  { color: #4ade80; text-shadow: 0 0 20px rgba(74,222,128,0.4); }
        .loss-banner { color: #f87171; text-shadow: 0 0 20px rgba(248,113,113,0.4); }
        .tie-banner  { color: var(--burnished-gold); text-shadow: 0 0 16px var(--gold-glow); }
        @keyframes popIn {
            0%   { transform: scale(0.7); opacity: 0; }
            70%  { transform: scale(1.06); }
            100% { transform: scale(1); opacity: 1; }
        }

        /* dice arena — horizontal player vs banker */
        .dice-arena {
            display: flex;
            gap: 16px;
            justify-content: center;
            align-items: stretch;
            flex: 1;
            margin-bottom: 18px;
        }

        .dice-side {
            flex: 1;
            background: #0E0500;
            border: 1px solid var(--gold-dim);
            border-radius: 12px;
            padding: 20px 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .dice-side.winner { border-color: var(--burnished-gold); box-shadow: 0 0 18px var(--gold-glow); }
        .dice-side.loser  { border-color: rgba(248,113,113,0.3); }

        .side-label {
            font-family: 'Times New Roman', serif;
            font-size: 1.1rem; letter-spacing: 3px;
        }
        .side-label.you    { color: #4ade80; }
        .side-label.banker { color: #f87171; }
        .side-label.neutral { color: var(--text-muted); }

        .dice-pair {
            display: flex; gap: 14px;
            align-items: center; justify-content: center;
        }

        .die-wrap {
            background: #1C0A08;
            border: 1px solid var(--gold-dim);
            border-radius: 10px; padding: 10px;
            display: flex; align-items: center; justify-content: center;
            transition: border-color 0.2s;
        }
        .die-wrap:hover { border-color: var(--burnished-gold); }

        .dice-icon {
            width: 90px; height: 90px; display: block;
            filter: drop-shadow(0 0 4px rgba(212,160,23,0.2));
        }

        .score-badge {
            background: rgba(212,160,23,0.1);
            border: 1px solid var(--gold-dim);
            border-radius: 20px; padding: 5px 20px;
            font-family: var(--sans); font-size: 0.78rem;
            letter-spacing: 2px; color: var(--text-muted);
        }
        .score-badge strong {
            color: var(--burnished-gold); font-size: 1.1rem; margin-left: 6px;
        }

        /* VS column */
        .vs-col {
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 8px; padding: 0 4px; flex-shrink: 0;
        }
        .vs-line {
            width: 1px; flex: 1;
            background: linear-gradient(180deg, transparent, var(--gold-dim), transparent);
        }
        .vs-text {
            font-family: 'Times New Roman', serif;
            font-size: 1.2rem; color: var(--text-muted); letter-spacing: 2px;
        }

        /* status msg */
        .game-status-message {
            text-align: center; font-family: var(--sans);
            font-size: 0.9rem; color: var(--warm-champagne);
            background: rgba(212,160,23,0.05);
            border: 1px solid var(--gold-dim);
            border-left: 3px solid var(--burnished-gold);
            border-radius: 8px; padding: 11px 16px;
            letter-spacing: 0.5px; margin-bottom: 16px;
        }

        /* action buttons */
        .action-buttons {
            display: flex; gap: 14px;
            justify-content: center; flex-wrap: wrap;
        }
        .btn-roll {
            background: linear-gradient(45deg, #C0152A, #e0294a);
            border: 1px solid rgba(212,160,23,0.35);
            color: var(--warm-champagne);
            padding: 14px 50px;
            font-family: 'Times New Roman', serif;
            font-size: 1.5rem; font-weight: bold; letter-spacing: 4px;
            border-radius: 9px; cursor: pointer; transition: 0.25s;
            box-shadow: 0 0 18px rgba(192,21,42,0.35);
        }
        .btn-roll:hover { box-shadow: 0 0 28px rgba(192,21,42,0.6); transform: scale(1.03); }

        .btn-secondary {
            background: rgba(212,160,23,0.06);
            border: 1px solid var(--gold-dim);
            color: var(--warm-champagne);
            padding: 12px 26px;
            font-family: var(--sans); font-size: 0.9rem;
            font-weight: bold; letter-spacing: 1.5px;
            border-radius: 7px; cursor: pointer; transition: 0.2s;
        }
        .btn-secondary:hover {
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

    <div class="back-container">
        <form method="post" style="display:inline;">
            <input type="hidden" name="action" value="back">
            <button type="submit" class="back-btn">&#8592; Back to Homepage</button>
        </form>
    </div>

    <div class="game-wrapper">

        <div class="game-title">DICE GAME &nbsp;&#8212;&nbsp; PLAYER VS BANKER</div>

        <div class="columns">
            <div class="col-left">
                <div class="panel">

                    <?php if (!$_SESSION['dice_active']): ?>
                        <div class="panel-title">PLACE YOUR BET</div>

                        <div class="payout-row">
                            <div class="payout-pill">Win <strong>2×</strong></div>
                            <div class="payout-pill">Tie — Bet returned</div>
                            <div class="payout-pill">Loss — Bet lost</div>
                        </div>

                        <div class="bet-balance-row">
                            <span class="bal-label">YOUR BALANCE</span>
                            <span class="bal-value">&#8369;<?php echo number_format($_SESSION['balance']); ?></span>
                        </div>

                        <form method="post">
                            <div class="bet-input-wrap">
                                <input type="number" name="bet" min="10" max="5000" step="10"
                                       class="bet-number-input"
                                       value="<?php echo isset($_POST['bet']) ? htmlspecialchars($_POST['bet']) : '10'; ?>"
                                       required>
                            </div>
                            <div class="gold-sep"></div>
                            <input type="hidden" name="action" value="confirm_bet">
                            <button type="submit" class="btn-confirm">CONFIRM BET</button>
                        </form>

                    <?php else: ?>
                        <div class="panel-title">CURRENT BET</div>

                        <div class="info-pills">
                            <div class="info-pill">
                                <span class="pill-label">BALANCE</span>
                                <span class="pill-value">&#8369;<?php echo number_format($_SESSION['balance']); ?></span>
                            </div>
                            <div class="info-pill">
                                <span class="pill-label">YOUR BET</span>
                                <span class="pill-value">&#8369;<?php echo number_format($_SESSION['dice_bet']); ?></span>
                            </div>
                            <div class="info-pill">
                                <span class="pill-label">POTENTIAL WIN</span>
                                <span class="pill-value">&#8369;<?php echo number_format($_SESSION['dice_bet'] * 2); ?></span>
                            </div>
                        </div>

                        <p class="active-note">Game in progress — roll or end below.</p>
                    <?php endif; ?>

                </div>
            </div><!-- /col-left -->

            <!-- RIGHT — RESULTS -->
            <div class="col-right">
                <div class="result-panel">
                    <div class="panel-title">GAME RESULT</div>

                    <?php if ($show_win_banner): ?>
                        <div class="banner win-banner">YOU WIN!</div>
                    <?php elseif ($show_loss_banner): ?>
                        <div class="banner loss-banner">YOU LOSE</div>
                    <?php elseif ($show_tie_banner): ?>
                        <div class="banner tie-banner">TIE</div>
                    <?php endif; ?>

                    <!-- DICE ARENA -->
                    <div class="dice-arena">

                        <!-- PLAYER -->
                        <div class="dice-side <?php
                            if ($show_win_banner)  echo 'winner';
                            elseif ($show_loss_banner) echo 'loser';
                        ?>">
                            <span class="side-label <?php
                                if ($show_win_banner) echo 'you';
                                elseif ($show_loss_banner) echo 'neutral';
                                else echo 'you';
                            ?>">&#9654; YOU</span>

                            <div class="dice-pair">
                                <div class="die-wrap">
                                    <img src="<?php echo getDiceImage($player_dice1); ?>" alt="Die" class="dice-icon">
                                </div>
                                <div class="die-wrap">
                                    <img src="<?php echo getDiceImage($player_dice2); ?>" alt="Die" class="dice-icon">
                                </div>
                            </div>

                            <div class="score-badge">
                                TOTAL <strong><?php echo $player_sum !== null ? $player_sum : '?'; ?></strong>
                            </div>
                        </div>

                        <!-- VS -->
                        <div class="vs-col">
                            <div class="vs-line"></div>
                            <span class="vs-text">VS</span>
                            <div class="vs-line"></div>
                        </div>

                        <!-- BANKER -->
                        <div class="dice-side <?php
                            if ($show_loss_banner) echo 'winner';
                            elseif ($show_win_banner) echo 'loser';
                        ?>">
                            <span class="side-label <?php
                                if ($show_loss_banner) echo 'you';
                                elseif ($show_win_banner) echo 'neutral';
                                else echo 'banker';
                            ?>">BANKER &#9664;</span>

                            <div class="dice-pair">
                                <div class="die-wrap">
                                    <img src="<?php echo getDiceImage($banker_dice1); ?>" alt="Die" class="dice-icon">
                                </div>
                                <div class="die-wrap">
                                    <img src="<?php echo getDiceImage($banker_dice2); ?>" alt="Die" class="dice-icon">
                                </div>
                            </div>

                            <div class="score-badge">
                                TOTAL <strong><?php echo $banker_sum !== null ? $banker_sum : '?'; ?></strong>
                            </div>
                        </div>

                    </div><!-- /dice-arena -->

                    <?php if ($message): ?>
                        <div class="game-status-message"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <?php if ($_SESSION['dice_active']): ?>
                    <div class="action-buttons">
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="roll">
                            <button type="submit" class="btn-roll">ROLL THE DICE</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="stop">
                            <button type="submit" class="btn-secondary">END THE GAME/CHANGE BET</button>
                        </form>
                    </div>
                    <?php endif; ?>

                </div><!-- /result-panel -->
            </div><!-- /col-right -->

        </div><!-- /columns -->

    </div><!-- /game-wrapper -->

</body>
</html>
<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['dice_bet'])) {
    $_SESSION['dice_bet'] = null;
}
if (!isset($_SESSION['dice_active'])) {
    $_SESSION['dice_active'] = false;
}

$message = '';
$player_dice1 = null;
$player_dice2 = null;
$banker_dice1 = null;
$banker_dice2 = null;
$player_sum = null;
$banker_sum = null;
$show_win_banner = false;
$show_loss_banner = false;
$show_tie_banner = false;

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
        $_SESSION['dice_bet'] = $bet;
        $_SESSION['dice_active'] = true;
        $message = "Bet confirmed: $bet credits. Click Roll Dice to play.";
    }
}

if ($action === 'roll' && $_SESSION['dice_active']) {
    $bet = $_SESSION['dice_bet'];
    if ($bet > $_SESSION['balance']) {
        $message = "Insufficient balance to continue. Please stop and try again with a lower bet.";
    } else {
        $player_dice1 = rand(1, 6);
        $player_dice2 = rand(1, 6);
        $player_sum = $player_dice1 + $player_dice2;

        $banker_dice1 = rand(1, 6);
        $banker_dice2 = rand(1, 6);
        $banker_sum = $banker_dice1 + $banker_dice2;

        $_SESSION['balance'] -= $bet;

        if ($player_sum > $banker_sum) {
            $_SESSION['balance'] += ($bet * 2);
            $profit = $bet;
            $message = "You earned +" . ($bet * 2) . " New balance: {$_SESSION['balance']}.";
            $show_win_banner = true;
        } elseif ($player_sum < $banker_sum) {
            $message = "You lost $bet credits. New balance: {$_SESSION['balance']}.";
            $show_loss_banner = true;
        } else {
            $_SESSION['balance'] += $bet;
            $message = "Bet returned. New balance: {$_SESSION['balance']}.";
            $show_tie_banner = true;
        }
    }
}

function getDiceImage($value) {
    if ($value === null) return "d1.png";
    return "d$value.png";
}

$username = htmlspecialchars($_SESSION['username'] ?? 'Kpop Idol');
$balance_display = number_format($_SESSION['balance']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Casino Ni Lady - Dice Game</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial Narrow', Arial, sans-serif;
        }

        body {
            background-color: #f0f0f0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

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

        .back-container {
            background-color: #f0f0f0;
            padding: 10px 20px;
        }

        .back-btn {
            display: inline-block;
            background-color: #901c1c;
            color: white;
            padding: 8px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            transition: 0.2s;
        }

        .back-btn:hover {
            background-color: #b02323;
        }

        .game-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
            flex: 1;
        }

        .game-title {
            font-size: 2rem;
            margin-bottom: 20px;
            text-align: center;
            color: #333;
        }

        .betting-section, .result-section {
            border: 2px solid #ccc;
            border-radius: 10px;
            background-color: #fff;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .section-title {
            text-align: center;
            font-weight: bold;
            font-size: 1.2em;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .bet-input-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 1.1em;
        }

        .bet-amount-display {
            border: 1px solid #ccc;
            padding: 5px 10px;
            border-radius: 5px;
            background-color: #f9f9f9;
            font-weight: bold;
        }

        .bet-input {
            margin: 20px 0;
            text-align: center;
        }

        .bet-input input {
            width: 200px;
            padding: 10px;
            font-size: 1.1rem;
            border: 2px solid #ccc;
            border-radius: 5px;
            text-align: center;
        }

        .bet-message {
            text-align: center;
            color: blue;
            font-weight: bold;
            margin: 15px 0;
            min-height: 2.5em;
        }

        .confirm-bet-btn, .action-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #fff;
            color: #000;
            border: 2px solid #000;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.2s;
        }

        .confirm-bet-btn:hover, .action-btn:hover {
            background-color: #f0f0f0;
        }

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
        .tie-banner {
            color: #6c757d;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        @keyframes pulse {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .dice-container {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .dice-player, .dice-banker {
            text-align: center;
        }

        .dice-icon {
            width: 70px;
            height: 70px;
            margin: 5px;
        }

        .score-display {
            margin-top: 10px;
            font-weight: bold;
            font-size: 1.1em;
        }

        .game-status-message {
            text-align: center;
            font-weight: bold;
            margin: 20px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            word-break: break-word;
            font-size: 1.2rem;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
        }

        @media (max-width: 700px) {
            .dice-icon {
                width: 50px;
                height: 50px;
            }
            .bet-input input {
                width: 150px;
            }
            .header-left, .header-right {
                width: 120px;
                padding: 0 10px;
                font-size: 0.9rem;
            }
            header h1 {
                font-size: 2rem;
            }
            .game-status-message {
                font-size: 1rem;
            }
            .banner {
                font-size: 2rem;
            }
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

<div class="back-container">
    <form method="post" style="display:inline;">
        <input type="hidden" name="action" value="back">
        <button type="submit" class="back-btn">Back to Homepage</button>
    </form>
</div>

<div class="game-container">
    <div class="game-title">DICE GAME (Player vs Banker)</div>

    <?php if (!$_SESSION['dice_active']): ?>
    <div class="betting-section">
        <div class="section-title">PLACE YOUR BET</div>
        <div class="bet-input-row">
            <span>Your Balance:</span>
            <span class="bet-amount-display">₱<?php echo $_SESSION['balance']; ?></span>
        </div>
        <form method="post">
            <div class="bet-input">
                <input type="number" name="bet" min="10" max="5000" step="10" value="<?php echo isset($_POST['bet']) ? htmlspecialchars($_POST['bet']) : '10'; ?>" required>
            </div>
            <div class="bet-message">
                You are about to bet ₱<span id="betValue"><?php echo isset($_POST['bet']) ? htmlspecialchars($_POST['bet']) : '10'; ?></span>
            </div>
            <input type="hidden" name="action" value="confirm_bet">
            <button type="submit" class="confirm-bet-btn">CONFIRM BET</button>
        </form>
        <script>
            const betInput = document.querySelector('input[name="bet"]');
            const betSpan = document.getElementById('betValue');
            if (betInput && betSpan) {
                betInput.addEventListener('input', function() {
                    betSpan.textContent = this.value;
                });
            }
        </script>
    </div>
    <?php else: ?>
    <div class="betting-section">
        <div class="section-title">CURRENT BET</div>
        <div class="bet-input-row">
            <span>Your Balance:</span>
            <span class="bet-amount-display">₱<?php echo $_SESSION['balance']; ?></span>
        </div>
        <div class="bet-input-row">
            <span>Current Bet:</span>
            <span class="bet-amount-display">₱<?php echo $_SESSION['dice_bet']; ?></span>
        </div>
        <p class="bet-message">Game in progress. Use controls below to roll or stop.</p>
    </div>
    <?php endif; ?>

    <div class="result-section">
        <div class="section-title">GAME RESULT</div>
        <?php if ($show_win_banner): ?>
        <div class="banner win-banner">YOU WIN</div>
        <?php elseif ($show_loss_banner): ?>
        <div class="banner loss-banner">YOU LOSE</div>
        <?php elseif ($show_tie_banner): ?>
        <div class="banner tie-banner">TIE</div>
        <?php endif; ?>
        <div class="dice-container">
            <div class="dice-player">
                <span style="color: green; font-weight: bold;">YOU</span><br>
                <img src="<?php echo getDiceImage($player_dice1); ?>" alt="Dice" class="dice-icon">
                <img src="<?php echo getDiceImage($player_dice2); ?>" alt="Dice" class="dice-icon">
                <div class="score-display">Total Score: <?php echo $player_sum !== null ? $player_sum : '?'; ?></div>
            </div>
            <div class="dice-banker">
                <span style="color: green; font-weight: bold;">BANKER</span><br>
                <img src="<?php echo getDiceImage($banker_dice1); ?>" alt="Dice" class="dice-icon">
                <img src="<?php echo getDiceImage($banker_dice2); ?>" alt="Dice" class="dice-icon">
                <div class="score-display">Total Score: <?php echo $banker_sum !== null ? $banker_sum : '?'; ?></div>
            </div>
        </div>
        <div class="game-status-message">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php if ($_SESSION['dice_active']): ?>
        <div class="action-buttons">
            <form method="post" style="width:100%;">
                <input type="hidden" name="action" value="roll">
                <button type="submit" class="action-btn">ROLL THE DICE</button>
            </form>
            <form method="post" style="width:100%;">
                <input type="hidden" name="action" value="stop">
                <button type="submit" class="action-btn">STOP</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
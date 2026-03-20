<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// Initialize session variables for the game
if (!isset($_SESSION['dice_bet'])) {
    $_SESSION['dice_bet'] = null;       // current bet amount (if active)
}
if (!isset($_SESSION['dice_active'])) {
    $_SESSION['dice_active'] = false;   // true if bet is confirmed and game is active
}

$message = '';
$player_dice1 = null;
$player_dice2 = null;
$banker_dice1 = null;
$banker_dice2 = null;
$player_sum = null;
$banker_sum = null;

// Handle actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'exit') {
    // Clear game state and go back to homepage
    unset($_SESSION['dice_bet']);
    $_SESSION['dice_active'] = false;
    header('Location: Homepage.php');
    exit;
}

if ($action === 'stop') {
    // Clear game state, return to bet entry
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
        // Roll dice for player
        $player_dice1 = rand(1, 6);
        $player_dice2 = rand(1, 6);
        $player_sum = $player_dice1 + $player_dice2;

        // Roll dice for banker
        $banker_dice1 = rand(1, 6);
        $banker_dice2 = rand(1, 6);
        $banker_sum = $banker_dice1 + $banker_dice2;

        // Deduct bet first
        $_SESSION['balance'] -= $bet;

        // Compare totals
        if ($player_sum > $banker_sum) {
            // Player wins – double the bet (net profit = bet)
            // We deducted bet, so to give profit of bet, we need to add back 2*bet (original bet + profit).
            $_SESSION['balance'] += ($bet * 2);
            $message = "You rolled $player_sum ($player_dice1 + $player_dice2), Banker rolled $banker_sum ($banker_dice1 + $banker_dice2). You win! You earn " . ($bet * 2) . " credits (profit $bet). New balance: {$_SESSION['balance']}.";
        } elseif ($player_sum < $banker_sum) {
            // Player loses – bet already deducted, no addition
            $message = "You rolled $player_sum ($player_dice1 + $player_dice2), Banker rolled $banker_sum ($banker_dice1 + $banker_dice2). You lose. You lose $bet credits. New balance: {$_SESSION['balance']}.";
        } else {
            // Tie – refund the bet
            $_SESSION['balance'] += $bet;
            $message = "You rolled $player_sum ($player_dice1 + $player_dice2), Banker rolled $banker_sum ($banker_dice1 + $banker_dice2). It's a tie! Bet returned. New balance: {$_SESSION['balance']}.";
        }
    }
}

// Default die image – uses d1.png (one of your existing dice images)
function defaultDieImage() {
    return "d1.png";
}
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        .game-area {
            margin: 20px 0;
        }
        .player-area, .banker-area {
            display: inline-block;
            margin: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
            vertical-align: top;
        }
        .dice {
            display: inline-block;
            margin: 5px;
        }
        .dice img {
            width: 60px;
            height: 60px;
        }
        .result {
            margin: 20px;
            font-weight: bold;
        }
        button {
            margin: 5px;
            padding: 5px 10px;
        }
    </style>
</head>
<body>
    <h2>Dice Game - Player vs Banker (2 dice each)</h2>
    <p>Your balance: <strong><?php echo $_SESSION['balance']; ?> credits</strong></p>

    <?php if ($message) echo "<p>$message</p>"; ?>

    <?php if (!$_SESSION['dice_active']): ?>
        <!-- Bet confirmation form -->
        <form method="post">
            Bet amount: <input type="number" name="bet" min="1" required>
            <input type="hidden" name="action" value="confirm_bet">
            <input type="submit" value="Bet Confirm">
        </form>
    <?php else: ?>
        <!-- Active game controls -->
        <p>Current bet: <strong><?php echo $_SESSION['dice_bet']; ?> credits</strong></p>
        <form method="post" style="display:inline;">
            <input type="hidden" name="action" value="roll">
            <input type="submit" value="Roll Dice">
        </form>
        <form method="post" style="display:inline;">
            <input type="hidden" name="action" value="stop">
            <input type="submit" value="Stop">
        </form>
        <form method="post" style="display:inline;">
            <input type="hidden" name="action" value="exit">
            <input type="submit" value="Exit / Try Again">
        </form>
    <?php endif; ?>

    <!-- Always display dice areas, with default d1.png images if no roll yet -->
    <div class="game-area">
        <div class="player-area">
            <h3>You</h3>
            <div class="dice">
                <img src="<?php echo $player_dice1 !== null ? "d$player_dice1.png" : defaultDieImage(); ?>" alt="dice">
            </div>
            <div class="dice">
                <img src="<?php echo $player_dice2 !== null ? "d$player_dice2.png" : defaultDieImage(); ?>" alt="dice">
            </div>
            <p>Total: <?php echo $player_sum !== null ? $player_sum : '?'; ?></p>
        </div>
        <div class="banker-area">
            <h3>Banker</h3>
            <div class="dice">
                <img src="<?php echo $banker_dice1 !== null ? "d$banker_dice1.png" : defaultDieImage(); ?>" alt="dice">
            </div>
            <div class="dice">
                <img src="<?php echo $banker_dice2 !== null ? "d$banker_dice2.png" : defaultDieImage(); ?>" alt="dice">
            </div>
            <p>Total: <?php echo $banker_sum !== null ? $banker_sum : '?'; ?></p>
        </div>
    </div>

    <br><br>
    <button onclick="location.href='Homepage.php'">Back to Homepage</button>
</body>
</html>
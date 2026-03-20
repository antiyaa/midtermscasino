<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// Define the six colors
$colors = ['Red', 'Green', 'Blue', 'Yellow', 'Purple', 'Orange'];

// Initialize session variables for the game
if (!isset($_SESSION['color_bet'])) {
    $_SESSION['color_bet'] = null;        // current bet amount
}
if (!isset($_SESSION['color_active'])) {
    $_SESSION['color_active'] = false;    // true if bet is confirmed
}
if (!isset($_SESSION['color_chosen'])) {
    $_SESSION['color_chosen'] = null;     // chosen color
}

$message = '';
$die1 = null;
$die2 = null;
$match_count = 0;

// Handle actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'exit') {
    // Clear game state and go back to homepage
    unset($_SESSION['color_bet'], $_SESSION['color_chosen']);
    $_SESSION['color_active'] = false;
    header('Location: Homepage.php');
    exit;
}

if ($action === 'stop') {
    // Clear game state, return to bet entry
    unset($_SESSION['color_bet'], $_SESSION['color_chosen']);
    $_SESSION['color_active'] = false;
    $message = "Game stopped. You can place a new bet.";
}

if ($action === 'confirm_bet') {
    $bet = (int)($_POST['bet'] ?? 0);
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
        $message = "Bet confirmed: $bet credits on $chosen_color. Click Roll Dice to play.";
    }
}

if ($action === 'roll' && $_SESSION['color_active']) {
    $bet = $_SESSION['color_bet'];
    $chosen = $_SESSION['color_chosen'];
    if ($bet > $_SESSION['balance']) {
        $message = "Insufficient balance to continue. Please stop and try again with a lower bet.";
    } else {
        // Roll the two dice (random colors)
        $die1 = $colors[array_rand($colors)];
        $die2 = $colors[array_rand($colors)];

        // Count matches
        $match_count = 0;
        if ($die1 === $chosen) $match_count++;
        if ($die2 === $chosen) $match_count++;

        // Deduct bet
        $_SESSION['balance'] -= $bet;

        // Determine result and payout
        if ($match_count == 0) {
            $message = "You rolled $die1 and $die2. You chose $chosen. No match. You lose $bet credits. New balance: {$_SESSION['balance']}.";
        } elseif ($match_count == 1) {
            $winnings = $bet * 2; // total payout = 2x bet
            $_SESSION['balance'] += $winnings;
            $message = "You rolled $die1 and $die2. You chose $chosen. One match! You win $winnings credits (profit " . ($winnings - $bet) . "). New balance: {$_SESSION['balance']}.";
        } else { // match_count == 2
            $winnings = $bet * 3; // total payout = 3x bet
            $_SESSION['balance'] += $winnings;
            $message = "You rolled $die1 and $die2. You chose $chosen. Both match! You win $winnings credits (profit " . ($winnings - $bet) . "). New balance: {$_SESSION['balance']}.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Color Dice Game</title>
    <style>
        /* Minimal styling for dice areas */
        .dice-area { margin: 20px 0; }
        .die {
            display: inline-block;
            width: 80px;
            height: 80px;
            margin: 10px;
            border: 1px solid #333;
            text-align: center;
            line-height: 80px;
            font-weight: bold;
            color: white;
        }
        button { margin: 5px; padding: 5px 10px; }
    </style>
</head>
<body>
    <h2>Color Dice Game - 2 Dice, 6 Colors</h2>
    <p>Your balance: <strong><?php echo $_SESSION['balance']; ?> credits</strong></p>

    <?php if ($message) echo "<p>$message</p>"; ?>

    <?php if (!$_SESSION['color_active']): ?>
        <!-- Bet confirmation form -->
        <form method="post">
            Bet amount: <input type="number" name="bet" min="1" required>
            <br><br>
            <strong>Choose a color:</strong><br>
            <?php foreach ($colors as $color): ?>
                <label><input type="radio" name="color" value="<?php echo $color; ?>" required> <?php echo $color; ?></label><br>
            <?php endforeach; ?>
            <br>
            <input type="hidden" name="action" value="confirm_bet">
            <input type="submit" value="Confirm Bet">
        </form>
    <?php else: ?>
        <!-- Active game controls -->
        <p>Current bet: <strong><?php echo $_SESSION['color_bet']; ?> credits</strong> on <strong><?php echo $_SESSION['color_chosen']; ?></strong></p>
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

    <!-- Always display dice areas, with black default if no roll yet -->
    <div class="dice-area">
        <h3>Dice Results:</h3>
        <div class="die" style="background-color: <?php echo $die1 !== null ? strtolower($die1) : 'black'; ?>;">
            <?php echo $die1 !== null ? $die1 : '&nbsp;'; ?>
        </div>
        <div class="die" style="background-color: <?php echo $die2 !== null ? strtolower($die2) : 'black'; ?>;">
            <?php echo $die2 !== null ? $die2 : '&nbsp;'; ?>
        </div>
        <p>Matches: <?php echo $die1 !== null ? $match_count : '-'; ?></p>
    </div>

    <br><br>
    <button onclick="location.href='Homepage.php'">Back to Homepage</button>
</body>
</html>
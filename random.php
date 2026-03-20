<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// Initialize session variables for this game (optional)
if (!isset($_SESSION['random_last_number'])) {
    $_SESSION['random_last_number'] = null;
}
if (!isset($_SESSION['random_last_option'])) {
    $_SESSION['random_last_option'] = null;
}

$message = '';
$bet = null;
$selected_option = null;
$random_number = null;
$win = false;
$payout_multiplier = 0;

// Handle game play
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['play'])) {
    $bet = (int)($_POST['bet'] ?? 0);
    $selected_option = $_POST['option'] ?? '';

    // Validation
    if ($bet <= 0) {
        $message = "Bet amount must be positive.";
    } elseif ($bet > $_SESSION['balance']) {
        $message = "Insufficient balance. Your balance is {$_SESSION['balance']} credits.";
    } elseif (!in_array($selected_option, ['odd', 'even', 'lucky7', '1-15', '16-30'])) {
        $message = "Please select a valid betting option.";
    } else {
        // Generate random number 1-30
        $random_number = rand(1, 30);

        // Determine win/loss and payout multiplier
        switch ($selected_option) {
            case 'odd':
                $win = ($random_number % 2 == 1);
                $payout_multiplier = 2; // 2x total payout (net profit = bet)
                break;
            case 'even':
                $win = ($random_number % 2 == 0);
                $payout_multiplier = 2;
                break;
            case 'lucky7':
                $win = ($random_number == 7);
                $payout_multiplier = 15; // 15x total payout
                break;
            case '1-15':
                $win = ($random_number >= 1 && $random_number <= 15);
                $payout_multiplier = 2;
                break;
            case '16-30':
                $win = ($random_number >= 16 && $random_number <= 30);
                $payout_multiplier = 2;
                break;
        }

        // Deduct bet first
        $_SESSION['balance'] -= $bet;

        // If win, add winnings (total payout = bet * multiplier)
        if ($win) {
            $winnings = $bet * $payout_multiplier;
            $_SESSION['balance'] += $winnings;
            $message = "Random number: $random_number. You selected: $selected_option. You won! You receive $winnings credits (profit " . ($winnings - $bet) . "). New balance: {$_SESSION['balance']}.";
        } else {
            $message = "Random number: $random_number. You selected: $selected_option. You lost. You lose $bet credits. New balance: {$_SESSION['balance']}.";
        }

        // Save for display
        $_SESSION['random_last_number'] = $random_number;
        $_SESSION['random_last_option'] = $selected_option;
    }
}

// Reset last game display (optional, so that after a new page load it clears)
if (isset($_GET['reset'])) {
    $_SESSION['random_last_number'] = null;
    $_SESSION['random_last_option'] = null;
    header('Location: random.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Random Number Game</title>
</head>
<body>
    <h2>Random Number Game (1-30)</h2>
    <p>Your balance: <strong><?php echo $_SESSION['balance']; ?> credits</strong></p>

    <?php if ($message): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>

    <!-- Game form -->
    <form method="post">
        <label for="bet">Bet amount:</label>
        <input type="number" name="bet" id="bet" min="1" required value="<?php echo htmlspecialchars($bet ?? ''); ?>">
        <br><br>
        <strong>Select an option:</strong><br>
        <label><input type="radio" name="option" value="odd" required> Odd (pays 2x)</label><br>
        <label><input type="radio" name="option" value="even"> Even (pays 2x)</label><br>
        <label><input type="radio" name="option" value="lucky7"> Lucky 7 (pays 15x)</label><br>
        <label><input type="radio" name="option" value="1-15"> 1-15 (pays 2x)</label><br>
        <label><input type="radio" name="option" value="16-30"> 16-30 (pays 2x)</label><br>
        <br>
        <input type="submit" name="play" value="Play">
    </form>

    <!-- Show last game result if available -->
    <?php if ($_SESSION['random_last_number'] !== null): ?>
        <hr>
        <h3>Last Game Result</h3>
        <p>Random number: <?php echo $_SESSION['random_last_number']; ?></p>
        <p>Your option: <?php echo $_SESSION['random_last_option']; ?></p>
    <?php endif; ?>

    <br>
    <button onclick="location.href='random.php?reset=1'">Clear Last Result</button>
    <button onclick="location.href='Homepage.php'">Back to Homepage</button>
</body>
</html>
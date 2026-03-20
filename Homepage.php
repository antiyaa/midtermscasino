<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    <p>Your balance: <strong><?php echo $_SESSION['balance']; ?> credits</strong></p>

    <button onclick="location.href='topup.php'">Top Up</button><br><br>
    <button onclick="location.href='logout.php'">Logout</button><br><br>
    <button onclick="location.href='dice.php'">Dice Game</button><br><br>
    <button onclick="location.href='random.php'">Random Number Game</button><br><br>
    <button onclick="location.href='inbetween.php'">In Between Game</button>
</body>
</html>
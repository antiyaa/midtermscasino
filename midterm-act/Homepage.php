<?php
session_start();
// Uncomment this in your actual file to enforce login
// if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
//     header('Location: index.php');
//     exit;
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Casino Ni Lady</title>
    <link rel="stylesheet" href="home.css">
</head>
<body>

    <header>
        <h1>CASINO NI LADY</h1>
    </header>

    <div class="layout-container">
        <aside class="sidebar">
            <div class="user-profile">
                <div class="avatar">👤</div>
                <h2><?php echo htmlspecialchars($_SESSION['username'] ?? 'Kpop Idol'); ?></h2>
            </div>
            
            <div class="balance-section">
                <span class="balance-label">Balance:</span>
                <span class="balance-amount">-₱<?php echo htmlspecialchars($_SESSION['balance'] ?? '9999999999'); ?></span>
            </div>

            <div class="sidebar-actions">
                <button class="btn-green" onclick="location.href='topup.php'">DEPOSIT</button>
                <button class="btn-green" onclick="location.href='withdraw.php'">WITHDRAW</button>
            </div>

            <button class="btn-red" onclick="location.href='logout.php'">LOG OUT</button>
        </aside>

        <main class="main-content">
            
            <div class="banner">
                <img src="endorser.jpg" alt="Endorser">
                <h2>Endorsed by the number 1 Filipino Kpop Idol</h2>
            </div>

            <div class="games-section">
                <h3>POPULAR GAMES</h3>
                <div class="games-grid">
                    
                    <a href="random.php" class="game-card">
                        <span class="help-icon">?</span>
                        <img src="rngicon.png" alt="Random Number">
                        <p>Random Number</p>
                    </a>

                    <a href="dice.php" class="game-card">
                        <span class="help-icon">?</span>
                        <img src="diceicon.png" alt="Dice Game">
                        <p>Dice Game</p>
                    </a>

                    <a href="inbetween.php" class="game-card">
                        <span class="help-icon">?</span>
                        <img src="coloricon.png" alt="In Between">
                        <p>in-between<br>or outside?</p>
                    </a>

                    <div class="game-card coming-soon">
                        <p>COMING<br>SOON</p>
                    </div>

                </div>
            </div>

        </main>
    </div>

</body>
</html>
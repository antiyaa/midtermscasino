<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}
$balance_display = number_format($_SESSION['balance']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Casino Ni Lady</title>
    <style>

/* --- RESET --- */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* --- COLOR VARIABLES --- */
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

/* --- BODY --- */
body {
    background: radial-gradient(circle at top, #1C0A08, #0E0500);
    display: flex;
    flex-direction: column;
    height: 100vh;
    overflow: hidden;
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
}

/* LEFT: logo image + title */
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
}

/* RIGHT: divider + balance + deposit */
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
    font-family: var(--sans);
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

/* --- MAIN --- */
.main-content {
    flex: 1;
    padding: 36px 40px;
    overflow-y: auto;
}

.main-content::-webkit-scrollbar { width: 4px; }
.main-content::-webkit-scrollbar-track { background: transparent; }
.main-content::-webkit-scrollbar-thumb { background: rgba(212,160,23,0.3); border-radius: 4px; }

/* --- BANNER --- */
.banner {
    width: 100%;
    height: 200px;
    border-radius: 14px;
    border: 1px solid var(--burnished-gold);
    box-shadow: 0 0 22px var(--gold-glow);
    margin-bottom: 40px;
    overflow: hidden;
}

.banner img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center top;
    display: block;
    transition: transform 0.4s ease;
}

.banner:hover img {
    transform: scale(1.03);
}

/* --- GAMES SECTION --- */
.games-section h3 {
    font-size: 2rem;
    letter-spacing: 3px;
    color: var(--text-muted);
    margin-bottom: 20px;
    font-family: 'Times New Roman', serif;
    font-weight: normal;
}

/* GRID */
.games-grid {
    display: flex;
    gap: 18px;
    flex-wrap: wrap;
}

/* --- GAME CARD --- */
.game-card {
    width: 300px;
    height: 300px;
    border-radius: 12px;
    position: relative;
    overflow: hidden;
    border: 1px solid var(--gold-dim);
    cursor: pointer;
    text-decoration: none;
    display: block;
    background: #1C0A08;
    transition: border-color 0.25s, transform 0.25s;
}

.game-card:hover {
    border-color: var(--burnished-gold);
    transform: translateY(-6px) scale(1.04);
}

.game-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.35s ease;
}

.game-card:hover img {
    transform: scale(1.08);
}

/* HOVER OVERLAY */
.game-overlay {
    position: absolute;
    inset: 0;
    background: rgba(14,5,0,0.82);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    opacity: 0;
    transition: opacity 0.25s ease;
}

.game-card:hover .game-overlay {
    opacity: 1;
}

.overlay-title {
    color: var(--burnished-gold);
    font-size: 1rem;
    font-weight: 600;
    letter-spacing: 1px;
    text-align: center;
    padding: 0 14px;
    font-family: 'Times New Roman', serif;
}

.overlay-desc {
    color: rgba(245,223,160,0.75);
    font-size: 0.72rem;
    line-height: 1.6;
    text-align: center;
    padding: 0 18px;
    margin: 2px 0 4px;
    font-family: var(--sans);
}

.overlay-play {
    background: var(--imperial-red);
    color: var(--warm-champagne);
    font-size: 0.68rem;
    font-weight: bold;
    letter-spacing: 2px;
    padding: 7px 20px;
    border-radius: 20px;
    border: 1px solid rgba(212,160,23,0.25);
    font-family: var(--sans);
}

.game-card-bar {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, var(--burnished-gold), transparent);
    opacity: 0;
    transition: opacity 0.25s;
}

.game-card:hover .game-card-bar {
    opacity: 1;
}

.help-icon {
    position: absolute;
    top: 8px;
    right: 8px;
    border: 1px solid rgba(212,160,23,0.5);
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 11px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--burnished-gold);
    background: rgba(14,5,0,0.6);
    z-index: 2;
    font-family: var(--sans);
}

.coming-soon {
    opacity: 0.4;
    border: 1px dashed rgba(212,160,23,0.25) !important;
    cursor: default;
    display: flex;
    align-items: center;
    justify-content: center;
}

.coming-soon:hover {
    transform: none !important;
    border-color: rgba(212,160,23,0.25) !important;
}

.coming-soon p {
    color: rgba(245,223,160,0.45);
    font-size: 0.9rem;
    letter-spacing: 1.5px;
    text-align: center;
    line-height: 1.7;
    font-family: var(--sans);
}

    </style>
</head>
<body>

    <header>
        <!-- LEFT: Logo image + Casino name -->
        <div class="header-left">
            <img src="logo.png" alt="Casino Ni Lady Logo" class="header-logo">
            <h1>AURUM</h1>
        </div>

        <!-- RIGHT: Divider + Balance + Deposit -->
        <div class="header-right">
            <div class="header-divider"></div>
            <div class="header-balance">
                <span class="balance-label">Your Balance</span>
                <span class="balance-amount">₱<?php echo htmlspecialchars($balance_display); ?></span>
            </div>
            <button class="btn-deposit" onclick="location.href='topup.php'">+ TOP UP</button>
        </div>
    </header>

    <main class="main-content">

        <div class="banner">
            <img src="banner1.png" alt="Endorser">
        </div>

        <div class="games-section">
            <h3>POPULAR GAMES</h3>
            <div class="games-grid">
                <a href="random.php" class="game-card">
                    <span class="help-icon">?</span>
                    <img src="rnggame.jpg" alt="Random Number">
                    <div class="game-overlay">
                        <span class="overlay-title">Random Number</span>
                        <span class="overlay-desc">Step into the thrill of chance—choose your lucky pattern, place your bet, and watch fortune unfold. Every spin could turn your luck into big rewards.</span>
                        <span class="overlay-play">PLAY NOW</span>
                    </div>
                    <div class="game-card-bar"></div>
                </a>

                <a href="dice.php" class="game-card">
                    <span class="help-icon">?</span>
                    <img src="dicegame1.png" alt="Dice Game">
                    <div class="game-overlay">
                        <span class="overlay-title">Dice Game</span>
                        <span class="overlay-desc">VS Banker and let the dice decide the winner. Simple, fast, and exciting with every roll bringing a chance to win.</span>
                        <span class="overlay-play">PLAY NOW</span>
                    </div>
                    <div class="game-card-bar"></div>
                </a>

                <a href="inbetween.php" class="game-card">
                    <span class="help-icon">?</span>
                    <img src="colorgame1.png" alt="Color Game">
                    <div class="game-overlay">
                        <span class="overlay-title">Color Game</span>
                        <span class="overlay-desc">Pick your color, trust your instincts, and let the game paint your destiny. Bold choices bring bold rewards in this vibrant game of chance.</span>
                        <span class="overlay-play">PLAY NOW</span>
                    </div>
                    <div class="game-card-bar"></div>
                </a>

                <div class="game-card coming-soon">
                    <p>COMING<br>SOON</p>
                </div>

            </div>
        </div>

    </main>

</body>
</html>
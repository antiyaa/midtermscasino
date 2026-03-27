<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['dice_bet']))        $_SESSION['dice_bet']        = null;
if (!isset($_SESSION['dice_active']))     $_SESSION['dice_active']     = false;
if (!isset($_SESSION['dice_current_bet'])) $_SESSION['dice_current_bet'] = 10; // default bet

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

// ---------- ADJUST BET (with MAX button) ----------
if ($action === 'adjust' && !$_SESSION['dice_active']) {
    $adjust = $_POST['adjust'] ?? '';
    $current = $_SESSION['dice_current_bet'];
    switch ($adjust) {
        case '-100': $new = $current - 100; break;
        case '-10':  $new = $current - 10;  break;
        case '+10':  $new = $current + 10;  break;
        case '+100': $new = $current + 100; break;
        case 'max':  $new = 5000;           break;
        default:     $new = $current;
    }
    $new = max(10, min(5000, $new));
    $_SESSION['dice_current_bet'] = $new;
    // No message – just refresh the displayed bet
}

// ---------- CONFIRM BET ----------
if ($action === 'confirm_bet' && !$_SESSION['dice_active']) {
    $bet = $_SESSION['dice_current_bet']; // use the adjusted value
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

// ---------- ROLL ----------
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
            $message         = "You won! +₱" . $bet . " profit. New balance: ₱" . number_format($_SESSION['balance']) . ".";
            $show_win_banner = true;
        } elseif ($player_sum < $banker_sum) {
            $message          = "Banker wins. You lost ₱$bet. New balance: ₱" . number_format($_SESSION['balance']) . ".";
            $show_loss_banner = true;
        } else {
            $_SESSION['balance'] += $bet;
            $message         = "It's a tie! Bet returned. Balance: ₱" . number_format($_SESSION['balance']) . ".";
            $show_tie_banner = true;
        }
    }
}

function getDiceImage($value) {
    if ($value === null) return "d1.png";
    return "d$value.png";
}

$balance_display = number_format($_SESSION['balance']);
$current_bet_display = number_format($_SESSION['dice_current_bet']);
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
            background: linear-gradient(45deg, #1C0A08, #2a100a);
            color: var(--warm-champagne);
            border: 1px solid rgba(212,160,23,0.35);
            padding: 11px 22px;
            font-weight: 600;
            font-size: 0.78rem;
            letter-spacing: 1.5px;
            cursor: pointer;
            border-radius: 2px;
            transition: 0.3s;
            white-space: nowrap;
            font-family: var(--sans);
            text-decoration: none;
            display: inline-block;
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

        /* --- HELP ICON (CSS-only modal trigger) --- */
        .help-toggle { display: none; }

        .help-icon {
            position: absolute; top: 14px; right: 14px;
            border: 1px solid rgba(212,160,23,0.5);
            border-radius: 50%; width: 28px; height: 28px;
            display: flex; justify-content: center; align-items: center;
            color: var(--burnished-gold);
            background: rgba(14,5,0,0.6);
            font-size: 13px; font-family: var(--sans);
            cursor: pointer; font-weight: bold;
            transition: 0.2s;
            user-select: none;
            z-index: 10;
        }

        .help-icon:hover {
            background: rgba(212,160,23,0.15);
            box-shadow: 0 0 10px var(--gold-glow);
        }

        /* --- MODAL OVERLAY (CSS-only) --- */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0; z-index: 1000;
            background: rgba(0,0,0,0.78);
            backdrop-filter: blur(3px);
            align-items: center; justify-content: center;
        }

        /* Show modal when checkbox is checked */
        .help-toggle:checked ~ .modal-overlay {
            display: flex;
        }

        .modal-box {
            background: linear-gradient(160deg, #1C0A08 60%, #2a0e08);
            border: 2px solid var(--burnished-gold);
            border-radius: 16px;
            padding: 40px 44px;
            max-width: 560px;
            width: 92%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 0 60px rgba(212,160,23,0.25), 0 0 120px rgba(0,0,0,0.8);
            position: relative;
            animation: modalIn 0.3s ease-out;
        }

        /* Scrollbar styling for modal */
        .modal-box::-webkit-scrollbar {
            width: 6px;
        }
        .modal-box::-webkit-scrollbar-track {
            background: rgba(212,160,23,0.1);
            border-radius: 8px;
        }
        .modal-box::-webkit-scrollbar-thumb {
            background: var(--burnished-gold);
            border-radius: 8px;
        }

        @keyframes modalIn {
            0%  { transform: scale(0.85) translateY(20px); opacity: 0; }
            100%{ transform: scale(1) translateY(0);        opacity: 1; }
        }

        /* Decorative corner accents */
        .modal-box::before,
        .modal-box::after {
            content: '';
            position: absolute;
            width: 18px; height: 18px;
            border-color: var(--burnished-gold);
            border-style: solid;
        }
        .modal-box::before { top: 10px; left: 10px; border-width: 2px 0 0 2px; border-radius: 4px 0 0 0; }
        .modal-box::after  { bottom: 10px; right: 10px; border-width: 0 2px 2px 0; border-radius: 0 0 4px 0; }

        .modal-close {
            position: absolute; top: 14px; right: 16px;
            width: 30px; height: 30px;
            border-radius: 50%;
            border: 1px solid rgba(212,160,23,0.4);
            background: rgba(14,5,0,0.7);
            color: var(--burnished-gold);
            font-size: 1rem; font-weight: bold;
            cursor: pointer; display: flex;
            align-items: center; justify-content: center;
            text-decoration: none;
            transition: 0.2s;
            line-height: 1;
        }

        .modal-close:hover {
            background: rgba(212,160,23,0.18);
            box-shadow: 0 0 10px var(--gold-glow);
        }

        .modal-title {
            font-family: 'Times New Roman', serif;
            font-size: 1.9rem;
            color: var(--burnished-gold);
            text-align: center;
            letter-spacing: 3px;
            margin-bottom: 6px;
            text-shadow: 0 0 12px rgba(212,160,23,0.35);
        }

        .modal-subtitle {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.75rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 28px;
        }

        .modal-divider {
            border: none;
            border-top: 1px solid rgba(212,160,23,0.2);
            margin: 18px 0;
        }

        .rule-section { margin-bottom: 22px; }

        .rule-section-title {
            font-family: 'Times New Roman', serif;
            font-size: 1.05rem;
            color: var(--burnished-gold);
            letter-spacing: 2px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rule-section-title::before {
            content: '';
            display: inline-block;
            width: 24px; height: 1px;
            background: var(--burnished-gold);
            opacity: 0.5;
        }

        .rule-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }

        .rule-table th {
            background: rgba(212,160,23,0.08);
            color: var(--burnished-gold);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: 0.72rem;
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid rgba(212,160,23,0.2);
            font-family: var(--sans);
        }

        .rule-table td {
            padding: 9px 12px;
            color: var(--warm-champagne);
            border-bottom: 1px solid rgba(212,160,23,0.07);
            font-family: var(--sans);
            vertical-align: middle;
        }

        .rule-table tr:last-child td { border-bottom: none; }

        .rule-table tr:hover td { background: rgba(212,160,23,0.04); }

        .badge {
            display: inline-block;
            border: 1px solid rgba(212,160,23,0.4);
            border-radius: 4px;
            padding: 2px 8px;
            font-size: 0.75rem;
            color: var(--burnished-gold);
            background: rgba(212,160,23,0.08);
            letter-spacing: 1px;
            font-family: var(--sans);
        }

        .rule-list {
            list-style: none;
            font-size: 0.88rem;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .rule-list li {
            padding-left: 20px;
            position: relative;
            color: var(--warm-champagne);
            line-height: 1.5;
            font-family: var(--sans);
        }

        .rule-list li::before {
            content: '◆';
            position: absolute; left: 0;
            color: var(--burnished-gold);
            font-size: 0.55rem;
            top: 5px;
        }

        .modal-note {
            background: rgba(212,160,23,0.05);
            border: 1px solid rgba(212,160,23,0.15);
            border-left: 3px solid var(--burnished-gold);
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 20px;
            font-family: var(--sans);
            line-height: 1.5;
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

        .bet-input-wrap {
            text-align: center;
            margin-bottom: 16px;
        }
        .bet-number-input {
            background: #0E0500;
            border: 1px solid var(--burnished-gold);
            border-radius: 8px;
            color: var(--burnished-gold);
            font-size: 1.4rem;
            font-weight: bold;
            font-family: var(--sans);
            width: 100%;
            text-align: center;
            padding: 10px;
            outline: none;
            pointer-events: none; /* read-only, adjusted by buttons */
        }
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
            margin: 4px 0 14px;
        }

        .btn-confirm {
            width: 100%;
            background: rgba(212,160,23,0.08);
            border: 1px solid var(--burnished-gold);
            color: var(--burnished-gold);
            padding: 12px;
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

        /* Fixed-size indicator container - no layout shift */
        .result-indicator {
            background: #1C0A08;
            border: 1px solid var(--gold-dim);
            border-left: 3px solid var(--burnished-gold);
            border-radius: 8px;
            padding: 14px 18px;
            min-height: 85px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--warm-champagne);
            font-family: var(--sans);
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            word-break: break-word;
            box-sizing: border-box;
            margin-bottom: 16px;
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

    <!-- CSS-only modal checkbox (must be before modal overlay) -->
    <input type="checkbox" id="helpToggle" class="help-toggle">

    <!-- MODAL OVERLAY -->
    <div class="modal-overlay">
        <div class="modal-box">
            <label for="helpToggle" class="modal-close" title="Close">✕</label>

            <div class="modal-title">HOW TO PLAY</div>
            <div class="modal-subtitle">Rules &amp; Guidelines — Dice Game</div>

            <hr class="modal-divider">

            <!-- Objective -->
            <div class="rule-section">
                <div class="rule-section-title">OBJECTIVE</div>
                <ul class="rule-list">
                    <li>Roll two dice for the <strong>Player</strong> and two dice for the <strong>Banker</strong>.</li>
                    <li>Your goal is to have a higher total than the Banker.</li>
                    <li>If your total is higher, you win! If lower, you lose. Ties return your bet.</li>
                </ul>
            </div>

            <hr class="modal-divider">

            <!-- Betting & Payouts -->
            <div class="rule-section">
                <div class="rule-section-title">BETTING &amp; PAYOUTS</div>
                <table class="rule-table">
                    <thead>
                        <tr><th>Outcome</th><th>Payout</th><th>Example (Bet ₱100)</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Player wins (sum > Banker)</td><td>2× your bet</td><td>You receive ₱200 (₱100 profit)</td></tr>
                        <tr><td>Banker wins (sum < Banker)</td><td>Bet lost</td><td>You lose ₱100</td></tr>
                        <tr><td>Tie (sums equal)</td><td>Bet returned</td><td>You get back ₱100</td></tr>
                    </tbody>
                </table>
            </div>

            <hr class="modal-divider">

            <!-- How to Play -->
            <div class="rule-section">
                <div class="rule-section-title">HOW TO PLAY</div>
                <ul class="rule-list">
                    <li><strong>Step 1 — Set Your Bet:</strong> Use the +/− buttons to adjust your wager (min ₱10 / max ₱5,000). The MAX button sets the bet to your current balance (up to ₱5,000).</li>
                    <li><strong>Step 2 — Confirm Bet:</strong> Click <em>CONFIRM BET</em> to lock your bet for the round.</li>
                    <li><strong>Step 3 — Roll Dice:</strong> Press <em>ROLL THE DICE</em> to see the result. The game will show both dice sets and announce the winner.</li>
                    <li><strong>After Each Round:</strong> You can either roll again (same bet) or click <em>END THE GAME / CHANGE BET</em> to stop and place a new bet.</li>
                </ul>
            </div>

            <hr class="modal-divider">

            <!-- Payout Example -->
            <div class="rule-section">
                <div class="rule-section-title">PAYOUT EXAMPLE</div>
                <ul class="rule-list">
                    <li>Bet ₱200 → Player dice: 4+5 = 9, Banker dice: 2+3 = 5 → <strong>You win ₱400</strong> (₱200 profit).</li>
                    <li>Bet ₱500 → Player dice: 1+2 = 3, Banker dice: 4+4 = 8 → <strong>You lose ₱500</strong>.</li>
                    <li>Bet ₱100 → Player dice: 3+3 = 6, Banker dice: 2+4 = 6 → <strong>Tie – bet returned</strong>.</li>
                </ul>
            </div>

            <div class="modal-note">
                ⚠ &nbsp;Please gamble responsibly. Top up only what you can afford. Results are randomly generated and cannot be predicted or influenced.
            </div>
        </div>
    </div>

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
                    <!-- Help icon: clicking this label checks the hidden checkbox -->
                    <label for="helpToggle" class="help-icon" title="Rules &amp; Help">?</label>

                    <?php if (!$_SESSION['dice_active']): ?>
                        <div class="panel-title">PLACE YOUR BET</div>

                        <div class="payout-row">
                            <div class="payout-pill">Win <strong>2×</strong></div>
                            <div class="payout-pill">Tie — Bet returned</div>
                            <div class="payout-pill">Loss — Bet lost</div>
                        </div>

                        <div class="bet-balance-row">
                            <span class="bal-label">YOUR BALANCE</span>
                            <span class="bal-value">₱<?php echo number_format($_SESSION['balance']); ?></span>
                        </div>
                        <form method="post">
                            <div class="bet-input-wrap">
                                <input type="text" class="bet-number-input" value="₱<?php echo $current_bet_display; ?>">
                            </div>

                            <div class="adjust-buttons">
                                <button type="submit" name="adjust" value="-100" class="adjust-btn">-100</button>
                                <button type="submit" name="adjust" value="-10"  class="adjust-btn">-10</button>
                                <button type="submit" name="adjust" value="max"   class="adjust-btn">MAX</button>
                                <button type="submit" name="adjust" value="+10"  class="adjust-btn">+10</button>
                                <button type="submit" name="adjust" value="+100" class="adjust-btn">+100</button>
                            </div>
                            <input type="hidden" name="action" value="adjust">
                        </form>

                        <div class="minmax-row">
                            <span>MIN: 10</span><span>MAX: 5000</span>
                        </div>

                        <div class="gold-sep"></div>
                        <form method="post">
                            <input type="hidden" name="action" value="confirm_bet">
                            <button type="submit" class="btn-confirm">CONFIRM BET</button>
                        </form>

                    <?php else: ?>
                        <div class="panel-title">CURRENT BET</div>
                        <div class="info-pills">
                            <div class="info-pill">
                                <span class="pill-label">BALANCE</span>
                                <span class="pill-value">₱<?php echo number_format($_SESSION['balance']); ?></span>
                            </div>
                            <div class="info-pill">
                                <span class="pill-label">YOUR BET</span>
                                <span class="pill-value">₱<?php echo number_format($_SESSION['dice_bet']); ?></span>
                            </div>
                            <div class="info-pill">
                                <span class="pill-label">POTENTIAL WIN</span>
                                <span class="pill-value">₱<?php echo number_format($_SESSION['dice_bet'] * 2); ?></span>
                            </div>
                        </div>

                        <p class="active-note">Game in progress — roll or end below.</p>
                    <?php endif; ?>

                </div>
            </div>
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

                    <!-- Fixed-size indicator container -->
                    <div class="result-indicator">
                        <?php echo $message ? htmlspecialchars($message) : "Awaiting action — confirm your bet and roll the dice."; ?>
                    </div>

                    <?php if ($_SESSION['dice_active']): ?>
                    <div class="action-buttons">
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="roll">
                            <button type="submit" class="btn-roll">ROLL THE DICE</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="stop">
                            <button type="submit" class="btn-secondary">END THE GAME / CHANGE BET</button>
                        </form>
                    </div>
                    <?php endif; ?>

                </div><!-- /result-panel -->
            </div><!-- /col-right -->

        </div><!-- /columns -->

    </div><!-- /game-wrapper -->

</body>
</html>
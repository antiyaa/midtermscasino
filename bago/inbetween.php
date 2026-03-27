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

if (!isset($_SESSION['color_bet']))      $_SESSION['color_bet']      = null;
if (!isset($_SESSION['color_active']))   $_SESSION['color_active']   = false;
if (!isset($_SESSION['color_chosen']))   $_SESSION['color_chosen']   = null;
if (!isset($_SESSION['color_bet_type'])) $_SESSION['color_bet_type'] = null; // 'one' or 'two'

$message         = '';
$die1            = null;
$die2            = null;
$match_count     = 0;
$show_win_banner  = false;
$show_loss_banner = false;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'exit') {
    unset($_SESSION['color_bet'], $_SESSION['color_chosen'], $_SESSION['color_bet_type']);
    $_SESSION['color_active'] = false;
    header('Location: Homepage.php');
    exit;
}

if ($action === 'stop') {
    unset($_SESSION['color_bet'], $_SESSION['color_chosen'], $_SESSION['color_bet_type']);
    $_SESSION['color_active'] = false;
    $message = "Game stopped. You can place a new bet.";
}

// ---------- ADJUST BET (with ALL IN button) ----------
if ($action === 'adjust' && !$_SESSION['color_active']) {
    if (isset($_POST['color']) && in_array($_POST['color'], $colors)) {
        $_SESSION['color_chosen'] = $_POST['color'];
    }
    if (isset($_POST['bet_type']) && in_array($_POST['bet_type'], ['one', 'two'])) {
        $_SESSION['color_bet_type'] = $_POST['bet_type'];
    }
    $adjust = $_POST['adjust'] ?? '';
    $current_bet = $_SESSION['current_bet'];
    $balance = $_SESSION['balance'];
    
    if ($adjust === 'allin') {
        $new_bet = min($balance, 5000);
    } else {
        switch ($adjust) {
            case '-100': $new_bet = $current_bet - 100; break;
            case '-10':  $new_bet = $current_bet - 10;  break;
            case '+10':  $new_bet = $current_bet + 10;  break;
            case '+100': $new_bet = $current_bet + 100; break;
            default:     $new_bet = $current_bet;
        }
    }
    
    $new_bet = max(10, min(5000, $new_bet));
    $_SESSION['current_bet'] = $new_bet;
    
    if ($_SESSION['color_active']) {
        unset($_SESSION['color_bet'], $_SESSION['color_chosen'], $_SESSION['color_bet_type']);
        $_SESSION['color_active'] = false;
        $message = "Bet amount changed. Please confirm your new bet.";
    }
}

// ---------- CONFIRM BET ----------
if ($action === 'confirm_bet' && !$_SESSION['color_active']) {
    $bet          = $_SESSION['current_bet'];
    $chosen_color = $_POST['color'] ?? '';
    $bet_type     = $_POST['bet_type'] ?? '';
    if ($bet <= 0) {
        $message = "Bet must be positive.";
    } elseif ($bet > $_SESSION['balance']) {
        $message = "Insufficient balance. Your balance is " . $_SESSION['balance'] . ".";
    } elseif (!in_array($chosen_color, $colors)) {
        $message = "Please select a valid color.";
    } elseif (!in_array($bet_type, ['one', 'two'])) {
        $message = "Please select a bet type (1 MATCH or 2 MATCHES).";
    } else {
        $_SESSION['color_bet']      = $bet;
        $_SESSION['color_chosen']   = $chosen_color;
        $_SESSION['color_bet_type'] = $bet_type;
        $_SESSION['color_active']   = true;
        $type_text = ($bet_type === 'one') ? '1 MATCH' : '2 MATCHES';
        $message = "Bet confirmed: ₱$bet on $chosen_color ($type_text). Click ROLL to play.";
    }
}

// ---------- ROLL ----------
if ($action === 'roll' && $_SESSION['color_active']) {
    $bet       = $_SESSION['color_bet'];
    $chosen    = $_SESSION['color_chosen'];
    $bet_type  = $_SESSION['color_bet_type'];
    if ($bet > $_SESSION['balance']) {
        $message = "Insufficient balance to continue. Please stop and try again with a lower bet.";
    } else {
        $die1 = $colors[array_rand($colors)];
        $die2 = $colors[array_rand($colors)];
        $match_count = 0;
        if ($die1 === $chosen) $match_count++;
        if ($die2 === $chosen) $match_count++;
        $_SESSION['balance'] -= $bet;
        
        if (($bet_type === 'one' && $match_count == 1) || ($bet_type === 'two' && $match_count == 2)) {
            $multiplier = ($bet_type === 'one') ? 2 : 4;
            $winnings = $bet * $multiplier;
            $_SESSION['balance'] += $winnings;
            $profit = $winnings - $bet;
            $message = "You rolled $die1 and $die2. $match_count match(es)! You win ₱$winnings (profit ₱$profit). New balance: ₱" . number_format($_SESSION['balance']) . ".";
            $show_win_banner = true;
        } else {
            $message = "You rolled $die1 and $die2. $match_count match(es). You lose ₱$bet. New balance: ₱" . number_format($_SESSION['balance']) . ".";
            $show_loss_banner = true;
        }
    }
}

$current_bet      = $_SESSION['current_bet'];
$color_active     = $_SESSION['color_active'];
$chosen_color     = $_SESSION['color_chosen'] ?? null;
$selected_bet_type = $_SESSION['color_bet_type'] ?? null;
$balance          = $_SESSION['balance'];
$balance_display  = number_format($balance);
$current_bet_display = number_format($current_bet);
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

        /* --- HELP ICON & MODAL --- */
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
        .modal-overlay {
            display: none;
            position: fixed; inset: 0; z-index: 1000;
            background: rgba(0,0,0,0.78);
            backdrop-filter: blur(3px);
            align-items: center; justify-content: center;
        }
        .help-toggle:checked ~ .modal-overlay { display: flex; }
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
        .modal-box::-webkit-scrollbar { width: 6px; }
        .modal-box::-webkit-scrollbar-track { background: rgba(212,160,23,0.1); border-radius: 8px; }
        .modal-box::-webkit-scrollbar-thumb { background: var(--burnished-gold); border-radius: 8px; }
        @keyframes modalIn {
            0%  { transform: scale(0.85) translateY(20px); opacity: 0; }
            100%{ transform: scale(1) translateY(0);        opacity: 1; }
        }
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
        .modal-divider { border: none; border-top: 1px solid rgba(212,160,23,0.2); margin: 18px 0; }
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

        /* --- COLOR GRID & BET TYPE TOGGLE --- */
        .color-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
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
        }
        .color-label:hover { border-color: rgba(212,160,23,0.5); transform: scale(1.03); }
        .color-dot {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.2);
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

        /* Bet type selector */
        .bet-type-row {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-bottom: 20px;
        }
        .bet-type-option input { display: none; }
        .bet-type-label {
            flex: 1;
            text-align: center;
            padding: 8px 0;
            border: 1px solid var(--gold-dim);
            border-radius: 8px;
            background: rgba(212,160,23,0.05);
            color: var(--warm-champagne);
            font-family: var(--sans);
            font-weight: bold;
            font-size: 0.9rem;
            letter-spacing: 1px;
            cursor: pointer;
            transition: 0.2s;
        }
        .bet-type-option input:checked + .bet-type-label {
            border-color: var(--burnished-gold);
            background: rgba(212,160,23,0.12);
            color: var(--burnished-gold);
            box-shadow: 0 0 10px var(--gold-glow);
        }
        .bet-type-option input:disabled + .bet-type-label {
            opacity: 0.45;
            cursor: not-allowed;
        }
        .bet-type-label:hover:not(:disabled) {
            border-color: var(--burnished-gold);
            background: rgba(212,160,23,0.08);
        }

        /* BET SECTION (split forms) */
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
            pointer-events: none; /* read-only */
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

        /* active bet pills (same as dice game) */
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

        /* Right side (dice & actions) – unchanged */
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

    <!-- CSS-only modal checkbox -->
    <input type="checkbox" id="helpToggle" class="help-toggle">
    <div class="modal-overlay">
        <div class="modal-box">
            <label for="helpToggle" class="modal-close" title="Close">✕</label>
            <div class="modal-title">HOW TO PLAY</div>
            <div class="modal-subtitle">Rules &amp; Guidelines — Color Game</div>
            <hr class="modal-divider">
            <div class="rule-section">
                <div class="rule-section-title">OBJECTIVE</div>
                <ul class="rule-list">
                    <li>Two colored dice are rolled each round.</li>
                    <li>Choose a color AND a bet type: <strong>1 MATCH</strong> (exactly one die matches) or <strong>2 MATCHES</strong> (both dice match).</li>
                    <li>Win only if the number of matches exactly matches your bet type.</li>
                </ul>
            </div>
            <hr class="modal-divider">
            <div class="rule-section">
                <div class="rule-section-title">PAYOUTS</div>
                <table class="rule-table">
                    <thead> caption<th>Bet Type</th><th>Multiplier</th><th>Example (Bet ₱100)</th> </thead>
                    <tbody>
                        <tr><td>1 MATCH</td><td>2×</td><td>Win ₱200 (₱100 profit)</td> </tr>
                        <tr><td>2 MATCHES</td><td>4×</td><td>Win ₱400 (₱300 profit)</td> </tr>
                    </tbody>
                 </table>
            </div>
            <hr class="modal-divider">
            <div class="rule-section">
                <div class="rule-section-title">HOW TO PLAY</div>
                <ul class="rule-list">
                    <li><strong>Step 1</strong> – Pick a color.</li>
                    <li><strong>Step 2</strong> – Choose 1 MATCH or 2 MATCHES.</li>
                    <li><strong>Step 3</strong> – Set your bet (min ₱10 / max ₱5,000). Use ALL IN to bet your full balance (up to ₱5,000).</li>
                    <li><strong>Step 4</strong> – Press CONFIRM BET to lock your bet.</li>
                    <li><strong>Step 5</strong> – Press ROLL to see the dice and result.</li>
                </ul>
            </div>
            <div class="modal-note">
                ⚠ Please gamble responsibly. Results are random and cannot be predicted.
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

    <div class="container">
        <div class="left">
            <div class="panel">
                <label for="helpToggle" class="help-icon" title="Rules &amp; Help">?</label>
                <div class="panel-title">PICK YOUR COLOR & BET TYPE</div>

                <!-- Color grid -->
                <div class="color-grid">
                    <?php foreach ($colors as $color): ?>
                        <label class="color-option">
                            <input type="radio" name="color_temp" value="<?php echo $color; ?>"
                                <?php echo ($chosen_color == $color) ? 'checked' : ''; ?>
                                <?php echo $color_active ? 'disabled' : ''; ?>
                                onchange="document.getElementById('color_hidden').value = this.value; updateSelections();">
                            <div class="color-label">
                                <div class="color-dot" style="background:<?php echo $color_classes[$color]; ?>;"></div>
                                <span style="color:#fff; font-size:0.8rem; letter-spacing:1.5px;"><?php echo strtoupper($color); ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Bet type toggle -->
                <div class="bet-type-row">
                    <label class="bet-type-option">
                        <input type="radio" name="bet_type_temp" value="one" <?php echo ($selected_bet_type === 'one') ? 'checked' : ''; ?> <?php echo $color_active ? 'disabled' : ''; ?> onchange="document.getElementById('bet_type_hidden').value = this.value; updateSelections();">
                        <div class="bet-type-label">1 MATCH (2×)</div>
                    </label>
                    <label class="bet-type-option">
                        <input type="radio" name="bet_type_temp" value="two" <?php echo ($selected_bet_type === 'two') ? 'checked' : ''; ?> <?php echo $color_active ? 'disabled' : ''; ?> onchange="document.getElementById('bet_type_hidden').value = this.value; updateSelections();">
                        <div class="bet-type-label">2 MATCHES (4×)</div>
                    </label>
                </div>

                <!-- Hidden fields to store selections for form submission -->
                <input type="hidden" name="color_hidden" id="color_hidden" value="<?php echo htmlspecialchars($chosen_color ?? ''); ?>">
                <input type="hidden" name="bet_type_hidden" id="bet_type_hidden" value="<?php echo htmlspecialchars($selected_bet_type ?? ''); ?>">

                <?php if (!$color_active): ?>
                    <!-- BET ADJUSTMENT FORM (separate) -->
                    <form method="post">
                        <div class="bet-label">YOUR BET</div>
                        <div class="bet-row">
                            <div class="bet-balance">Balance: <strong>&#8369;<?php echo number_format($_SESSION['balance']); ?></strong></div>
                            <input type="text" class="bet-input" value="₱<?php echo $current_bet_display; ?>" readonly>
                        </div>
                        <div class="adjust-buttons">
                            <button type="submit" name="adjust" value="-100" class="adjust-btn">-100</button>
                            <button type="submit" name="adjust" value="-10"  class="adjust-btn">-10</button>
                            <button type="submit" name="adjust" value="allin" class="adjust-btn">ALL IN</button>
                            <button type="submit" name="adjust" value="+10"  class="adjust-btn">+10</button>
                            <button type="submit" name="adjust" value="+100" class="adjust-btn">+100</button>
                        </div>
                        <input type="hidden" name="action" value="adjust">
                        <input type="hidden" name="color" id="adjust_color" value="<?php echo htmlspecialchars($chosen_color ?? ''); ?>">
                        <input type="hidden" name="bet_type" id="adjust_bet_type" value="<?php echo htmlspecialchars($selected_bet_type ?? ''); ?>">
                    </form>

                    <div class="minmax-row">
                        <span>MIN: 10</span><span>MAX: 5000</span>
                    </div>
                    <div class="gold-sep"></div>

                    <!-- CONFIRM BET FORM (separate) -->
                    <form method="post">
                        <input type="hidden" name="action" value="confirm_bet">
                        <input type="hidden" name="color" id="confirm_color" value="<?php echo htmlspecialchars($chosen_color ?? ''); ?>">
                        <input type="hidden" name="bet_type" id="confirm_bet_type" value="<?php echo htmlspecialchars($selected_bet_type ?? ''); ?>">
                        <button type="submit" class="btn-confirm" <?php echo $color_active ? 'disabled' : ''; ?>>CONFIRM BET</button>
                    </form>

                <?php else: ?>
                    <!-- Active bet info -->
                    <div class="info-pills">
                        <div class="info-pill">
                            <span class="pill-label">BALANCE</span>
                            <span class="pill-value">₱<?php echo number_format($_SESSION['balance']); ?></span>
                        </div>
                        <div class="info-pill">
                            <span class="pill-label">YOUR BET</span>
                            <span class="pill-value">₱<?php echo number_format($_SESSION['color_bet']); ?></span>
                        </div>
                        <div class="info-pill">
                            <span class="pill-label">POTENTIAL WIN</span>
                            <span class="pill-value">₱<?php
                                $multiplier = ($_SESSION['color_bet_type'] === 'one') ? 2 : 4;
                                echo number_format($_SESSION['color_bet'] * $multiplier);
                            ?></span>
                        </div>
                    </div>
                    <p class="active-note">Game in progress — roll or end below.</p>
                <?php endif; ?>
            </div>
        </div><!-- /left -->

        <!-- RIGHT COLUMN (dice & actions) -->
        <div class="right">
            <div class="dice-area">
                <div class="dice-title">DICE RESULTS</div>

                <?php if ($show_win_banner): ?>
                    <div class="banner win-banner">YOU WIN!</div>
                <?php elseif ($show_loss_banner): ?>
                    <div class="banner loss-banner">YOU LOSE</div>
                <?php endif; ?>

                <div class="dice-row">
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

            <div class="result-indicator">
                <?php echo $message ? htmlspecialchars($message) : "Pick a color, choose 1 or 2 matches, then confirm your bet."; ?>
            </div>

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

    <script>
        // Sync the hidden fields with the actual selected radio buttons
        function updateSelections() {
            // Get selected color
            let colorRadios = document.querySelectorAll('input[name="color_temp"]');
            let selectedColor = null;
            for (let radio of colorRadios) {
                if (radio.checked) {
                    selectedColor = radio.value;
                    break;
                }
            }
            // Get selected bet type
            let betTypeRadios = document.querySelectorAll('input[name="bet_type_temp"]');
            let selectedBetType = null;
            for (let radio of betTypeRadios) {
                if (radio.checked) {
                    selectedBetType = radio.value;
                    break;
                }
            }
            // Update hidden fields
            document.getElementById('color_hidden').value = selectedColor || '';
            document.getElementById('bet_type_hidden').value = selectedBetType || '';
            document.getElementById('adjust_color').value = selectedColor || '';
            document.getElementById('adjust_bet_type').value = selectedBetType || '';
            document.getElementById('confirm_color').value = selectedColor || '';
            document.getElementById('confirm_bet_type').value = selectedBetType || '';
        }

        // Initial sync (on page load)
        updateSelections();
        // Add event listeners to all radios
        document.querySelectorAll('input[name="color_temp"], input[name="bet_type_temp"]').forEach(radio => {
            radio.addEventListener('change', updateSelections);
        });
    </script>

</body>
</html>
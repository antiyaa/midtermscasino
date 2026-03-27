<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// Ensure all dice‑game session variables exist
if (!isset($_SESSION['dice_bet']))          $_SESSION['dice_bet']          = null;
if (!isset($_SESSION['dice_active']))       $_SESSION['dice_active']       = false;
if (!isset($_SESSION['dice_current_bet']))  $_SESSION['dice_current_bet']  = 10;
if (!isset($_SESSION['dice_bet_type']))     $_SESSION['dice_bet_type']     = 'low';
if (!isset($_SESSION['dice_specific_num'])) $_SESSION['dice_specific_num'] = 7;
if (!isset($_SESSION['dice_history']))      $_SESSION['dice_history']      = [];

$message          = '';
$player_dice1     = null;
$player_dice2     = null;
$player_sum       = null;
$show_win_banner  = false;
$show_loss_banner = false;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'back') {
    $_SESSION['dice_active']       = false;
    $_SESSION['dice_bet']          = null;
    $_SESSION['dice_bet_type']     = 'low';
    $_SESSION['dice_specific_num'] = 7;
    header('Location: Homepage.php');
    exit;
}

if ($action === 'stop') {
    $_SESSION['dice_active']       = false;
    $_SESSION['dice_bet']          = null;
    $_SESSION['dice_bet_type']     = 'low';
    $_SESSION['dice_specific_num'] = 7;
    $message = "Game stopped. You can place a new bet.";
}

// ---------- MANUAL BET INPUT (no Set Bet button) ----------
if ($action === 'set_bet' && !$_SESSION['dice_active']) {
    $typed = intval($_POST['typed_bet'] ?? 0);
    $balance = $_SESSION['balance'];
    $typed = max(1, min($balance, $typed));
    $_SESSION['dice_current_bet'] = $typed;
}

if ($action === 'adjust' && !$_SESSION['dice_active']) {
    $adjust = $_POST['adjust'] ?? '';
    $current = $_SESSION['dice_current_bet'];
    switch ($adjust) {
        case '-100': $new = $current - 100; break;
        case '-10':  $new = $current - 10;  break;
        case '10':   $new = 10;             break;
        case '+10':  $new = $current + 10;  break;
        case '+100': $new = $current + 100; break;
        case 'all_in':
            $new = $_SESSION['balance'];
            break;
        default:     $new = $current;
    }
    $new = max(1, min($_SESSION['balance'], $new));
    $_SESSION['dice_current_bet'] = $new;
}

// ---------- UPDATE BET TYPE (while not active) ----------
if ($action === 'set_type' && !$_SESSION['dice_active']) {
    $type = $_POST['bet_type'] ?? 'low';
    if (in_array($type, ['low', 'mid', 'high', 'specific'])) {
        $_SESSION['dice_bet_type'] = $type;
    }
    if ($type === 'specific' && isset($_POST['specific_num'])) {
        $num = (int)$_POST['specific_num'];
        if ($num >= 2 && $num <= 12) {
            $_SESSION['dice_specific_num'] = $num;
        }
    }
}

// ---------- CONFIRM BET ----------
if ($action === 'confirm_bet' && !$_SESSION['dice_active']) {
    $bet = $_SESSION['dice_current_bet'];
    if ($bet <= 0) {
        $message = "Bet must be positive.";
    } elseif ($bet > $_SESSION['balance']) {
        $message = "Insufficient balance. Your balance is " . number_format($_SESSION['balance']) . ".";
    } else {
        $_SESSION['dice_bet'] = $bet;
        $_SESSION['dice_active'] = true;
        $message = "Bet confirmed: " . number_format($bet) . " (" . ucfirst($_SESSION['dice_bet_type']) .
                   ($_SESSION['dice_bet_type'] === 'specific' ? " {$_SESSION['dice_specific_num']}" : "") .
                   "). Click Roll Dice to play.";
    }
}

// Helper: get multiplier for a given bet type and sum
function getMultiplier($bet_type, $sum, $specific_num) {
    switch ($bet_type) {
        case 'low':  return ($sum >= 2 && $sum <= 5) ? 2 : 0;
        case 'mid':  return ($sum >= 6 && $sum <= 8) ? 2.5 : 0;
        case 'high': return ($sum >= 9 && $sum <= 12) ? 2 : 0;
        case 'specific': return ($sum == $specific_num) ? 5 : 0;
        default: return 0;
    }
}

// Helper: get a readable name for bet type
function getBetTypeName($bet_type, $specific_num) {
    switch ($bet_type) {
        case 'low':  return 'Low (2‑5)';
        case 'mid':  return 'Mid (6‑8)';
        case 'high': return 'High (9‑12)';
        case 'specific': return "Specific $specific_num";
        default: return 'Unknown';
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

        $bet_type = $_SESSION['dice_bet_type'];
        $specific = $_SESSION['dice_specific_num'];
        $multiplier = getMultiplier($bet_type, $player_sum, $specific);

        $_SESSION['balance'] -= $bet;

        if ($multiplier > 0) {
            $win_amount = $bet * $multiplier;
            $_SESSION['balance'] += $win_amount;
            $profit = $win_amount - $bet;
            $message = "You rolled $player_sum! You won! +" . number_format($profit) . " profit. New balance: " . number_format($_SESSION['balance']) . ".";
            $show_win_banner = true;
            $result_text = "WIN";
        } else {
            $message = "You rolled $player_sum. You lose. Lost " . number_format($bet) . ". New balance: " . number_format($_SESSION['balance']) . ".";
            $show_loss_banner = true;
            $profit = -$bet;
            $result_text = "LOSS";
        }

        $history_entry = [
            'time'    => date('H:i:s'),
            'pattern' => getBetTypeName($bet_type, $specific),
            'bet'     => $bet,
            'sum'     => $player_sum,
            'result'  => $result_text,
            'profit'  => $profit,
        ];
        array_unshift($_SESSION['dice_history'], $history_entry);
        if (count($_SESSION['dice_history']) > 20) {
            array_pop($_SESSION['dice_history']);
        }
    }
}

function getDiceImage($value) {
    if ($value === null) return "d1.png";
    return "d$value.png";
}

$balance_display = number_format($_SESSION['balance']);
$current_bet_display = number_format($_SESSION['dice_current_bet']);

// For potential win display – safely use current bet type and specific num
$bet_type = $_SESSION['dice_bet_type'] ?? 'low';
$specific = $_SESSION['dice_specific_num'] ?? 7;
$temp_multiplier = 0;
if ($bet_type === 'low') $temp_multiplier = 2;
elseif ($bet_type === 'mid') $temp_multiplier = 2.5;
elseif ($bet_type === 'high') $temp_multiplier = 2;
elseif ($bet_type === 'specific') $temp_multiplier = 5;
$potential_win = $_SESSION['dice_current_bet'] * $temp_multiplier;
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
        header {
            background: linear-gradient(90deg, #0E0500, #1C0A08);
            padding: 12px 32px;
            border-bottom: 2px solid var(--burnished-gold);
            border-top: 2px solid var(--burnished-gold);
            box-shadow: 0 0 24px var(--gold-glow);
            display: flex; align-items: center; justify-content: space-between;
            width: 100%;
        }
        .header-left { display: flex; align-items: center; gap: 16px; }
        .header-logo {
            width: 56px; height: 56px; object-fit: contain; border-radius: 8px;
            filter: drop-shadow(0 0 6px rgba(212,160,23,0.4));
        }
        header h1 {
            color: var(--burnished-gold); font-size: 2.2rem; letter-spacing: 4px;
            font-family: 'Times New Roman', serif;
            text-shadow: 0 0 14px rgba(212,160,23,0.5); margin: 0;
        }
        .header-right { display: flex; align-items: center; gap: 20px; }
        .header-divider {
            width: 1px; height: 44px;
            background: linear-gradient(to bottom, transparent, var(--burnished-gold), transparent);
            opacity: 0.5; flex-shrink: 0;
        }
        .header-balance { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; }
        .balance-label { color: var(--text-muted); font-size: 0.72rem; letter-spacing: 1.5px; text-transform: uppercase; }
        .balance-amount {
            color: var(--burnished-gold); font-size: 1.45rem; font-weight: bold;
            letter-spacing: 1px; text-shadow: 0 0 8px rgba(212,160,23,0.4);
        }
        .btn-deposit {
            background: linear-gradient(45deg, #1C0A08, #2a100a);
            color: var(--warm-champagne); border: 1px solid rgba(212,160,23,0.35);
            padding: 11px 22px; font-weight: 600; font-size: 0.78rem;
            letter-spacing: 1.5px; cursor: pointer; border-radius: 2px;
            transition: 0.3s; white-space: nowrap; font-family: var(--sans);
            text-decoration: none; display: inline-block;
        }
        .btn-deposit:hover { box-shadow: 0 0 18px rgba(192,21,42,0.65); transform: scale(1.04); }
        .back-container { padding: 18px 28px 0; }
        .back-btn {
            display: inline-flex; align-items: center; gap: 6px;
            background: linear-gradient(45deg, #C0152A, #e0294a);
            color: var(--warm-champagne); padding: 8px 20px; border-radius: 6px;
            font-weight: bold; border: none; cursor: pointer;
            font-size: 0.85rem; font-family: var(--sans); letter-spacing: 1.5px; transition: 0.2s;
        }
        .back-btn:hover { box-shadow: 0 0 16px rgba(192,21,42,0.55); transform: scale(1.03); }
        .game-wrapper {
            max-width: 1320px; width: 96%;
            margin: 22px auto; flex: 1;
            display: flex; flex-direction: column;
            gap: 18px; padding-bottom: 40px;
        }
        .game-title {
            font-family: 'Times New Roman', serif; font-size: 1.6rem;
            text-align: center; color: var(--burnished-gold);
            letter-spacing: 5px; text-shadow: 0 0 14px var(--gold-glow);
        }
        .game-title::after {
            content: ''; display: block; height: 1px;
            background: linear-gradient(90deg, transparent, var(--burnished-gold), transparent);
            margin: 10px auto 0; width: 50%;
        }
        /* Three columns stretch to equal height */
        .columns {
            display: flex;
            gap: 20px;
            align-items: stretch;
        }
        .col-left {
            width: 320px; flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .col-center {
            flex: 1; min-width: 0;
            display: flex;
            flex-direction: column;
        }
        .col-right {
            width: 330px; flex-shrink: 0;
            display: flex;
            flex-direction: column;
        }
        .panel {
            background: #1C0A08; border: 1px solid var(--gold-dim);
            border-radius: 12px; padding: 24px 26px;
            position: relative; transition: box-shadow 0.2s;
        }
        .panel:hover { box-shadow: 0 0 18px var(--gold-glow); }
        .panel-title {
            font-family: 'Times New Roman', serif; font-size: 1.2rem;
            color: var(--burnished-gold); letter-spacing: 3px;
            text-align: center; margin-bottom: 6px;
        }
        .panel-title::after {
            content: ''; display: block; height: 1px;
            background: linear-gradient(90deg, transparent, var(--burnished-gold), transparent);
            margin: 8px auto 20px; width: 70%;
        }
        .help-toggle { display: none; }
        .help-icon {
            position: absolute; top: 14px; right: 14px;
            border: 1px solid rgba(212,160,23,0.5); border-radius: 50%;
            width: 28px; height: 28px; display: flex;
            justify-content: center; align-items: center;
            color: var(--burnished-gold); background: rgba(14,5,0,0.6);
            font-size: 13px; font-family: var(--sans); cursor: pointer;
            font-weight: bold; transition: 0.2s; user-select: none; z-index: 10;
        }
        .help-icon:hover { background: rgba(212,160,23,0.15); box-shadow: 0 0 10px var(--gold-glow); }
        .modal-overlay {
            display: none; position: fixed; inset: 0; z-index: 1000;
            background: rgba(0,0,0,0.78); backdrop-filter: blur(3px);
            align-items: center; justify-content: center;
        }
        .help-toggle:checked ~ .modal-overlay { display: flex; }
        .modal-box {
            background: linear-gradient(160deg, #1C0A08 60%, #2a0e08);
            border: 2px solid var(--burnished-gold); border-radius: 16px;
            padding: 40px 44px; max-width: 560px; width: 92%;
            max-height: 80vh; overflow-y: auto;
            box-shadow: 0 0 60px rgba(212,160,23,0.25), 0 0 120px rgba(0,0,0,0.8);
            position: relative; animation: modalIn 0.3s ease-out;
        }
        .modal-box::-webkit-scrollbar { width: 6px; }
        .modal-box::-webkit-scrollbar-track { background: rgba(212,160,23,0.1); border-radius: 8px; }
        .modal-box::-webkit-scrollbar-thumb { background: var(--burnished-gold); border-radius: 8px; }
        @keyframes modalIn {
            0%  { transform: scale(0.85) translateY(20px); opacity: 0; }
            100%{ transform: scale(1) translateY(0); opacity: 1; }
        }
        .modal-box::before, .modal-box::after {
            content: ''; position: absolute; width: 18px; height: 18px;
            border-color: var(--burnished-gold); border-style: solid;
        }
        .modal-box::before { top: 10px; left: 10px; border-width: 2px 0 0 2px; border-radius: 4px 0 0 0; }
        .modal-box::after  { bottom: 10px; right: 10px; border-width: 0 2px 2px 0; border-radius: 0 0 4px 0; }
        .modal-close {
            position: absolute; top: 14px; right: 16px;
            width: 30px; height: 30px; border-radius: 50%;
            border: 1px solid rgba(212,160,23,0.4); background: rgba(14,5,0,0.7);
            color: var(--burnished-gold); font-size: 1rem; font-weight: bold;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            text-decoration: none; transition: 0.2s; line-height: 1;
        }
        .modal-close:hover { background: rgba(212,160,23,0.18); box-shadow: 0 0 10px var(--gold-glow); }
        .modal-title {
            font-family: 'Times New Roman', serif; font-size: 1.9rem;
            color: var(--burnished-gold); text-align: center;
            letter-spacing: 3px; margin-bottom: 6px;
            text-shadow: 0 0 12px rgba(212,160,23,0.35);
        }
        .modal-subtitle {
            text-align: center; color: var(--text-muted);
            font-size: 0.75rem; letter-spacing: 2px;
            text-transform: uppercase; margin-bottom: 28px;
        }
        .modal-divider { border: none; border-top: 1px solid rgba(212,160,23,0.2); margin: 18px 0; }
        .rule-section { margin-bottom: 22px; }
        .rule-section-title {
            font-family: 'Times New Roman', serif; font-size: 1.05rem;
            color: var(--burnished-gold); letter-spacing: 2px;
            margin-bottom: 12px; display: flex; align-items: center; gap: 8px;
        }
        .rule-section-title::before {
            content: ''; display: inline-block; width: 24px; height: 1px;
            background: var(--burnished-gold); opacity: 0.5;
        }
        .rule-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        .rule-table th {
            background: rgba(212,160,23,0.08); color: var(--burnished-gold);
            text-transform: uppercase; letter-spacing: 1.5px; font-size: 0.72rem;
            padding: 8px 12px; text-align: left;
            border-bottom: 1px solid rgba(212,160,23,0.2); font-family: var(--sans);
        }
        .rule-table td {
            padding: 9px 12px; color: var(--warm-champagne);
            border-bottom: 1px solid rgba(212,160,23,0.07);
            font-family: var(--sans); vertical-align: middle;
        }
        .rule-table tr:last-child td { border-bottom: none; }
        .rule-table tr:hover td { background: rgba(212,160,23,0.04); }
        .badge {
            display: inline-block; border: 1px solid rgba(212,160,23,0.4);
            border-radius: 4px; padding: 2px 8px; font-size: 0.75rem;
            color: var(--burnished-gold); background: rgba(212,160,23,0.08);
            letter-spacing: 1px; font-family: var(--sans);
        }
        .badge-special { border-color: rgba(192,21,42,0.5); color: #f87171; background: rgba(192,21,42,0.08); }
        .rule-list { list-style: none; font-size: 0.88rem; display: flex; flex-direction: column; gap: 8px; }
        .rule-list li {
            padding-left: 20px; position: relative;
            color: var(--warm-champagne); line-height: 1.5; font-family: var(--sans);
        }
        .rule-list li::before {
            content: '◆'; position: absolute; left: 0;
            color: var(--burnished-gold); font-size: 0.55rem; top: 5px;
        }
        .modal-note {
            background: rgba(212,160,23,0.05); border: 1px solid rgba(212,160,23,0.15);
            border-left: 3px solid var(--burnished-gold); border-radius: 6px;
            padding: 10px 14px; font-size: 0.82rem; color: var(--text-muted);
            margin-top: 20px; font-family: var(--sans); line-height: 1.5;
        }
        .pattern-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-bottom: 16px; }
        .pattern-radio { display: none; }
        .pattern-label {
            border: 1px solid var(--gold-dim); border-radius: 10px;
            padding: 10px 12px; font-size: 1rem;
            font-family: 'Times New Roman', serif; font-weight: bold;
            cursor: pointer; transition: 0.2s; min-width: 80px;
            text-align: center; color: var(--warm-champagne);
            background: rgba(212,160,23,0.04);
        }
        .pattern-label:hover { border-color: var(--burnished-gold); background: rgba(212,160,23,0.08); }
        .pattern-radio:checked + .pattern-label {
            border-color: var(--burnished-gold); color: var(--burnished-gold);
            background: rgba(212,160,23,0.12); box-shadow: 0 0 12px var(--gold-glow);
        }
        .specific-select {
            text-align: center; margin-top: 12px;
        }
        .specific-select select {
            background: #0E0500; border: 1px solid var(--burnished-gold);
            border-radius: 8px; color: var(--burnished-gold);
            padding: 8px 16px; font-family: var(--sans); font-size: 1rem;
            cursor: pointer;
        }
        .payout-row { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; margin-bottom: 20px; }
        .payout-pill {
            background: rgba(212,160,23,0.07); border: 1px solid var(--gold-dim);
            border-radius: 20px; padding: 4px 12px; font-size: 11px;
            font-family: var(--sans); color: var(--text-muted); letter-spacing: 1px;
        }
        .payout-pill strong { color: var(--burnished-gold); }
        .bet-balance-row {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 16px; font-family: var(--sans);
        }
        .bal-label { color: var(--text-muted); letter-spacing: 1px; font-size: 0.75rem; }
        .bal-value { color: var(--burnished-gold); font-weight: bold; font-size: 1.1rem; }
        .bet-input-group {
            margin-bottom: 16px;
        }
        .bet-input-wrap {
            width: 100%;
            background: #0E0500;
            border: 1px solid var(--burnished-gold);
            border-radius: 8px;
            overflow: hidden;
        }
        .bet-number-input {
            background: #0E0500;
            border: none;
            color: var(--burnished-gold);
            font-size: 1.4rem;
            font-weight: bold;
            font-family: var(--sans);
            width: 100%;
            padding: 10px 14px;
            outline: none;
            -moz-appearance: textfield;
            text-align: center;
        }
        .bet-number-input::-webkit-outer-spin-button,
        .bet-number-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .adjust-buttons { display: flex; gap: 8px; justify-content: center; margin-bottom: 10px; flex-wrap: wrap; }
        .adjust-btn {
            border: 1px solid var(--gold-dim); background: rgba(212,160,23,0.05);
            color: var(--warm-champagne); padding: 6px 12px; border-radius: 6px;
            font-family: var(--sans); font-size: 0.88rem; font-weight: bold;
            cursor: pointer; transition: 0.15s;
        }
        .adjust-btn:hover:not(:disabled) { border-color: var(--burnished-gold); color: var(--burnished-gold); background: rgba(212,160,23,0.12); }
        .adjust-btn:disabled { opacity: 0.3; cursor: not-allowed; }
        .adjust-btn.allin {
            border-color: rgba(192,21,42,0.45);
            color: #f87171;
            background: rgba(192,21,42,0.06);
        }
        .adjust-btn.allin:hover:not(:disabled) {
            border-color: #f87171;
            background: rgba(192,21,42,0.14);
            color: #f87171;
        }
        .minmax-row {
            display: flex; justify-content: space-between;
            font-family: var(--sans); font-size: 11px; color: var(--text-muted); margin-bottom: 12px;
        }
        .gold-sep { height: 1px; background: linear-gradient(90deg, transparent, var(--gold-dim), transparent); margin: 4px 0 14px; }
        .btn-confirm {
            width: 100%; background: rgba(212,160,23,0.08);
            border: 1px solid var(--burnished-gold); color: var(--burnished-gold);
            padding: 12px; font-family: 'Times New Roman', serif;
            font-size: 1.2rem; font-weight: bold; letter-spacing: 2px;
            cursor: pointer; border-radius: 8px; transition: 0.2s;
        }
        .btn-confirm:hover:not(:disabled) { background: rgba(212,160,23,0.17); box-shadow: 0 0 14px var(--gold-glow); }
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
        /* result panel – no min-height, stretches with flex */
        .result-panel {
            background: #1C0A08; border: 1px solid var(--gold-dim);
            border-radius: 12px; padding: 32px 34px;
            display: flex; flex-direction: column;
            transition: box-shadow 0.2s;
            height: 100%;
        }
        .result-panel:hover { box-shadow: 0 0 18px var(--gold-glow); }
        .banner-area {
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            position: relative;
        }
        .banner {
            font-family: 'Times New Roman', serif; font-size: 3rem;
            font-weight: bold; letter-spacing: 6px;
            text-align: center;
            animation: popIn 0.4s ease-out;
        }
        .invisible-placeholder {
            visibility: hidden;
            font-family: 'Times New Roman', serif; font-size: 3rem;
            font-weight: bold; letter-spacing: 6px;
            white-space: pre;
        }
        .win-banner  { color: #4ade80; text-shadow: 0 0 24px rgba(74,222,128,0.5); }
        .loss-banner { color: #f87171; text-shadow: 0 0 24px rgba(248,113,113,0.5); }
        @keyframes popIn {
            0%  { transform: scale(0.7); opacity: 0; }
            70% { transform: scale(1.06); }
            100%{ transform: scale(1); opacity: 1; }
        }
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
            padding: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .dice-side.winner { border-color: var(--burnished-gold); box-shadow: 0 0 18px var(--gold-glow); }
        .dice-side.loser  { border-color: rgba(248,113,113,0.3); }
        .side-label {
            font-family: 'Times New Roman', serif;
            font-size: 1.1rem; letter-spacing: 3px;
        }
        .side-label.you    { color: #4ade80; }
        .dice-pair {
            display: flex; gap: 12px;
            align-items: center; justify-content: center;
        }
        .die-wrap {
            background: #1C0A08;
            border: 1px solid var(--gold-dim);
            border-radius: 10px; padding: 8px;
            display: flex; align-items: center; justify-content: center;
            transition: border-color 0.2s;
        }
        .die-wrap:hover { border-color: var(--burnished-gold); }
        .dice-icon {
            width: 70px; height: 70px;
            display: block;
            filter: drop-shadow(0 0 4px rgba(212,160,23,0.2));
        }
        .score-badge {
            background: rgba(212,160,23,0.1);
            border: 1px solid var(--gold-dim);
            border-radius: 20px;
            padding: 4px 16px;
            font-family: var(--sans); font-size: 0.78rem;
            letter-spacing: 2px; color: var(--text-muted);
        }
        .score-badge strong {
            color: var(--burnished-gold); font-size: 1.1rem; margin-left: 6px;
        }
        .result-indicator {
            background: #0E0500; border: 1px solid var(--gold-dim);
            border-left: 3px solid var(--burnished-gold); border-radius: 8px;
            padding: 16px 22px; min-height: 72px;
            display: flex; align-items: center; justify-content: center;
            text-align: center; color: var(--warm-champagne);
            font-family: var(--sans); font-size: 1rem;
            letter-spacing: 0.5px; word-break: break-word;
            margin-bottom: 22px;
        }
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
        /* history panel – fills height */
        .history-panel {
            background: #1C0A08; border: 1px solid var(--gold-dim);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            transition: box-shadow 0.2s;
            height: 100%;
        }
        .history-panel:hover { box-shadow: 0 0 18px var(--gold-glow); }
        .history-head {
            padding: 22px 20px 0;
            flex-shrink: 0;
        }
        .history-col-bar {
            display: grid;
            grid-template-columns: 50px 1fr 56px 32px 46px 66px;
            padding: 0 16px 8px;
            margin-top: 4px;
            border-bottom: 1px solid rgba(212,160,23,0.18);
            flex-shrink: 0;
        }
        .hcb {
            font-family: var(--sans); font-size: 0.63rem;
            text-transform: uppercase; letter-spacing: 1.3px;
            color: var(--text-muted); padding: 6px 3px 4px;
        }
        .history-rows {
            flex: 1;
            overflow-y: auto;
            padding-bottom: 10px;
        }
        .history-rows::-webkit-scrollbar { width: 4px; }
        .history-rows::-webkit-scrollbar-track { background: transparent; }
        .history-rows::-webkit-scrollbar-thumb { background: rgba(212,160,23,0.28); border-radius: 4px; }
        .h-row {
            display: grid;
            grid-template-columns: 50px 1fr 56px 32px 46px 66px;
            padding: 8px 16px;
            border-bottom: 1px solid rgba(212,160,23,0.06);
            align-items: center;
            transition: background 0.12s;
        }
        .h-row:hover { background: rgba(212,160,23,0.045); }
        .h-row:last-child { border-bottom: none; }
        .hc-time    { font-size: 0.7rem; color: var(--text-muted); font-family: var(--sans); }
        .hc-pattern { font-size: 0.75rem; font-family: var(--sans); }
        .hc-bet     { font-size: 0.75rem; font-family: var(--sans); color: var(--warm-champagne); text-align: right; padding-right: 4px; }
        .hc-sum     {
            font-size: 0.9rem; font-family: 'Times New Roman', serif;
            font-weight: bold; color: var(--burnished-gold); text-align: center;
        }
        .hc-result  {
            font-size: 0.65rem; font-weight: bold; font-family: var(--sans);
            letter-spacing: 0.6px; text-align: center;
            padding: 2px 5px; border-radius: 4px; width: fit-content; margin: 0 auto;
        }
        .hc-result.win  { color: #4ade80; background: rgba(74,222,128,0.1); border: 1px solid rgba(74,222,128,0.22); }
        .hc-result.loss { color: #f87171; background: rgba(248,113,113,0.08); border: 1px solid rgba(248,113,113,0.18); }
        .hc-profit  { font-size: 0.75rem; font-family: var(--sans); font-weight: bold; text-align: right; }
        .hc-profit.pos { color: #4ade80; }
        .hc-profit.neg { color: #f87171; }
        .ptag {
            display: inline-block;
            background: rgba(212,160,23,0.08); border: 1px solid rgba(212,160,23,0.2);
            border-radius: 3px; padding: 1px 5px;
            font-size: 0.68rem; color: var(--burnished-gold);
            letter-spacing: 0.4px; font-family: var(--sans); white-space: nowrap;
        }
        .history-empty {
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 12px; padding: 40px 20px;
            color: var(--text-muted); font-family: var(--sans);
            font-size: 0.8rem; letter-spacing: 1px; text-align: center;
            flex: 1;
        }
        .history-empty-icon { font-size: 2.4rem; opacity: 0.25; }
        @keyframes shake {
            0% { transform: translate(1px, 1px) rotate(0deg); }
            10% { transform: translate(-1px, -2px) rotate(-1deg); }
            20% { transform: translate(-3px, 0px) rotate(1deg); }
            30% { transform: translate(3px, 2px) rotate(0deg); }
            40% { transform: translate(1px, -1px) rotate(1deg); }
            50% { transform: translate(-1px, 2px) rotate(-1deg); }
            60% { transform: translate(-3px, 1px) rotate(0deg); }
            70% { transform: translate(3px, 1px) rotate(-1deg); }
            80% { transform: translate(-1px, -1px) rotate(1deg); }
            90% { transform: translate(1px, 2px) rotate(0deg); }
            100% { transform: translate(1px, -2px) rotate(-1deg); }
        }
        .shake {
            animation: shake 0.5s ease-in-out;
        }
        /* Additional stretch rules for left column forms */
        .col-left form,
        .col-left .panel {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .col-left form .panel:last-child {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .btn-confirm {
            margin-top: auto;
        }
        .col-left .panel:first-child {
            margin-bottom: 24px; /* gap between pattern and bet panels */
        }
    </style>
</head>
<body>

    <input type="checkbox" id="helpToggle" class="help-toggle">

    <div class="modal-overlay">
        <div class="modal-box">
            <label for="helpToggle" class="modal-close" title="Close">✕</label>
            <div class="modal-title">HOW TO PLAY</div>
            <div class="modal-subtitle">Rules &amp; Guidelines — Dice Game</div>
            <hr class="modal-divider">
            <div class="rule-section">
                <div class="rule-section-title">BETTING OPTIONS</div>
                <ul class="rule-list">
                    <li><strong>Low (2‑5)</strong> – Win if total is 2, 3, 4, or 5. Payout: <strong>2×</strong> your bet.</li>
                    <li><strong>Mid (6‑8)</strong> – Win if total is 6, 7, or 8. Payout: <strong>2.5×</strong> your bet.</li>
                    <li><strong>High (9‑12)</strong> – Win if total is 9, 10, 11, or 12. Payout: <strong>2×</strong> your bet.</li>
                    <li><strong>Specific Number (2‑12)</strong> – Pick a number. Win if the total matches exactly. Payout: <strong>5×</strong> your bet.</li>
                </ul>
            </div>
            <hr class="modal-divider">
            <div class="rule-section">
                <div class="rule-section-title">HOW TO PLAY</div>
                <ul class="rule-list">
                    <li><strong>Step 1 — Pick a Pattern:</strong> Select Low, Mid, High, or Specific Number.</li>
                    <li><strong>Step 2 — Set Your Bet:</strong> Type an amount or use the +/− buttons. MIN is 1, MAX is your full balance.</li>
                    <li><strong>Step 3 — Confirm Bet:</strong> Press <em>CONFIRM BET</em> to lock your wager.</li>
                    <li><strong>Step 4 — Roll:</strong> Press <em>ROLL THE DICE</em>. The result determines if you win.</li>
                    <li><strong>After Each Round:</strong> Roll again (same bet) or click <em>END THE GAME / CHANGE BET</em> to start over.</li>
                </ul>
            </div>
            <div class="modal-note">
                ⚠ &nbsp;Please gamble responsibly. Results are random and cannot be influenced.
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
                <span class="balance-amount"><?php echo htmlspecialchars($balance_display); ?></span>
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
        <div class="game-title">DICE GAME &nbsp;&#8212;&nbsp; 2 – 12</div>

        <div class="columns">
            <!-- LEFT COLUMN: BET SETTINGS -->
            <div class="col-left">
                <?php if ($_SESSION['dice_active']): ?>
                    <!-- Active game info pills -->
                    <div class="panel">
                        <label for="helpToggle" class="help-icon" title="Rules &amp; Help">?</label>
                        <div class="panel-title">CURRENT BET</div>
                        <div class="info-pills">
                            <div class="info-pill">
                                <span class="pill-label">BALANCE</span>
                                <span class="pill-value"><?php echo number_format($_SESSION['balance']); ?></span>
                            </div>
                            <div class="info-pill">
                                <span class="pill-label">YOUR BET</span>
                                <span class="pill-value"><?php echo number_format($_SESSION['dice_bet']); ?></span>
                            </div>
                            <div class="info-pill">
                                <span class="pill-label">BET TYPE</span>
                                <span class="pill-value"><?php echo ucfirst($_SESSION['dice_bet_type']) . ($_SESSION['dice_bet_type'] == 'specific' ? ' ' . $_SESSION['dice_specific_num'] : ''); ?></span>
                            </div>
                            <div class="info-pill">
                                <span class="pill-label">POTENTIAL WIN</span>
                                <span class="pill-value"><?php echo number_format($_SESSION['dice_bet'] * $temp_multiplier); ?></span>
                            </div>
                        </div>
                        <p class="active-note">Game in progress — roll or end below.</p>
                    </div>
                <?php else: ?>
                    <!-- Pattern Selection Panel -->
                    <div class="panel">
                        <label for="helpToggle" class="help-icon" title="Rules &amp; Help">?</label>
                        <div class="panel-title">PICK YOUR PATTERN</div>
                        <div class="payout-row">
                            <div class="payout-pill">Low <strong>2×</strong></div>
                            <div class="payout-pill">Mid <strong>2.5×</strong></div>
                            <div class="payout-pill">High <strong>2×</strong></div>
                            <div class="payout-pill">Specific <strong>5×</strong></div>
                        </div>
                        <form method="post" id="betTypeForm">
                            <div class="pattern-grid">
                                <input type="radio" name="bet_type" id="low" value="low" class="pattern-radio"
                                    <?php echo ($_SESSION['dice_bet_type'] == 'low') ? 'checked' : ''; ?>
                                    onchange="this.form.submit()">
                                <label for="low" class="pattern-label">Low (2‑5)</label>

                                <input type="radio" name="bet_type" id="mid" value="mid" class="pattern-radio"
                                    <?php echo ($_SESSION['dice_bet_type'] == 'mid') ? 'checked' : ''; ?>
                                    onchange="this.form.submit()">
                                <label for="mid" class="pattern-label">Mid (6‑8)</label>

                                <input type="radio" name="bet_type" id="high" value="high" class="pattern-radio"
                                    <?php echo ($_SESSION['dice_bet_type'] == 'high') ? 'checked' : ''; ?>
                                    onchange="this.form.submit()">
                                <label for="high" class="pattern-label">High (9‑12)</label>

                                <input type="radio" name="bet_type" id="specific" value="specific" class="pattern-radio"
                                    <?php echo ($_SESSION['dice_bet_type'] == 'specific') ? 'checked' : ''; ?>
                                    onchange="this.form.submit()">
                                <label for="specific" class="pattern-label">Specific</label>
                            </div>

                            <?php if ($_SESSION['dice_bet_type'] == 'specific'): ?>
                            <div class="specific-select">
                                <select name="specific_num" onchange="this.form.submit()">
                                    <?php for ($i = 2; $i <= 12; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($_SESSION['dice_specific_num'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <input type="hidden" name="action" value="set_type">
                        </form>
                    </div>

                    <!-- Bet Panel – only input field (no Set Bet button) -->
                    <div class="panel">
                        <div class="panel-title">PLACE YOUR BET</div>
                        <div class="bet-balance-row">
                            <span class="bal-label">YOUR BALANCE</span>
                            <span class="bal-value"><?php echo number_format($_SESSION['balance']); ?></span>
                        </div>

                        <!-- Form for manual bet input (auto‑submit on blur/enter) -->
                        <form method="post" id="manualBetForm">
                            <div class="bet-input-group">
                                <div class="bet-input-wrap">
                                    <input type="number" class="bet-number-input"
                                           name="typed_bet" id="typedBet"
                                           value="<?php echo $_SESSION['dice_current_bet']; ?>"
                                           min="1" max="<?php echo $_SESSION['balance']; ?>" autocomplete="off">
                                </div>
                            </div>
                            <input type="hidden" name="action" value="set_bet">
                        </form>

                        <!-- Quick adjust buttons (separate form) -->
                        <form method="post" id="adjustForm">
                            <div class="adjust-buttons">
                                <button type="submit" name="adjust" value="-100" class="adjust-btn">-100</button>
                                <button type="submit" name="adjust" value="-10"  class="adjust-btn">-10</button>
                                <button type="submit" name="adjust" value="10"   class="adjust-btn">10</button>
                                <button type="submit" name="adjust" value="+10"  class="adjust-btn">+10</button>
                                <button type="submit" name="adjust" value="+100" class="adjust-btn">+100</button>
                                <button type="submit" name="adjust" value="all_in" class="adjust-btn allin">All In</button>
                            </div>
                            <div class="minmax-row">
                                <span>MIN: 1</span>
                                <span>MAX: <?php echo number_format($_SESSION['balance']); ?></span>
                            </div>
                            <input type="hidden" name="action" value="adjust">
                        </form>

                        <div class="gold-sep"></div>

                        <form method="post">
                            <input type="hidden" name="action" value="confirm_bet">
                            <button type="submit" class="btn-confirm">CONFIRM BET</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- CENTER COLUMN: GAME RESULT -->
            <div class="col-center">
                <div class="result-panel">
                    <div class="panel-title">GAME RESULT</div>

                    <div class="banner-area">
                        <?php if ($show_win_banner): ?>
                            <div class="banner win-banner">YOU WIN!</div>
                        <?php elseif ($show_loss_banner): ?>
                            <div class="banner loss-banner">YOU LOSE</div>
                        <?php else: ?>
                            <div class="invisible-placeholder">WIN</div>
                        <?php endif; ?>
                    </div>

                    <div class="dice-arena">
                        <div class="dice-side <?php if ($show_win_banner) echo 'winner'; elseif ($show_loss_banner) echo 'loser'; ?>">
                            <span class="side-label you">&#9654; YOUR ROLL</span>
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
                    </div>

                    <div class="result-indicator">
                        <?php echo $message ? htmlspecialchars($message) : "Awaiting action — confirm your bet and roll the dice."; ?>
                    </div>

                    <?php if ($_SESSION['dice_active']): ?>
                    <div class="action-buttons">
                        <form method="post" id="rollForm" style="display:inline;">
                            <input type="hidden" name="action" value="roll">
                            <button type="submit" class="btn-roll">ROLL THE DICE</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="stop">
                            <button type="submit" class="btn-secondary">END THE GAME / CHANGE BET</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT COLUMN: HISTORY -->
            <div class="col-right">
                <div class="history-panel">
                    <div class="history-head">
                        <div class="panel-title">GAME HISTORY</div>
                    </div>

                    <?php if (empty($_SESSION['dice_history'])): ?>
                        <div class="history-empty">
                            <div class="history-empty-icon">🎲</div>
                            <div>No games played yet.<br>Your results will appear here.</div>
                        </div>
                    <?php else: ?>
                        <div class="history-col-bar">
                            <div class="hcb">Time</div>
                            <div class="hcb">Pattern</div>
                            <div class="hcb" style="text-align:right;">Bet</div>
                            <div class="hcb" style="text-align:center;">Sum</div>
                            <div class="hcb" style="text-align:center;">Result</div>
                            <div class="hcb" style="text-align:right;">Profit</div>
                        </div>
                        <div class="history-rows">
                            <?php foreach ($_SESSION['dice_history'] as $entry): ?>
                            <div class="h-row">
                                <span class="hc-time"><?php echo htmlspecialchars($entry['time']); ?></span>
                                <span class="hc-pattern">
                                    <span class="ptag"><?php echo htmlspecialchars($entry['pattern']); ?></span>
                                </span>
                                <span class="hc-bet"><?php echo number_format($entry['bet']); ?></span>
                                <span class="hc-sum"><?php echo $entry['sum']; ?></span>
                                <span class="hc-result <?php echo $entry['result'] == 'WIN' ? 'win' : 'loss'; ?>">
                                    <?php echo $entry['result']; ?>
                                </span>
                                <span class="hc-profit <?php echo $entry['profit'] >= 0 ? 'pos' : 'neg'; ?>">
                                    <?php echo ($entry['profit'] >= 0 ? '+' : '−') . number_format(abs($entry['profit'])); ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle manual bet input: submit on blur or Enter
        const typedBetField = document.getElementById('typedBet');
        const manualBetForm = document.getElementById('manualBetForm');

        if (typedBetField && manualBetForm) {
            const applyTypedBet = () => {
                let val = parseInt(typedBetField.value, 10);
                const max = parseInt(typedBetField.getAttribute('max'), 10);
                if (isNaN(val) || val < 1) val = 1;
                if (val > max) val = max;
                typedBetField.value = val;
                manualBetForm.submit();
            };
            typedBetField.addEventListener('blur', applyTypedBet);
            typedBetField.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    applyTypedBet();
                }
            });
            typedBetField.addEventListener('input', () => {
                let val = parseInt(typedBetField.value, 10);
                const max = parseInt(typedBetField.getAttribute('max'), 10);
                if (!isNaN(val) && val > max) typedBetField.value = max;
            });
        }

        // Roll dice shake effect
        const rollForm = document.getElementById('rollForm');
        if (rollForm) {
            rollForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const diceImages = document.querySelectorAll('.die-wrap img');
                diceImages.forEach(img => img.classList.add('shake'));
                setTimeout(() => rollForm.submit(), 500);
            });
        }
    </script>
</body>
</html>
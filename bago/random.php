<?php
session_start();

if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 0;
}
if (!isset($_SESSION['current_bet'])) {
    $_SESSION['current_bet'] = 10;
}

$message = '';
$selected_option = null;
$random_number = null;
$win = false;
$payout_multiplier = 0;

$display_number = "?";
$result_status = "";
$result_amount = "";
$result_class = "";
$confirm_message = "";
$confirm_error = "";
$result_detail = "";

$show_win_banner  = false;
$show_loss_banner = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'back') {
    header('Location: Homepage.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust'])) {
    if (isset($_POST['option']) && in_array($_POST['option'], ['odd', 'even', 'lucky7', '1-15', '16-30'])) {
        $_SESSION['selected_option'] = $_POST['option'];
    }

    $adjust = $_POST['adjust'];
    $current_bet = $_SESSION['current_bet'];
    $balance = $_SESSION['balance'];

    switch ($adjust) {
        case '-100': $new_bet = $current_bet - 100; break;
        case '-10':  $new_bet = $current_bet - 10;  break;
        case 'max':  $new_bet = min($balance, 5000); break;
        case '+10':  $new_bet = $current_bet + 10;  break;
        case '+100': $new_bet = $current_bet + 100; break;
        default:     $new_bet = $current_bet;
    }

    $new_bet = max(10, min(5000, $new_bet));
    $_SESSION['current_bet'] = $new_bet;
    unset($_SESSION['confirmed_bet']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_bet'])) {
    if (isset($_POST['option']) && in_array($_POST['option'], ['odd', 'even', 'lucky7', '1-15', '16-30'])) {
        $_SESSION['selected_option'] = $_POST['option'];
    }

    $bet = $_SESSION['current_bet'];

    if ($bet < 10 || $bet > 5000) {
        $confirm_error = "Bet must be between 10 and 5000.";
    } elseif ($bet > $_SESSION['balance']) {
        $confirm_error = "Insufficient balance.";
    } else {
        $_SESSION['confirmed_bet'] = $bet;
        $confirm_message = "Bet confirmed: ₱$bet. Press START to play.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['play'])) {
    if (isset($_POST['option']) && in_array($_POST['option'], ['odd', 'even', 'lucky7', '1-15', '16-30'])) {
        $_SESSION['selected_option'] = $_POST['option'];
    }

    if (!isset($_SESSION['confirmed_bet'])) {
        $message = "Please confirm your bet amount first.";
    } else {
        $bet = $_SESSION['confirmed_bet'];
        $selected_option = $_SESSION['selected_option'] ?? '';

        if (!in_array($selected_option, ['odd', 'even', 'lucky7', '1-15', '16-30'])) {
            $message = "Please select a valid betting pattern.";
        } else {
            $random_number = rand(1, 30);
            $display_number = $random_number;

            switch ($selected_option) {
                case 'odd':    $win = ($random_number % 2 == 1); $payout_multiplier = 2; break;
                case 'even':   $win = ($random_number % 2 == 0); $payout_multiplier = 2; break;
                case 'lucky7': $win = ($random_number % 7 == 0); $payout_multiplier = 3; break;
                case '1-15':   $win = ($random_number >= 1 && $random_number <= 15); $payout_multiplier = 2; break;
                case '16-30':  $win = ($random_number >= 16 && $random_number <= 30); $payout_multiplier = 2; break;
            }

            $_SESSION['balance'] -= $bet;

            if ($win) {
                $winnings = $bet * $payout_multiplier;
                $_SESSION['balance'] += $winnings;
                $profit = $winnings - $bet;
                $show_win_banner = true;
                $result_detail = "You won! +₱" . number_format($profit) . " profit. New balance: ₱" . number_format($_SESSION['balance']) . ".";
            } else {
                $show_loss_banner = true;
                $result_detail = "You lost ₱" . number_format($bet) . ". New balance: ₱" . number_format($_SESSION['balance']) . ".";
            }

            unset($_SESSION['confirmed_bet']);
        }
    }
}

$confirmed_bet     = $_SESSION['confirmed_bet'] ?? null;
$has_confirmed_bet = ($confirmed_bet !== null);
$selected_option   = $_SESSION['selected_option'] ?? null;
$current_bet       = $_SESSION['current_bet'];
$balance_display   = number_format($_SESSION['balance']);
$current_bet_display = number_format($current_bet);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Casino Ni Lady – Random Number</title>
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

        .header-left { display: flex; align-items: center; gap: 16px; }

        .header-logo {
            width: 56px; height: 56px;
            object-fit: contain; border-radius: 8px;
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

        .header-right { display: flex; align-items: center; gap: 20px; }

        .header-divider {
            width: 1px; height: 44px;
            background: linear-gradient(to bottom, transparent, var(--burnished-gold), transparent);
            opacity: 0.5; flex-shrink: 0;
        }

        .header-balance { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; }

        .balance-label {
            color: var(--text-muted); font-size: 0.72rem;
            letter-spacing: 1.5px; text-transform: uppercase;
        }

        .balance-amount {
            color: var(--burnished-gold); font-size: 1.45rem;
            font-weight: bold; letter-spacing: 1px;
            text-shadow: 0 0 8px rgba(212,160,23,0.4);
        }

        .btn-deposit {
            background: linear-gradient(45deg, #1C0A08, #2a100a);
            color: var(--warm-champagne);
            border: 1px solid rgba(212,160,23,0.35);
            padding: 11px 22px;
            font-weight: 600; font-size: 0.78rem;
            letter-spacing: 1.5px; cursor: pointer;
            border-radius: 2px; transition: 0.3s;
            white-space: nowrap; font-family: var(--sans);
            text-decoration: none; display: inline-block;
        }
        .btn-deposit:hover { box-shadow: 0 0 18px rgba(192,21,42,0.65); transform: scale(1.04); }

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
            gap: 16px;
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

        /* --- HELP ICON --- */
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
            transition: 0.2s; user-select: none; z-index: 10;
        }
        .help-icon:hover { background: rgba(212,160,23,0.15); box-shadow: 0 0 10px var(--gold-glow); }

        /* --- MODAL OVERLAY --- */
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
            border-radius: 16px; padding: 40px 44px;
            max-width: 560px; width: 92%; max-height: 80vh;
            overflow-y: auto;
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
            content: ''; position: absolute;
            width: 18px; height: 18px;
            border-color: var(--burnished-gold); border-style: solid;
        }
        .modal-box::before { top: 10px; left: 10px; border-width: 2px 0 0 2px; border-radius: 4px 0 0 0; }
        .modal-box::after  { bottom: 10px; right: 10px; border-width: 0 2px 2px 0; border-radius: 0 0 4px 0; }

        .modal-close {
            position: absolute; top: 14px; right: 16px;
            width: 30px; height: 30px; border-radius: 50%;
            border: 1px solid rgba(212,160,23,0.4);
            background: rgba(14,5,0,0.7); color: var(--burnished-gold);
            font-size: 1rem; font-weight: bold; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
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
            content: ''; display: inline-block;
            width: 24px; height: 1px; background: var(--burnished-gold); opacity: 0.5;
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

        /* --- PATTERN GRID --- */
        .pattern-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-bottom: 16px; }

        .pattern-radio { display: none; }

        .pattern-label {
            border: 1px solid var(--gold-dim); border-radius: 10px;
            padding: 10px 20px; font-size: 1.4rem;
            font-family: 'Times New Roman', serif; font-weight: bold;
            cursor: pointer; transition: 0.2s; min-width: 100px;
            text-align: center; color: var(--warm-champagne);
            background: rgba(212,160,23,0.04);
        }
        .pattern-label:hover { border-color: var(--burnished-gold); background: rgba(212,160,23,0.08); }
        .pattern-radio:checked + .pattern-label {
            border-color: var(--burnished-gold); color: var(--burnished-gold);
            background: rgba(212,160,23,0.12); box-shadow: 0 0 12px var(--gold-glow);
        }

        /* --- PAYOUT PILLS --- */
        .payout-row { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; margin-bottom: 20px; }
        .payout-pill {
            background: rgba(212,160,23,0.07); border: 1px solid var(--gold-dim);
            border-radius: 20px; padding: 4px 13px; font-size: 11px;
            font-family: var(--sans); color: var(--text-muted); letter-spacing: 1px;
        }
        .payout-pill strong { color: var(--burnished-gold); }

        /* --- BET FORM --- */
        .bet-balance-row {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 16px; font-family: var(--sans); font-size: 0.9rem;
        }
        .bal-label { color: var(--text-muted); letter-spacing: 1px; font-size: 0.75rem; }
        .bal-value { color: var(--burnished-gold); font-weight: bold; font-size: 1.1rem; }

        .bet-input-wrap { text-align: center; margin-bottom: 16px; }
        .bet-number-input {
            background: #0E0500; border: 1px solid var(--burnished-gold);
            border-radius: 8px; color: var(--burnished-gold);
            font-size: 1.4rem; font-weight: bold; font-family: var(--sans);
            width: 100%; text-align: center; padding: 10px; outline: none;
            pointer-events: none;
        }

        .adjust-buttons { display: flex; gap: 8px; justify-content: center; margin-bottom: 10px; flex-wrap: wrap; }
        .adjust-btn {
            border: 1px solid var(--gold-dim); background: rgba(212,160,23,0.05);
            color: var(--warm-champagne); padding: 6px 13px; border-radius: 6px;
            font-family: var(--sans); font-size: 0.88rem; font-weight: bold;
            cursor: pointer; transition: 0.15s;
        }
        .adjust-btn:hover:not(:disabled) { border-color: var(--burnished-gold); color: var(--burnished-gold); background: rgba(212,160,23,0.12); }

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
        .btn-confirm:hover { background: rgba(212,160,23,0.17); box-shadow: 0 0 14px var(--gold-glow); }

        .confirm-message { margin-top: 10px; font-size: 0.88rem; color: #4ade80; text-align: center; font-family: var(--sans); }
        .error-message   { color: #f87171; text-align: center; margin-top: 10px; font-size: 0.88rem; font-family: var(--sans); }

        /* --- RESULT PANEL --- */
        .result-panel {
            background: #1C0A08; border: 1px solid var(--gold-dim);
            border-radius: 12px; padding: 28px 26px; flex: 1;
            display: flex; flex-direction: column; transition: box-shadow 0.2s;
        }
        .result-panel:hover { box-shadow: 0 0 18px var(--gold-glow); }

        /* banner */
        .banner {
            font-family: 'Times New Roman', serif; font-size: 2.8rem;
            font-weight: bold; letter-spacing: 6px; text-align: center;
            margin-bottom: 20px; animation: popIn 0.4s ease-out;
        }
        .win-banner  { color: #4ade80; text-shadow: 0 0 20px rgba(74,222,128,0.4); }
        .loss-banner { color: #f87171; text-shadow: 0 0 20px rgba(248,113,113,0.4); }

        @keyframes popIn {
            0%   { transform: scale(0.7); opacity: 0; }
            70%  { transform: scale(1.06); }
            100% { transform: scale(1); opacity: 1; }
        }

        /* number circle */
        .number-arena {
            display: flex; align-items: center; justify-content: center;
            flex: 1; margin-bottom: 18px;
        }

        .circle-wrap {
            display: flex; flex-direction: column;
            align-items: center; justify-content: center; gap: 16px;
        }

        .circle-label {
            font-family: 'Times New Roman', serif; font-size: 1.1rem;
            letter-spacing: 3px; color: var(--text-muted);
        }

        .number-circle {
            width: 200px; height: 200px;
            border: 3px solid var(--burnished-gold);
            border-radius: 50%; display: flex;
            justify-content: center; align-items: center;
            font-size: 5.5rem; font-family: 'Times New Roman', serif;
            background: #0E0500;
            box-shadow: 0 0 30px var(--gold-glow), inset 0 0 30px rgba(212,160,23,0.06);
            color: var(--warm-champagne);
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .number-circle.win-circle  { border-color: #4ade80; box-shadow: 0 0 30px rgba(74,222,128,0.3); }
        .number-circle.loss-circle { border-color: #f87171; box-shadow: 0 0 30px rgba(248,113,113,0.2); }

        .score-badge {
            background: rgba(212,160,23,0.1); border: 1px solid var(--gold-dim);
            border-radius: 20px; padding: 5px 20px; font-family: var(--sans);
            font-size: 0.78rem; letter-spacing: 2px; color: var(--text-muted);
        }
        .score-badge strong { color: var(--burnished-gold); font-size: 1.1rem; margin-left: 6px; }

        /* result indicator */
        .result-indicator {
            background: #1C0A08; border: 1px solid var(--gold-dim);
            border-left: 3px solid var(--burnished-gold); border-radius: 8px;
            padding: 14px 18px; min-height: 85px;
            display: flex; align-items: center; justify-content: center;
            text-align: center; color: var(--warm-champagne);
            font-family: var(--sans); font-size: 0.95rem;
            letter-spacing: 0.5px; word-break: break-word;
            box-sizing: border-box; margin-bottom: 16px;
        }

        /* action buttons */
        .action-buttons { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }

        .btn-roll {
            background: linear-gradient(45deg, #C0152A, #e0294a);
            border: 1px solid rgba(212,160,23,0.35); color: var(--warm-champagne);
            padding: 14px 50px; font-family: 'Times New Roman', serif;
            font-size: 1.5rem; font-weight: bold; letter-spacing: 4px;
            border-radius: 9px; cursor: pointer; transition: 0.25s;
            box-shadow: 0 0 18px rgba(192,21,42,0.35);
        }
        .btn-roll:hover:not(:disabled) { box-shadow: 0 0 28px rgba(192,21,42,0.6); transform: scale(1.03); }
        .btn-roll:disabled { opacity: 0.35; cursor: not-allowed; transform: none; box-shadow: none; }
    </style>
</head>
<body>

    <!-- CSS-only modal checkbox -->
    <input type="checkbox" id="helpToggle" class="help-toggle">

    <!-- MODAL OVERLAY -->
    <div class="modal-overlay">
        <div class="modal-box">
            <label for="helpToggle" class="modal-close" title="Close">✕</label>

            <div class="modal-title">HOW TO PLAY</div>
            <div class="modal-subtitle">Rules &amp; Guidelines — Random Number Game</div>

            <hr class="modal-divider">

            <div class="rule-section">
                <div class="rule-section-title">OBJECTIVE</div>
                <ul class="rule-list">
                    <li>A random number from <strong>1 to 30</strong> is drawn each round.</li>
                    <li>Choose a betting pattern that you believe matches the drawn number to win.</li>
                    <li>Confirm your bet before starting — once the round begins, bets are locked.</li>
                </ul>
            </div>

            <hr class="modal-divider">

            <div class="rule-section">
                <div class="rule-section-title">BETTING PATTERNS &amp; PAYOUTS</div>
                <table class="rule-table">
                    <thead>
                        <tr><th>Pattern</th><th>Condition</th><th>Payout</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><span class="badge">ODD</span></td><td>Drawn number is odd (1, 3, 5 … 29)</td><td>2× your bet</td></tr>
                        <tr><td><span class="badge">EVEN</span></td><td>Drawn number is even (2, 4, 6 … 30)</td><td>2× your bet</td></tr>
                        <tr><td><span class="badge">1–15</span></td><td>Drawn number is between 1 and 15</td><td>2× your bet</td></tr>
                        <tr><td><span class="badge">16–30</span></td><td>Drawn number is between 16 and 30</td><td>2× your bet</td></tr>
                        <tr><td><span class="badge badge-special">LUCKY 7</span></td><td>Drawn number is a multiple of 7 (7, 14, 21, 28)</td><td><strong style="color:#f87171;">3× your bet</strong></td></tr>
                    </tbody>
                </table>
            </div>

            <hr class="modal-divider">

            <div class="rule-section">
                <div class="rule-section-title">HOW TO PLAY</div>
                <ul class="rule-list">
                    <li><strong>Step 1 — Pick a Pattern:</strong> Select one of the five betting patterns on the left panel.</li>
                    <li><strong>Step 2 — Set Your Bet:</strong> Use the +/− buttons to adjust your wager (min ₱10 / max ₱5,000).</li>
                    <li><strong>Step 3 — Confirm Bet:</strong> Press <em>CONFIRM BET</em>. Your bet is now locked for the round.</li>
                    <li><strong>Step 4 — Start:</strong> Press <em>START THE DRAW</em> to reveal the number and see your result.</li>
                </ul>
            </div>

            <hr class="modal-divider">

            <div class="rule-section">
                <div class="rule-section-title">PAYOUT EXAMPLE</div>
                <ul class="rule-list">
                    <li>Bet ₱200 on <strong>ODD</strong> → number drawn is 13 → You receive ₱400 (₱200 profit).</li>
                    <li>Bet ₱500 on <strong>LUCKY 7</strong> → number drawn is 21 → You receive ₱1,500 (₱1,000 profit).</li>
                    <li>Bet ₱100 on <strong>16–30</strong> → number drawn is 5 → You lose ₱100.</li>
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

        <div class="game-title">RANDOM NUMBER &nbsp;&#8212;&nbsp; 1 TO 30</div>

        <div class="columns">

            <!-- LEFT COLUMN -->
            <div class="col-left">

                <!-- Pattern Selection Panel -->
                <div class="panel">
                    <label for="helpToggle" class="help-icon" title="Rules &amp; Help">?</label>
                    <div class="panel-title">PICK YOUR PATTERN</div>

                    <div class="payout-row">
                        <div class="payout-pill">ODD/EVEN <strong>2×</strong></div>
                        <div class="payout-pill">1-15/16-30 <strong>2×</strong></div>
                        <div class="payout-pill">Lucky 7 <strong>3×</strong></div>
                    </div>

                    <form method="post" id="patternForm">
                        <div class="pattern-grid">
                            <input type="radio" name="option" id="o1" value="odd"   class="pattern-radio" <?php if($selected_option=='odd')   echo 'checked'; ?>>
                            <label for="o1" class="pattern-label">ODD</label>

                            <input type="radio" name="option" id="o2" value="even"  class="pattern-radio" <?php if($selected_option=='even')  echo 'checked'; ?>>
                            <label for="o2" class="pattern-label">EVEN</label>

                            <input type="radio" name="option" id="o3" value="lucky7" class="pattern-radio" <?php if($selected_option=='lucky7') echo 'checked'; ?>>
                            <label for="o3" class="pattern-label" style="font-size:2rem;">LUCKY 7</label>

                            <input type="radio" name="option" id="o4" value="1-15"  class="pattern-radio" <?php if($selected_option=='1-15')  echo 'checked'; ?>>
                            <label for="o4" class="pattern-label">1–15</label>

                            <input type="radio" name="option" id="o5" value="16-30" class="pattern-radio" <?php if($selected_option=='16-30') echo 'checked'; ?>>
                            <label for="o5" class="pattern-label">16–30</label>
                        </div>
                    </form>
                </div>

                <!-- Bet Panel -->
                <div class="panel">
                    <div class="panel-title">PLACE YOUR BET</div>

                    <div class="bet-balance-row">
                        <span class="bal-label">YOUR BALANCE</span>
                        <span class="bal-value">₱<?php echo number_format($_SESSION['balance']); ?></span>
                    </div>

                    <form method="post" id="betForm">
                        <input type="hidden" name="option" value="<?php echo htmlspecialchars($selected_option ?? ''); ?>">
                        <div class="bet-input-wrap">
                            <input type="text" class="bet-number-input" value="₱<?php echo $current_bet_display; ?>">
                        </div>
                        <div class="adjust-buttons">
                            <button type="submit" name="adjust" value="-100" class="adjust-btn">-100</button>
                            <button type="submit" name="adjust" value="-10"  class="adjust-btn">-10</button>
                            <button type="submit" name="adjust" value="max"  class="adjust-btn">MAX</button>
                            <button type="submit" name="adjust" value="+10"  class="adjust-btn">+10</button>
                            <button type="submit" name="adjust" value="+100" class="adjust-btn">+100</button>
                        </div>
                        <div class="minmax-row">
                            <span>MIN: 10</span><span>MAX: 5000</span>
                        </div>
                        <div class="gold-sep"></div>
                        <button type="submit" name="confirm_bet" class="btn-confirm">CONFIRM BET</button>
                        <?php if ($confirm_message): ?>
                            <div class="confirm-message"><?php echo htmlspecialchars($confirm_message); ?></div>
                        <?php endif; ?>
                        <?php if ($confirm_error): ?>
                            <div class="error-message"><?php echo htmlspecialchars($confirm_error); ?></div>
                        <?php endif; ?>
                        <?php if ($message): ?>
                            <div class="error-message"><?php echo htmlspecialchars($message); ?></div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <div class="col-right">
                <div class="result-panel">
                    <div class="panel-title">GAME RESULT</div>

                    <?php if ($show_win_banner): ?>
                        <div class="banner win-banner">YOU WIN!</div>
                    <?php elseif ($show_loss_banner): ?>
                        <div class="banner loss-banner">YOU LOSE</div>
                    <?php endif; ?>
                    <div class="number-arena">
                        <div class="circle-wrap">
                            <span class="circle-label">DRAWN NUMBER</span>
                            <div class="number-circle <?php
                                if ($show_win_banner)  echo 'win-circle';
                                elseif ($show_loss_banner) echo 'loss-circle';
                            ?>"><?php echo htmlspecialchars($display_number); ?></div>
                            <div class="score-badge">
                                RANGE <strong>1 — 30</strong>
                            </div>
                        </div>
                    </div>
                    <div class="result-indicator">
                        <?php
                            if ($result_detail) {
                                echo htmlspecialchars($result_detail);
                            } elseif ($has_confirmed_bet && $selected_option) {
                                echo "Bet confirmed — press START THE DRAW to play!";
                            } else {
                                echo "Pick a pattern, confirm your bet, then start the draw.";
                            }
                        ?>
                    </div>
                    <div class="action-buttons">
                        <form method="post">
                            <input type="hidden" name="option" value="<?php echo htmlspecialchars($selected_option ?? ''); ?>">
                            <button
                                type="submit"
                                name="play"
                                class="btn-roll"
                                <?php echo ($has_confirmed_bet && $selected_option) ? '' : 'disabled'; ?>
                            >START THE DRAW</button>
                        </form>
                    </div>
                </div><!-- /result-panel -->
            </div><!-- /col-right -->
        </div><!-- /columns -->
    </div><!-- /game-wrapper -->

</body>
</html>

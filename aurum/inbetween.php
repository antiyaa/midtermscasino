<?php
session_start();

if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 99999;
}
if (!isset($_SESSION['bets'])) {
    $_SESSION['bets'] = [];
}
if (!isset($_SESSION['bets_confirmed'])) {
    $_SESSION['bets_confirmed'] = false;
}
if (!isset($_SESSION['color_game_history'])) {
    $_SESSION['color_game_history'] = [];
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

$message = '';
$die1 = null;
$die2 = null;
$show_win_banner = false;
$show_loss_banner = false;
$roll_results = [];

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'back') {
    header('Location: Homepage.php');
    exit;
}

if ($action === 'stop') {
    $total_bets_to_refund = array_sum($_SESSION['bets']);
    if ($total_bets_to_refund > 0) {
        $_SESSION['balance'] += $total_bets_to_refund;
        $message = "All bets have been cleared. " . number_format($total_bets_to_refund) . " refunded to your balance.";
    } else {
        $message = "All bets have been cleared.";
    }
    $_SESSION['bets'] = [];
    $_SESSION['bets_confirmed'] = false;
}

if ($action === 'confirm_bets') {
    if (empty($_SESSION['bets'])) {
        $message = "No bets to confirm. Please place at least one bet first.";
    } else {
        $_SESSION['bets_confirmed'] = true;
        $message = "Bets are locked. You can now roll the dice.";
    }
}

if ($action === 'edit_bets') {
    $_SESSION['bets_confirmed'] = false;
    $message = "You can now add or remove bets. Don't forget to confirm again before rolling.";
}

if ($action === 'place_bet' && isset($_POST['color']) && in_array($_POST['color'], $colors)) {
    if ($_SESSION['bets_confirmed']) {
        $message = "Bets are locked. Please click 'EDIT BETS' to make changes, then confirm again.";
    } else {
        $color = $_POST['color'];
        $chip_option = $_POST['chip_amount'] ?? '';
        $bet_amount = 0;
        if ($chip_option === 'all_in') {
            $bet_amount = $_SESSION['balance'];
        } elseif (in_array($chip_option, [10, 20, 50, 100, 1000])) {
            $bet_amount = (int)$chip_option;
        } else {
            $message = "Please select a valid chip amount.";
        }
        if ($bet_amount > 0) {
            if ($bet_amount > $_SESSION['balance']) {
                $message = "Insufficient balance. You have " . number_format($_SESSION['balance']) . ".";
            } else {
                $_SESSION['balance'] -= $bet_amount;
                if (isset($_SESSION['bets'][$color])) {
                    $_SESSION['bets'][$color] += $bet_amount;
                } else {
                    $_SESSION['bets'][$color] = $bet_amount;
                }
                $message = "You placed " . number_format($bet_amount) . " on $color.";
            }
        } else {
            $message = "Invalid chip amount selected.";
        }
    }
}

if ($action === 'roll') {
    if (empty($_SESSION['bets'])) {
        $message = "No bets placed. Please place at least one bet before rolling.";
    } elseif (!$_SESSION['bets_confirmed']) {
        $message = "Please confirm your bets first using the 'CONFIRM BETS' button.";
    } else {
        $die1 = $colors[array_rand($colors)];
        $die2 = $colors[array_rand($colors)];
        $total_winnings = 0;
        $roll_results = [];
        $total_bets = array_sum($_SESSION['bets']);
        foreach ($_SESSION['bets'] as $color => $bet_amount) {
            $matches = 0;
            if ($die1 === $color) $matches++;
            if ($die2 === $color) $matches++;
            $win_amount = 0;
            if ($matches === 1) { $win_amount = $bet_amount * 2; }
            elseif ($matches === 2) { $win_amount = $bet_amount * 4; }
            if ($win_amount > 0) {
                $total_winnings += $win_amount;
                $roll_results[$color] = ['bet' => $bet_amount, 'matches' => $matches, 'win' => $win_amount];
            } else {
                $roll_results[$color] = ['bet' => $bet_amount, 'matches' => 0, 'win' => 0];
            }
        }
        $_SESSION['balance'] += $total_winnings;
        $net_profit = $total_winnings - $total_bets;
        if ($net_profit > 0) {
            $show_win_banner = true;
            $result_text = "WIN";
            $message = "You rolled $die1 and $die2. Total winnings: " . number_format($total_winnings) . ". Net profit: +" . number_format($net_profit) . ". New balance: " . number_format($_SESSION['balance']) . ".";
        } elseif ($net_profit < 0) {
            $show_loss_banner = true;
            $result_text = "LOSS";
            $message = "You rolled $die1 and $die2. No winning bets. Net loss: " . number_format(abs($net_profit)) . ". New balance: " . number_format($_SESSION['balance']) . ".";
        } else {
            $result_text = "PUSH";
            $message = "You rolled $die1 and $die2. You broke even. New balance: " . number_format($_SESSION['balance']) . ".";
        }
        $pattern_parts = [];
        foreach ($_SESSION['bets'] as $color => $amount) {
            $pattern_parts[] = substr($color, 0, 1) . ":" . number_format($amount);
        }
        $pattern_summary = implode(', ', $pattern_parts);
        $history_entry = [
            'time'    => date('H:i:s'),
            'pattern' => $pattern_summary,
            'bet'     => $total_bets,
            'dice1'   => $die1,
            'dice2'   => $die2,
            'result'  => $result_text,
            'profit'  => $net_profit,
        ];
        array_unshift($_SESSION['color_game_history'], $history_entry);
        if (count($_SESSION['color_game_history']) > 20) {
            array_pop($_SESSION['color_game_history']);
        }
        $_SESSION['bets'] = [];
        $_SESSION['bets_confirmed'] = false;
    }
}

$balance = $_SESSION['balance'];
$balance_display = number_format($balance);
$bets = $_SESSION['bets'];
$total_bets = array_sum($bets);
$bets_confirmed = $_SESSION['bets_confirmed'];
$selected_chip = $_POST['chip_amount'] ?? ($_GET['chip'] ?? '');

$potential_win_summary = [];
if ($bets_confirmed) {
    foreach ($bets as $color => $amount) {
        $potential_win_summary[$color] = ['one' => $amount * 2, 'two' => $amount * 4];
    }
}
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

        /* ── HEADER ── */
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
        .balance-label  { color: var(--text-muted); font-size: 0.72rem; letter-spacing: 1.5px; text-transform: uppercase; }
        .balance-amount {
            color: var(--burnished-gold); font-size: 1.45rem; font-weight: bold;
            letter-spacing: 1px; text-shadow: 0 0 8px rgba(212,160,23,0.4);
        }
        .btn-deposit {
            background: linear-gradient(45deg, #C0152A, #e0294a);
            color: var(--warm-champagne); border: none; padding: 11px 22px;
            font-weight: bold; font-size: 0.82rem; letter-spacing: 1.5px;
            cursor: pointer; border-radius: 8px; transition: 0.3s;
            white-space: nowrap; font-family: var(--sans);
        }
        .btn-deposit:hover { box-shadow: 0 0 18px rgba(192,21,42,0.65); transform: scale(1.04); }

        /* ── SUB-HEADER BAR ── */
        .sub-header {
            background: rgba(14,5,0,0.6);
            border-bottom: 1px solid var(--gold-dim);
            padding: 10px 32px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .back-btn {
            background: transparent;
            border: 1px solid var(--gold-dim);
            color: var(--text-muted);
            padding: 6px 16px;
            border-radius: 6px;
            font-family: var(--sans);
            font-size: 0.8rem;
            font-weight: bold;
            letter-spacing: 1.5px;
            cursor: pointer;
            transition: 0.2s;
        }
        .back-btn:hover {
            border-color: var(--burnished-gold);
            color: var(--burnished-gold);
            background: rgba(212,160,23,0.08);
        }
        .page-breadcrumb {
            font-family: var(--sans);
            font-size: 0.72rem;
            letter-spacing: 2.5px;
            color: var(--text-muted);
            text-transform: uppercase;
        }
        .page-breadcrumb span { color: var(--burnished-gold); }

        /* ── MAIN LAYOUT ── */
        .game-wrapper {
            flex: 1;
            display: flex;
            gap: 18px;
            padding: 20px 24px 32px;
            min-height: 0;
            align-items: flex-start;
        }

        /* ── PANEL ── */
        .panel {
            background: #1C0A08;
            border: 1px solid var(--gold-dim);
            border-radius: 12px;
            padding: 22px 24px;
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
            display: block;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--burnished-gold), transparent);
            margin: 8px auto 18px;
            width: 70%;
        }

        /* ── HELP MODAL ── */
        .help-toggle { display: none; }
        .help-icon {
            position: absolute; top: 14px; right: 14px;
            border: 1px solid rgba(212,160,23,0.5); border-radius: 50%;
            width: 28px; height: 28px; display: flex; justify-content: center; align-items: center;
            color: var(--burnished-gold); background: rgba(14,5,0,0.6);
            font-size: 13px; cursor: pointer; font-weight: bold;
            transition: 0.2s; user-select: none; z-index: 10;
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
            padding: 40px 44px; max-width: 560px; width: 92%; max-height: 80vh;
            overflow-y: auto; box-shadow: 0 0 60px rgba(212,160,23,0.25);
            position: relative; animation: modalIn 0.3s ease-out;
        }
        .modal-box::-webkit-scrollbar { width: 6px; }
        .modal-box::-webkit-scrollbar-track { background: rgba(212,160,23,0.1); border-radius: 8px; }
        .modal-box::-webkit-scrollbar-thumb { background: var(--burnished-gold); border-radius: 8px; }
        @keyframes modalIn {
            0%   { transform: scale(0.85) translateY(20px); opacity: 0; }
            100% { transform: scale(1) translateY(0);        opacity: 1; }
        }
        .modal-box::before, .modal-box::after {
            content: ''; position: absolute;
            width: 18px; height: 18px;
            border-color: var(--burnished-gold); border-style: solid;
        }
        .modal-box::before { top: 10px; left: 10px; border-width: 2px 0 0 2px; border-radius: 4px 0 0 0; }
        .modal-box::after  { bottom: 10px; right: 10px; border-width: 0 2px 2px 0; border-radius: 0 0 4px 0; }
        .modal-close {
            position: absolute; top: 14px; right: 16px; width: 30px; height: 30px;
            border-radius: 50%; border: 1px solid rgba(212,160,23,0.4);
            background: rgba(14,5,0,0.7); color: var(--burnished-gold);
            font-size: 1rem; font-weight: bold; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; transition: 0.2s; line-height: 1;
        }
        .modal-close:hover { background: rgba(212,160,23,0.18); box-shadow: 0 0 10px var(--gold-glow); }
        .modal-title {
            font-family: 'Times New Roman', serif; font-size: 1.9rem;
            color: var(--burnished-gold); text-align: center; letter-spacing: 3px; margin-bottom: 6px;
            text-shadow: 0 0 12px rgba(212,160,23,0.35);
        }
        .modal-subtitle {
            text-align: center; color: var(--text-muted); font-size: 0.75rem;
            letter-spacing: 2px; text-transform: uppercase; margin-bottom: 28px;
        }
        .modal-divider { border: none; border-top: 1px solid rgba(212,160,23,0.2); margin: 18px 0; }
        .rule-section { margin-bottom: 22px; }
        .rule-section-title {
            font-family: 'Times New Roman', serif; font-size: 1.05rem;
            color: var(--burnished-gold); letter-spacing: 2px; margin-bottom: 12px;
            display: flex; align-items: center; gap: 8px;
        }
        .rule-section-title::before {
            content: ''; display: inline-block; width: 24px; height: 1px;
            background: var(--burnished-gold); opacity: 0.5;
        }
        .rule-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        .rule-table th {
            background: rgba(212,160,23,0.08); color: var(--burnished-gold);
            text-transform: uppercase; letter-spacing: 1.5px; font-size: 0.72rem;
            padding: 8px 12px; text-align: left; border-bottom: 1px solid rgba(212,160,23,0.2);
            font-family: var(--sans);
        }
        .rule-table td {
            padding: 9px 12px; color: var(--warm-champagne);
            border-bottom: 1px solid rgba(212,160,23,0.07);
            font-family: var(--sans); vertical-align: middle;
        }
        .rule-table tr:last-child td { border-bottom: none; }
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

        /* ── LEFT COLUMN ── */
        .col-left {
            width: 300px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* Chip selector — circular casino chips */
        .chip-row {
            display: flex; gap: 8px; flex-wrap: wrap;
            justify-content: center; margin-bottom: 18px;
        }
        .chip-radio input { display: none; }
        .chip-label {
            display: flex; align-items: center; justify-content: center;
            width: 48px; height: 48px; border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.12);
            background: #120500;
            font-family: 'Times New Roman', serif;
            font-size: 0.7rem; font-weight: bold;
            color: var(--warm-champagne); cursor: pointer;
            transition: 0.2s; text-align: center; line-height: 1.2;
            box-shadow: inset 0 2px 6px rgba(0,0,0,0.5), 0 2px 8px rgba(0,0,0,0.3);
        }
        .chip-label[data-val="10"]    { border-color: #6b7280; }
        .chip-label[data-val="20"]    { border-color: #16a34a; }
        .chip-label[data-val="50"]    { border-color: #2563eb; }
        .chip-label[data-val="100"]   { border-color: #b91c1c; }
        .chip-label[data-val="1000"]  { border-color: #7c3aed; }
        .chip-label[data-val="allin"] { border-color: var(--burnished-gold); color: var(--burnished-gold); font-size: 0.6rem; }
        .chip-radio input:checked + .chip-label {
            transform: translateY(-5px) scale(1.12);
            box-shadow: 0 6px 18px rgba(212,160,23,0.4), inset 0 2px 6px rgba(0,0,0,0.5);
            border-color: var(--burnished-gold) !important;
            color: var(--burnished-gold);
        }
        .chip-radio input:disabled + .chip-label { opacity: 0.35; cursor: not-allowed; transform: none; }

        /* Color buttons */
        .color-bet-grid {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 18px;
        }
        .color-bet-btn {
            position: relative; border: 2px solid transparent;
            border-radius: 10px; padding: 16px 8px 12px;
            font-family: 'Times New Roman', serif; font-size: 1rem;
            font-weight: bold; letter-spacing: 2px;
            color: #fff; cursor: pointer; transition: 0.2s;
            text-align: center; text-shadow: 0 1px 4px rgba(0,0,0,0.8);
            box-shadow: 0 4px 12px rgba(0,0,0,0.4);
        }
        .color-bet-btn:hover:not(:disabled) {
            border-color: var(--burnished-gold);
            transform: scale(1.03);
            box-shadow: 0 0 14px var(--gold-glow), 0 4px 12px rgba(0,0,0,0.4);
        }
        .color-bet-btn:disabled { opacity: 0.45; cursor: not-allowed; transform: none; }
        .bet-amount-badge {
            display: block; margin-top: 5px;
            background: rgba(0,0,0,0.55); border: 1px solid rgba(255,255,255,0.15);
            border-radius: 20px; font-family: var(--sans);
            font-size: 0.68rem; letter-spacing: 1px; padding: 2px 8px;
            color: #fff; font-weight: bold;
        }

        /* Bets summary */
        .gold-sep { height: 1px; background: linear-gradient(90deg, transparent, var(--gold-dim), transparent); margin: 4px 0 14px; }
        .bets-sub-label {
            font-family: var(--sans); font-size: 0.68rem; letter-spacing: 3px;
            color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;
        }
        .bets-list { display: flex; flex-direction: column; gap: 5px; margin-bottom: 10px; }
        .bets-list-row {
            display: flex; justify-content: space-between;
            font-family: var(--sans); font-size: 0.88rem;
        }
        .bets-list-color { font-weight: bold; }
        .bets-list-amount { color: var(--burnished-gold); }
        .bets-total-row {
            display: flex; justify-content: space-between;
            padding-top: 8px; border-top: 1px solid var(--gold-dim);
            font-family: var(--sans); font-size: 0.88rem;
        }
        .bets-total-label { color: var(--text-muted); letter-spacing: 1.5px; font-size: 0.72rem; }
        .bets-total-value { color: var(--burnished-gold); font-weight: bold; font-size: 1rem; }
        .no-bets-msg { font-family: var(--sans); font-size: 0.8rem; color: var(--text-muted); font-style: italic; }

        /* Locked badge */
        .locked-badge {
            display: flex; align-items: center; justify-content: center; gap: 6px;
            background: rgba(212,160,23,0.06); border: 1px solid rgba(212,160,23,0.22);
            border-radius: 7px; padding: 7px 12px; margin-top: 10px;
            font-family: var(--sans); font-size: 0.75rem; letter-spacing: 1.5px;
            color: var(--burnished-gold);
        }

        /* Info pills (locked mode) */
        .info-pills { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
        .info-pill {
            background: rgba(212,160,23,0.06); border: 1px solid var(--gold-dim);
            border-radius: 8px; padding: 10px 14px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .pill-label { font-family: var(--sans); font-size: 0.68rem; letter-spacing: 2px; color: var(--text-muted); text-transform: uppercase; }
        .pill-value { font-family: var(--sans); font-size: 1.05rem; font-weight: bold; color: var(--burnished-gold); }
        .active-note { text-align: center; font-family: var(--sans); font-size: 0.78rem; color: var(--text-muted); letter-spacing: 1px; margin-top: 6px; }

        /* Confirm row */
        .confirm-row { display: flex; gap: 10px; margin-top: 14px; }
        .btn-confirm-bets {
            flex: 1; background: rgba(212,160,23,0.08); border: 1px solid var(--burnished-gold);
            color: var(--burnished-gold); padding: 10px;
            font-family: 'Times New Roman', serif; font-size: 1rem;
            font-weight: bold; letter-spacing: 2px; cursor: pointer;
            border-radius: 8px; transition: 0.2s;
        }
        .btn-confirm-bets:hover:not(:disabled) { background: rgba(212,160,23,0.17); box-shadow: 0 0 14px var(--gold-glow); }
        .btn-confirm-bets:disabled { opacity: 0.35; cursor: not-allowed; }
        .btn-edit-bets {
            flex: 1; background: rgba(212,160,23,0.05); border: 1px solid var(--gold-dim);
            color: var(--warm-champagne); padding: 10px; font-family: var(--sans);
            font-size: 0.88rem; font-weight: bold; letter-spacing: 1.5px; cursor: pointer;
            border-radius: 8px; transition: 0.2s;
        }
        .btn-edit-bets:hover { border-color: var(--burnished-gold); color: var(--burnished-gold); background: rgba(212,160,23,0.12); }
        .btn-clear {
            flex: 1; background: rgba(192,21,42,0.06); border: 1px solid rgba(192,21,42,0.28);
            color: #f87171; padding: 10px; font-family: var(--sans);
            font-size: 0.88rem; font-weight: bold; letter-spacing: 1.5px; cursor: pointer;
            border-radius: 8px; transition: 0.2s;
        }
        .btn-clear:hover:not(:disabled) { background: rgba(192,21,42,0.13); border-color: #C0152A; }
        .btn-clear:disabled { opacity: 0.35; cursor: not-allowed; }

        /* ── CENTER COLUMN ── */
        .col-center {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* Dice area */
        .dice-area {
            background: #1C0A08; border: 1px solid var(--gold-dim);
            border-radius: 12px; padding: 26px 28px;
            display: flex; flex-direction: column;
            align-items: center; gap: 18px;
            transition: box-shadow 0.2s; flex: 1;
        }
        .dice-area:hover { box-shadow: 0 0 20px var(--gold-glow); }
        .dice-title {
            font-family: 'Times New Roman', serif; font-size: 1.4rem;
            color: var(--burnished-gold); letter-spacing: 4px;
            text-shadow: 0 0 12px var(--gold-glow);
        }
        .dice-title::after {
            content: ''; display: block; height: 1px;
            background: linear-gradient(90deg, transparent, var(--burnished-gold), transparent);
            margin: 8px auto 0; width: 60%;
        }

        /* Banner slot — fixed height so layout never shifts */
        .banner-slot {
            height: 3.2rem; display: flex; align-items: center;
            justify-content: center; flex-shrink: 0;
        }
        .banner {
            font-family: 'Times New Roman', serif; font-size: 2.8rem;
            font-weight: bold; letter-spacing: 6px; animation: popIn 0.4s ease-out; line-height: 1;
        }
        .win-banner  { color: #4ade80; text-shadow: 0 0 24px rgba(74,222,128,0.5); }
        .loss-banner { color: #f87171; text-shadow: 0 0 24px rgba(248,113,113,0.5); }
        @keyframes popIn {
            0%   { transform: scale(0.7); opacity: 0; }
            70%  { transform: scale(1.06); }
            100% { transform: scale(1); opacity: 1; }
        }

        /* Dice pair */
        .dice-row { display: flex; justify-content: center; gap: 36px; flex-wrap: wrap; }
        .die {
            width: 200px; background: #0E0500;
            border: 2px solid var(--burnished-gold);
            border-radius: 16px; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 26px 18px; gap: 14px;
            box-shadow: 0 0 16px var(--gold-glow); transition: transform 0.2s;
        }
        .die:hover { transform: translateY(-4px); }
        .die-label {
            font-family: 'Times New Roman', serif; font-size: 1.5rem;
            font-weight: bold; letter-spacing: 2px; color: var(--warm-champagne);
        }
        .die-label.placeholder { color: var(--text-muted); font-size: 2.4rem; }
        .die-color-circle {
            width: 100px; height: 100px; border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.18);
            box-shadow: 0 0 18px rgba(0,0,0,0.5), inset 0 0 14px rgba(255,255,255,0.06);
        }
        .die-tag { font-family: var(--sans); font-size: 0.7rem; letter-spacing: 2px; color: var(--text-muted); }

        /* Roll breakdown */
        .roll-breakdown {
            width: 100%; background: rgba(14,5,0,0.6);
            border: 1px solid var(--gold-dim); border-radius: 8px; padding: 10px 14px;
            display: flex; flex-direction: column; gap: 5px;
        }
        .roll-breakdown-row {
            display: flex; justify-content: space-between;
            font-family: var(--sans); font-size: 0.82rem;
        }
        .rb-color { font-weight: bold; }
        .rb-win   { color: #4ade80; }
        .rb-loss  { color: #f87171; }

        /* Message box — fixed height inside dice area */
        .message-box {
            width: 100%; height: 44px; min-height: 44px; max-height: 44px;
            background: rgba(14,5,0,0.6); border: 1px solid var(--gold-dim);
            border-left: 3px solid var(--burnished-gold); border-radius: 8px;
            padding: 0 18px; display: flex; align-items: center; justify-content: center;
            color: var(--warm-champagne); font-family: var(--sans); font-size: 0.88rem;
            letter-spacing: 0.5px; text-align: center; flex-shrink: 0;
            overflow: hidden; white-space: nowrap; text-overflow: ellipsis;
        }

        /* Roll button row */
        .action-buttons { display: flex; gap: 14px; justify-content: center; align-items: center; }
        .btn-roll {
            background: linear-gradient(45deg, #C0152A, #e0294a);
            border: 1px solid rgba(212,160,23,0.35); color: var(--warm-champagne);
            padding: 13px 48px; font-family: 'Times New Roman', serif;
            font-size: 1.5rem; font-weight: bold; letter-spacing: 4px;
            border-radius: 9px; cursor: pointer; transition: 0.25s;
            box-shadow: 0 0 18px rgba(192,21,42,0.35);
        }
        .btn-roll:hover:not(:disabled) { box-shadow: 0 0 28px rgba(192,21,42,0.6); transform: scale(1.03); }
        .btn-roll:disabled { opacity: 0.35; cursor: not-allowed; box-shadow: none; }

        /* ── RIGHT COLUMN ── */
        .col-right {
            width: 430px;
            flex-shrink: 0;
        }

        /* History panel */
        .history-panel {
            background: #1C0A08; border: 1px solid var(--gold-dim);
            border-radius: 12px; overflow: hidden;
            display: flex; flex-direction: column; transition: box-shadow 0.2s;
            height: 100%;
        }
        .history-panel:hover { box-shadow: 0 0 18px var(--gold-glow); }
        .history-head { padding: 22px 22px 0; flex-shrink: 0; }

        .history-col-bar {
            display: grid;
            grid-template-columns: 50px 1fr 58px 34px 46px 66px;
            padding: 0 18px 8px;
            margin-top: 4px;
            border-bottom: 1px solid rgba(212,160,23,0.18);
        }
        .hcb {
            font-family: var(--sans); font-size: 0.62rem;
            text-transform: uppercase; letter-spacing: 1.3px; color: var(--text-muted); padding: 6px 3px 4px;
        }

        .history-rows { max-height: 560px; overflow-y: auto; padding-bottom: 10px; }
        .history-rows::-webkit-scrollbar { width: 4px; }
        .history-rows::-webkit-scrollbar-track { background: transparent; }
        .history-rows::-webkit-scrollbar-thumb { background: rgba(212,160,23,0.28); border-radius: 4px; }

        .h-row {
            display: grid;
            grid-template-columns: 50px 1fr 58px 34px 46px 66px;
            padding: 8px 18px;
            border-bottom: 1px solid rgba(212,160,23,0.06);
            align-items: center; transition: background 0.12s;
        }
        .h-row:hover { background: rgba(212,160,23,0.04); }
        .h-row:last-child { border-bottom: none; }

        .hc-time    { font-size: 0.68rem; color: var(--text-muted); font-family: var(--sans); }
        .hc-pattern { font-size: 0.74rem; font-family: var(--sans); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .hc-bet     { font-size: 0.74rem; font-family: var(--sans); color: var(--warm-champagne); text-align: right; padding-right: 4px; }
        .hc-dice    { font-size: 0.74rem; font-weight: bold; color: var(--burnished-gold); text-align: center; }
        .hc-result  {
            font-size: 0.63rem; font-weight: bold; font-family: var(--sans);
            letter-spacing: 0.5px; text-align: center;
            padding: 2px 4px; border-radius: 4px; width: fit-content; margin: 0 auto;
        }
        .hc-result.win  { color: #4ade80; background: rgba(74,222,128,0.1);   border: 1px solid rgba(74,222,128,0.22); }
        .hc-result.loss { color: #f87171; background: rgba(248,113,113,0.08); border: 1px solid rgba(248,113,113,0.18); }
        .hc-result.push { color: #facc15; background: rgba(250,204,21,0.08);  border: 1px solid rgba(250,204,21,0.18); }
        .hc-profit  { font-size: 0.74rem; font-family: var(--sans); font-weight: bold; text-align: right; }
        .hc-profit.pos  { color: #4ade80; }
        .hc-profit.neg  { color: #f87171; }
        .hc-profit.zero { color: #facc15; }

        .history-empty {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 12px; padding: 48px 20px;
            color: var(--text-muted); font-family: var(--sans); font-size: 0.8rem;
            letter-spacing: 1px; text-align: center;
        }
        .history-empty-dot {
            width: 40px; height: 40px; border-radius: 50%;
            border: 1px solid var(--gold-dim);
            display: flex; align-items: center; justify-content: center;
            color: var(--gold-dim); font-size: 1.4rem;
        }

        /* Payout pills */
        .payout-row { display: flex; gap: 8px; justify-content: center; margin-bottom: 16px; }
        .payout-pill {
            background: rgba(212,160,23,0.06); border: 1px solid var(--gold-dim);
            border-radius: 20px; padding: 4px 14px; font-size: 0.72rem;
            font-family: var(--sans); color: var(--text-muted); letter-spacing: 1px;
        }
        .payout-pill strong { color: var(--burnished-gold); }

        @keyframes colorFlash {
            0%   { background: #b91c1c; }
            16%  { background: #166534; }
            33%  { background: #1d4ed8; }
            50%  { background: #ca8a04; }
            66%  { background: #6b21a8; }
            83%  { background: #c2410f; }
            100% { background: var(--final-color, #b91c1c); }
        }
        .die-color-circle.flash { animation: colorFlash 0.6s ease-in-out forwards; }
    </style>
</head>
<body>

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
                <li>Select a chip value, then click on a color to place that bet on that color.</li>
                <li>You can bet on multiple colors and stack chips on the same color.</li>
                <li>Once you're done betting, click CONFIRM BETS to lock your bets.</li>
                <li>After confirmation, click ROLL DICE to see the results.</li>
            </ul>
        </div>
        <hr class="modal-divider">
        <div class="rule-section">
            <div class="rule-section-title">PAYOUTS</div>
            <table class="rule-table">
                <thead><tr><th>Match Type</th><th>Multiplier</th><th>Example (Bet 100)</th></tr></thead>
                <tbody>
                    <tr><td>Exactly one die matches</td><td>2x</td><td>Win 200 (100 profit)</td></tr>
                    <tr><td>Both dice match</td><td>4x</td><td>Win 400 (300 profit)</td></tr>
                </tbody>
            </table>
        </div>
        <hr class="modal-divider">
        <div class="rule-section">
            <div class="rule-section-title">HOW TO PLAY</div>
            <ul class="rule-list">
                <li>Step 1 — Choose a chip amount (10, 20, 50, 100, 1000, or ALL IN).</li>
                <li>Step 2 — Click on any color button to place that chip onto that color.</li>
                <li>Step 3 — The bet is instantly deducted from your balance and shown on the color.</li>
                <li>Step 4 — Repeat steps 1-3 to stack more bets across any colors.</li>
                <li>Step 5 — Press CONFIRM BETS to lock your selections.</li>
                <li>Step 6 — Press ROLL DICE to see the dice and collect winnings.</li>
            </ul>
        </div>
        <div class="modal-note">Please gamble responsibly. Results are random and cannot be predicted.</div>
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
            <span class="balance-amount">&#8369;<?php echo htmlspecialchars($balance_display); ?></span>
        </div>
        <button class="btn-deposit" onclick="location.href='topup.php'">+ TOP UP</button>
    </div>
</header>

<!-- Sub-header with breadcrumb + back -->
<div class="sub-header">
    <form method="post" style="display:inline;">
        <input type="hidden" name="action" value="back">
        <button type="submit" class="back-btn">&#8592; BACK</button>
    </form>
    <span class="page-breadcrumb">Homepage &rsaquo; <span>Color Game</span></span>
</div>

<div class="game-wrapper">

    <!-- ── LEFT: BETTING CONTROLS ── -->
    <div class="col-left">
        <?php if (!$bets_confirmed): ?>
        <div class="panel">
            <label for="helpToggle" class="help-icon" title="Rules & Help">?</label>
            <div class="panel-title">PLACE YOUR BETS</div>

            <form method="post" id="betForm">
                <input type="hidden" name="action" value="place_bet">

                <!-- Circular chip selector -->
                <div class="chip-row">
                    <?php $chips = ['10'=>'10','20'=>'20','50'=>'50','100'=>'100','1000'=>'1K','all_in'=>'ALL IN'];
                    foreach ($chips as $val => $lbl): ?>
                        <label class="chip-radio">
                            <input type="radio" name="chip_amount" value="<?php echo $val; ?>"
                                <?php echo ($selected_chip == $val) ? 'checked' : ''; ?>>
                            <div class="chip-label" data-val="<?php echo $val === 'all_in' ? 'allin' : $val; ?>"><?php echo $lbl; ?></div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Payout reference -->
                <div class="payout-row">
                    <div class="payout-pill">1 match <strong>2x</strong></div>
                    <div class="payout-pill">2 match <strong>4x</strong></div>
                </div>

                <!-- Color buttons -->
                <div class="color-bet-grid">
                    <?php foreach ($colors as $color):
                        $currentBet = $bets[$color] ?? 0;
                    ?>
                        <button type="submit" name="color" value="<?php echo $color; ?>"
                                class="color-bet-btn"
                                style="background:<?php echo $color_classes[$color]; ?>;">
                            <?php echo strtoupper($color); ?>
                            <?php if ($currentBet > 0): ?>
                                <span class="bet-amount-badge">&#8369;<?php echo number_format($currentBet); ?></span>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </form>

            <!-- Bets summary -->
            <div class="gold-sep"></div>
            <div class="bets-sub-label">YOUR BETS</div>

            <?php if (empty($bets)): ?>
                <div class="no-bets-msg">No bets placed yet. Select a chip and click a color.</div>
            <?php else: ?>
                <div class="bets-list">
                    <?php foreach ($bets as $color => $amount): ?>
                        <div class="bets-list-row">
                            <span class="bets-list-color" style="color:<?php echo $color_classes[$color]; ?>"><?php echo $color; ?></span>
                            <span class="bets-list-amount">&#8369;<?php echo number_format($amount); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="bets-total-row">
                    <span class="bets-total-label">TOTAL BET</span>
                    <span class="bets-total-value">&#8369;<?php echo number_format($total_bets); ?></span>
                </div>
            <?php endif; ?>

            <div class="confirm-row">
                <form method="post" style="flex:1;display:flex;">
                    <input type="hidden" name="action" value="confirm_bets">
                    <button type="submit" class="btn-confirm-bets" style="flex:1;" <?php echo empty($bets) ? 'disabled' : ''; ?>>CONFIRM</button>
                </form>
                <form method="post" style="flex:1;display:flex;">
                    <input type="hidden" name="action" value="stop">
                    <button type="submit" class="btn-clear" style="flex:1;" <?php echo empty($bets) ? 'disabled' : ''; ?>>CLEAR</button>
                </form>
            </div>
        </div>

        <?php else: ?>
        <!-- Locked mode summary -->
        <div class="panel">
            <div class="panel-title">BETS LOCKED</div>
            <div class="info-pills">
                <div class="info-pill">
                    <span class="pill-label">Balance</span>
                    <span class="pill-value">&#8369;<?php echo number_format($_SESSION['balance']); ?></span>
                </div>
                <div class="info-pill">
                    <span class="pill-label">Total Bet</span>
                    <span class="pill-value">&#8369;<?php echo number_format($total_bets); ?></span>
                </div>
                <?php foreach ($bets as $color => $amount): ?>
                <div class="info-pill">
                    <span class="pill-label" style="color:<?php echo $color_classes[$color]; ?>"><?php echo strtoupper($color); ?></span>
                    <span class="pill-value">&#8369;<?php echo number_format($amount); ?></span>
                </div>
                <?php endforeach; ?>
                <div class="info-pill">
                    <span class="pill-label">Max Possible Win</span>
                    <span class="pill-value">&#8369;<?php
                        $max_win = 0;
                        foreach ($bets as $color => $amount) $max_win = max($max_win, $amount * 4);
                        echo number_format($max_win);
                    ?></span>
                </div>
            </div>
            <p class="active-note">Bets are locked. Roll when ready.</p>
            <div class="locked-badge">BETS LOCKED — READY TO ROLL</div>
            <div class="confirm-row">
                <form method="post" style="flex:1;display:flex;">
                    <input type="hidden" name="action" value="edit_bets">
                    <button type="submit" class="btn-edit-bets" style="flex:1;">EDIT BETS</button>
                </form>
                <form method="post" style="flex:1;display:flex;">
                    <input type="hidden" name="action" value="stop">
                    <button type="submit" class="btn-clear" style="flex:1;">CLEAR ALL</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── CENTER: DICE + RESULT ── -->
    <div class="col-center">
        <div class="dice-area">
            <div class="dice-title">DICE RESULTS</div>

            <!-- Fixed-height banner slot -->
            <div class="banner-slot">
                <?php if ($show_win_banner): ?>
                    <div class="banner win-banner">YOU WIN!</div>
                <?php elseif ($show_loss_banner): ?>
                    <div class="banner loss-banner">YOU LOSE</div>
                <?php endif; ?>
            </div>

            <!-- Dice pair -->
            <div class="dice-row">
                <div class="die">
                    <?php if ($die1): ?>
                        <div class="die-label"><?php echo strtoupper($die1); ?></div>
                        <div id="die1-circle" class="die-color-circle" style="background:<?php echo $color_classes[$die1]; ?>;"></div>
                        <div class="die-tag">DIE 1</div>
                    <?php else: ?>
                        <div class="die-label placeholder">?</div>
                        <div id="die1-circle" class="die-color-circle" style="background:#1a0a04;border-color:rgba(212,160,23,0.12);"></div>
                        <div class="die-tag">DIE 1</div>
                    <?php endif; ?>
                </div>
                <div class="die">
                    <?php if ($die2): ?>
                        <div class="die-label"><?php echo strtoupper($die2); ?></div>
                        <div id="die2-circle" class="die-color-circle" style="background:<?php echo $color_classes[$die2]; ?>;"></div>
                        <div class="die-tag">DIE 2</div>
                    <?php else: ?>
                        <div class="die-label placeholder">?</div>
                        <div id="die2-circle" class="die-color-circle" style="background:#1a0a04;border-color:rgba(212,160,23,0.12);"></div>
                        <div class="die-tag">DIE 2</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Per-color roll breakdown -->
            <?php if (!empty($roll_results)): ?>
                <div class="roll-breakdown">
                    <?php foreach ($roll_results as $color => $res): ?>
                        <div class="roll-breakdown-row">
                            <span class="rb-color" style="color:<?php echo $color_classes[$color]; ?>"><?php echo $color; ?></span>
                            <span>&#8369;<?php echo number_format($res['bet']); ?> &middot; <?php echo $res['matches']; ?> match<?php echo $res['matches'] !== 1 ? 'es' : ''; ?></span>
                            <span class="<?php echo $res['win'] > 0 ? 'rb-win' : 'rb-loss'; ?>">
                                <?php echo $res['win'] > 0 ? '+&#8369;' . number_format($res['win']) : 'Lost'; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Fixed-height message box -->
            <div class="message-box">
                <?php echo $message
                    ? htmlspecialchars($message)
                    : 'Select a chip, click a color to bet, then confirm and roll.'; ?>
            </div>
        </div>

        <!-- Roll button -->
        <div class="action-buttons">
            <form method="post" id="rollForm" style="display:inline;">
                <input type="hidden" name="action" value="roll">
                <button type="submit" class="btn-roll"
                    <?php echo (empty($bets) || !$bets_confirmed) ? 'disabled' : ''; ?>>ROLL DICE</button>
            </form>
        </div>
    </div>

    <!-- ── RIGHT: HISTORY ── -->
    <div class="col-right">
        <div class="history-panel">
            <div class="history-head">
                <div class="panel-title">GAME HISTORY</div>
            </div>

            <?php if (empty($_SESSION['color_game_history'])): ?>
                <div class="history-empty">
                    <div class="history-empty-dot">&#9632;</div>
                    <div>No games played yet.<br>Your results will appear here.</div>
                </div>
            <?php else: ?>
                <div class="history-col-bar">
                    <div class="hcb">Time</div>
                    <div class="hcb">Pattern</div>
                    <div class="hcb" style="text-align:right;">Bet</div>
                    <div class="hcb" style="text-align:center;">Dice</div>
                    <div class="hcb" style="text-align:center;">Result</div>
                    <div class="hcb" style="text-align:right;">Profit</div>
                </div>
                <div class="history-rows">
                    <?php foreach ($_SESSION['color_game_history'] as $entry): ?>
                        <div class="h-row">
                            <span class="hc-time"><?php echo htmlspecialchars($entry['time']); ?></span>
                            <span class="hc-pattern" title="<?php echo htmlspecialchars($entry['pattern']); ?>"><?php echo htmlspecialchars($entry['pattern']); ?></span>
                            <span class="hc-bet">&#8369;<?php echo number_format($entry['bet']); ?></span>
                            <span class="hc-dice"><?php echo substr($entry['dice1'],0,1).'|'.substr($entry['dice2'],0,1); ?></span>
                            <span class="hc-result <?php echo $entry['result']==='WIN'?'win':($entry['result']==='LOSS'?'loss':'push'); ?>">
                                <?php echo $entry['result']; ?>
                            </span>
                            <span class="hc-profit <?php echo $entry['profit']>0?'pos':($entry['profit']<0?'neg':'zero'); ?>">
                                <?php echo ($entry['profit']>=0?'+':'').number_format($entry['profit']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
    const rollForm = document.getElementById('rollForm');
    if (rollForm) {
        rollForm.addEventListener('submit', function(e) {
            const rollButton = this.querySelector('.btn-roll');
            if (rollButton.disabled) return;
            e.preventDefault();
            const die1Circle = document.getElementById('die1-circle');
            const die2Circle = document.getElementById('die2-circle');
            if (die1Circle && die2Circle) {
                die1Circle.classList.remove('flash');
                die2Circle.classList.remove('flash');
                void die1Circle.offsetWidth;
                void die2Circle.offsetWidth;
                die1Circle.classList.add('flash');
                die2Circle.classList.add('flash');
                setTimeout(() => rollForm.submit(), 600);
            } else {
                rollForm.submit();
            }
        });
    }
</script>
</body>
</html>
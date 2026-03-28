<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: Homepage.php');
    exit;
}

// Check if system is in "exited" state (too many failures)
if (isset($_SESSION['exited']) && $_SESSION['exited'] === true) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Aurum — System Locked</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=Barlow+Condensed:wght@300;400;600&display=swap" rel="stylesheet">
        <style>
            *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
            :root {
                --mahogany: #0E0500;
                --deep-burgundy: #1C0A08;
                --imperial-red: #C0152A;
                --burnished-gold: #D4A017;
                --warm-champagne: #F5DFA0;
                --text-muted: rgba(245,223,160,0.45);
                --sans: 'Barlow Condensed', 'Arial Narrow', Arial, sans-serif;
                --serif: 'Cormorant Garamond', 'Times New Roman', serif;
            }
            body {
                background: radial-gradient(ellipse at top, #1C0A08 0%, #0E0500 70%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: var(--sans);
                color: var(--warm-champagne);
            }
            .lock-card {
                text-align: center;
                padding: 60px 48px;
                border: 1px solid rgba(212,160,23,0.35);
                max-width: 400px;
                width: 90%;
                background: linear-gradient(160deg, #1C0A08, #0E0500);
                position: relative;
            }
            .lock-card::before, .lock-card::after,
            .lock-card .c2::before, .lock-card .c2::after {
                content: '';
                position: absolute;
                width: 14px; height: 14px;
                border-color: var(--burnished-gold);
                border-style: solid;
                opacity: 0.65;
            }
            .lock-card::before  { top:-1px; left:-1px;  border-width: 2px 0 0 2px; }
            .lock-card::after   { top:-1px; right:-1px; border-width: 2px 2px 0 0; }
            .lock-card .c2::before { bottom:-1px; left:-1px;  border-width: 0 0 2px 2px; }
            .lock-card .c2::after  { bottom:-1px; right:-1px; border-width: 0 2px 2px 0; }
            .lock-icon { font-size: 2.4rem; margin-bottom: 20px; opacity: 0.7; }
            h2 {
                font-family: var(--serif);
                font-size: 1.7rem;
                color: var(--burnished-gold);
                letter-spacing: 3px;
                font-weight: 600;
                margin-bottom: 14px;
            }
            p {
                font-size: 0.85rem;
                letter-spacing: 1.2px;
                color: var(--text-muted);
                line-height: 1.8;
            }
            .divider {
                height: 1px;
                background: linear-gradient(90deg, transparent, rgba(212,160,23,0.35), transparent);
                margin: 24px 0;
            }
        </style>
    </head>
    <body>
        <div class="lock-card">
            <div class="c2"></div>
            <div class="lock-icon">&#9888;</div>
            <h2>ACCESS DENIED</h2>
            <div class="divider"></div>
            <p>Too many failed login attempts.<br>The system has been locked for security.</p>
            <div class="divider"></div>
            <p>Please <strong style="color:var(--warm-champagne);">close your browser</strong> and reopen it to try again.</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Initialize attempt counter if not set
if (!isset($_SESSION['attempts'])) {
    $_SESSION['attempts'] = 0;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Step 1: Phone number submitted
    if (isset($_POST['phone']) && empty($_POST['code'])) {
        $phone = trim($_POST['phone']);
        if (preg_match('/^\d{11}$/', $phone)) {
            $code = rand(100000, 999999);
            $_SESSION['verification_code']  = $code;
            $_SESSION['verification_phone'] = $phone;
            $_SESSION['code_timestamp']     = time();
            $_SESSION['attempts']           = 0;
            $_SESSION['show_alert']         = true;
            $message = "Verification code sent to $phone.";
        } else {
            $error = "Phone number must be exactly 11 digits.";
        }
    }

    // Step 2: Verification code submitted
    elseif (isset($_POST['code']) && !empty($_POST['code'])) {
        $submitted_code = trim($_POST['code']);
        if (isset($_SESSION['verification_code']) && isset($_SESSION['code_timestamp'])) {
            $expiry     = 300;
            $now        = time();
            $code_valid = ($submitted_code == $_SESSION['verification_code']) &&
                          ($now - $_SESSION['code_timestamp'] <= $expiry);
            if ($code_valid) {
                $_SESSION['loggedin'] = true;
                $_SESSION['phone']    = $_SESSION['verification_phone'];
                $_SESSION['balance']  = 10;
                $_SESSION['attempts'] = 0;
                unset($_SESSION['exited'], $_SESSION['verification_code'],
                      $_SESSION['verification_phone'], $_SESSION['code_timestamp'],
                      $_SESSION['show_alert']);
                header('Location: Homepage.php');
                exit;
            } else {
                $_SESSION['attempts']++;
                if ($_SESSION['attempts'] >= 3) {
                    $_SESSION['exited'] = true;
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    $error = 'Invalid or expired code. Attempt ' . $_SESSION['attempts'] . ' of 3.';
                }
            }
        } else {
            $error = 'No verification code found. Please enter your phone number first.';
        }
    }
}

// Resend: show same code again
if (isset($_GET['resend']) && $_GET['resend'] == 1 && isset($_SESSION['verification_code'])) {
    $_SESSION['show_alert'] = true;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Reset: clear everything
if (isset($_GET['reset']) && $_GET['reset'] == 1) {
    unset($_SESSION['verification_code'], $_SESSION['verification_phone'],
          $_SESSION['code_timestamp'], $_SESSION['show_alert']);
    $_SESSION['attempts'] = 0;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$attempts_remaining = 3 - (int)$_SESSION['attempts'];
$in_step2 = isset($_SESSION['verification_code']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aurum — Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Barlow+Condensed:wght@300;400;600&display=swap" rel="stylesheet">

    <?php if (isset($_SESSION['show_alert']) && $_SESSION['show_alert'] === true): ?>
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            alert("Your verification code is: <?php echo $_SESSION['verification_code']; ?>");
        });
    </script>
    <?php unset($_SESSION['show_alert']); ?>
    <?php endif; ?>

    <style>
        *, *::before, *::after {
            margin: 0; padding: 0; box-sizing: border-box;
        }

        :root {
            --mahogany:       #0E0500;
            --imperial-red:   #C0152A;
            --burnished-gold: #D4A017;
            --warm-champagne: #F5DFA0;
            --deep-burgundy:  #1C0A08;
            --text-muted:     rgba(245,223,160,0.45);
            --gold-dim:       rgba(212,160,23,0.15);
            --sans:           'Barlow Condensed', 'Arial Narrow', Arial, sans-serif;
            --serif:          'Cormorant Garamond', 'Times New Roman', serif;
        }

        body {
            background: radial-gradient(ellipse at top, #1C0A08 0%, #0E0500 70%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-family: var(--sans);
            color: var(--warm-champagne);
            overflow: hidden;
            position: relative;
        }

        /* Subtle grid texture */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                repeating-linear-gradient(0deg, transparent, transparent 59px, rgba(212,160,23,0.025) 60px),
                repeating-linear-gradient(90deg, transparent, transparent 59px, rgba(212,160,23,0.025) 60px);
            pointer-events: none;
            z-index: 0;
        }

        /* Ambient glow top-center */
        body::after {
            content: '';
            position: fixed;
            top: -120px; left: 50%;
            transform: translateX(-50%);
            width: 500px; height: 300px;
            background: radial-gradient(ellipse, rgba(212,160,23,0.07) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        /* ── PAGE HEADER ── */
        .page-header {
            position: fixed;
            top: 0; left: 0; right: 0;
            padding: 12px 32px;
            display: flex;
            align-items: center;
            gap: 14px;
            border-bottom: 1px solid rgba(212,160,23,0.2);
            background: linear-gradient(90deg, #0E0500, #1C0A08);
            z-index: 10;
        }
        .header-logo {
            width: 40px; height: 40px;
            object-fit: contain;
            border-radius: 6px;
            filter: drop-shadow(0 0 5px rgba(212,160,23,0.3));
        }
        .header-brand {
            font-family: var(--serif);
            font-size: 1.55rem;
            letter-spacing: 5px;
            color: var(--burnished-gold);
            font-weight: 600;
        }

        /* ── LOGIN CARD ── */
        .login-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-card {
            background: linear-gradient(160deg, #1C0A08 0%, #0E0500 100%);
            border: 1px solid rgba(212,160,23,0.38);
            padding: 44px 42px 38px;
            position: relative;
            animation: fadeUp 0.45s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Corner brackets */
        .login-card::before,
        .login-card::after,
        .login-card .corners::before,
        .login-card .corners::after {
            content: '';
            position: absolute;
            width: 14px; height: 14px;
            border-color: var(--burnished-gold);
            border-style: solid;
            opacity: 0.6;
        }
        .login-card::before          { top:-1px;    left:-1px;  border-width: 2px 0 0 2px; }
        .login-card::after           { top:-1px;    right:-1px; border-width: 2px 2px 0 0; }
        .login-card .corners::before { bottom:-1px; left:-1px;  border-width: 0 0 2px 2px; }
        .login-card .corners::after  { bottom:-1px; right:-1px; border-width: 0 2px 2px 0; }

        /* ── CARD BRAND ── */
        .card-logo-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 4px;
        }
        .suit {
            font-size: 20px;
            color: var(--burnished-gold);
            opacity: 0.5;
            line-height: 1;
        }
        .card-brand {
            font-family: var(--serif);
            font-size: 2.2rem;
            letter-spacing: 7px;
            color: var(--burnished-gold);
            font-weight: 600;
            line-height: 1;
        }
        .card-tagline {
            text-align: center;
            font-size: 0.67rem;
            letter-spacing: 3.5px;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 26px;
        }
        .gold-rule {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(212,160,23,0.4), transparent);
            margin-bottom: 26px;
        }

        /* ── STEP LABEL ── */
        .step-label {
            font-size: 0.67rem;
            letter-spacing: 2.5px;
            color: var(--text-muted);
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 20px;
        }
        .step-sub {
            font-size: 0.78rem;
            letter-spacing: 0.8px;
            color: var(--text-muted);
            text-align: center;
            line-height: 1.75;
            margin-bottom: 20px;
        }
        .step-sub strong {
            color: var(--burnished-gold);
            font-weight: 400;
        }
        .step-sub em {
            display: block;
            font-size: 0.68rem;
            opacity: 0.7;
            font-style: italic;
            margin-top: 4px;
        }

        /* ── FIELDS ── */
        .field {
            margin-bottom: 16px;
        }
        .field label {
            display: block;
            font-size: 0.67rem;
            letter-spacing: 2.5px;
            color: rgba(212,160,23,0.5);
            text-transform: uppercase;
            margin-bottom: 7px;
        }
        .field input {
            width: 100%;
            background: rgba(14,5,0,0.75);
            border: 1px solid rgba(212,160,23,0.28);
            color: var(--warm-champagne);
            padding: 12px 16px;
            font-size: 1rem;
            font-family: var(--sans);
            font-weight: 300;
            letter-spacing: 2px;
            outline: none;
            border-radius: 2px;
            transition: border-color 0.2s, box-shadow 0.2s;
            -webkit-appearance: none;
        }
        .field input::placeholder {
            color: rgba(245,223,160,0.18);
            font-size: 0.82rem;
            letter-spacing: 2px;
        }
        .field input:focus {
            border-color: rgba(212,160,23,0.6);
            box-shadow: 0 0 0 3px rgba(212,160,23,0.06);
        }

        /* ── BUTTON ── */
        .btn-primary {
            width: 100%;
            background: linear-gradient(180deg, #C0152A 0%, #8f0e1f 100%);
            color: var(--warm-champagne);
            border: 1px solid rgba(192,21,42,0.35);
            padding: 13px 20px;
            font-family: var(--sans);
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 3.5px;
            text-transform: uppercase;
            cursor: pointer;
            border-radius: 2px;
            margin-top: 4px;
            transition: filter 0.2s, transform 0.15s, box-shadow 0.2s;
        }
        .btn-primary:hover {
            filter: brightness(1.14);
            transform: translateY(-1px);
            box-shadow: 0 6px 22px rgba(192,21,42,0.3);
        }
        .btn-primary:active { transform: translateY(0); filter: brightness(0.95); }

        /* ── MESSAGES ── */
        .msg {
            font-size: 0.72rem;
            letter-spacing: 1px;
            font-family: var(--sans);
            text-align: center;
            padding: 10px 14px;
            border-radius: 2px;
            margin-top: 14px;
            line-height: 1.6;
        }
        .msg-error {
            color: #f08080;
            background: rgba(192,21,42,0.14);
            border: 1px solid rgba(192,21,42,0.28);
        }
        .msg-success {
            color: #a8d8a0;
            background: rgba(40,100,30,0.14);
            border: 1px solid rgba(60,160,40,0.25);
        }

        /* ── INFO ROW ── */
        .info-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 18px;
        }
        .attempts-badge {
            font-size: 0.67rem;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            text-transform: uppercase;
        }
        .attempts-badge span {
            color: var(--burnished-gold);
            font-weight: 600;
        }
        .link-action {
            font-family: var(--sans);
            font-size: 0.67rem;
            letter-spacing: 1.8px;
            color: rgba(212,160,23,0.5);
            text-transform: uppercase;
            text-decoration: underline;
            text-underline-offset: 3px;
            transition: color 0.2s;
        }
        .link-action:hover { color: var(--burnished-gold); }

        .links-center {
            display: flex;
            justify-content: center;
            margin-top: 12px;
        }

        /* ── CARD FOOTER ── */
        .card-foot {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid rgba(212,160,23,0.1);
        }
        .pip {
            width: 4px; height: 4px;
            border-radius: 50%;
            background: rgba(212,160,23,0.22);
        }
        .foot-text {
            font-size: 0.62rem;
            letter-spacing: 2px;
            color: rgba(245,223,160,0.22);
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <header class="page-header">
        <img src="logo.png" alt="Aurum Logo" class="header-logo"
             onerror="this.style.display='none'">
        <span class="header-brand">AURUM</span>
    </header>

    <div class="login-wrap">
        <div class="login-card">
            <div class="corners"></div>

            <div class="card-logo-row">
                <div class="card-brand">AURUM</div>
            </div>
            <p class="card-tagline">Secure Player Login</p>
            <div class="gold-rule"></div>

            <?php if (!$in_step2): ?>
            <p class="step-label">Step 1 &mdash; Enter your number</p>

            <form method="post" autocomplete="off">
                <div class="field">
                    <label for="phone">Phone Number</label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        required
                        pattern="[0-9]{11}"
                        maxlength="11"
                        placeholder="09XXXXXXXXX"
                        value="<?php echo isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : ''; ?>"
                        autofocus>
                </div>
                <button type="submit" class="btn-primary">Send Verification Code</button>
            </form>

            <?php if (isset($error)): ?>
                <p class="msg msg-error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <?php if (isset($message)): ?>
                <p class="msg msg-success"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>

            <?php else: ?>
            <p class="step-label">Step 2 &mdash; Enter your code</p>
            <p class="step-sub">
                A code was sent to
                <strong><?php echo htmlspecialchars($_SESSION['verification_phone']); ?></strong>
                <em>Valid for 5 minutes &nbsp;&middot;&nbsp; same code for all attempts</em>
            </p>

            <form method="post" autocomplete="off">
                <div class="field">
                    <label for="code">Verification Code</label>
                    <input
                        type="text"
                        id="code"
                        name="code"
                        required
                        maxlength="6"
                        placeholder="Enter 6-digit code"
                        inputmode="numeric"
                        autofocus>
                </div>
                <button type="submit" class="btn-primary">Verify &amp; Enter</button>
            </form>

            <?php if (isset($error)): ?>
                <p class="msg msg-error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <div class="info-row">
                <span class="attempts-badge">
                    Attempts: <span><?php echo $attempts_remaining; ?></span> / 3
                </span>
                <a href="?resend=1" class="link-action">Resend code</a>
            </div>
            <div class="links-center">
                <a href="?reset=1" class="link-action">Use a different number</a>
            </div>

            <?php endif; ?>


        </div>
    </div>

</body>
</html>
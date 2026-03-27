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
    <html>
    <body>
        <h2>System Exited</h2>
        <p>Too many failed login attempts. The system has exited.</p>
        <p>Please <strong>close your browser</strong> and reopen it to try again.</p>
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
    // Step 1: Phone number submitted (request code)
    if (isset($_POST['phone']) && empty($_POST['code'])) {
        $phone = trim($_POST['phone']);
        
        // Validate phone number: exactly 11 digits
        if (preg_match('/^\d{11}$/', $phone)) {
            // Generate a random 6-digit code
            $code = rand(100000, 999999);
            
            // Store code, phone, and timestamp in session
            $_SESSION['verification_code'] = $code;
            $_SESSION['verification_phone'] = $phone;
            $_SESSION['code_timestamp'] = time();
            
            // Reset attempts when a new code is requested
            $_SESSION['attempts'] = 0;
            
            // Set flag to show alert with the code
            $_SESSION['show_alert'] = true;
            $message = "Verification code sent to $phone.";
        } else {
            $error = "Phone number must be exactly 11 digits.";
        }
    }
    
    // Step 2: Verification code submitted
    elseif (isset($_POST['code']) && !empty($_POST['code'])) {
        $submitted_code = trim($_POST['code']);
        
        // Check if code exists and is not expired (5 minute expiry)
        if (isset($_SESSION['verification_code']) && isset($_SESSION['code_timestamp'])) {
            $expiry = 300; // 5 minutes
            $now = time();
            $code_valid = ($submitted_code == $_SESSION['verification_code']) && 
                          ($now - $_SESSION['code_timestamp'] <= $expiry);
            
            if ($code_valid) {
                // Successful login
                $_SESSION['loggedin'] = true;
                $_SESSION['phone'] = $_SESSION['verification_phone'];
                $_SESSION['balance'] = 10;
                $_SESSION['attempts'] = 0;
                unset($_SESSION['exited']);
                unset($_SESSION['verification_code']);
                unset($_SESSION['verification_phone']);
                unset($_SESSION['code_timestamp']);
                unset($_SESSION['show_alert']);
                
                header('Location: Homepage.php');
                exit;
            } else {
                // Invalid or expired code
                $_SESSION['attempts']++;
                if ($_SESSION['attempts'] >= 3) {
                    $_SESSION['exited'] = true;
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    $error = 'Invalid or expired verification code. Attempts: ' . $_SESSION['attempts'] . '/3';
                    // The code remains in session – user can try again with the same code
                }
            }
        } else {
            $error = 'No verification code requested. Please enter your phone number first.';
        }
    }
}

// Handle resend request (GET) – show same code in alert, no change to session data
if (isset($_GET['resend']) && $_GET['resend'] == 1 && isset($_SESSION['verification_code'])) {
    $_SESSION['show_alert'] = true;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle reset request (GET) – clear everything
if (isset($_GET['reset']) && $_GET['reset'] == 1) {
    unset($_SESSION['verification_code']);
    unset($_SESSION['verification_phone']);
    unset($_SESSION['code_timestamp']);
    unset($_SESSION['show_alert']);
    $_SESSION['attempts'] = 0;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <script>
        // Show alert with the code if requested
        <?php if (isset($_SESSION['show_alert']) && $_SESSION['show_alert'] === true): ?>
            alert("Your verification code is: <?php echo $_SESSION['verification_code']; ?>");
            <?php unset($_SESSION['show_alert']); ?>
        <?php endif; ?>
    </script>
</head>
<body>
    <h2>Login with Phone & Verification Code</h2>
    
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <?php if (isset($message)) echo "<p style='color:green;'>$message</p>"; ?>
    
    <?php if (!isset($_SESSION['verification_code'])): ?>
        <!-- Step 1: Request code (must be exactly 11 digits) -->
        <form method="post">
            Phone Number (09XXXXXXXXX):
            <input type="tel" name="phone" required pattern="[0-9]{11}" 
                   placeholder="Enter exactly 11 digits"><br><br>
            <input type="submit" value="Send Code">
        </form>
    <?php else: ?>
        <!-- Step 2: Enter code -->
        <form method="post">
            <p>We sent a code to <?php echo htmlspecialchars($_SESSION['verification_phone']); ?>.</p>
            <p><em>(The same code can be used for up to 3 attempts or 5 minutes.)</em></p>
            Verification Code:
            <input type="text" name="code" required 
                   placeholder="Enter verification code"><br><br>
            <input type="submit" value="Verify">
        </form>
        <p>Attempts remaining: <?php echo 3 - $_SESSION['attempts']; ?></p>
        <p><a href="?reset=1">Use a different phone number</a></p>
        <!-- Resend code link – shows the same code in an alert -->
        <p><a href="?resend=1">Resend code</a></p>
    <?php endif; ?>
</body>
</html>
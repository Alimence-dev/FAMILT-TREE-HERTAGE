<?php
/**
 * Kibaza Rwanda Heritage System — Gatekeeping Login Portal
 * Path: /index.php
 */
session_start();

// Look up active error states and retention cache arrays
$error = isset($_GET['error']) ? $_GET['error'] : '';
$saved_email = isset($_SESSION['login_email']) ? htmlspecialchars($_SESSION['login_email']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

$feedbackMessage = "";
$feedbackClass = "";

if ($error === 'mismatch') {
    $feedbackMessage = "❌ Invalid email address or password combination.";
    $feedbackClass = "error-alert";
} elseif ($error === 'pending') {
    $feedbackMessage = "⏳ Access Restricted: Account status is 'Pending'. Please await Elder verification.";
    $feedbackClass = "pending-alert";
} elseif ($status === 'pending') {
    $feedbackMessage = "🎉 Registration Submitted! Please contact your family Elder for validation.";
    $feedbackClass = "success-alert";
}

// Clear retention cache step so a clean refresh empties old tracking variables
unset($_SESSION['login_email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Gateway | Kibaza Heritage</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root { --green: #27ae60; --gold: #c9a84c; --ink: #1a1a2e; --sand: #faf7f0; --white: #ffffff; }
        body { font-family: 'DM Sans', sans-serif; background: #f4f7f6; margin: 0; padding: 0; display: flex; min-height: 100vh; align-items: center; justify-content: center; }
        
        .login-wrap { width: 100%; max-width: 420px; padding: 20px; }
        .card { background: var(--white); border-radius: 24px; box-shadow: 0 15px 40px rgba(0,0,0,0.06); overflow: hidden; text-align: center; }
        .header { background: var(--ink); padding: 35px 20px; color: #f0d98a; }
        .header h2 { margin: 0; font-size: 1.6rem; letter-spacing: 0.5px; }
        .header p { margin: 5px 0 0; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.7; }
        
        .body { padding: 40px 30px; text-align: left; }
        .field { margin-bottom: 20px; position: relative; }
        label { display: block; font-size: 0.75rem; font-weight: 700; color: #9ca3af; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        input { width: 100%; padding: 14px; border: 2px solid #f3f4f6; border-radius: 12px; font-family: inherit; box-sizing: border-box; background: #f1f5f9; font-size: 0.9rem; transition: 0.3s; color: var(--ink); }
        input:focus { outline: none; border-color: var(--green); background: #fff; }
        
        /* PASSWORD TOGGLE BADGE COMPONENT STYLE */
        .toggle-pw {
            position: absolute; right: 12px; top: 37px;
            cursor: pointer; color: var(--gold); font-size: 0.65rem; font-weight: 700;
            background: #e2e8f0; padding: 4px 8px; border-radius: 6px;
            user-select: none; transition: 0.2s;
        }
        .toggle-pw:hover { background: #cbd5e1; color: var(--ink); }
        
        .alert-box { padding: 12px 15px; border-radius: 10px; font-size: 0.8rem; font-weight: 600; margin-top: 15px; margin-bottom: 5px; line-height: 1.4; text-align: center; }
        .error-alert { background: #fff5f5; color: #c53030; border: 1px solid #fed7d7; }
        .pending-alert { background: #fffdf5; color: #b7791f; border: 1px solid #fef3c7; }
        .success-alert { background: #f0fff4; color: #22543d; border: 1px solid #c6f6d5; }
        
        .submit-btn { width: 100%; padding: 16px; background: var(--green); color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; font-size: 0.95rem; text-transform: uppercase; transition: 0.3s; margin-top: 15px; }
        .submit-btn:hover { background: #219150; transform: translateY(-1px); }
        
        .footer-links { text-align: center; margin-top: 25px; font-size: 0.85rem; color: #7f8c8d; line-height: 1.6; }
        .footer-links a { color: var(--green); text-decoration: none; font-weight: 700; }
        .footer-links a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="login-wrap">
    <div class="card">
        <div class="header">
            <h2>Kibaza Portal</h2>
            <p>Heritage & Genealogy Archive</p>
        </div>
        <div class="body">
            <form action="actions/process_login.php" method="POST">
                <div class="field">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo $saved_email; ?>" placeholder="name@domain.com" required>
                </div>
                
                <div class="field">
                    <label>Password</label>
                    <input type="password" name="password" id="log_pw" placeholder="••••••••" required>
                    <span class="toggle-pw" onclick="togglePassword()">VIEW</span>
                </div>

                <?php if(!empty($feedbackMessage)): ?>
                    <div class="alert-box <?php echo $feedbackClass; ?>"><?php echo $feedbackMessage; ?></div>
                <?php endif; ?>

                <button type="submit" class="submit-btn">Login to Portal</button>
            </form>

            <div class="footer-links">
                New Member? <a href="register.php">Join Family</a>
                <br>
                <a href="search.php" style="color: #34495e; font-size: 0.8rem; display: inline-block; margin-top: 15px;">← Public Search</a>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordField = document.getElementById('log_pw');
    const toggleBadge = event.target;

    if (passwordField.type === "password") {
        passwordField.type = "text";
        toggleBadge.innerText = "HIDE";
    } else {
        passwordField.type = "password";
        toggleBadge.innerText = "VIEW";
    }
}
</script>

</body>
</html>
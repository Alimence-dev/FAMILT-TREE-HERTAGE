<?php
/**
 * Kibaza Rwanda Heritage System - Secure Logout Processor
 * Path: /logout.php (Root Folder)
 */

session_start();

// 1. Wipe all session variables
$_SESSION = array();

// 2. Invalidate the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, 
        $params["path"], $params["domain"], 
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy session on server
session_destroy();

// 4. Set simple destination
$dest = "index.php?action=loggedout";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="2;url=index.php?action=loggedout">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    
    <title>Logging Out | Kibaza Heritage</title>
    
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'DM Sans', sans-serif; 
            background: #f4f7f6; 
            display: flex; justify-content: center; align-items: center; 
            min-height: 100vh; margin: 0; 
        }
        .logout-card { 
            text-align: center; background: white; padding: 50px; 
            border-radius: 24px; box-shadow: 0 15px 40px rgba(0,0,0,0.06); 
            max-width: 380px; width: 90%; 
        }
        .spinner { 
            width: 40px; height: 40px; border: 4px solid #eaf7ef; 
            border-top-color: #27ae60; border-radius: 50%; 
            animation: spin 1s linear infinite; margin: 0 auto 20px; 
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        h3 { color: #1a1a2e; margin: 0 0 10px; font-size: 1.3rem; }
        p { color: #7f8c8d; font-size: 0.85rem; line-height: 1.5; }
        .manual-link { 
            color: #27ae60; font-weight: 700; font-size: 0.8rem; 
            text-decoration: none; display: block; margin-top: 20px; 
        }
    </style>
</head>
<body>

<div class="logout-card">
    <div class="spinner"></div>
    <h3>Ending Session</h3>
    <p>Securely clearing your heritage records and redirecting you to the portal...</p>
    <a href="index.php?action=loggedout" class="manual-link">Click here if not redirected →</a>
</div>

<script>
    // 1. Clear storage to prevent "Back-Button" data leakage
    try {
        sessionStorage.clear();
        localStorage.clear();
    } catch(e) {
        console.log("Storage cleared.");
    }

    // 2. Immediate redirect
    setTimeout(function() {
        window.location.replace('index.php?action=loggedout');
    }, 1200);
</script>
</body>
</html>
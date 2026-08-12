<?php
/**
 * Kibaza Rwanda Heritage System — Join Your Family (Registration)
 * Path: /register.php
 */
session_start();
require_once 'db_connect.php';

// Safe retrieval of old data to prevent "Undefined index" notices
$old = isset($_SESSION['reg_data']) ? $_SESSION['reg_data'] : [];
$error = isset($_GET['error']) ? $_GET['error'] : '';
$errorMessage = "";

if ($error === 'invalid_code') {
    $errorMessage = "⚠️ The Family Join Code is incorrect. Please verify with your Elder.";
} elseif ($error === 'pw_mismatch') {
    $errorMessage = "❌ Passwords do not match. Please try again.";
} elseif ($error === 'email_exists') {
    $errorMessage = "📧 This email is already registered.";
} elseif ($error === 'system_error') {
    $errorMessage = "🛑 Database sync error. Ensure columns match the schema.";
} elseif ($error === 'future_birth') {
    $errorMessage = "📅 Error: Birth date cannot be in the future.";
} elseif ($error === 'invalid_age') {
    $errorMessage = "❌ Error: Please enter a biologically realistic birth date.";
}

unset($_SESSION['reg_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Your Family | Kibaza Heritage</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        :root { --green: #27ae60; --gold: #c9a84c; --ink: #1a1a2e; --sand: #faf7f0; --white: #ffffff; }
        body { font-family: 'DM Sans', sans-serif; background: var(--sand); padding: 40px 15px; color: var(--ink); }
        .wrap { max-width: 850px; margin: 0 auto; position: relative; }
        
        .back-nav { 
            position: absolute; right: 0; top: -35px; 
            text-decoration: none; color: var(--ink); 
            font-weight: 700; font-size: 0.9rem; 
            display: flex; align-items: center; transition: 0.3s;
        }
        .back-nav:hover { color: var(--green); }
        .back-nav span { margin-left: 8px; font-size: 1.2rem; }

        .card { background: var(--white); border-radius: 24px; box-shadow: 0 15px 50px rgba(0,0,0,0.06); overflow: hidden; }
        .card-header { background: var(--ink); padding: 40px; text-align: center; color: #f0d98a; border-bottom: 4px solid var(--gold); }
        .card-body { padding: 45px; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .full { grid-column: span 2; }
        .section-tag { grid-column: span 2; font-size: 0.75rem; font-weight: 700; color: var(--gold); text-transform: uppercase; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-top: 15px; }
        
        label { display: block; font-size: 0.8rem; font-weight: 700; color: #9ca3af; margin-bottom: 8px; text-transform: uppercase; }
        input, select, textarea { width: 100%; padding: 12px; border: 2px solid #f3f4f6; border-radius: 12px; font-family: inherit; box-sizing: border-box; background: #fafafa; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: var(--green); background: #fff; }
        
        .pass-group { position: relative; }
        .toggle-pw {
            position: absolute; right: 12px; top: 38px;
            cursor: pointer; color: var(--gold); font-size: 0.65rem; font-weight: 700;
            background: #eee; padding: 4px 8px; border-radius: 4px;
        }

        .error-alert { background: #fff5f5; color: #c53030; padding: 15px; border-radius: 12px; margin-bottom: 20px; border-left: 5px solid #c53030; font-weight: 600; }
        .submit-btn { width: 100%; padding: 18px; background: var(--green); color: white; border: none; border-radius: 14px; font-weight: 700; cursor: pointer; margin-top: 30px; transition: 0.3s; }
        .submit-btn:hover { background: #219150; transform: translateY(-2px); }
    </style>
</head>
<body>
<div class="wrap">
    <a href="index.php" class="back-nav">Back to Login <span>→</span></a>
    <div class="card">
        <div class="card-header">
            <h2 style="font-family: 'Playfair Display'; margin:0;">Join Your Family</h2>
        </div>
        <div class="card-body">
            <?php if($errorMessage): ?>
                <div class="error-alert"><?php echo $errorMessage; ?></div>
            <?php endif; ?>

            <form action="actions/process_register.php" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="section-tag">1. Identity Information</div>
                    <div class="full">
                        <label>Full Name *</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>" required>
                    </div>
                    <div>
                        <label>ID Card Number (NID)</label>
                        <input type="text" name="id_card_number" value="<?php echo htmlspecialchars($old['id_card_number'] ?? ''); ?>" placeholder="1 199X X XXXXXXX X XX">
                    </div>
                    <div>
                        <label>Email Address *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>" required>
                    </div>
                    <div>
                        <label>Contact Phone</label>
                        <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($old['contact_phone'] ?? ''); ?>" placeholder="078... / 072...">
                    </div>
                    <div>
                        <label>Birth Date *</label>
                        <input type="date" name="birth_date" max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($old['birth_date'] ?? ''); ?>" required>
                    </div>
                    
                    <div>
                        <label>Gender (Gitsina) *</label>
                        <select name="gender" required>
                            <option value="Male" <?php echo (isset($old['gender']) && $old['gender'] === 'Male') ? 'selected' : ''; ?>>Male (Gabo)</option>
                            <option value="Female" <?php echo (isset($old['gender']) && $old['gender'] === 'Female') ? 'selected' : ''; ?>>Female (Gore)</option>
                        </select>
                    </div>
                    <div class="full">
                        <label>Profile Picture</label>
                        <input type="file" name="profile_pic" accept="image/*">
                    </div>

                    <div class="section-tag">2. Cultural Heritage & Lineage</div>
                    <div>
                        <label>Clan (Ubwoko) *</label>
                        <select name="clan_id" required>
                            <option value="">-- Choose Clan --</option>
                            <?php
                            $clans = $conn->query("SELECT * FROM clans ORDER BY clan_name ASC");
                            if($clans) {
                                while($c = $clans->fetch_assoc()) {
                                    $sel = (isset($old['clan_id']) && $old['clan_id'] == $c['id']) ? 'selected' : '';
                                    echo "<option value='{$c['id']}' $sel>{$c['clan_name']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label>Family Role *</label>
                        <input type="text" name="role_in_family" value="<?php echo htmlspecialchars($old['role_in_family'] ?? ''); ?>" placeholder="e.g. Son, Daughter" required>
                    </div>
                    <div class="full">
                        <label>Historical Bio / Notes</label>
                        <textarea name="bio_note" rows="3"><?php echo htmlspecialchars($old['bio_note'] ?? ''); ?></textarea>
                    </div>

                    <div class="section-tag">3. Security</div>
                    <div class="pass-group">
                        <label>Password *</label>
                        <input type="password" name="password" id="pw1" required>
                        <span class="toggle-pw" onclick="toggle('pw1')">VIEW</span>
                    </div>
                    <div class="pass-group">
                        <label>Confirm Password *</label>
                        <input type="password" name="password_confirm" id="pw2" required>
                        <span class="toggle-pw" onclick="toggle('pw2')">VIEW</span>
                    </div>

                    <div class="full">
                        <label>Family Join Code *</label>
                        <input type="text" name="family_code" value="<?php echo htmlspecialchars($old['family_code'] ?? ''); ?>" style="border: 2px solid var(--green); text-align: center; font-weight: 700;" placeholder="KIB-XXXX" required>
                    </div>
                </div>
                <button type="submit" class="submit-btn">SUBMIT REGISTRATION</button>
            </form>
        </div>
    </div>
</div>

<script>
function toggle(id) {
    const input = document.getElementById(id);
    const btn = event.target;
    if (input.type === "password") {
        input.type = "text";
        btn.innerText = "HIDE";
    } else {
        input.type = "password";
        btn.innerText = "VIEW";
    }
}
</script>

</body>
</html>
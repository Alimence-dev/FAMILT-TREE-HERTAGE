<?php
/**
 * Kibaza Rwanda Heritage System - Public Search Results
 * Path: /search_result.php
 * Finalized for BIT Thesis Submission - May 2026
 */

require_once 'db_connect.php';

$query_text = "";
if (isset($_GET['query'])) {
    $query_text = trim($_GET['query']);
}

// If search is empty, redirect back to main search
if (empty($query_text)) {
    header("Location: search.php");
    exit();
}

// 1. SECURE DATA RETRIEVAL (Using Prepared Statements)
// PRIVACY FIX: Removed f.family_code search lookup to protect registration gatekeeping keys
$search_param = "%" . $query_text . "%";
$sql = "SELECT m.*, f.family_name
        FROM members m
        JOIN families f ON m.family_id = f.id
        WHERE (
            m.name LIKE ? 
            OR m.id_card_number = ?
        )
        AND m.is_validated = 1
        ORDER BY m.name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $search_param, $query_text);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results | <?php echo isset($system_name) ? $system_name : 'Kibaza Heritage'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #27ae60;
            --dark-ink: #1a1a2e;
            --soft-white: #f4f7f6;
            --border-color: #eee;
        }

        body { 
            font-family: 'DM Sans', sans-serif; 
            background: var(--soft-white); 
            padding: 40px 20px; 
            margin: 0; 
            color: #333; 
        }

        .container { 
            max-width: 850px; 
            margin: 0 auto; 
            background: white; 
            padding: 50px; 
            border-radius: 20px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.06); 
        }

        h2 { 
            font-family: 'Playfair Display', serif; 
            color: var(--dark-ink); 
            border-bottom: 4px solid var(--primary-green); 
            padding-bottom: 12px; 
            margin-bottom: 40px; 
            font-size: 2rem;
        }

        .result-item { 
            border-bottom: 1px solid var(--border-color); 
            padding: 25px 0; 
            display: flex; 
            align-items: center; 
            transition: transform 0.2s ease; 
        }

        .result-item:hover { transform: translateX(5px); }

        .profile-pic { 
            width: 80px; 
            height: 80px; 
            border-radius: 50%; 
            margin-right: 30px; 
            object-fit: cover; 
            border: 3px solid var(--primary-green); 
            flex-shrink: 0; 
        }

        .info-box { flex-grow: 1; }

        .member-name { 
            font-size: 1.3rem; 
            color: var(--dark-ink); 
            text-decoration: none; 
            font-weight: 700; 
        }
        .member-name:hover { color: var(--primary-green); }

        .metadata { 
            font-size: 0.95rem; 
            color: #7f8c8d; 
            margin-top: 8px; 
        }

        .no-results { 
            text-align: center; 
            padding: 40px 0; 
        }

        .btn-view {
            display: inline-block;
            margin-top: 15px;
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .footer-nav { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-top: 50px; 
            border-top: 2px solid var(--soft-white);
            padding-top: 30px;
        }

        .btn-back { color: #7f8c8d; text-decoration: none; font-weight: 500; }
        .btn-back:hover { color: var(--dark-ink); }

        .btn-portal { 
            background: var(--dark-ink); 
            color: white; 
            padding: 12px 25px; 
            border-radius: 10px; 
            text-decoration: none; 
            font-weight: 700; 
            font-size: 0.9rem;
            transition: 0.3s;
        }
        .btn-portal:hover { background: var(--primary-green); }

        @media (max-width: 600px) {
            .container { padding: 30px; }
            .result-item { flex-direction: column; text-align: center; }
            .profile-pic { margin-right: 0; margin-bottom: 20px; }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Query Results: "<?php echo htmlspecialchars($query_text); ?>"</h2>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()):
            $image = !empty($row['profile_image']) ? $row['profile_image'] : 'default_user.png';
        ?>
        <div class="result-item">
            <img src="uploads/<?php echo htmlspecialchars($image); ?>" 
                 class="profile-pic"
                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($row['name']); ?>&background=27ae60&color=fff'">

            <div class="info-box">
                <a href="family_tree.php?member_id=<?php echo $row['id']; ?>" class="member-name">
                    <?php echo htmlspecialchars($row['name']); ?>
                </a>
                <div class="metadata">
                    <strong><?php echo htmlspecialchars($row['role_in_family']); ?></strong> &nbsp;•&nbsp; 
                    <?php echo htmlspecialchars($row['family_name']); ?> Family
                    </div>
                <a href="family_tree.php?member_id=<?php echo $row['id']; ?>" class="btn-view">
                    Open Lineage Tree →
                </a>
            </div>
        </div>
        <?php endwhile; ?>

    <?php else: ?>
        <div class="no-results">
            <p style="font-size: 1.2rem; color: #e74c3c; font-weight: 700;">No verified records found.</p>
            <p style="color: #7f8c8d;">
                Try searching for a different ancestor name or contact your lineage Elder.
            </p>
        </div>
    <?php endif; ?>

    <div class="footer-nav">
        <a href="search.php" class="btn-back">← New Search</a>
        <a href="index.php" class="btn-portal">🔐 Access Portal</a>
    </div>
</div>

</body>
</html>
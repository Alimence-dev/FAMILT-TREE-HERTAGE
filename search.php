<?php
/**
 * Kibaza Rwanda Heritage System — Public Discovery Portal
 * Path: /search.php (Main Landing Page)
 */
require_once 'db_connect.php';

$res_members   = $conn->query("SELECT COUNT(*) as count FROM members");
$total_members = ($res_members) ? $res_members->fetch_assoc()['count'] : 0;

$res_families   = $conn->query("SELECT COUNT(*) as count FROM families");
$total_families = ($res_families) ? $res_families->fetch_assoc()['count'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kibaza | Discover Your Rwandan Heritage</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-green: #27ae60;
            --dark-ink: #1a1a2e;
            --gold-accent: #c9a84c;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            color: var(--dark-ink);
            line-height: 1.6;
            overflow-x: hidden;
            background-color: #f9f9f9;
        }

        /* ── NAVIGATION ── */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 8%;
            background: rgba(255, 255, 255, 0.95);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .logo {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-green);
            text-decoration: none;
            letter-spacing: -0.5px;
        }
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .nav-links a {
            text-decoration: none;
            color: var(--dark-ink);
            font-weight: 500;
            font-size: 0.85rem;
        }

        /* ── HERO SECTION (The Background Fix) ── */
        .hero {
            /* Reduced the black overlay darkness (0.2 instead of 0.3) */
            background: linear-gradient(rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0.4)), 
                        url('assets/hero-bg.png'); 
            background-size: cover;
            /* background-position: top center; ensures the faces at the top aren't cut off */
            background-position: top center; 
            min-height: 95vh; /* Taller height to show more of the image */
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }

        /* ── TRANSLUCENT HERO BOX ── */
        .hero-content {
            background: rgba(0, 0, 0, 0.1); /* Very light overlay to prioritize the image */
            padding: 40px;
            border-radius: 24px;
            backdrop-filter: blur(4px); /* Lower blur makes background more visible */
            border: 1px solid rgba(255, 255, 255, 0.15);
            max-width: 600px;
            text-align: center;
            color: white;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
        }

        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 6vw, 3.5rem);
            margin-bottom: 10px;
            text-shadow: 0px 4px 10px rgba(0,0,0,0.4);
        }
        .hero p {
            font-size: 1.1rem;
            margin-bottom: 35px;
            font-weight: 400;
            text-shadow: 0px 2px 5px rgba(0,0,0,0.4);
        }

        /* ── SEARCH UI ── */
        .search-wrapper {
            background: white;
            padding: 8px 8px 8px 25px;
            border-radius: 60px;
            display: flex;
            align-items: center;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }
        .search-wrapper input {
            flex: 1;
            border: none;
            font-size: 1rem;
            outline: none;
            color: var(--dark-ink);
        }
        .search-btn {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.85rem;
            transition: transform 0.2s;
        }
        .search-btn:hover { transform: scale(1.05); }

        /* Stats Grid */
        .stats-container { padding: 80px 10%; background: white; text-align: center; }
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(3, 1fr); 
            gap: 40px; 
            max-width: 1000px; 
            margin: 0 auto; 
        }
        .stat-card h2 { font-family: 'Playfair Display', serif; font-size: 3rem; color: var(--primary-green); margin-bottom: 5px; }
        .stat-card p { font-size: 0.75rem; letter-spacing: 2px; text-transform: uppercase; color: #7f8c8d; }

        footer { background: var(--dark-ink); padding: 50px 20px; text-align: center; color: rgba(255,255,255,0.7); font-size: 0.85rem; }
    </style>
</head>
<body>

<nav>
    <a href="search.php" class="logo">🌿 KIBAZA HERITAGE</a>
    <div class="nav-links">
        <a href="index.php">Portal Login</a>
        <a href="about.php" style="border:1.5px solid var(--primary-green); padding: 7px 20px; border-radius: 25px; color: var(--primary-green); font-weight: 700;">Purpose</a>
    </div>
</nav>

<div class="hero">
    <div class="hero-content">
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'not_found'): ?>
            <div style="background: rgba(231, 76, 60, 0.2); padding: 10px; border-radius: 10px; margin-bottom: 20px; border: 1px solid rgba(231, 76, 60, 0.4);">
                <p style="color: #ff9f87; font-weight: bold; margin:0;">⚠️ Ancestor not found.</p>
            </div>
        <?php endif; ?>

        <h1>Find your lineage</h1>
        <p>Connecting youth to traditional oral history in a digital age.</p>

        <form action="search_result.php" method="GET" class="search-wrapper">
            <input type="text" name="query" placeholder="Name or Family Code" required>
            <button type="submit" class="search-btn">Find Roots</button>
        </form>
    </div>
</div>

<section class="stats-container">
    <div class="stats-grid">
        <div class="stat-card">
            <h2><?php echo $total_families; ?></h2>
            <p>Family Branches</p>
        </div>
        <div class="stat-card">
            <h2><?php echo $total_members; ?></h2>
            <p>Archived Records</p>
        </div>
        <div class="stat-card">
            <h2>100%</h2>
            <p>Oral Integrity</p>
        </div>
    </div>
</section>

<footer>
    <p>&copy; <?php echo date('Y'); ?> BIT Capstone Project | <strong>University of Kigali</strong></p>
</footer>

</body>
</html>
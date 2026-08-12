<?php
/* Kibaza Genealogy System - About Rwanda Heritage
    Finalized for BIT Thesis Submission - May 2026
    Developer: Dushime Alimence
*/
require_once 'db_connect.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About | <?php echo $system_name; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #27ae60;
            --dark-ink: #1a1a2e;
            --soft-white: #f4f7f6;
            --accent-gold: #f1c40f;
        }

        body { 
            font-family: 'DM Sans', 'Segoe UI', Tahoma, sans-serif; 
            line-height: 1.6; 
            color: #333; 
            background-color: var(--soft-white); 
            margin: 0; 
            padding: 0; 
        }

        .header { 
            background: linear-gradient(rgba(26, 26, 46, 0.8), rgba(26, 26, 46, 0.8)), url('https://www.visitrwanda.com/wp-content/uploads/visitrwanda-hero.jpg');
            background-size: cover;
            background-position: center;
            color: white; 
            padding: 80px 20px; 
            text-align: center; 
        }

        .container { 
            max-width: 900px; 
            margin: -60px auto 60px; 
            background: white; 
            padding: 50px; 
            border-radius: 16px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
        }

        h1 { margin: 0; font-size: 2.8em; letter-spacing: -1px; }
        
        h2 { 
            color: var(--dark-ink); 
            border-bottom: 3px solid var(--primary-green); 
            padding-bottom: 10px; 
            margin-top: 40px; 
            display: inline-block;
        }

        p { text-align: justify; margin-bottom: 24px; font-size: 1.05em; color: #555; }

        .highlight-box { 
            background: #f0fff4; 
            border-left: 6px solid var(--primary-green); 
            padding: 30px; 
            margin: 40px 0; 
            font-style: italic; 
            font-size: 1.1em;
            color: var(--dark-ink);
            border-radius: 0 12px 12px 0;
        }

        .feature-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 25px; 
            margin-top: 40px; 
        }

        .feature-card { 
            background: #fff; 
            border: 1px solid #eee; 
            padding: 25px; 
            border-radius: 12px; 
            transition: transform 0.3s ease;
        }

        .feature-card:hover { transform: translateY(-5px); border-color: var(--primary-green); }

        .feature-card strong { 
            color: var(--primary-green); 
            display: block; 
            margin-bottom: 12px; 
            font-size: 1.2em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .footer-link { text-align: center; margin-top: 50px; }

        .btn { 
            background: var(--dark-ink); 
            color: white; 
            padding: 15px 35px; 
            text-decoration: none; 
            border-radius: 8px; 
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .btn:hover { background: var(--primary-green); }

        footer {
            text-align: center;
            padding: 30px;
            color: #888;
            font-size: 0.9em;
        }

        @media (max-width: 600px) {
            .container { padding: 25px; margin-top: -30px; }
            h1 { font-size: 1.8em; }
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Preserving the Rwandan Spirit</h1>
</div>

<div class="container">
    <h2>Our Mission</h2>
    <p>
        <strong><?php echo $system_name; ?></strong> is a dedicated digital sanctuary created to safeguard the rich tapestry of Rwandan family lineages. In an era where urbanization and time threaten the continuity of oral traditions, our platform serves as a bridge—connecting the wisdom of the elders with the digital fluency of the youth. 
    </p>

    <div class="highlight-box">
        "Lineage is the thread that connects our past to our future. By archiving these stories today, we ensure that no Rwandan child ever feels lost in the shadows of history."
    </div>

    <h2>Scholarly Foundation</h2>
    <p>
        This system was conceptualized and engineered as a capstone research project for the <strong>Bachelor of Information Technology</strong> at the <strong>University of Kigali</strong>. It represents a synthesis of technical innovation and sociological preservation, utilizing modern web frameworks to solve real-world cultural challenges.
    </p>

    <div class="feature-grid">
        <div class="feature-card">
            <strong>Cultural Authenticity</strong>
            Integrating deep-rooted Rwandan identity markers such as <b>Amoko (Clans)</b> and <b>Inkomoko (Origins)</b>, we provide a structured environment that respects traditional values.
        </div>
        <div class="feature-card">
            <strong>Technical Rigor</strong>
            Powered by a robust <b>PHP/MySQL</b> architecture, the system offers high-fidelity record retrieval, secure session management, and intuitive lineage visualization.
        </div>
    </div>

    <div class="footer-link">
        <a href="index.php" class="btn">Return to Search</a>
    </div>
</div>

<footer>
    &copy; <?php echo $system_year . " " . $system_name; ?> Project. All Rights Reserved.
</footer>

</body>
</html>
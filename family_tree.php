<?php
/**
 * Kibaza Rwanda Heritage System — Royal Spouse-Paired Tree Engine
 * Path: /family_tree.php
 * Finalized for BIT Thesis Submission - Premium Balanced Lineage Engine
 * Version: 10.0 (Zoom + Pan Navigation Update)
 */

ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

// --- DATA FETCHING ---
$target_id = isset($_GET['member_id']) ? intval($_GET['member_id']) : null;

if ($target_id) {
    $tq = $conn->query("SELECT family_id FROM members WHERE id = " . intval($target_id));
    $family_id = ($tq && $tq->num_rows > 0) ? (int)$tq->fetch_assoc()['family_id'] : null;
} elseif (isset($_GET['id'])) {
    $family_id = intval($_GET['id']);
} elseif (isset($_SESSION['family_id'])) {
    $family_id = (int)$_SESSION['family_id'];
} else {
    header("Location: index.php?msg=access_denied");
    exit();
}

if (!$family_id) {
    die("Invalid or missing family ID.");
}

$fq = $conn->query("SELECT family_name, family_code, origin_location, founder_name FROM families WHERE id = " . intval($family_id));
if (!$fq || $fq->num_rows === 0) {
    die("Family record not found.");
}
$family = $fq->fetch_assoc();

// --- FETCH ENGINE MATRIX INTO STORAGE ---
$all_members  = [];
$children_map = [];
$spouse_map   = [];
$root_nodes   = [];

$sql = "SELECT id, name, gender, parent_id, spouse_id, role_in_family,
               birth_date, death_date, profile_image,
               contact_phone, bio_note, clan_id
        FROM members
        WHERE family_id = " . intval($family_id) . "
        ORDER BY birth_date ASC";

// clans table: clan_id (PK), clan_name, totem
// members.clan_id → clans.clan_id  (NOT clans.id — common mismatch)
$clan_names = []; // [ clan_id => ['name' => ..., 'totem' => ...] ]
$cq = @$conn->query("SELECT clan_id, clan_name, totem FROM clans");
if ($cq && $cq->num_rows > 0) {
    while ($crow = $cq->fetch_assoc()) {
        $clan_names[(int)$crow['clan_id']] = [
            'name'  => trim($crow['clan_name'] ?? ''),
            'totem' => trim($crow['totem']     ?? ''),
        ];
    }
}

$aq = $conn->query($sql);
while ($row = $aq->fetch_assoc()) {
    $all_members[(int)$row['id']] = $row;
}

// =========================================================================
// ADVANCED LINEAGE MATRIX: MULTI-SPOUSE TWO-WAY MAPPING ENGINE
// =========================================================================
foreach ($all_members as $id => $m) {
    $sid = (!empty($m['spouse_id'])) ? (int)$m['spouse_id'] : null;
    $pid = (!empty($m['parent_id'])) ? (int)$m['parent_id'] : null;

    if ($sid && isset($all_members[$sid])) {
        if (!isset($spouse_map[$id]))  { $spouse_map[$id]  = []; }
        if (!isset($spouse_map[$sid])) { $spouse_map[$sid] = []; }
        if (!in_array($sid, $spouse_map[$id]))  { $spouse_map[$id][]  = $sid; }
        if (!in_array($id,  $spouse_map[$sid])) { $spouse_map[$sid][] = $id;  }
    }

    if ($pid === null || !isset($all_members[$pid])) {
        if ($m['gender'] === 'Female' && $sid) {
            continue;
        }
        $root_nodes[] = $id;
    } else {
        $children_map[$pid][] = $id;
    }
}

$root_nodes = array_values(array_unique($root_nodes));

/**
 * HELPER: Outputs a Member Card Element
 */
function drawMemberCard($id, $all_members, $target_id, $clan_names = [], $is_founder = false) {
    $r = $all_members[$id];

    // ── Safe string extraction ──────────────────────────────────────────────
    $img    = !empty($r['profile_image']) ? htmlspecialchars($r['profile_image'], ENT_QUOTES) : 'default_user.png';
    $phone  = !empty($r['contact_phone']) ? htmlspecialchars($r['contact_phone'], ENT_QUOTES) : 'Not Provided';
    $name   = htmlspecialchars($r['name']           ?? '', ENT_QUOTES);
    $gender = !empty($r['gender'])        ? htmlspecialchars($r['gender'],         ENT_QUOTES) : 'Male';
    $role   = htmlspecialchars($r['role_in_family'] ?? '', ENT_QUOTES);
    $bday   = !empty($r['birth_date'])    ? date('M d, Y', strtotime($r['birth_date'])) : 'Unknown';
    $dday   = !empty($r['death_date'])    ? date('M d, Y', strtotime($r['death_date'])) : 'Living';

    // ── Clan resolution ─────────────────────────────────────────────────────
    $cid   = (!empty($r['clan_id']) && (int)$r['clan_id'] > 0) ? (int)$r['clan_id'] : 0;
    $clan  = ($cid && !empty($clan_names[$cid]['name']))  ? htmlspecialchars($clan_names[$cid]['name'],  ENT_QUOTES) : ($cid ? 'Clan '.$cid : 'Not Assigned');
    $totem = ($cid && !empty($clan_names[$cid]['totem'])) ? htmlspecialchars($clan_names[$cid]['totem'], ENT_QUOTES) : '';

    // ── Bio: sanitize — discard placeholder values, keep real content ───────────
    $bio_raw = (isset($r['bio_note']) && is_string($r['bio_note'])) ? trim($r['bio_note']) : '';
    if (in_array($bio_raw, ['0', '-', 'N/A', 'n/a', 'null', 'NULL'], true)) {
        $bio_raw = '';
    }
    // Clean invalid bytes with iconv IGNORE (safe for Kinyarwanda U+2019 apostrophes)
    if ($bio_raw !== '' && function_exists('iconv')) {
        $cleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $bio_raw);
        if ($cleaned !== false && $cleaned !== '') { $bio_raw = $cleaned; }
    }
    $bio_safe = ($bio_raw !== '') ? htmlspecialchars($bio_raw, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';

    // ── Card classes ────────────────────────────────────────────────────────
    $is_target    = ((int)$r['id'] === (int)$target_id);
    $card_classes = 'member-card';
    if ($is_target)  $card_classes .= ' highlighted-member';
    if ($is_founder) $card_classes .= ' founder-node';

    $fallback_bg  = ($gender === 'Female') ? 'd946ef' : '2563eb';
    $gender_label = ($gender === 'Female') ? 'Gore' : 'Gabo';

    // ── Card HTML ───────────────────────────────────────────────────────────
    echo "<div class='$card_classes'
               data-id='" . intval($r['id']) . "'
               data-name='$name' data-gender='$gender' data-role='$role'
               data-birth='" . htmlspecialchars($bday, ENT_QUOTES) . "'
               data-death='" . htmlspecialchars($dday, ENT_QUOTES) . "'
               data-phone='$phone' data-img='$img'
               onclick='openMemberModal(this)'>";

    if ($is_target)              echo "<div class='highlight-label'>YOU</div>";
    if ($is_founder && !$is_target) echo "<div class='founder-label'>&#x1F451; FOUNDER</div>";

    echo "<img src='uploads/$img' class='tree-thumb' onerror=\"this.src='https://ui-avatars.com/api/?background=$fallback_bg&color=fff&name=" . urlencode($r['name']) . "'\">";
    echo "<strong class='member-name'>$name</strong>";
    echo "<span class='gender-pill'>$gender ($gender_label)</span>";
    echo "<span class='role-txt'>$role</span>";

    echo ($dday === 'Living')
        ? "<span class='status-chip status-living'>Living</span>"
        : "<span class='status-chip status-ancestor'>&dagger; " . date('Y', strtotime($r['death_date'])) . "</span>";

    // Hidden div — only safe transport for rich bio text through HTML.
    // JS reads .textContent which automatically decodes HTML entities back to real chars.
    echo "<div class=\"bio-data\" style=\"display:none;\">" . $bio_safe . "</div>";

    echo "</div>";
}


function renderSpousePairedTree($id, $all_members, $children_map, $spouse_map, $target_id, $clan_names = [], $is_founder = false) {
    if (!isset($all_members[$id])) return;

    $spouses      = isset($spouse_map[$id]) ? $spouse_map[$id] : [];
    $all_children = [];

    if (isset($children_map[$id])) $all_children = array_merge($all_children, $children_map[$id]);
    foreach ($spouses as $s_id) {
        if (isset($children_map[$s_id])) {
            $all_children = array_merge($all_children, $children_map[$s_id]);
        }
    }
    $all_children = array_unique($all_children);
    $has_children = !empty($all_children);

    echo "<li>";
    echo "<div class='marital-couple-row" . (!empty($spouses) ? " paired-union" : "") . "'>";

    drawMemberCard($id, $all_members, $target_id, $clan_names, $is_founder);

    foreach ($spouses as $s_id) {
        if ($all_members[$s_id]['gender'] === 'Female' || $all_members[$id]['gender'] === 'Female') {
            if ($id < $s_id) {
                drawMemberCard($s_id, $all_members, $target_id, $clan_names, $is_founder);
            }
        }
    }
    echo "</div>";

    if ($has_children) {
        echo "<ul>";
        foreach ($all_children as $child_id) {
            renderSpousePairedTree($child_id, $all_members, $children_map, $spouse_map, $target_id, $clan_names, false);
        }
        echo "</ul>";
    }
    echo "</li>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lineage | <?php echo htmlspecialchars($family['family_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:         #f8f6f0;
            --navbar:     #1b1f3b;
            --accent:     #2e8b57;
            --gold:       #d4af37;
            --text-dark:  #1f2937;
            --card-bg:    #fffdf8;
            --connector:  #8aa899;
            --gray-muted: #6b7280;
            --shadow-sm:  rgba(27, 31, 59, 0.04);
            --shadow-md:  rgba(27, 31, 59, 0.08);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            color: var(--text-dark);
            overflow: hidden;
            position: relative; /* required for correct offsetLeft/offsetTop calc */
        }

        /* ── TOPBAR ── */
        .topbar {
            background: var(--navbar);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 200;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            height: 56px;
        }
        .topbar-brand { display: flex; align-items: center; gap: 10px; }
        .topbar-title {
            font-family: 'Playfair Display', serif;
            color: var(--gold);
            font-size: 1.2rem;
            font-weight: 700;
        }
        .topbar-title small {
            color: rgba(255,255,255,0.45);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.78rem;
            font-weight: 400;
            margin-left: 8px;
        }
        .topbar-right { display: flex; align-items: center; gap: 10px; }

        /* ── SEARCH BAR ── */
        .search-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .search-wrap input {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            padding: 7px 36px 7px 14px;
            border-radius: 10px;
            font-size: 0.82rem;
            width: 200px;
            outline: none;
            transition: background 0.2s, border-color 0.2s, width 0.3s;
            font-family: 'DM Sans', sans-serif;
        }
        .search-wrap input::placeholder { color: rgba(255,255,255,0.4); }
        .search-wrap input:focus {
            background: rgba(255,255,255,0.15);
            border-color: var(--accent);
            width: 260px;
        }
        .search-icon {
            position: absolute;
            right: 28px;
            color: rgba(255,255,255,0.4);
            font-size: 0.85rem;
            pointer-events: none;
        }
        .search-clear {
            position: absolute;
            right: 8px;
            background: none;
            border: none;
            color: rgba(255,255,255,0.4);
            cursor: pointer;
            font-size: 0.75rem;
            padding: 2px 4px;
            display: none;
            line-height: 1;
        }
        .search-clear:hover { color: #fff; }
        .search-dropdown {
            position: absolute;
            top: 56px;
            right: 140px;
            width: 300px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 12px 40px rgba(17,24,39,0.15);
            z-index: 500;
            max-height: 360px;
            overflow-y: auto;
            display: none;
            border: 1px solid rgba(46,139,87,0.15);
        }
        .search-result-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            cursor: pointer;
            border-bottom: 1px solid #f3f1eb;
            transition: background 0.15s;
        }
        .search-result-item:last-child { border-bottom: none; }
        .search-result-item:hover { background: #f8f6f0; }
        .search-result-item:hover .sri-name { color: var(--accent); }
        .sri-img {
            width: 36px; height: 36px; border-radius: 50%;
            object-fit: cover; border: 2px solid var(--accent);
            flex-shrink: 0;
        }
        .sri-info { flex: 1; min-width: 0; }
        .sri-name {
            font-size: 0.85rem; font-weight: 700;
            color: var(--text-dark); white-space: nowrap;
            overflow: hidden; text-overflow: ellipsis;
        }
        .sri-meta { font-size: 0.72rem; color: var(--gray-muted); margin-top: 1px; }
        .sri-status {
            font-size: 0.68rem; font-weight: 700; padding: 2px 8px;
            border-radius: 10px; white-space: nowrap;
        }
        .sri-living  { background: #d1fae5; color: #065f46; }
        .sri-deceased { background: #fef3c7; color: #92400e; }
        .search-empty {
            padding: 20px; text-align: center;
            color: var(--gray-muted); font-size: 0.82rem; font-style: italic;
        }
        .search-highlight { color: var(--accent); font-weight: 800; }

        /* ── ZOOM CONTROLS ── */
        .zoom-controls {
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 4px 10px;
        }
        .zoom-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            color: #fff;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4px 10px;
            gap: 1px;
            flex-shrink: 0;
            min-width: 60px;
        }
        .zoom-btn:hover { background: var(--accent); border-color: var(--accent); }
        .zoom-btn:active { transform: scale(0.95); }
        .zoom-icon { font-size: 1rem; font-weight: 700; line-height: 1.2; }
        .zoom-cap {
            font-size: 0.58rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.55);
            white-space: nowrap;
        }
        .zoom-btn:hover .zoom-cap { color: rgba(255,255,255,0.9); }
        #zoom-label {
            color: rgba(255,255,255,0.65);
            font-size: 0.76rem;
            font-weight: 600;
            min-width: 40px;
            text-align: center;
            letter-spacing: 0.2px;
        }
        .zoom-hint {
            color: rgba(255,255,255,0.3);
            font-size: 0.68rem;
            white-space: nowrap;
        }

        .back-btn {
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.82rem;
            padding: 7px 16px;
            border: 2px solid var(--accent);
            background: var(--accent);
            border-radius: 8px;
            transition: 0.25s ease;
            white-space: nowrap;
        }
        .back-btn:hover { background: transparent; color: var(--accent); }

        /* ── CANVAS WRAPPER (scrollable + draggable) ── */
        .canvas-wrap {
            display: block;
            width: 100%;
            height: calc(100vh - 56px);
            overflow: auto;
            cursor: grab;
            user-select: none;
            -webkit-user-select: none;
            background: var(--bg);
            position: relative; /* anchor for getBoundingClientRect accuracy */
        }
        .canvas-wrap.is-panning { cursor: grabbing; }

        /* ── INNER SCALER (zoom target) ── */
        #treeScaler {
            display: inline-flex;
            justify-content: center;
            align-items: flex-start;
            padding: 60px 80px 120px;
            min-width: 100%;
            min-height: 100%;
            transform-origin: top center;
            transition: transform 0.18s ease;
        }
        @media (max-width: 768px) {
            #treeScaler {
                transform-origin: top left; /* prevents off-screen scaling on mobile */
                padding: 30px 20px 80px;
                justify-content: flex-start;
            }
        }

        /* ── TREE CONNECTORS ── */
        .canvas-wrap ul {
            padding-top: 45px;
            position: relative;
            transition: all 0.3s ease;
            display: flex;
            justify-content: center;
            list-style-type: none;
            padding-left: 0;
            gap: 16px;
        }
        .canvas-wrap li {
            text-align: center;
            list-style-type: none;
            position: relative;
            padding: 45px 6px 0 6px;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .canvas-wrap li::before, .canvas-wrap li::after {
            content: '';
            position: absolute;
            top: 0;
            right: 50%;
            border-top: 3px solid var(--connector);
            width: 50%;
            height: 45px;
        }
        .canvas-wrap li::after { right: auto; left: 50%; border-left: 3px solid var(--connector); }
        .canvas-wrap li:only-child::after,
        .canvas-wrap li:only-child::before { display: none; }
        .canvas-wrap li:only-child { padding-top: 0; }
        .canvas-wrap li:first-child::before,
        .canvas-wrap li:last-child::after { border: 0 none; }
        .canvas-wrap li:last-child::before  { border-right: 3px solid var(--connector); border-radius: 0 8px 0 0; }
        .canvas-wrap li:first-child::after  { border-radius: 8px 0 0 0; }
        .canvas-wrap ul ul::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            border-left: 3px solid var(--connector);
            width: 0;
            height: 45px;
            transform: translateX(-50%);
        }

        /* ── COUPLE ROW ── */
        .marital-couple-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            position: relative;
            z-index: 5;
            transition: 0.3s ease;
        }
        .marital-couple-row.paired-union {
            background: rgba(46, 139, 87, 0.04);
            padding: 8px 14px;
            border-radius: 22px;
            border: 2px dashed rgba(46, 139, 87, 0.3);
        }

        /* ── MEMBER CARD ── */
        .member-card {
            background: var(--card-bg);
            border: 2px solid var(--accent);
            border-radius: 18px;
            padding: 14px 12px 12px;
            width: 150px;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
            position: relative;
            text-align: center;
            box-shadow: 0 4px 12px var(--shadow-sm);
        }
        .member-card:hover { transform: translateY(-4px); box-shadow: 0 10px 24px var(--shadow-md); }
        .member-card.search-match {
            border-color: var(--gold) !important;
            box-shadow: 0 0 0 3px rgba(212,175,55,0.3) !important;
        }
        @keyframes searchFlash {
            0%   { box-shadow: 0 0 0 4px rgba(46,139,87,0.6); transform: scale(1.04); }
            100% { box-shadow: 0 4px 12px var(--shadow-sm);   transform: scale(1); }
        }
        .member-card.search-flash {
            animation: searchFlash 0.6s ease-out forwards;
            border-color: var(--accent) !important;
        }

        .founder-node { border: 2px solid var(--gold); background: #fffefa; box-shadow: 0 6px 20px rgba(212,175,55,0.12); }
        .founder-node:hover { border-color: #b59226; }
        .founder-label {
            position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
            background: var(--gold); color: #fff; font-size: 8px; font-weight: 700;
            padding: 2px 10px; border-radius: 20px; letter-spacing: 0.5px; white-space: nowrap;
            box-shadow: 0 2px 6px rgba(212,175,55,0.3);
        }
        .highlighted-member { border: 2.5px solid var(--gold); box-shadow: 0 0 0 4px rgba(212,175,55,0.15); }
        .highlight-label {
            position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
            background: var(--text-dark); color: #fff; font-size: 8px; font-weight: 700;
            padding: 2px 10px; border-radius: 20px; letter-spacing: 1px; white-space: nowrap;
        }
        .tree-thumb {
            width: 52px; height: 52px; border-radius: 50%; object-fit: cover;
            border: 2px solid #e8e2d5; display: block; margin: 0 auto 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03);
        }
        .founder-node .tree-thumb { border-color: var(--gold); }
        .member-card .member-name {
            display: block; font-size: 14px; font-weight: 700; color: #1f2937;
            margin-bottom: 5px; line-height: 1.3;
        }
        .gender-pill {
            display: inline-block; background: #f3f1eb; color: #52634f;
            font-size: 8px; font-weight: 700; padding: 2px 8px; border-radius: 4px;
            text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 5px;
        }
        .member-card .role-txt {
            display: block; font-size: 10px; font-weight: 600; color: #2e8b57;
            margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.2px;
        }
        .status-chip { font-size: 9px; padding: 2px 10px; border-radius: 20px; font-weight: 700; display: inline-block; letter-spacing: 0.3px; }
        .status-living   { background: #d1fae5; color: #065f46; }
        .status-ancestor { background: #fef3c7; color: #92400e; }

        /* ── EMPTY STATE ── */
        .empty-state { text-align: center; padding: 80px 20px; color: var(--gray-muted); width: 100%; }
        .empty-state h2 { font-size: 1.4rem; margin-bottom: 10px; color: var(--text-dark); font-family: 'Playfair Display', serif; }

        /* ── MODAL ── */
        .modal {
            display: none; position: fixed; z-index: 1000; inset: 0;
            background: rgba(17,24,39,0.45); backdrop-filter: blur(5px);
        }
        .modal-content {
            background: var(--card-bg); margin: 6% auto; padding: 40px 32px 30px;
            width: 92%; max-width: 390px; border-radius: 24px; text-align: center;
            position: relative; box-shadow: 0 20px 48px rgba(17,24,39,0.15);
            border: 1px solid rgba(59,122,87,0.1);
            animation: popIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes popIn { from { opacity:0; transform: translateY(12px) scale(0.98); } to { opacity:1; transform: none; } }
        .close { position: absolute; right: 20px; top: 16px; font-size: 24px; cursor: pointer; color: var(--gray-muted); line-height: 1; transition: 0.2s; }
        .close:hover { color: var(--text-dark); }
        .modal-img { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 3px solid var(--accent); margin-bottom: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        #m-name { font-family: 'Playfair Display', serif; color: var(--text-dark); font-size: 1.3rem; font-weight: 700; margin-bottom: 2px; }
        #m-role { color: var(--accent); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; margin-bottom: 20px; }
        .modal-field { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eef2f3; font-size: 0.85rem; gap: 8px; }
        .modal-field strong { color: var(--text-dark); font-weight: 500; }
        .modal-field span   { color: var(--gray-muted); font-weight: 700; }
        .bio-label { text-align: left; font-size: 0.7rem; font-weight: 700; color: var(--gold); text-transform: uppercase; letter-spacing: 1px; margin: 18px 0 6px; }
        #m-bio {
            color: var(--text-dark); line-height: 1.7; text-align: justify; font-size: 0.82rem;
            background: #fdfcf9; padding: 12px 14px; border-radius: 12px;
            border-left: 3px solid var(--gold); border-right: 1px solid #f1ece1;
            border-top: 1px solid #f1ece1; border-bottom: 1px solid #f1ece1;
            max-height: 140px; overflow-y: auto; white-space: pre-line;
            scrollbar-width: thin; scrollbar-color: var(--gold) #f1ece1;
        }

        /* ══════════════════════════════════════════
           MOBILE RESPONSIVE
        ══════════════════════════════════════════ */
        @media (max-width: 768px) {

            /* Topbar: stack into two rows */
            .topbar {
                height: auto;
                padding: 8px 12px;
                flex-wrap: wrap;
                gap: 8px;
            }
            .topbar-brand { flex: 1; min-width: 0; }
            .topbar-title { font-size: 0.9rem; }
            .topbar-title small { display: none; } /* hide subtitle on mobile */
            .topbar-right {
                width: 100%;
                justify-content: space-between;
                gap: 6px;
                flex-wrap: wrap;
            }

            /* Search: full width on mobile */
            .search-wrap { flex: 1; min-width: 120px; }
            .search-wrap input { width: 100% !important; font-size: 0.75rem; padding: 6px 30px 6px 10px; }

            /* Zoom controls: compact */
            .zoom-controls { padding: 3px 6px; gap: 4px; }
            .zoom-btn { min-width: 44px; padding: 3px 6px; }
            .zoom-icon { font-size: 0.9rem; }
            .zoom-cap { font-size: 0.5rem; }
            #zoom-label { font-size: 0.68rem; min-width: 32px; }
            .zoom-hint { display: none; }

            /* Back button: shorter */
            .back-btn { padding: 5px 10px; font-size: 0.72rem; }

            /* Canvas: full height, show scrollbars so user knows it's scrollable */
            .canvas-wrap {
                height: calc(100vh - 88px);
                overflow: scroll; /* force scroll on iOS Safari */
                -webkit-overflow-scrolling: touch; /* smooth momentum scroll on iOS */
            }

            /* Tree cards: smaller on mobile */
            .member-card {
                width: 110px;
                padding: 10px 8px 8px;
                border-radius: 14px;
            }
            .tree-thumb { width: 40px; height: 40px; margin-bottom: 6px; }
            .member-card .member-name { font-size: 11px; margin-bottom: 3px; }
            .gender-pill { font-size: 7px; padding: 1px 6px; margin-bottom: 3px; }
            .member-card .role-txt { font-size: 8px; margin-bottom: 4px; }
            .status-chip { font-size: 7px; padding: 1px 7px; }
            .founder-label { font-size: 7px; padding: 2px 7px; top: -10px; }
            .highlight-label { font-size: 7px; padding: 2px 7px; top: -10px; }

            /* Connector lines: tighter gap on mobile */
            .canvas-wrap ul { gap: 8px; padding-top: 35px; }
            .canvas-wrap li { padding: 35px 4px 0 4px; }
            .canvas-wrap li::before, .canvas-wrap li::after { height: 35px; }
            .canvas-wrap ul ul::before { height: 35px; }

            /* Couple row: tighter */
            .marital-couple-row.paired-union { padding: 6px 8px; gap: 8px; }

            /* Modal: full screen on mobile */
            .modal-content {
                margin: 0;
                width: 100%;
                max-width: 100%;
                min-height: 100vh;
                border-radius: 0;
                padding: 30px 20px 40px;
                overflow-y: auto;
            }
            .modal-img { width: 70px; height: 70px; }
            #m-name { font-size: 1.1rem; }
            .modal-field { font-size: 0.78rem; }
            #m-bio { font-size: 0.78rem; max-height: 200px; }

            /* Search dropdown: full width */
            .search-dropdown {
                right: 0;
                left: 0;
                width: 100%;
                top: 88px;
                border-radius: 0 0 14px 14px;
            }
        }

        @media (max-width: 480px) {
            .member-card { width: 90px; padding: 8px 6px 6px; }
            .tree-thumb { width: 32px; height: 32px; }
            .member-card .member-name { font-size: 9px; }
            .gender-pill { display: none; } /* hide on very small screens */
            .canvas-wrap ul { gap: 6px; }
        }
    </style>
</head>
<body>

<!-- ══════════════════════════════════════════
     TOPBAR
══════════════════════════════════════════ -->
<div class="topbar">
    <div class="topbar-brand">
        <span style="color:var(--gold); font-size:1.4rem;">👑</span>
        <div class="topbar-title">
            <?php echo htmlspecialchars($family['family_name']); ?> Archive
            <small>
                Genealogy Ledger
                <?php if (!empty($family['origin_location'])): ?>
                · <?php echo htmlspecialchars($family['origin_location']); ?>
                <?php endif; ?>
                <?php if (!empty($family['founder_name'])): ?>
                · Founded by <?php echo htmlspecialchars($family['founder_name']); ?>
                <?php endif; ?>
            </small>
        </div>
    </div>

    <div class="topbar-right">
        <!-- Search Bar -->
        <div class="search-wrap">
            <input type="text" id="treeSearch" placeholder="Search member..." autocomplete="off"
                   oninput="liveSearch(this.value)" onkeydown="if(event.key==='Escape')clearSearch()">
            <span class="search-icon">&#128269;</span>
            <button class="search-clear" id="searchClear" onclick="clearSearch()" title="Clear">&#x2715;</button>
        </div>
        <div id="searchResults" class="search-dropdown"></div>
        <!-- Zoom Controls -->
        <div class="zoom-controls">
            <button class="zoom-btn" onclick="zoomTree(-0.15)" title="Zoom Out (-)">
                <span class="zoom-icon">-</span>
                <span class="zoom-cap">Zoom Out</span>
            </button>
            <span id="zoom-label">100%</span>
            <button class="zoom-btn" onclick="zoomTree(+0.15)" title="Zoom In (+)">
                <span class="zoom-icon">+</span>
                <span class="zoom-cap">Zoom In</span>
            </button>
            <button class="zoom-btn zoom-reset" onclick="resetZoom()" title="Reset Zoom (0)">
                <span class="zoom-icon">&#8634;</span>
                <span class="zoom-cap">Reset</span>
            </button>
        </div>
        <span class="zoom-hint">· drag to pan</span>
        <a href="dashboards/youth_dashboard.php" class="back-btn">← Console</a>
    </div>
</div>

<!-- ══════════════════════════════════════════
     CANVAS (scrollable + draggable)
══════════════════════════════════════════ -->
<div class="canvas-wrap" id="canvasWrap">
    <div id="treeScaler">

        <?php if (empty($root_nodes)): ?>
            <div class="empty-state">
                <h2>No lineage entries documented</h2>
                <p>No authenticated ancestry rows exist inside family profile mapping space <?php echo $family_id; ?>.</p>
            </div>
        <?php else: ?>
            <ul class="tree-root">
                <?php foreach ($root_nodes as $rid): ?>
                    <?php renderSpousePairedTree($rid, $all_members, $children_map, $spouse_map, $target_id, $clan_names, true); ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

    </div><!-- #treeScaler -->
</div><!-- .canvas-wrap -->

<!-- ══════════════════════════════════════════
     MEMBER DETAIL MODAL
══════════════════════════════════════════ -->
<div id="bioModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <img id="m-img" src="" class="modal-img" alt="">
        <h2 id="m-name"></h2>
        <p  id="m-role"></p>
        <div class="modal-field"><strong>Gender (Gitsina)</strong><span id="m-gender"></span></div>
        <div class="modal-field"><strong>Clan (Ubwoko)</strong>   <span id="m-clan"></span></div>
        <div class="modal-field" id="m-totem-row" style="display:none;"><strong>Totem (Inyambo)</strong><span id="m-totem"></span></div>
        <div class="modal-field"><strong>Born</strong>             <span id="m-date"></span></div>
        <div class="modal-field"><strong>Status</strong>           <span id="m-death"></span></div>
        <div class="modal-field"><strong>Contact</strong>          <span id="m-phone" style="color:var(--accent);"></span></div>
        <div class="bio-label">Oral History &amp; Parables</div>
        <div id="m-bio"></div>
    </div>
</div>

<!-- ══════════════════════════════════════════
     SCRIPTS
══════════════════════════════════════════ -->
<script>
// ─── MEMBER DATA MAP ─────────────────────────────────────────────────────────────────────────────
// Built server-side with json_encode — safely handles all bio content:
// apostrophes, quotes, newlines, unicode, HTML entities, copied document text.
// Bio and clan data are NEVER transported through HTML attributes.
const MEMBER_DATA = <?php
    $js_map = [];
    foreach ($all_members as $mid => $m) {
        // Scrub bio text: strip invalid UTF-8 bytes (common with Word/PDF copy-paste)
        // Without this, a single bad byte causes json_encode to return false,
        // silently breaking MEMBER_DATA entirely and hiding ALL bios and clans.
        $bio_raw = (isset($m['bio_note']) && is_string($m['bio_note'])) ? trim($m['bio_note']) : '';
        // Treat literal '0', '-', 'N/A' as empty — these are DB placeholder values
        if (in_array($bio_raw, ['0', '-', 'N/A', 'n/a', 'null', 'NULL'], true)) {
            $bio_raw = '';
        }
        if ($bio_raw !== '') {
            // Clean invalid bytes only — use iconv with IGNORE flag.
            // mb_convert_encoding('UTF-8','UTF-8') can corrupt valid multibyte chars
            // (e.g. Kinyarwanda n’iterambere using U+2019 right single quote)
            // iconv IGNORE safely drops only truly invalid bytes, preserving all valid UTF-8
            if (function_exists('iconv')) {
                $cleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $bio_raw);
                if ($cleaned !== false && $cleaned !== '') {
                    $bio_raw = $cleaned;
                }
            }
        }

        $cid = (!empty($m['clan_id']) && (int)$m['clan_id'] > 0) ? (int)$m['clan_id'] : 0;
        if ($cid && !empty($clan_names[$cid]['name'])) {
            $clan_display = $clan_names[$cid]['name'];
        } elseif ($cid) {
            $clan_display = 'Clan #' . $cid;
        } else {
            $clan_display = 'Not Assigned';
        }

        $totem_display = ($cid && !empty($clan_names[$cid]['totem'])) ? $clan_names[$cid]['totem'] : '';
        $js_map[$mid]  = ['bio' => $bio_raw, 'clan' => $clan_display, 'totem' => $totem_display];
    }
    // JSON_INVALID_UTF8_SUBSTITUTE replaces bad chars with U+FFFD instead of failing
    // This is available PHP 7.2+ — older PHP falls back to the scrubbed values above
    $flags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
    }
    // JSON_PARTIAL_OUTPUT_ON_ERROR prevents silent null output on bad UTF-8
    if (defined('JSON_PARTIAL_OUTPUT_ON_ERROR')) {
        $flags |= JSON_PARTIAL_OUTPUT_ON_ERROR;
    }
    $json_out = json_encode($js_map, $flags);
    // If json_encode still fails (extremely rare), output empty object so JS never breaks
    echo ($json_out !== false) ? $json_out : '{}'
?>;

// ─── MEMBER DETAIL MODAL ─────────────────────────────────────────────────────────────────────────────
function openMemberModal(el) {
    const g        = a => el.getAttribute('data-' + a);
    const memberId = parseInt(g('id'), 10);
    const data     = MEMBER_DATA[memberId] || {};

    document.getElementById('m-name').textContent  = g('name');
    document.getElementById('m-role').textContent  = g('role');
    const birthVal = g('birth') || '';
    document.getElementById('m-date').textContent = (birthVal && birthVal !== 'Unknown') ? birthVal : 'Not Recorded';
    document.getElementById('m-phone').textContent = g('phone');

    // Clan + Totem from server-side JSON map
    document.getElementById('m-clan').textContent = data.clan || 'Not Assigned';
    const totemRow = document.getElementById('m-totem-row');
    if (data.totem) {
        document.getElementById('m-totem').textContent = data.totem;
        totemRow.style.display = 'flex';
    } else {
        totemRow.style.display = 'none';
    }

    const rawGender = g('gender');
    document.getElementById('m-gender').textContent = (rawGender === 'Female') ? 'Female (Gore)' : 'Male (Gabo)';

    const death = g('death');
    const de    = document.getElementById('m-death');
    const live  = (!death || death === 'Living' || death.trim() === '');
    de.textContent = live ? 'Living till Present' : 'Deceased — ' + death;
    de.style.color = live ? '#065f46' : '#92400e';

    // Bio: read from hidden .bio-data div (most reliable — htmlspecialchars encoded).
    // textContent decodes HTML entities back to real characters automatically.
    // Falls back to MEMBER_DATA json_encode map, then shows empty state.
    const bioEl      = document.getElementById('m-bio');
    const hiddenDiv  = el.querySelector('.bio-data');
    const bioFromDiv = hiddenDiv ? hiddenDiv.textContent.trim() : '';
    const bioFromMap = (data.bio || '').trim();
    const bioFinal   = bioFromDiv || bioFromMap;

    if (bioFinal) {
        bioEl.textContent     = bioFinal;
        bioEl.style.color     = '';
        bioEl.style.fontStyle = 'normal';
    } else {
        bioEl.textContent     = 'No oral history archived for this member.';
        bioEl.style.color     = 'var(--gray-muted)';
        bioEl.style.fontStyle = 'italic';
    }

    const fallbackColor = (rawGender === 'Female') ? 'd946ef' : '2563eb';
    const profileImg    = document.getElementById('m-img');
    profileImg.src      = 'uploads/' + g('img');
    profileImg.onerror  = function () {
        this.src = 'https://ui-avatars.com/api/?background=' + fallbackColor + '&color=fff&name=' + encodeURIComponent(g('name'));
    };

    document.getElementById('bioModal').style.display = 'block';
}

function closeModal() { document.getElementById('bioModal').style.display = 'none'; }
window.onclick = e => {
    if (e.target === document.getElementById('bioModal')) closeModal();
    if (!e.target.closest('.search-wrap') && !e.target.closest('.search-dropdown')) {
        document.getElementById('searchResults').style.display = 'none';
    }
};

// ─── SEARCH ENGINE ────────────────────────────────────────────────────────────
// Build member index AFTER DOM is fully rendered (DOMContentLoaded guarantee)
let memberIndex = [];
function initSearchIndex() {
    memberIndex = [];
    document.querySelectorAll('.member-card').forEach(card => {
        memberIndex.push({
            id:     card.getAttribute('data-id'),
            name:   card.getAttribute('data-name')   || '',
            role:   card.getAttribute('data-role')   || '',
            gender: card.getAttribute('data-gender') || '',
            birth:  card.getAttribute('data-birth')  || '',
            death:  card.getAttribute('data-death')  || '',
            img:    card.getAttribute('data-img')    || '',
            el:     card
        });
    });
}
// Trigger after full DOM paint — requestAnimationFrame ensures tree is rendered
document.addEventListener('DOMContentLoaded', () => requestAnimationFrame(() => {
    initSearchIndex();
}));

// Auto-fit: called after tree is fully painted
window.addEventListener('load', () => {
    if (window.innerWidth <= 900) {
        const wrap   = document.getElementById('canvasWrap');
        const scaler = document.getElementById('treeScaler');
        requestAnimationFrame(() => {
            // Use the unscaled tree width (reset to 1 first to measure true size)
            scaler.style.transform = 'scale(1)';
            const treeW   = scaler.offsetWidth;
            const wrapW   = wrap.clientWidth  - 40; // 20px padding each side
            const fitScale = Math.min(wrapW / treeW, 0.85); // never exceed 85%
            currentScale   = Math.max(fitScale, 0.3);        // never below 30%
            applyZoom();
            // Scroll to top-center after fit
            wrap.scrollLeft = (wrap.scrollWidth - wrap.clientWidth) / 2;
            wrap.scrollTop  = 0;
        });
    }
});

function liveSearch(query) {
    const q      = query.trim().toLowerCase();
    const box    = document.getElementById('searchResults');
    const clear  = document.getElementById('searchClear');
    clear.style.display = q ? 'block' : 'none';

    // Remove previous highlights
    document.querySelectorAll('.member-card.search-match').forEach(c => c.classList.remove('search-match'));

    if (!q || q.length < 2) { box.style.display = 'none'; return; }

    const hits = memberIndex.filter(m =>
        m.name.toLowerCase().includes(q) ||
        m.role.toLowerCase().includes(q)
    ).slice(0, 10);

    if (!hits.length) {
        box.innerHTML = '<div class="search-empty">No members found for "' + query + '"</div>';
        box.style.display = 'block';
        return;
    }

    box.innerHTML = hits.map(m => {
        const living   = (!m.death || m.death === 'Living');
        const statusCls = living ? 'sri-living' : 'sri-deceased';
        const statusTxt = living ? 'Living' : '&#x2020; ' + m.death;
        const fallback  = (m.gender === 'Female') ? 'd946ef' : '2563eb';
        const imgSrc    = m.img ? 'uploads/' + m.img : '';
        const avatarUrl = 'https://ui-avatars.com/api/?background=' + fallback + '&color=fff&name=' + encodeURIComponent(m.name);
        const imgTag    = '<img class="sri-img" src="' + imgSrc + '" onerror="this.onerror=null;this.src=&quot;' + avatarUrl + '&quot;">';

        // Highlight matching part of name
        const re   = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        const highlighted = m.name.replace(re, '<span class="search-highlight">$1</span>');

        return '<div class="search-result-item" onclick="selectResult(\'' + m.id + '\')">'

            + imgTag
            + '<div class="sri-info">'
            + '<div class="sri-name">' + highlighted + '</div>'
            + '<div class="sri-meta">' + (m.role || 'Member') + ' &bull; ' + (m.birth || 'Unknown') + '</div>'
            + '</div>'
            + '<span class="sri-status ' + statusCls + '">' + statusTxt + '</span>'
            + '</div>';
    }).join('');

    box.style.display = 'block';

    // Highlight matching cards on the tree
    hits.forEach(m => m.el.classList.add('search-match'));
}

function selectResult(memberId) {
    const card = memberIndex.find(m => m.id === memberId);
    if (!card) return;

    // Close dropdown, clear input
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('treeSearch').value = '';
    document.getElementById('searchClear').style.display = 'none';
    document.querySelectorAll('.member-card.search-match').forEach(c => c.classList.remove('search-match'));

    // Scroll card into view smoothly
    card.el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });

    // Flash highlight then open modal
    card.el.classList.add('search-flash');
    setTimeout(() => {
        card.el.classList.remove('search-flash');
        openMemberModal(card.el);
    }, 600);
}

function clearSearch() {
    document.getElementById('treeSearch').value = '';
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('searchClear').style.display = 'none';
    document.querySelectorAll('.member-card.search-match').forEach(c => c.classList.remove('search-match'));
}

// ─── ZOOM ENGINE ─────────────────────────────────────────────────────────────
let currentScale = 1.0;
const MIN_SCALE  = 0.25;
const MAX_SCALE  = 2.5;
const scaler     = document.getElementById('treeScaler');
const zoomLabel  = document.getElementById('zoom-label');

function zoomTree(delta) {
    currentScale = Math.min(MAX_SCALE, Math.max(MIN_SCALE, currentScale + delta));
    applyZoom();
}
function resetZoom() {
    currentScale = 1.0;
    applyZoom();
}
function applyZoom() {
    scaler.style.transform = 'scale(' + currentScale + ')';
    zoomLabel.textContent  = Math.round(currentScale * 100) + '%';
    // On mobile with top-left origin, re-center horizontally after zoom
    if (window.innerWidth <= 768) {
        const wrap = document.getElementById('canvasWrap');
        // Small delay so browser recalculates scroll dimensions after transform
        setTimeout(() => {
            const maxScroll = wrap.scrollWidth - wrap.clientWidth;
            if (maxScroll > 0) wrap.scrollLeft = maxScroll / 2;
        }, 50);
    }
}

// Keyboard shortcuts: +/= to zoom in, - to zoom out, 0 to reset
document.addEventListener('keydown', e => {
    if (document.getElementById('bioModal').style.display === 'block') return;
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
    if (e.key === '+' || e.key === '=') { e.preventDefault(); zoomTree(+0.1); }
    if (e.key === '-' || e.key === '_') { e.preventDefault(); zoomTree(-0.1); }
    if (e.key === '0')                  { e.preventDefault(); resetZoom(); }
});

// Mouse-wheel zoom (Ctrl + scroll)
document.getElementById('canvasWrap').addEventListener('wheel', e => {
    if (e.ctrlKey || e.metaKey) {
        e.preventDefault();
        zoomTree(e.deltaY < 0 ? +0.08 : -0.08);
    }
}, { passive: false });

// ─── PAN / DRAG-TO-SCROLL ENGINE ─────────────────────────────────────────────
const wrap = document.getElementById('canvasWrap');
let isPanning = false, startX, startY, scrollLeft, scrollTop;

wrap.addEventListener('mousedown', e => {
    // Don't hijack clicks on member cards or the modal
    if (e.target.closest('.member-card') || e.target.closest('.modal')) return;
    isPanning  = true;
    wrap.classList.add('is-panning');
    // getBoundingClientRect is accurate even under CSS transform:scale()
    const rect = wrap.getBoundingClientRect();
    startX     = e.clientX - rect.left;
    startY     = e.clientY - rect.top;
    scrollLeft = wrap.scrollLeft;
    scrollTop  = wrap.scrollTop;
});

wrap.addEventListener('mouseleave', () => { isPanning = false; wrap.classList.remove('is-panning'); });
wrap.addEventListener('mouseup',    () => { isPanning = false; wrap.classList.remove('is-panning'); });

wrap.addEventListener('mousemove', e => {
    if (!isPanning) return;
    e.preventDefault();
    const rect = wrap.getBoundingClientRect();
    const x    = e.clientX - rect.left;
    const y    = e.clientY - rect.top;
    wrap.scrollLeft = scrollLeft - (x - startX);
    wrap.scrollTop  = scrollTop  - (y - startY);
});

// Touch drag (mobile)
wrap.addEventListener('touchstart', e => {
    if (e.target.closest('.member-card')) return;
    const t  = e.touches[0];
    isPanning  = true;
    startX     = t.pageX;
    startY     = t.pageY;
    scrollLeft = wrap.scrollLeft;
    scrollTop  = wrap.scrollTop;
}, { passive: true });

wrap.addEventListener('touchend', () => { isPanning = false; });

wrap.addEventListener('touchmove', e => {
    if (!isPanning) return;
    const t = e.touches[0];
    wrap.scrollLeft = scrollLeft - (t.pageX - startX);
    wrap.scrollTop  = scrollTop  - (t.pageY - startY);
}, { passive: true });
</script>

</body>
</html>
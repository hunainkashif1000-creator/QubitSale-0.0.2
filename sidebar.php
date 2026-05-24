<?php
// sidebar.php - Include this in all pages
$current_page = basename($_SERVER['PHP_SELF']);

// ── Load customization settings ──────────────────────────────────────────
$_cust = [];
if(isset($conn)){
    $res = $conn->query("SELECT `key`,`value` FROM app_settings");
    if($res) while($r = $res->fetch_assoc()) $_cust[$r['key']] = $r['value'];
}
function _cfg($k,$d){ global $_cust; return $_cust[$k] ?? $d; }

$_app_name    = _cfg('app_name',       'Stockora');
$_app_tagline = _cfg('app_tagline',    'POS System');
$_accent      = _cfg('accent_color',   '#3b82f6');
$_accent2     = _cfg('accent2_color',  '#10b981');
$_bg          = _cfg('bg_color',       '#0f172a');
$_bg2         = _cfg('bg2_color',      '#1e293b');
$_danger      = _cfg('danger_color',   '#ef4444');
$_sw          = _cfg('sidebar_width',  '230');
$_brad        = _cfg('border_radius',  '12');
$_font        = _cfg('font_family',    'Plus Jakarta Sans');
$_logo_emoji  = _cfg('logo_emoji',     '🏪');
$_logo_type   = _cfg('logo_type',      'emoji');
$_logo_file   = _cfg('logo_file',      '');
$_favicon     = _cfg('favicon_file',   '');
 $logo_size    = _cfg('logo_size',      '36');
// Derive light-mode variants (slightly adjusted from dark bg)
function _lighten($hex){
    // returns a very light tint of the accent for light mode
    return $hex; // color stays same, light-mode CSS overrides bg/card
}

?>
<?php if(!empty($_favicon) && file_exists('uploads/'.$_favicon)): ?>
<link rel="icon" href="uploads/<?= htmlspecialchars($_favicon) ?>?v=<?= filemtime('uploads/'.$_favicon) ?>">
<?php endif; ?>
<link href="https://fonts.googleapis.com/css2?family=<?= urlencode($_font) ?>:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
:root {
    --sidebar-w: <?= (int)$_sw ?>px;
    --bg:        <?= $_bg ?>;
    --bg2:       <?= $_bg2 ?>;
    --accent:    <?= $_accent ?>;
    --accent2:   <?= $_accent2 ?>;
    --text:      #f1f5f9;
    --text-muted:#94a3b8;
    --card:      <?= $_bg2 ?>;
    --border:    <?= $_bg === '#0f172a' ? '#334155' : 'rgba(255,255,255,0.1)' ?>;
    --danger:    <?= $_danger ?>;
    --brad:      <?= (int)$_brad ?>px;
}

/* LIGHT THEME */
body.light-mode {
    --bg:        #f0f4ff;
    --bg2:       #ffffff;
    --card:      #ffffff;
    --border:    #d1d9e6;
    --text:      #1a1f36;
    --text-muted:#6b7280;
    --accent:    <?= $_accent ?>;
    --accent2:   <?= $_accent2 ?>;
    --danger:    <?= $_danger ?>;
}
body.light-mode .sidebar { box-shadow: 2px 0 16px rgba(0,0,0,0.1); }
body.light-mode .main-content { background: var(--bg); }
body.light-mode .card { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
body.light-mode input, body.light-mode select { background: #f0f4ff !important; color: var(--text) !important; border-color: var(--border) !important; }
body.light-mode table { background: #fff; }
body.light-mode th { background: #e8ecf4; color: var(--text-muted); }
body.light-mode td { color: var(--text); border-color: #e8ecf4; }

/* Theme Toggle */
.theme-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    margin: 0 10px 10px;
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border);
    border-radius: var(--brad);
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-muted);
    transition: all 0.2s;
    user-select: none;
}
.theme-toggle:hover { border-color: var(--accent); color: var(--text); }
.toggle-track {
    width: 36px; height: 19px;
    background: var(--border);
    border-radius: 20px;
    position: relative;
    transition: background 0.3s;
    flex-shrink: 0;
}
.toggle-track.on { background: var(--accent); }
.toggle-thumb {
    width: 13px; height: 13px;
    background: #fff;
    border-radius: 50%;
    position: absolute;
    top: 3px; left: 3px;
    transition: left 0.3s;
    box-shadow: 0 1px 4px rgba(0,0,0,0.3);
}
.toggle-track.on .toggle-thumb { left: 20px; }

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: '<?= $_font ?>', 'Plus Jakarta Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    display: flex;
    min-height: 100vh;
}

/* SIDEBAR */
.sidebar {
    width: var(--sidebar-w);
    background: var(--bg2);
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0; left: 0;
    height: 100vh;
    z-index: 100;
    transition: transform 0.3s;
}

.sidebar-logo {
    padding: 24px 20px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 12px;
}

.sidebar-logo .logo-icon {
    width: 38px; height: 38px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    border-radius: calc(var(--brad) * 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    overflow: hidden;
    flex-shrink: 0;
}
.sidebar-logo .logo-icon img {
    width: 100%; height: 100%;
    object-fit: cover;
}

.sidebar-logo .logo-text {
    font-size: 18px;
    font-weight: 800;
    letter-spacing: 0.5px;
    color: var(--text);
}

.sidebar-logo .logo-sub {
    font-size: 10px;
    color: var(--text-muted);
    letter-spacing: 2px;
    text-transform: uppercase;
}

.sidebar-nav {
    flex: 1;
    padding: 16px 12px;
    overflow-y: auto;
}

.nav-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--text-muted);
    padding: 10px 8px 6px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    border-radius: calc(var(--brad) * 0.5);
    transition: background .15s, color .15s;
    user-select: none;
    margin-top: 4px;
}
.nav-label:hover { background: rgba(255,255,255,0.04); color: var(--text); }
.nav-label .nl-text { display: flex; align-items: center; gap: 7px; }
.nav-label .nl-arrow {
    font-size: 9px;
    color: var(--text-muted);
    transition: transform .25s cubic-bezier(0.34,1.56,0.64,1);
    flex-shrink: 0;
}
.nav-label.open .nl-arrow { transform: rotate(180deg); }

/* Collapsible group */
.nav-group {
    overflow: hidden;
    max-height: 0;
    transition: max-height .3s cubic-bezier(0.4,0,0.2,1);
}
.nav-group.open { max-height: 400px; }

.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: calc(var(--brad) * 0.6);
    text-decoration: none;
    color: var(--text-muted);
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
    margin-bottom: 2px;
    position: relative;
}

.nav-item:hover {
    background: rgba(59,130,246,0.1);
    color: var(--text);
}

.nav-item.active {
    background: linear-gradient(135deg, rgba(59,130,246,0.2), rgba(16,185,129,0.1));
    color: var(--accent);
    font-weight: 600;
}

.nav-item.active::before {
    content: '';
    position: absolute;
    left: 0; top: 20%; bottom: 20%;
    width: 3px;
    background: var(--accent);
    border-radius: 0 3px 3px 0;
}

.nav-item i { width: 18px; text-align: center; font-size: 15px; }

.nav-badge {
    margin-left: auto;
    background: var(--accent);
    color: white;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 20px;
    min-width: 20px;
    text-align: center;
}

/* SIDEBAR FOOTER */
.sidebar-footer {
    padding: 16px;
    border-top: 1px solid var(--border);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: calc(var(--brad) * 0.6);
    background: rgba(255,255,255,0.04);
    margin-bottom: 10px;
}

.user-avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700;
    color: #fff;
    flex-shrink: 0;
}

.user-name { font-size: 13px; font-weight: 600; color: var(--text); }
.user-role { font-size: 11px; color: var(--text-muted); }

.logout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 9px;
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.2);
    border-radius: calc(var(--brad) * 0.6);
    color: var(--danger);
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.2s;
}
.logout-btn:hover { background: rgba(239,68,68,0.2); }

/* MAIN CONTENT */
.main-content {
    margin-left: var(--sidebar-w);
    flex: 1;
    min-height: 100vh;
    background: var(--bg);
}

.page-header {
    padding: 28px 32px 0;
    margin-bottom: 24px;
}
.page-title { font-size: 22px; font-weight: 800; color: var(--text); }
.page-subtitle { font-size: 13px; color: var(--text-muted); margin-top: 3px; }

.content-area { padding: 0 32px 32px; }

/* CARDS */
.card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--brad);
    padding: 20px;
}

/* TABLES */
table { width: 100%; border-collapse: collapse; }
th, td { padding: 12px 14px; text-align: left; font-size: 13px; }
th {
    background: rgba(255,255,255,0.04);
    color: var(--text-muted);
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--border);
}
td { border-bottom: 1px solid rgba(51,65,85,0.5); color: var(--text); }
tr:last-child td { border-bottom: none; }
tr:hover td { background: rgba(255,255,255,0.02); }

/* INPUTS & BUTTONS */
input, select, textarea {
    background: rgba(255,255,255,0.06);
    border: 1px solid var(--border);
    border-radius: calc(var(--brad) * 0.6);
    color: var(--text);
    padding: 10px 14px;
    font-size: 14px;
    font-family: '<?= $_font ?>', 'Plus Jakarta Sans', sans-serif;
    outline: none;
    transition: border-color 0.2s;
    width: 100%;
}
input:focus, select:focus { border-color: var(--accent); }
input::placeholder { color: var(--text-muted); }

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: calc(var(--brad) * 0.6);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    font-family: '<?= $_font ?>', 'Plus Jakarta Sans', sans-serif;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 7px;
}
.btn-primary { background: var(--accent); color: white; }
.btn-primary:hover { opacity: 0.88; }
.btn-success { background: var(--accent2); color: white; }
.btn-success:hover { opacity: 0.88; }
.btn-danger { background: var(--danger); color: white; }
.btn-danger:hover { opacity: 0.88; }
.btn-dark { background: var(--bg2); color: var(--text); border: 1px solid var(--border); }

/* STAT CARDS */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.stat-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--brad);
    padding: 20px;
    position: relative;
    overflow: hidden;
}
.stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
}
.stat-card.blue::before   { background: var(--accent); }
.stat-card.green::before  { background: var(--accent2); }
.stat-card.orange::before { background: #f59e0b; }
.stat-card.red::before    { background: var(--danger); }
.stat-card.purple::before { background: #a855f7; }

.stat-icon {
    width: 42px; height: 42px;
    border-radius: calc(var(--brad) * 0.7);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    margin-bottom: 14px;
}
.stat-card.blue .stat-icon   { background: rgba(59,130,246,0.15);  color: var(--accent); }
.stat-card.green .stat-icon  { background: rgba(16,185,129,0.15);  color: var(--accent2); }
.stat-card.orange .stat-icon { background: rgba(245,158,11,0.15);  color: #f59e0b; }
.stat-card.red .stat-icon    { background: rgba(239,68,68,0.15);   color: var(--danger); }
.stat-card.purple .stat-icon { background: rgba(168,85,247,0.15);  color: #a855f7; }

.stat-value { font-size: 24px; font-weight: 800; color: var(--text); }
.stat-label { font-size: 12px; color: var(--text-muted); margin-top: 4px; font-weight: 500; }

/* MOBILE TOGGLE */
.mob-toggle {
    display: none;
    position: fixed;
    top: 12px; left: 12px;
    z-index: 200;
    background: var(--bg2);
    border: 1px solid var(--border);
    color: var(--text);
    padding: 8px 12px;
    border-radius: calc(var(--brad) * 0.6);
    cursor: pointer;
    font-size: 16px;
}

@media(max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.open { transform: translateX(0); }
    .main-content { margin-left: 0; }
    .mob-toggle { display: block; }
    .content-area { padding: 0 16px 32px; }
    .page-header { padding: 60px 16px 0; }
}
    
.nav-dropdown{
    position: relative;
}

.branch-dropdown{
    display: none;
    background: #fff;
    margin-left: 15px;
    border-radius: 8px;
    overflow: hidden;
}

.branch-dropdown a{
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    color: #333;
    font-size: 14px;
}

.branch-dropdown a:hover{
    background: #f1f1f1;
}

.branch-dropdown.show{
    display: block;
}

</style>

<!-- MOBILE TOGGLE -->
<button class="mob-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">
    <i class="fas fa-bars"></i>
</button>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-logo">
       <div class="logo-icon" style="width:<?= intval($logo_size) ?>px;height:<?= intval($logo_size) ?>px;">
            <?php if($_logo_type === 'image' && !empty($_logo_file) && file_exists('uploads/'.$_logo_file)): ?>
                <img src="uploads/<?= htmlspecialchars($_logo_file) ?>" alt="Logo">
            <?php else: ?>
               <span style="font-size:<?= intval($logo_size * 0.45) ?>px;">
    <?= htmlspecialchars($_logo_emoji) ?>
</span>
            <?php endif; ?>
        </div>
        <div>
            <div class="logo-text"><?= htmlspecialchars($_app_name) ?></div>
            <div class="logo-sub"><?= htmlspecialchars($_app_tagline) ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">

        <!-- ── MAIN ── -->
        <div class="nav-label" id="grp-main" onclick="toggleGroup('main')">
            <span class="nl-text"><i class="fas fa-home" style="width:14px;font-size:11px;"></i> Main</span>
            <span class="nl-arrow">▼</span>
        </div>
        <div class="nav-group" id="grp-main-items">
            <a href="index.php" class="nav-item <?= $current_page=='index.php'?'active':'' ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="sales_slip.php" class="nav-item <?= $current_page=='sales_slip.php'?'active':'' ?>">
                <i class="fas fa-receipt"></i> Sales Slip
                <?php if(!empty($_SESSION['cart'])): ?>
                <span class="nav-badge"><?= array_sum($_SESSION['cart']) ?></span>
                <?php endif; ?>
            </a>
        </div>

        <!-- ── MANAGEMENT ── -->
        <div class="nav-label" id="grp-mgmt" onclick="toggleGroup('mgmt')">
            <span class="nl-text"><i class="fas fa-layer-group" style="width:14px;font-size:11px;"></i> Management</span>
            <span class="nl-arrow">▼</span>
        </div>
        <div class="nav-group" id="grp-mgmt-items">
            <a href="products.php" class="nav-item <?= $current_page=='products.php'?'active':'' ?>">
                <i class="fas fa-box"></i> Products
            </a>
            <a href="inventory.php" class="nav-item <?= $current_page=='inventory.php'?'active':'' ?>">
                <i class="fas fa-warehouse"></i> Inventory
            </a>
            <a href="sales.php" class="nav-item <?= $current_page=='sales.php'?'active':'' ?>">
                <i class="fas fa-chart-bar"></i> Sale Records
            </a>
            <a href="expenses.php" class="nav-item <?= $current_page=='expenses.php'?'active':'' ?>">
                <i class="fas fa-wallet"></i> Expenses
            </a>
        </div>

        <!-- ── ADMIN ── -->
        <div class="nav-label" id="grp-admin" onclick="toggleGroup('admin')">
            <span class="nl-text"><i class="fas fa-shield-alt" style="width:14px;font-size:11px;"></i> Admin</span>
            <span class="nl-arrow">▼</span>
        </div>
        <div class="nav-group" id="grp-admin-items">
            <a href="user_list.php" class="nav-item <?= $current_page=='user_list.php'?'active':'' ?>">
                <i class="fas fa-users"></i> Users
            </a>
            <a href="upload.php" class="nav-item <?= $current_page=='upload.php'?'active':'' ?>">
                <i class="fas fa-upload"></i> Bulk Upload
            </a>
            
            <div class="nav-dropdown">
    
    <a href="javascript:void(0)" onclick="toggleBranchDropdown()" 
       class="nav-item <?= $current_page=='branch.php'?'active':'' ?>">
       
       <i class="fa-solid fa-arrows-split-up-and-left"></i> 
       Branches▼
    </a>

    <div class="branch-dropdown" id="branchDropdown">
		<a href="branch.php" target="_blank">2nd-Branche</a>
    </div>
</div>
              <a href="user_performance.php" class="nav-item <?= $current_page=='user_performance.php'?'active':'' ?>">
                <i class="fas fa-chart-line"></i> User Performance
            </a>
             <a href="customize.php" class="nav-item <?= $current_page=='customize.php'?'active':'' ?>">
             <i class="fa-solid fa-sliders"></i> settings
            </a>
        </div>

    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'G', 0, 1)) ?></div>
            <div>
                <div class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Guest') ?></div>
                <div class="user-role"><?= ucfirst($_SESSION['role'] ?? '') ?></div>
            </div>
        </div>
        <!-- THEME TOGGLE -->
        <div class="theme-toggle" onclick="toggleTheme()" id="themeBtn">
            <span id="themeLabel">🌙 Dark Mode</span>
            <div class="toggle-track" id="themeTrack">
                <div class="toggle-thumb"></div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<script>
function toggleTheme(){
    const isLight = document.body.classList.toggle('light-mode');
    const track = document.getElementById('themeTrack');
    const label = document.getElementById('themeLabel');
    if(track) track.classList.toggle('on', isLight);
    if(label) label.textContent = isLight ? '☀️ Light Mode' : '🌙 Dark Mode';
    localStorage.setItem('stockora_theme', isLight ? 'light' : 'dark');
}
(function(){
    const saved = localStorage.getItem('stockora_theme');
    // Default is light mode unless user has explicitly chosen dark
    if(saved !== 'dark'){
        document.body.classList.add('light-mode');
        const track = document.getElementById('themeTrack');
        const label = document.getElementById('themeLabel');
        if(track) track.classList.add('on');
        if(label) label.textContent = '☀️ Light Mode';
    }
})();

// ── Collapsible nav groups ──────────────────────────────────────────────
function toggleGroup(name){
    const items = document.getElementById('grp-' + name + '-items');
    const label = document.getElementById('grp-' + name);
    if(!items || !label) return;
    const isOpen = items.classList.toggle('open');
    label.classList.toggle('open', isOpen);
    // persist state
    try { localStorage.setItem('nav_' + name, isOpen ? '1' : '0'); } catch(e){}
}

(function initNavGroups(){
    const groups = ['main','mgmt','admin'];
    // Determine which group the current active item belongs to
    const activeItem = document.querySelector('.nav-item.active');
    let activeGroup = null;
    if(activeItem){
        const grp = activeItem.closest('.nav-group');
        if(grp) activeGroup = grp.id.replace('grp-','').replace('-items','');
    }
    groups.forEach(name => {
        const items = document.getElementById('grp-' + name + '-items');
        const label = document.getElementById('grp-' + name);
        if(!items || !label) return;
        // Open if: current page is in this group OR saved as open OR default open
        let saved = null;
        try { saved = localStorage.getItem('nav_' + name); } catch(e){}
        const shouldOpen = (name === activeGroup) || (saved === '1') || (saved === null && name === 'main');
        if(shouldOpen){
            items.classList.add('open');
            label.classList.add('open');
        }
    });
})();
    

function toggleBranchDropdown() {
    document.getElementById("branchDropdown")
        .classList.toggle("show");
}

</script>
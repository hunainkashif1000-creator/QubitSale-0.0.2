<?php
session_start();
include 'db.php';
require_once 'auth.php';
require_once 'roles.php';
allow_roles(['admin']);
$conn->set_charset("utf8mb4");

// ── Save settings ──────────────────────────────────────────────────────────
if(isset($_POST['save_settings'])){
    $fields = ['app_name','app_tagline','accent_color','accent2_color',
               'bg_color','bg2_color','danger_color','sidebar_width',
               'border_radius','font_family','logo_emoji','logo_type','logo_size'];
    foreach($fields as $f){
        if(isset($_POST[$f])){
            $val = $conn->real_escape_string(trim($_POST[$f]));
            $conn->query("INSERT INTO app_settings (`key`,`value`) VALUES ('$f','$val')
                          ON DUPLICATE KEY UPDATE `value`='$val'");
        }
    }

    // Logo image upload
    if(isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] === 0){
        $allowed = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'];
        if(in_array($_FILES['logo_image']['type'], $allowed)){
            $old = $conn->query("SELECT value FROM app_settings WHERE `key`='logo_file'")->fetch_assoc();
            if($old && !empty($old['value']) && file_exists('uploads/'.$old['value'])) unlink('uploads/'.$old['value']);
            $ext = pathinfo($_FILES['logo_image']['name'], PATHINFO_EXTENSION);
            $filename = 'logo_'.time().'.'.$ext;
            if(move_uploaded_file($_FILES['logo_image']['tmp_name'], 'uploads/'.$filename)){
                $conn->query("INSERT INTO app_settings (`key`,`value`) VALUES ('logo_file','$filename')
                              ON DUPLICATE KEY UPDATE `value`='$filename'");
            }
        }
    }

    // Delete logo
    if(isset($_POST['delete_logo'])){
        $old = $conn->query("SELECT value FROM app_settings WHERE `key`='logo_file'")->fetch_assoc();
        if($old && !empty($old['value']) && file_exists('uploads/'.$old['value'])) unlink('uploads/'.$old['value']);
        $conn->query("DELETE FROM app_settings WHERE `key`='logo_file'");
    }

    // Favicon upload
    if(isset($_FILES['favicon_image']) && $_FILES['favicon_image']['error'] === 0){
        $allowed_fav = ['image/png','image/jpeg','image/gif','image/webp','image/x-icon','image/vnd.microsoft.icon'];
        if(in_array($_FILES['favicon_image']['type'], $allowed_fav)){
            $old_fav = $conn->query("SELECT value FROM app_settings WHERE `key`='favicon_file'")->fetch_assoc();
            if($old_fav && !empty($old_fav['value']) && file_exists('uploads/'.$old_fav['value'])) unlink('uploads/'.$old_fav['value']);
            $ext_fav  = pathinfo($_FILES['favicon_image']['name'], PATHINFO_EXTENSION);
            $fav_name = 'favicon_'.time().'.'.$ext_fav;
            if(move_uploaded_file($_FILES['favicon_image']['tmp_name'], 'uploads/'.$fav_name)){
                $conn->query("INSERT INTO app_settings (`key`,`value`) VALUES ('favicon_file','$fav_name')
                              ON DUPLICATE KEY UPDATE `value`='$fav_name'");
            }
        }
    }

    // Delete favicon
    if(isset($_POST['delete_favicon'])){
        $old_fav = $conn->query("SELECT value FROM app_settings WHERE `key`='favicon_file'")->fetch_assoc();
        if($old_fav && !empty($old_fav['value']) && file_exists('uploads/'.$old_fav['value'])) unlink('uploads/'.$old_fav['value']);
        $conn->query("DELETE FROM app_settings WHERE `key`='favicon_file'");
    }

    header("Location: customize.php?saved=1"); exit;
}

// ── Reset to defaults ──────────────────────────────────────────────────────
if(isset($_POST['reset_defaults'])){
    $conn->query("DELETE FROM app_settings");
    header("Location: customize.php?reset=1"); exit;
}

// ── Load current settings ──────────────────────────────────────────────────
$settings = [];
$res = $conn->query("SELECT `key`,`value` FROM app_settings");
if($res) while($r = $res->fetch_assoc()) $settings[$r['key']] = $r['value'];

function cfg($key, $default, $settings){ 
    return $settings[$key] ?? $default; 
}if (!function_exists('cfg')) {
    function cfg($key, $default, $settings){
        return $settings[$key] ?? $default;
    }
}

$app_name     = cfg('app_name',      'Stockora',          $settings);
$app_tagline  = cfg('app_tagline',   'POS System',        $settings);
$accent       = cfg('accent_color',  '#3b82f6',           $settings);
$accent2      = cfg('accent2_color', '#10b981',           $settings);
$bg           = cfg('bg_color',      '#0f172a',           $settings);
$bg2          = cfg('bg2_color',     '#1e293b',           $settings);
$danger       = cfg('danger_color',  '#ef4444',           $settings);
$sidebar_w    = cfg('sidebar_width', '230',               $settings);
$brad         = cfg('border_radius', '12',                $settings);
$font         = cfg('font_family',   'Plus Jakarta Sans',  $settings);
$logo_emoji   = cfg('logo_emoji',    '🏪',                $settings);
$logo_type    = cfg('logo_type',     'emoji',             $settings);
$logo_file    = cfg('logo_file',     '',                  $settings);
$logo_size    = cfg('logo_size',     '36',                $settings);
$favicon_file = cfg('favicon_file',  '',                  $settings);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customize - Stockora</title>
<?php if(!empty($favicon_file) && file_exists('uploads/'.$favicon_file)): ?>
<link rel="icon" href="uploads/<?= htmlspecialchars($favicon_file) ?>?v=<?= time() ?>">
<?php endif; ?>
<?php include 'sidebar.php'; ?>
<style>
/* ── Layout ── */
.cust-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 24px;
    align-items: start;
}
@media(max-width:900px){ .cust-layout { grid-template-columns: 1fr; } }

/* ── Section card ── */
.cust-section {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 22px;
    margin-bottom: 20px;
}
.cust-section:last-child { margin-bottom: 0; }
.section-title {
    font-size: 12px;
    font-weight: 700;
    color: var(--text-muted);
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.section-title i { color: var(--accent); }

/* ── Form helpers ── */
.field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 14px;
}
.field-row.single { grid-template-columns: 1fr; }
.field-row.triple { grid-template-columns: 1fr 1fr 1fr; }
@media(max-width:600px){ .field-row,.field-row.triple { grid-template-columns: 1fr; } }

.field label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: var(--text-muted);
    letter-spacing: 0.4px;
    margin-bottom: 6px;
}

/* ── Color picker ── */
.color-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.05);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 6px 10px;
    transition: border-color .2s;
}
.color-wrap:focus-within { border-color: var(--accent); }
.color-wrap input[type="color"] {
    width: 32px; height: 32px;
    border: none; background: none;
    padding: 0; cursor: pointer;
    border-radius: 6px;
    flex-shrink: 0;
}
.color-wrap input[type="text"] {
    background: none; border: none;
    font-size: 13px; font-family: monospace;
    color: var(--text); padding: 0;
    width: 100%;
}
.color-wrap input[type="text"]:focus { outline: none; }

/* ── Emoji picker ── */
.emoji-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}
.emoji-btn {
    width: 40px; height: 40px;
    background: rgba(255,255,255,0.05);
    border: 2px solid transparent;
    border-radius: 8px;
    font-size: 20px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all .15s;
}
.emoji-btn:hover { border-color: var(--accent); background: rgba(59,130,246,0.1); }
.emoji-btn.selected { border-color: var(--accent); background: rgba(59,130,246,0.15); }

/* ── Font preview ── */
.font-option {
    padding: 10px 14px;
    background: rgba(255,255,255,0.04);
    border: 2px solid transparent;
    border-radius: 8px;
    cursor: pointer;
    transition: all .15s;
    text-align: center;
    font-size: 14px;
}
.font-option:hover { border-color: var(--accent); }
.font-option.selected { border-color: var(--accent); background: rgba(59,130,246,0.1); color: var(--accent); }

/* ── Preset themes ── */
.preset-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 10px;
}
.preset-card {
    border-radius: 10px;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    transition: border-color .2s, transform .15s;
}
.preset-card:hover { border-color: var(--accent); transform: translateY(-2px); }
.preset-thumb { height: 50px; display: flex; }
.preset-sidebar { width: 30%; }
.preset-main { flex: 1; }
.preset-label {
    background: rgba(255,255,255,0.06);
    font-size: 10px;
    font-weight: 700;
    text-align: center;
    padding: 5px 4px;
    color: var(--text-muted);
    letter-spacing: 0.3px;
}

/* ── Preview panel ── */
.preview-panel {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    position: sticky;
    top: 20px;
}
.preview-sidebar {
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 12px;
}
.preview-logo-area {
    display: flex; align-items: center; gap: 10px; margin-bottom: 14px;
    padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.1);
}
.preview-logo-icon {
    width: <?= intval($logo_size) ?>px;
    height: <?= intval($logo_size) ?>px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: <?= intval($logo_size * 0.45) ?>px;
    overflow: hidden;
    flex-shrink: 0;
}
.preview-logo-img { width: 100%; height: 100%; object-fit: cover; }
.preview-logo-text { font-size: 14px; font-weight: 800; }
.preview-logo-sub  { font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; opacity: .5; }
.preview-nav-item {
    display: flex; align-items: center; gap: 8px;
    padding: 7px 10px; border-radius: 6px;
    font-size: 12px; margin-bottom: 3px; opacity: .6;
}
.preview-nav-item.active { opacity: 1; font-weight: 700; }
.preview-stat {
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 8px;
    border-left: 3px solid;
}
.preview-stat .val { font-size: 18px; font-weight: 800; }
.preview-stat .lbl { font-size: 10px; opacity: .6; margin-top: 2px; }

/* ── Logo upload area ── */
.logo-upload-label {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 14px;
    background: rgba(255,255,255,0.04);
    border: 1px dashed var(--border);
    border-radius: 8px;
    cursor: pointer; font-size: 13px; color: var(--text-muted);
    transition: border-color .2s, color .2s;
    margin-bottom: 10px;
}
.logo-upload-label:hover { border-color: var(--accent); color: var(--text); }
.logo-upload-label input { display: none; }
.current-logo-wrap {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 14px;
    background: rgba(16,185,129,0.08);
    border: 1px solid rgba(16,185,129,0.2);
    border-radius: 8px;
    margin-bottom: 10px;
}
.current-logo-wrap img { width: 40px; height: 40px; object-fit: cover; border-radius: 6px; }

/* ── Range slider ── */
input[type="range"] {
    -webkit-appearance: none;
    width: 100%; height: 6px;
    background: var(--border);
    border-radius: 3px; outline: none; padding: 0; border: none;
}
input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 18px; height: 18px;
    border-radius: 50%;
    background: var(--accent);
    cursor: pointer;
}

/* ── Saved toast ── */
.toast {
    position: fixed; bottom: 24px; right: 24px;
    background: #10b981; color: #fff;
    padding: 12px 20px; border-radius: 10px;
    font-size: 14px; font-weight: 600;
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    z-index: 9999;
    animation: slideUp .3s ease;
}
@keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
</style>
</head>
<body>
<div class="main-content">
  <div class="page-header">
    <div class="page-title">Customize</div>
    <div class="page-subtitle">Personalize your POS system appearance</div>
  </div>
  <div class="content-area">

    <?php if(isset($_GET['saved'])): ?>
    <div class="toast" id="toast">✅ Settings saved!</div>
    <script>setTimeout(()=>{ const t=document.getElementById('toast'); if(t)t.style.display='none'; }, 3000);</script>
    <?php elseif(isset($_GET['reset'])): ?>
    <div class="toast" id="toast" style="background:#3b82f6;">🔄 Reset to defaults!</div>
    <script>setTimeout(()=>{ const t=document.getElementById('toast'); if(t)t.style.display='none'; }, 3000);</script>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
    <div class="cust-layout">

      <!-- ═══ LEFT COLUMN ═══ -->
      <div>

        <!-- PRESET THEMES -->
        <div class="cust-section">
          <div class="section-title"><i class="fas fa-palette"></i> Quick Presets</div>
          <div class="preset-grid">
            <?php
            $presets = [
              'Ocean'   => ['#3b82f6','#10b981','#0f172a','#1e293b'],
              'Purple'  => ['#8b5cf6','#ec4899','#13111c','#1e1b2e'],
              'Sunset'  => ['#f97316','#f59e0b','#1a0f0a','#2d1810'],
              'Rose'    => ['#f43f5e','#fb7185','#1a0a10','#2d1018'],
              'Teal'    => ['#14b8a6','#06b6d4','#0a1a1a','#0f2626'],
              'Indigo'  => ['#6366f1','#a78bfa','#0d0d1a','#14142b'],
              'Green'   => ['#22c55e','#84cc16','#0a150d','#111f14'],
              'Amber'   => ['#f59e0b','#fbbf24','#1a1200','#2a1e00'],
            ];
            foreach($presets as $name => $colors):
            ?>
            <div class="preset-card" onclick="applyPreset('<?= $colors[0] ?>','<?= $colors[1] ?>','<?= $colors[2] ?>','<?= $colors[3] ?>')">
              <div class="preset-thumb">
                <div class="preset-sidebar" style="background:<?= $colors[3] ?>;"></div>
                <div class="preset-main" style="background:<?= $colors[2] ?>;display:flex;flex-direction:column;justify-content:flex-end;padding:4px;">
                  <div style="height:4px;border-radius:2px;background:<?= $colors[0] ?>;margin-bottom:3px;"></div>
                  <div style="height:4px;border-radius:2px;background:<?= $colors[1] ?>;width:60%;"></div>
                </div>
              </div>
              <div class="preset-label"><?= $name ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- BRANDING -->
        <div class="cust-section">
          <div class="section-title"><i class="fas fa-store"></i> Branding</div>
          <div class="field-row">
            <div class="field">
              <label>App Name</label>
              <input type="text" name="app_name" value="<?= htmlspecialchars($app_name) ?>" placeholder="Stockora" oninput="updatePreview()">
            </div>
            <div class="field">
              <label>Tagline</label>
              <input type="text" name="app_tagline" value="<?= htmlspecialchars($app_tagline) ?>" placeholder="POS System" oninput="updatePreview()">
            </div>
          </div>

          <!-- LOGO TYPE TABS -->
          <label style="font-size:11px;font-weight:700;color:var(--text-muted);letter-spacing:.4px;display:block;margin-bottom:10px;">LOGO TYPE</label>
          <div style="display:flex;gap:8px;margin-bottom:14px;">
            <button type="button" class="btn btn-dark" id="tab-emoji" onclick="switchLogoTab('emoji')" style="font-size:13px;padding:8px 16px;">😊 Emoji</button>
            <button type="button" class="btn btn-dark" id="tab-image" onclick="switchLogoTab('image')" style="font-size:13px;padding:8px 16px;">🖼️ Image</button>
          </div>
          <input type="hidden" name="logo_type" id="logo_type_input" value="<?= $logo_type ?>">

          <!-- EMOJI TAB -->
          <div id="tab-emoji-content">
            <label style="font-size:11px;font-weight:700;color:var(--text-muted);letter-spacing:.4px;display:block;margin-bottom:8px;">CHOOSE EMOJI</label>
            <input type="hidden" name="logo_emoji" id="logo_emoji_val" value="<?= htmlspecialchars($logo_emoji) ?>">
            <div class="emoji-grid" id="emojiGrid">
              <?php
              $emojis = ['🏪','🛒','🏬','🛍️','💼','🏢','🏗️','🍕','🍔','☕','🧁','🍜','💊','📦','🎮','👗','👟','📱','💻','🔧','🚗','🏠','🌿','💈','🧴'];
              foreach($emojis as $e):
              ?>
              <button type="button" class="emoji-btn <?= $logo_emoji===$e&&$logo_type==='emoji'?'selected':'' ?>"
                onclick="selectEmoji('<?= $e ?>')"><?= $e ?></button>
              <?php endforeach; ?>
            </div>
            <div style="margin-top:12px;display:flex;gap:8px;align-items:center;">
              <label style="font-size:11px;font-weight:700;color:var(--text-muted);white-space:nowrap;">CUSTOM EMOJI</label>
              <input type="text" id="custom_emoji_input" placeholder="Paste any emoji..." maxlength="4" style="max-width:160px;" oninput="selectEmoji(this.value)">
            </div>
          </div>

          <!-- IMAGE TAB -->
          <div id="tab-image-content" style="display:none;">
            <?php if(!empty($logo_file) && file_exists('uploads/'.$logo_file)): ?>
            <div class="current-logo-wrap">
              <img src="uploads/<?= htmlspecialchars($logo_file) ?>" alt="Logo">
              <div>
                <div style="font-size:13px;font-weight:600;">Current Logo</div>
                <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($logo_file) ?></div>
              </div>
              <button type="submit" name="delete_logo" value="1"
                style="margin-left:auto;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#ef4444;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;"
                onclick="return confirm('Delete logo?')">🗑 Remove</button>
            </div>
            <?php endif; ?>
            <label class="logo-upload-label">
              <i class="fas fa-upload" style="color:var(--accent);"></i>
              <span id="logoFileLabel">Upload Logo Image (PNG, JPG, SVG)</span>
              <input type="file" name="logo_image" accept="image/*" onchange="previewLogo(this)">
            </label>
            <img id="logoPreviewImg" style="display:none;width:60px;height:60px;object-fit:cover;border-radius:10px;border:1px solid var(--border);" alt="">
          </div>
        </div>

        <!-- COLORS -->
        <div class="cust-section">
          <div class="section-title"><i class="fas fa-fill-drip"></i> Colors</div>
          <div class="field-row triple">
            <div class="field">
              <label>Primary Accent</label>
              <div class="color-wrap">
                <input type="color" id="cp_accent" value="<?= $accent ?>" oninput="syncColor('accent_color',this.value)">
                <input type="text" id="ct_accent" name="accent_color" value="<?= $accent ?>" maxlength="7" oninput="syncColorFromText('cp_accent',this)">
              </div>
            </div>
            <div class="field">
              <label>Secondary / Success</label>
              <div class="color-wrap">
                <input type="color" id="cp_accent2" value="<?= $accent2 ?>" oninput="syncColor('accent2_color',this.value)">
                <input type="text" id="ct_accent2" name="accent2_color" value="<?= $accent2 ?>" maxlength="7" oninput="syncColorFromText('cp_accent2',this)">
              </div>
            </div>
            <div class="field">
              <label>Danger / Delete</label>
              <div class="color-wrap">
                <input type="color" id="cp_danger" value="<?= $danger ?>" oninput="syncColor('danger_color',this.value)">
                <input type="text" id="ct_danger" name="danger_color" value="<?= $danger ?>" maxlength="7" oninput="syncColorFromText('cp_danger',this)">
              </div>
            </div>
          </div>
          <div class="field-row">
            <div class="field">
              <label>Background (Main)</label>
              <div class="color-wrap">
                <input type="color" id="cp_bg" value="<?= $bg ?>" oninput="syncColor('bg_color',this.value)">
                <input type="text" id="ct_bg" name="bg_color" value="<?= $bg ?>" maxlength="7" oninput="syncColorFromText('cp_bg',this)">
              </div>
            </div>
            <div class="field">
              <label>Background (Sidebar / Cards)</label>
              <div class="color-wrap">
                <input type="color" id="cp_bg2" value="<?= $bg2 ?>" oninput="syncColor('bg2_color',this.value)">
                <input type="text" id="ct_bg2" name="bg2_color" value="<?= $bg2 ?>" maxlength="7" oninput="syncColorFromText('cp_bg2',this)">
              </div>
            </div>
          </div>
        </div>

        <!-- LAYOUT & FONT -->
        <div class="cust-section">
          <div class="section-title"><i class="fas fa-ruler-combined"></i> Layout & Font</div>
          <div class="field-row">
            <div class="field">
              <label>Sidebar Width: <span id="sw_val"><?= $sidebar_w ?></span>px</label>
              <input type="range" name="sidebar_width" id="sidebar_width" min="180" max="300" step="5" value="<?= $sidebar_w ?>"
                oninput="document.getElementById('sw_val').textContent=this.value;updatePreview()">
            </div>
            <div class="field">
              <label>Border Radius: <span id="br_val"><?= $brad ?></span>px</label>
              <input type="range" name="border_radius" id="border_radius" min="0" max="24" step="2" value="<?= $brad ?>"
                oninput="document.getElementById('br_val').textContent=this.value;updatePreview()">
            </div>
          </div>
          <div class="field-row single">
            <div class="field">
              <label>Logo Icon Size: <span id="lis_val"><?= intval($logo_size) ?></span>px</label>
              <input type="range" name="logo_size" id="logo_size" min="24" max="72" step="2" value="<?= intval($logo_size) ?>"
                oninput="document.getElementById('lis_val').textContent=this.value;updatePreview()">
            </div>
          </div>
          <div class="field">
            <label style="margin-bottom:10px;display:block;">Font Family</label>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:8px;">
              <?php
              $fonts = [
                'Plus Jakarta Sans' => 'Plus Jakarta Sans',
                'Inter'             => 'Inter',
                'Poppins'           => 'Poppins',
                'Nunito'            => 'Nunito',
                'DM Sans'           => 'DM Sans',
                'Outfit'            => 'Outfit',
              ];
              foreach($fonts as $fval => $fname):
              ?>
              <div class="font-option <?= $font===$fval?'selected':'' ?>"
                style="font-family:'<?= $fname ?>',sans-serif;"
                onclick="selectFont('<?= $fval ?>', this)">
                <?= $fname ?>
              </div>
              <?php endforeach; ?>
            </div>
            <input type="hidden" name="font_family" id="font_family_val" value="<?= htmlspecialchars($font) ?>">
          </div>
        </div>

        <!-- FAVICON -->
        <div class="cust-section">
          <div class="section-title"><i class="fas fa-globe"></i> Favicon (Browser Tab Icon)</div>
          <p style="font-size:12px;color:var(--text-muted);margin:0 0 14px;line-height:1.7;">
            Browser tab mein dikhne wala icon. Recommended: 32×32 ya 64×64 PNG/ICO.
          </p>
          <?php if(!empty($favicon_file) && file_exists('uploads/'.$favicon_file)): ?>
          <div class="current-logo-wrap">
            <img src="uploads/<?= htmlspecialchars($favicon_file) ?>" alt="Favicon" style="width:32px;height:32px;border-radius:4px;">
            <div>
              <div style="font-size:13px;font-weight:600;">Current Favicon</div>
              <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($favicon_file) ?></div>
            </div>
            <button type="submit" name="delete_favicon" value="1"
              style="margin-left:auto;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#ef4444;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;"
              onclick="return confirm('Favicon delete karna chahte hain?')">🗑 Remove</button>
          </div>
          <?php endif; ?>
          <label class="logo-upload-label">
            <i class="fas fa-upload" style="color:var(--accent);"></i>
            <span id="faviconFileLabel">Upload Favicon (PNG, ICO, JPG)</span>
            <input type="file" name="favicon_image" accept="image/*,.ico" onchange="previewFavicon(this)">
          </label>
          <div id="faviconPreviewWrap" style="display:none;align-items:center;gap:10px;margin-top:6px;">
            <img id="faviconPreviewImg" style="width:32px;height:32px;object-fit:cover;border-radius:4px;border:1px solid var(--border);" alt="">
            <span style="font-size:12px;color:var(--text-muted);">Preview</span>
          </div>
        </div>

        <!-- ACTIONS -->
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
          <button type="submit" name="save_settings" class="btn btn-success" style="flex:1;justify-content:center;min-width:160px;">
            <i class="fas fa-save"></i> Save Changes
          </button>
          <button type="submit" name="reset_defaults" class="btn btn-danger" onclick="return confirm('Reset all customizations to default?')" style="justify-content:center;">
            <i class="fas fa-undo"></i> Reset Defaults
          </button>
        </div>

      </div><!-- end left -->

      <!-- ═══ RIGHT: LIVE PREVIEW ═══ -->
      <div>
        <div class="preview-panel">
          <div style="font-size:12px;font-weight:700;color:var(--text-muted);letter-spacing:1px;margin-bottom:14px;">LIVE PREVIEW</div>

          <!-- Sidebar Preview -->
          <div class="preview-sidebar" id="prev_sidebar" style="background:<?= $bg2 ?>;">
            <div class="preview-logo-area">
              <div class="preview-logo-icon" id="prev_logo_icon" style="background:linear-gradient(135deg,<?= $accent ?>,<?= $accent2 ?>);">
                <?php if($logo_type==='image' && !empty($logo_file) && file_exists('uploads/'.$logo_file)): ?>
                  <img src="uploads/<?= htmlspecialchars($logo_file) ?>" class="preview-logo-img" alt="">
                <?php else: ?>
                  <span id="prev_emoji"><?= htmlspecialchars($logo_emoji) ?></span>
                <?php endif; ?>
              </div>
              <div>
                <div class="preview-logo-text" id="prev_appname" style="color:#f1f5f9;"><?= htmlspecialchars($app_name) ?></div>
                <div class="preview-logo-sub" id="prev_tagline"><?= htmlspecialchars($app_tagline) ?></div>
              </div>
            </div>
            <div class="preview-nav-item active" id="prev_nav_active"
              style="background:linear-gradient(135deg,rgba(59,130,246,0.2),rgba(16,185,129,0.1));color:<?= $accent ?>;">
              <i class="fas fa-home" style="width:16px;"></i>
              <span style="font-size:12px;font-family:'<?= $font ?>',sans-serif;">Dashboard</span>
            </div>
            <div class="preview-nav-item" style="color:#94a3b8;">
              <i class="fas fa-receipt" style="width:16px;"></i>
              <span style="font-size:12px;font-family:'<?= $font ?>',sans-serif;">Sales Slip</span>
            </div>
            <div class="preview-nav-item" style="color:#94a3b8;">
              <i class="fas fa-box" style="width:16px;"></i>
              <span style="font-size:12px;font-family:'<?= $font ?>',sans-serif;">Products</span>
            </div>
          </div>

          <!-- Stats Preview -->
          <div id="prev_stats">
            <div class="preview-stat" style="background:<?= $bg2 ?>;border-color:<?= $accent ?>;">
              <div class="val" style="color:#f1f5f9;font-family:'<?= $font ?>',sans-serif;">Rs 84,200</div>
              <div class="lbl" style="color:#94a3b8;">Total Sales</div>
            </div>
            <div class="preview-stat" style="background:<?= $bg2 ?>;border-color:<?= $accent2 ?>;">
              <div class="val" style="color:#f1f5f9;font-family:'<?= $font ?>',sans-serif;">142</div>
              <div class="lbl" style="color:#94a3b8;">Products</div>
            </div>
          </div>

          <!-- Button Preview -->
          <div style="display:flex;gap:8px;margin-top:12px;">
            <div id="prev_btn_primary" style="flex:1;text-align:center;padding:9px;border-radius:8px;font-size:13px;font-weight:700;color:#fff;font-family:'<?= $font ?>',sans-serif;background:<?= $accent ?>;">Primary</div>
            <div id="prev_btn_success" style="flex:1;text-align:center;padding:9px;border-radius:8px;font-size:13px;font-weight:700;color:#fff;font-family:'<?= $font ?>',sans-serif;background:<?= $accent2 ?>;">Success</div>
            <div id="prev_btn_danger"  style="flex:1;text-align:center;padding:9px;border-radius:8px;font-size:13px;font-weight:700;color:#fff;font-family:'<?= $font ?>',sans-serif;background:<?= $danger ?>;">Danger</div>
          </div>

          <div style="margin-top:16px;padding:12px;background:rgba(255,255,255,0.03);border-radius:8px;font-size:11px;color:var(--text-muted);text-align:center;line-height:1.6;">
            Changes apply site-wide after saving.<br>All pages use <code style="color:var(--accent);">sidebar.php</code> which loads these settings.
          </div>
        </div>
      </div><!-- end right -->

    </div><!-- end cust-layout -->
    </form>
  </div>
</div>

<!-- Load selected Google Font dynamically -->
<link id="fontLink" rel="stylesheet" href="https://fonts.googleapis.com/css2?family=<?= urlencode($font) ?>:wght@400;500;600;700;800&display=swap">

<script>
// ── Init logo tab ──────────────────────────────────────────────────────────
switchLogoTab('<?= $logo_type ?>');

// ── Preset apply ──────────────────────────────────────────────────────────
function applyPreset(a, a2, bg, bg2){
    setColor('accent_color', a);
    setColor('accent2_color', a2);
    setColor('bg_color', bg);
    setColor('bg2_color', bg2);
    updatePreview();
}
function setColor(name, val){
    const txt = document.querySelector(`input[name="${name}"]`);
    const col = document.getElementById('cp_' + name.replace('_color',''));
    if(txt){ txt.value = val; }
    if(col){ col.value = val; }
}

// ── Color sync ────────────────────────────────────────────────────────────
function syncColor(name, val){
    const txt = document.querySelector(`input[name="${name}"]`);
    if(txt) txt.value = val;
    updatePreview();
}
function syncColorFromText(cpId, txtEl){
    const val = txtEl.value;
    if(/^#[0-9a-fA-F]{6}$/.test(val)){
        document.getElementById(cpId).value = val;
        updatePreview();
    }
}

// ── Live preview update ────────────────────────────────────────────────────
function updatePreview(){
    const a    = document.querySelector('input[name="accent_color"]').value;
    const a2   = document.querySelector('input[name="accent2_color"]').value;
    const bg2  = document.querySelector('input[name="bg2_color"]').value;
    const d    = document.querySelector('input[name="danger_color"]').value;
    const name = document.querySelector('input[name="app_name"]').value || 'Stockora';
    const tag  = document.querySelector('input[name="app_tagline"]').value || 'POS System';
    const brad = document.getElementById('border_radius').value;
    const font = document.getElementById('font_family_val').value;
    const lis  = parseInt(document.getElementById('logo_size').value);

    // Logo icon size
    const logoIcon = document.getElementById('prev_logo_icon');
    logoIcon.style.width    = lis + 'px';
    logoIcon.style.height   = lis + 'px';
    logoIcon.style.fontSize = Math.round(lis * 0.45) + 'px';
    logoIcon.style.background = `linear-gradient(135deg,${a},${a2})`;

    // Sidebar bg
    document.getElementById('prev_sidebar').style.background = bg2;

    // Active nav
    const nav = document.getElementById('prev_nav_active');
    nav.style.background = `linear-gradient(135deg,rgba(59,130,246,0.2),rgba(16,185,129,0.1))`;
    nav.style.color = a;

    // App name / tagline
    document.getElementById('prev_appname').textContent = name;
    document.getElementById('prev_tagline').textContent = tag;

    // Stats
    document.querySelectorAll('#prev_stats .preview-stat').forEach((el,i) => {
        el.style.background   = bg2;
        el.style.borderColor  = i===0 ? a : a2;
        el.style.borderRadius = brad + 'px';
        el.querySelector('.val').style.fontFamily = `'${font}',sans-serif`;
    });

    // Buttons
    document.getElementById('prev_btn_primary').style.background = a;
    document.getElementById('prev_btn_success').style.background = a2;
    document.getElementById('prev_btn_danger').style.background  = d;
    ['prev_btn_primary','prev_btn_success','prev_btn_danger'].forEach(id => {
        document.getElementById(id).style.borderRadius = brad + 'px';
        document.getElementById(id).style.fontFamily   = `'${font}',sans-serif`;
    });

    // Nav fonts
    document.querySelectorAll('.preview-nav-item span').forEach(el => {
        el.style.fontFamily = `'${font}',sans-serif`;
    });

    // Load font
    document.getElementById('fontLink').href =
        `https://fonts.googleapis.com/css2?family=${encodeURIComponent(font)}:wght@400;500;600;700;800&display=swap`;
}

// ── Emoji select ──────────────────────────────────────────────────────────
function selectEmoji(e){
    document.getElementById('logo_emoji_val').value = e;
    const span = document.getElementById('prev_emoji');
    if(span) span.textContent = e;
    document.querySelectorAll('.emoji-btn').forEach(b => {
        b.classList.toggle('selected', b.textContent.trim()===e);
    });
}

// ── Font select ───────────────────────────────────────────────────────────
function selectFont(val, el){
    document.getElementById('font_family_val').value = val;
    document.querySelectorAll('.font-option').forEach(f => f.classList.remove('selected'));
    el.classList.add('selected');
    updatePreview();
}

// ── Logo tab switch ───────────────────────────────────────────────────────
function switchLogoTab(tab){
    document.getElementById('logo_type_input').value = tab;
    document.getElementById('tab-emoji-content').style.display = tab==='emoji' ? '' : 'none';
    document.getElementById('tab-image-content').style.display = tab==='image' ? '' : 'none';
    document.getElementById('tab-emoji').style.background = tab==='emoji' ? 'var(--accent)' : '';
    document.getElementById('tab-emoji').style.color      = tab==='emoji' ? '#fff' : '';
    document.getElementById('tab-image').style.background = tab==='image' ? 'var(--accent)' : '';
    document.getElementById('tab-image').style.color      = tab==='image' ? '#fff' : '';
}

// ── Logo file preview ─────────────────────────────────────────────────────
function previewLogo(input){
    if(input.files && input.files[0]){
        const r = new FileReader();
        r.onload = e => {
            const img = document.getElementById('logoPreviewImg');
            img.src = e.target.result; img.style.display = 'block';
            document.getElementById('logoFileLabel').textContent = input.files[0].name;
            document.getElementById('prev_logo_icon').innerHTML =
                `<img src="${e.target.result}" class="preview-logo-img" alt="">`;
        };
        r.readAsDataURL(input.files[0]);
    }
}

// ── Favicon file preview ──────────────────────────────────────────────────
function previewFavicon(input){
    if(input.files && input.files[0]){
        const r = new FileReader();
        r.onload = e => {
            const img = document.getElementById('faviconPreviewImg');
            img.src = e.target.result;
            document.getElementById('faviconPreviewWrap').style.display = 'flex';
            document.getElementById('faviconFileLabel').textContent = input.files[0].name;
        };
        r.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>
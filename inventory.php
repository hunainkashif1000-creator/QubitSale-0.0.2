<?php
session_start();
include 'db.php';
$conn->set_charset("utf8mb4");
$search = '';
if (isset($_GET['search'])) $search = $conn->real_escape_string($_GET['search']);

// Load all products for the change-barcode modal dropdown
$all_products = [];
$res = $conn->query("SELECT id, name, barcode, price FROM products ORDER BY name ASC");
while ($row = $res->fetch_assoc()) $all_products[] = $row;
?>
<!DOCTYPE html>
<html>
<head>
<title>Inventory - Stockora</title>
<meta charset="UTF-8">
<?php include 'sidebar.php'; ?>
<style>
/* ── Existing styles ──────────────────────────────── */
.badge{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.badge-out{background:rgba(239,68,68,0.15);color:#ef4444;}
.badge-low{background:rgba(245,158,11,0.15);color:#f59e0b;}
.badge-ok{background:rgba(16,185,129,0.15);color:#10b981;}
.search-row{display:flex;gap:12px;align-items:center;margin-bottom:20px;flex-wrap:wrap;}
.search-row input{max-width:280px;}

/* ══════════════════════════════════════════════════
   CHANGE BARCODE POPUP
══════════════════════════════════════════════════ */
#pairOverlay {
  display: none;
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(0,0,0,0.6);
  backdrop-filter: blur(3px);
  align-items: center;
  justify-content: center;
  padding: 16px;
}
#pairOverlay.open {
  display: flex;
  animation: overlayIn .18s ease;
}
@keyframes overlayIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}
#pairCard {
  background: var(--card-bg, #1e1e2e);
  border: 1px solid var(--border, rgba(255,255,255,.1));
  border-radius: 16px;
  width: 100%;
  max-width: 430px;
  box-shadow: 0 24px 64px rgba(0,0,0,.5);
  overflow: hidden;
  animation: cardIn .2s cubic-bezier(.34,1.3,.64,1);
}
@keyframes cardIn {
  from { transform: scale(.93) translateY(10px); opacity: 0; }
  to   { transform: scale(1)   translateY(0);    opacity: 1; }
}
.pm-hdr {
  display: flex;
  align-items: flex-start;
  gap: 13px;
  padding: 18px 20px 16px;
  border-bottom: 1px solid var(--border, rgba(255,255,255,.08));
}
.pm-hdr-icon {
  width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
  background: rgba(245,158,11,.13);
  display: flex; align-items: center; justify-content: center;
}
.pm-hdr-icon i { color: #f59e0b; font-size: 18px; }
.pm-hdr-title {
  font-size: 14px; font-weight: 700;
  color: var(--text-primary, #f0f0f0);
  margin: 0 0 4px; line-height: 1.4;
}
.pm-hdr-sub {
  font-size: 12px; color: var(--text-muted, #888); margin: 0;
}
.bc-pill {
  display: inline-block;
  background: rgba(99,102,241,.18); color: #a5b4fc;
  font-family: monospace; font-size: 12px; font-weight: 700;
  padding: 1px 8px; border-radius: 5px; letter-spacing: .4px;
}
.pm-close {
  margin-left: auto; flex-shrink: 0;
  width: 28px; height: 28px; border-radius: 8px; border: none;
  background: rgba(255,255,255,.06); color: var(--text-muted, #999);
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  font-size: 13px; transition: background .15s;
}
.pm-close:hover { background: rgba(255,255,255,.12); }
.pm-body { padding: 16px 20px 12px; }
.pm-lbl {
  font-size: 11px; font-weight: 700; text-transform: uppercase;
  letter-spacing: .7px; color: var(--text-muted, #888);
  margin-bottom: 7px;
}
.pm-search-wrap { position: relative; margin-bottom: 13px; }
.pm-search-wrap input {
  width: 100%; box-sizing: border-box;
  background: var(--input-bg, #252537);
  border: 1px solid var(--border, rgba(255,255,255,.1));
  border-radius: 9px;
  padding: 9px 36px 9px 13px;
  color: var(--text-primary, #f0f0f0);
  font-size: 13px; outline: none;
  transition: border-color .15s;
}
.pm-search-wrap input::placeholder { color: var(--text-muted, #555); }
.pm-search-wrap input:focus { border-color: #6366f1; }
.pm-search-wrap .pm-sico {
  position: absolute; right: 11px; top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted, #666); font-size: 13px; pointer-events: none;
}
.pm-list {
  border: 1px solid var(--border, rgba(255,255,255,.08));
  border-radius: 10px;
  max-height: 240px; overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: rgba(255,255,255,.1) transparent;
}
.pm-list::-webkit-scrollbar { width: 4px; }
.pm-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 4px; }
.pm-item {
  display: flex; align-items: center; gap: 11px;
  padding: 10px 13px; cursor: pointer;
  border-bottom: 1px solid var(--border, rgba(255,255,255,.05));
  transition: background .1s;
}
.pm-item:last-child { border-bottom: none; }
.pm-item:hover { background: rgba(255,255,255,.04); }
.pm-item.sel { background: rgba(99,102,241,.12); }
.pm-radio {
  width: 16px; height: 16px; border-radius: 50%; flex-shrink: 0;
  border: 2px solid rgba(255,255,255,.2);
  display: flex; align-items: center; justify-content: center;
  transition: border-color .12s, background .12s;
}
.pm-item.sel .pm-radio { border-color: #6366f1; background: #6366f1; }
.pm-item.sel .pm-radio::after {
  content: ''; width: 5px; height: 5px; border-radius: 50%; background: #fff;
}
.pm-pname { font-size: 13px; font-weight: 600; color: var(--text-primary, #f0f0f0); }
.pm-pmeta { font-size: 11px; color: var(--text-muted, #888); margin-top: 2px; }
.pm-nores {
  padding: 20px; text-align: center;
  font-size: 13px; color: var(--text-muted, #666);
  display: none;
}
.pm-footer {
  display: flex; gap: 10px;
  padding: 13px 20px 18px;
  border-top: 1px solid var(--border, rgba(255,255,255,.07));
}
.pm-btn-cancel {
  flex: 1; padding: 10px 0; border-radius: 9px;
  border: 1px solid var(--border, rgba(255,255,255,.1));
  background: transparent; color: var(--text-muted, #aaa);
  font-size: 13px; font-weight: 600; cursor: pointer;
  transition: background .13s;
}
.pm-btn-cancel:hover { background: rgba(255,255,255,.05); }
.pm-btn-pair {
  flex: 1; padding: 10px 0; border-radius: 9px; border: none;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: #fff; font-size: 13px; font-weight: 700; cursor: pointer;
  transition: opacity .15s, transform .1s;
}
.pm-btn-pair:hover:not(:disabled) { opacity: .88; transform: translateY(-1px); }
.pm-btn-pair:disabled { opacity: .3; cursor: not-allowed; transform: none; }

/* Toast */
#pmToast {
  position: fixed; bottom: 22px; right: 22px; z-index: 99999;
  padding: 10px 16px; border-radius: 9px;
  font-size: 13px; font-weight: 600;
  display: none; align-items: center; gap: 8px;
  box-shadow: 0 6px 24px rgba(0,0,0,.35);
}
#pmToast.show  { display: flex; animation: tin .2s ease; }
#pmToast.ok    { background: #10b981; color: #fff; }
#pmToast.err   { background: #ef4444; color: #fff; }
@keyframes tin { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
</style>
</head>
<body>
<div class="main-content">
  <div class="page-header">
    <div class="page-title">Inventory</div>
    <div class="page-subtitle">Stock levels and product status</div>
  </div>
  <div class="content-area">
    <div class="search-row">
      <form method="get" style="display:flex;gap:10px;flex:1;" id="inventorySearchForm">
        <input type="text" name="search" id="inventorySearchInput"
               placeholder="Search product or barcode..."
               value="<?php echo htmlspecialchars($search); ?>"
               autocomplete="off">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
        <?php if($search): ?><a href="inventory.php" class="btn btn-dark">Clear</a><?php endif; ?>
      </form>
      <a href="print_barcodes.php" target="_blank" class="btn btn-dark"><i class="fas fa-print"></i> Print All Barcodes</a>
    </div>
    <div class="card">
      <table>
        <thead><tr><th>#</th><th>Product</th><th>Barcode</th><th>Price</th><th>Stock</th><th>Status</th></tr></thead>
        <tbody>
        <?php
        $i = 1;
        $sql = "SELECT * FROM products";
        if ($search != '') $sql .= " WHERE name LIKE '%$search%' OR barcode LIKE '%$search%'";
        $q = $conn->query($sql);
        while ($p = $q->fetch_assoc()):
        ?>
        <tr>
          <td style="color:var(--text-muted);"><?php echo $i++; ?></td>
          <td style="font-weight:600;"><?php echo htmlspecialchars($p['name']); ?></td>
          <td style="font-family:monospace;font-size:12px;color:var(--text-muted);"><?php echo htmlspecialchars($p['barcode']); ?></td>
          <td style="color:var(--accent2);font-weight:700;">Rs <?php echo number_format($p['price'],2); ?></td>
          <td style="font-weight:700;"><?php echo $p['stock']; ?></td>
          <td><?php
            if($p['stock']==0) echo "<span class='badge badge-out'>Out of Stock</span>";
            elseif($p['stock']<=5) echo "<span class='badge badge-low'>Low Stock</span>";
            else echo "<span class='badge badge-ok'>In Stock</span>";
          ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════
     CHANGE BARCODE POPUP MODAL
══════════════════════════════════════════════════ -->
<div id="pairOverlay">
  <div id="pairCard">

    <!-- Header -->
    <div class="pm-hdr">
      <div class="pm-hdr-icon"><i class="fas fa-barcode"></i></div>
      <div style="flex:1;">
        <div class="pm-hdr-title">
          Pair Unknown Barcode: <span class="bc-pill" id="pmBarcode">—</span>
        </div>
        <p class="pm-hdr-sub">Unknown barcode detected. Select a product to assign this barcode.</p>
      </div>
      <button class="pm-close" onclick="closePairModal()" title="Close">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <!-- Body -->
    <div class="pm-body">

      <div class="pm-lbl">Search Product:</div>
      <div class="pm-search-wrap">
        <input type="text" id="pmSearch"
               placeholder="Type product name or barcode…"
               autocomplete="off"
               oninput="pmFilter(this.value)">
        <i class="fas fa-search pm-sico"></i>
      </div>

      <div class="pm-lbl">Select Product:</div>
      <div class="pm-list" id="pmList">
        <?php foreach ($all_products as $p): ?>
        <div class="pm-item"
             data-id="<?php echo $p['id']; ?>"
             data-name="<?php echo htmlspecialchars($p['name']); ?>"
             data-search="<?php echo strtolower(htmlspecialchars($p['name'] . ' ' . $p['barcode'])); ?>"
             onclick="pmPick(this)">
          <div class="pm-radio"></div>
          <div>
            <div class="pm-pname"><?php echo htmlspecialchars($p['name']); ?></div>
            <div class="pm-pmeta">
              <?php if ($p['barcode']): ?>
                <i class="fas fa-tag" style="font-size:9px;opacity:.6;margin-right:3px;"></i><?php echo htmlspecialchars($p['barcode']); ?> &nbsp;
              <?php endif; ?>
              Rs <?php echo number_format($p['price'], 2); ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <div class="pm-nores" id="pmNoRes"><i class="fas fa-search" style="opacity:.4;margin-right:6px;"></i>No products found</div>
      </div>

    </div>

    <!-- Footer -->
    <div class="pm-footer">
      <button class="pm-btn-cancel" onclick="closePairModal()">
        <i class="fas fa-times"></i> Cancel
      </button>
      <button class="pm-btn-pair" id="pmPairBtn" disabled onclick="pmDoPair()">
        <i class="fas fa-exchange-alt"></i> Change Barcode
      </button>
    </div>

  </div>
</div>

<!-- Toast -->
<div id="pmToast">
  <i id="pmToastIco"></i>
  <span id="pmToastMsg"></span>
</div>

<script>
// ── State ──────────────────────────────────────────────────────────────────
let _barcode = null;
let _selId   = null;
let _selName = null;

const overlay  = document.getElementById('pairOverlay');
const pmItems  = document.querySelectorAll('.pm-item');
const pmBtn    = document.getElementById('pmPairBtn');
const pmNoRes  = document.getElementById('pmNoRes');
const pmSearch = document.getElementById('pmSearch');

// ── Open popup ─────────────────────────────────────────────────────────────
function openPairModal(barcode) {
  _barcode = barcode;
  _selId   = null;
  _selName = null;

  document.getElementById('pmBarcode').textContent = barcode;
  pmSearch.value = '';
  pmItems.forEach(el => { el.classList.remove('sel'); el.style.display = ''; });
  pmNoRes.style.display = 'none';
  pmBtn.disabled = true;
  pmBtn.innerHTML = '<i class="fas fa-exchange-alt"></i> Change Barcode';
  pmBtn.style.background = '';

  overlay.classList.add('open');
  setTimeout(() => pmSearch.focus(), 220);
}

// ── Close popup ────────────────────────────────────────────────────────────
function closePairModal() {
  overlay.classList.remove('open');
}

// Close on backdrop click
overlay.addEventListener('click', function(e) {
  if (e.target === overlay) closePairModal();
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closePairModal();
});

// ── Pick a product from the list ───────────────────────────────────────────
function pmPick(el) {
  pmItems.forEach(i => i.classList.remove('sel'));
  el.classList.add('sel');
  _selId   = el.dataset.id;
  _selName = el.dataset.name;
  pmBtn.disabled = false;
}

// ── Filter product list by search text ────────────────────────────────────
function pmFilter(q) {
  q = q.toLowerCase().trim();
  let visible = 0;
  pmItems.forEach(el => {
    const show = !q || el.dataset.search.includes(q);
    el.style.display = show ? '' : 'none';
    if (show) visible++;
  });
  pmNoRes.style.display = visible ? 'none' : 'block';
}

// ── POST to pair_barcode.php → updates barcode in DB ──────────────────────
async function pmDoPair() {
  if (!_selId || !_barcode) return;

  pmBtn.disabled = true;
  pmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

  try {
    const res = await fetch('pair_barcode.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ barcode: _barcode, product_id: parseInt(_selId) })
    });
    const data = await res.json();

    if (data.success) {
      pmBtn.innerHTML        = '<i class="fas fa-check"></i> Changed!';
      pmBtn.style.background = '#10b981';
      pmShowToast('"' + _selName + '" barcode set to ' + _barcode, 'ok');
      // Reload page after close so table shows updated barcode
      setTimeout(() => { closePairModal(); location.reload(); }, 1500);
    } else {
      pmShowToast('Error: ' + (data.error || 'Unknown'), 'err');
      pmBtn.disabled = false;
      pmBtn.innerHTML = '<i class="fas fa-exchange-alt"></i> Change Barcode';
    }
  } catch (e) {
    pmShowToast('Network error. Try again.', 'err');
    pmBtn.disabled = false;
    pmBtn.innerHTML = '<i class="fas fa-exchange-alt"></i> Change Barcode';
  }
}

// ── Toast helper ───────────────────────────────────────────────────────────
function pmShowToast(msg, type) {
  const t = document.getElementById('pmToast');
  document.getElementById('pmToastMsg').textContent = msg;
  document.getElementById('pmToastIco').className =
    type === 'ok' ? 'fas fa-check-circle' : 'fas fa-times-circle';
  t.className = 'show ' + type;
  clearTimeout(t._t);
  t._t = setTimeout(() => t.className = '', 3500);
}

// ══════════════════════════════════════════════════════════════════════════
//  SEARCH BAR BARCODE INTERCEPTION
//  If the search input value looks like a barcode (no spaces, ≥3 chars,
//  alphanumeric/dashes only), check pair_barcode.php before submitting:
//    unknown → open Change Barcode popup
//    known   → proceed with normal page search
// ══════════════════════════════════════════════════════════════════════════
(function () {
  const form  = document.getElementById('inventorySearchForm');
  const input = document.getElementById('inventorySearchInput');
  if (!form || !input) return;

  form.addEventListener('submit', async function (e) {
    const val = input.value.trim();

    // Only intercept barcode-like strings (no spaces, alphanumeric/dashes, ≥3 chars)
    const looksLikeBarcode = val.length >= 3 && /^[\w\-]+$/.test(val);
    if (!looksLikeBarcode) return; // normal product-name search — let it through

    e.preventDefault();

    try {
      const res  = await fetch('pair_barcode.php?barcode=' + encodeURIComponent(val));
      const data = await res.json();

      if (data.error === 'unknown') {
        openPairModal(val);
        input.value = '';
      } else {
        // Known barcode — run the normal inventory search
        form.submit();
      }
    } catch (err) {
      form.submit(); // network error fallback
    }
  });
})();

// ══════════════════════════════════════════════════════════════════════════
//  HARDWARE BARCODE SCANNER LISTENER
//  USB / Bluetooth scanners fire keystrokes very fast, then send Enter.
//  Skipped when the user is actively typing inside any input field.
// ══════════════════════════════════════════════════════════════════════════
(function () {
  let buf = '', last = 0;
  const GAP = 80; // ms — scanners type faster than humans

  document.addEventListener('keydown', async function (e) {
    const tag = document.activeElement.tagName;
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(tag)) return;

    const now = Date.now();
    if (now - last > GAP * 6) buf = '';
    last = now;

    if (e.key === 'Enter') {
      const scanned = buf.trim();
      buf = '';
      if (scanned.length >= 3) await checkBarcode(scanned);
      return;
    }
    if (e.key.length === 1) buf += e.key;
  });

  async function checkBarcode(barcode) {
    try {
      const res  = await fetch('pair_barcode.php?barcode=' + encodeURIComponent(barcode));
      const data = await res.json();

      if (data.error === 'unknown') {
        openPairModal(barcode);
      } else if (data.product_id) {
        document.dispatchEvent(new CustomEvent('barcodeFound', {
          detail: { barcode, product_id: data.product_id, name: data.name }
        }));
        pmShowToast('✓ ' + data.name, 'ok');
      }
    } catch (err) {
      console.warn('Barcode lookup failed:', err);
    }
  }
})();
</script>
</body>
</html>

<?php
require_once 'auth.php';
require_once 'roles.php';
include 'db.php';
$conn->set_charset("utf8mb4");
date_default_timezone_set('Asia/Karachi');
$conn->query("SET time_zone = '+05:00'");

/* ----------- SCAN BARCODE OR NAME TO CART ----------- */
if(isset($_POST['scan_barcode'])){
    $input = trim($_POST['scan_barcode']);
    $input = $conn->real_escape_string($input);
    $q = $conn->query("SELECT id, stock FROM products WHERE barcode='$input' OR name LIKE '%$input%' LIMIT 1");
    if($q && $q->num_rows > 0){
        $p = $q->fetch_assoc();
        if($p['stock'] > 0){
            $_SESSION['cart'][$p['id']] = ($_SESSION['cart'][$p['id']] ?? 0) + 1;
            $conn->query("UPDATE products SET stock = stock - 1 WHERE id=".$p['id']);
        }
    }
}

/* -------- ADD TO CART -------- */
if(isset($_GET['add'])){
    $id = (int)$_GET['add'];
    $q = $conn->query("SELECT stock FROM products WHERE id=$id");
    if($q && $q->num_rows){ $p = $q->fetch_assoc(); if($p['stock'] > 0){ $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1; $conn->query("UPDATE products SET stock = stock - 1 WHERE id=$id"); } }
    header("Location: index.php"); exit;
}

/* -------- RESET FULL CART -------- */
if(isset($_GET['reset_cart'])){
    if(!empty($_SESSION['cart'])){ foreach($_SESSION['cart'] as $id => $qty){ $conn->query("UPDATE products SET stock = stock + $qty WHERE id=$id"); } }
    unset($_SESSION['cart']); header("Location: index.php"); exit;
}

/* -------- REMOVE FROM CART -------- */
if(isset($_GET['remove'])){
    $id = (int)$_GET['remove'];
    if(isset($_SESSION['cart'][$id])){ $qty = $_SESSION['cart'][$id]; $conn->query("UPDATE products SET stock = stock + $qty WHERE id=$id"); unset($_SESSION['cart'][$id]); }
    header("Location: index.php"); exit;
}

/* -------- DELETE PRODUCT -------- */
if(isset($_GET['delete']) && $_SESSION['role'] == 'admin'){
    $conn->query("DELETE FROM products WHERE id=".(int)$_GET['delete']);
    header("Location: index.php"); exit;
}

/* -------- AUTO BARCODE -------- */
$res = $conn->query("SELECT id FROM products WHERE barcode='' OR barcode IS NULL");
while($r = $res->fetch_assoc()){ $barcode = rand(1000000000,9999999999); $conn->query("UPDATE products SET barcode='$barcode' WHERE id=".$r['id']); }

/* -------- DASHBOARD STATS -------- */
$totalProducts = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'];
$totalSales    = $conn->query("SELECT COALESCE(SUM(qty*price),0) as c FROM sales")->fetch_assoc()['c'];
$totalExpenses = $conn->query("SELECT COALESCE(SUM(amount),0) as c FROM expenses")->fetch_assoc()['c'];
$lowStock      = $conn->query("SELECT COUNT(*) as c FROM products WHERE stock <= 5")->fetch_assoc()['c'];
$todaySales    = $conn->query("SELECT COALESCE(SUM(qty*price),0) as c FROM sales WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['c'];

/* -------- SALES CHART DATA (last 7 days) -------- */
$chartLabels = []; $chartData = [];
for($i = 6; $i >= 0; $i--){
    $date = date('Y-m-d', strtotime("-$i days")); $label = date('D', strtotime($date));
    $res2 = $conn->query("SELECT COALESCE(SUM(qty*price),0) as total FROM sales WHERE DATE(created_at)='$date'");
    $row2 = $res2->fetch_assoc();
    $chartLabels[] = $label; $chartData[] = (float)$row2['total'];
}

/* -------- ALL PRODUCTS FOR JS -------- */
$plistAll = $conn->query("SELECT id, name, price, barcode, stock, image FROM products ORDER BY name");
$productsJson = [];
while($pl2 = $plistAll->fetch_assoc()){
    $productsJson[] = ['id'=>(int)$pl2['id'],'name'=>$pl2['name'],'price'=>(float)$pl2['price'],'barcode'=>$pl2['barcode'],'stock'=>(int)$pl2['stock'],'image'=>$pl2['image']??''];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard - Stockora</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?php include 'sidebar.php'; ?>
<style>
/* Payment dropdown dark mode fix */
.pay-select {
    width: 100%;
    background: var(--bg2) !important;
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text) !important;
    font-family: inherit;
    cursor: pointer;
    -webkit-appearance: none;
    appearance: none;
}
.pay-select option {
    background: var(--bg2) !important;
    color: var(--text) !important;
}

/* Dashboard-specific overrides */
.main-content { background: var(--bg); }

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.stat-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    position: relative;
    overflow: hidden;
    transition: transform .2s, border-color .2s;
}
.stat-card:hover { transform: translateY(-2px); border-color: rgba(59,130,246,.4); }
.stat-card::before { content:''; position:absolute; top:0;left:0;right:0; height:3px; }
.stat-card.blue::before { background: var(--accent); }
.stat-card.green::before { background: var(--accent2); }
.stat-card.orange::before { background: #f59e0b; }
.stat-card.red::before { background: var(--danger); }
.stat-card.purple::before { background: #a855f7; }

.stat-icon { width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;margin-bottom:14px; }
.stat-card.blue .stat-icon   { background:rgba(59,130,246,0.15); color:var(--accent); }
.stat-card.green .stat-icon  { background:rgba(16,185,129,0.15); color:var(--accent2); }
.stat-card.orange .stat-icon { background:rgba(245,158,11,0.15); color:#f59e0b; }
.stat-card.red .stat-icon    { background:rgba(239,68,68,0.15); color:var(--danger); }
.stat-card.purple .stat-icon { background:rgba(168,85,247,0.15); color:#a855f7; }
.stat-value { font-size:22px;font-weight:800;color:var(--text);margin-bottom:3px; }
.stat-label { font-size:12px;color:var(--text-muted);font-weight:500; }

/* DASHBOARD MAIN LAYOUT */
.dash-row {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 20px;
    margin-bottom: 24px;
    align-items: start;
}
@media(max-width:900px){ .dash-row { grid-template-columns: 1fr; } }

.chart-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 22px;
}
.card-title {
    font-size:14px;font-weight:700;color:var(--text);
    margin-bottom:18px;display:flex;align-items:center;gap:8px;
}
.card-dot { width:8px;height:8px;border-radius:50%;background:var(--accent); }

/* SALES SLIP PANEL */
.slip-panel {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    position: sticky;
    top: 20px;
}
.slip-panel input {
    background: rgba(255,255,255,0.05);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    padding: 9px 12px;
    font-size: 13px;
    outline: none;
    width: 100%;
    transition: border-color .2s;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.slip-panel input:focus { border-color: var(--accent); }
.slip-panel input::placeholder { color: var(--text-muted); }
.cart-item-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 9px 0; border-bottom: 1px solid rgba(51,65,85,0.5); font-size: 13px;
}
.cart-total-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 12px 0 0; font-weight: 800; font-size: 16px;
    border-top: 1px solid var(--border);
}

/* PRODUCTS SECTION */
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(185px, 1fr));
    gap: 14px;
}
.product-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    transition: transform .2s, border-color .2s;
}
.product-card:hover { transform: translateY(-2px); border-color: rgba(59,130,246,.35); }
.product-card-img { width:100%;height:110px;object-fit:cover;display:block; }
.product-card-placeholder { width:100%;height:110px;background:rgba(255,255,255,0.03);display:flex;align-items:center;justify-content:center;font-size:32px;color:var(--border); }
.product-card-body { padding: 12px 14px; }
.pname { font-size:14px;font-weight:700;margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.pmeta { display:flex;justify-content:space-between;align-items:center;margin-bottom:12px; }
.pprice { font-size:15px;font-weight:800;color:var(--accent2); }
.pstock { font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px; }
.stock-ok  { background:rgba(16,185,129,0.15);color:var(--accent2); }
.stock-low { background:rgba(245,158,11,0.15);color:#f59e0b; }
.stock-out { background:rgba(239,68,68,0.15);color:var(--danger); }
.pbtn-row { display:flex;gap:6px; }
.pbtn { flex:1;padding:7px 0;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;transition:opacity .15s;font-family:'Plus Jakarta Sans',sans-serif; }
.pbtn:hover{opacity:.8;}
.pbtn-add { background:var(--accent);color:#fff; }
.pbtn-add:disabled { background:var(--border);color:var(--text-muted);cursor:not-allowed;opacity:1; }
.pbtn-bc  { background:rgba(255,255,255,0.06);color:var(--text-muted); }
.pbtn-del { background:rgba(239,68,68,0.12);color:var(--danger); }

/* TOPBAR SCAN */
.topbar {
    padding: 16px 32px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 12px;
    background: var(--card);
    margin-bottom: 0;
}
.scan-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.05);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 9px 14px;
    flex: 1;
    max-width: 400px;
}
.scan-wrap input {
    background: none;
    border: none;
    outline: none;
    color: var(--text);
    font-size: 14px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    width: 100%;
}
.scan-wrap input::placeholder { color: var(--text-muted); }

/* Dropdown */
.slip-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    left: 0; right: 0;
    background: var(--card);
    border: 1px solid var(--accent);
    border-radius: 8px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 9999;
    box-shadow: 0 8px 24px rgba(0,0,0,0.4);
    display: none;
}
.slip-opt {
    padding: 10px 13px;
    cursor: pointer;
    font-size: 13px;
    color: var(--text);
    border-bottom: 1px solid rgba(51,65,85,0.5);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.slip-opt:hover { background: rgba(59,130,246,0.12); }
.slip-opt:last-child { border-bottom: none; }
</style>
</head>
<body>
<div class="main-content">

    <!-- TOPBAR SCAN -->
    <div class="topbar">
        <div class="page-title" style="font-size:18px;white-space:nowrap;">Dashboard</div>
        <form class="scan-wrap" onsubmit="topBarScan(event)" style="flex:1;max-width:400px;">
            <i class="fas fa-barcode" style="color:var(--text-muted);font-size:16px;"></i>
            <input type="text" id="topBarInput" placeholder="Scan barcode or type product name..." autocomplete="off" autofocus>
        </form>
        <form method="get" style="display:flex;gap:8px;align-items:center;">
            <div class="scan-wrap" style="max-width:220px;padding:8px 12px;">
                <i class="fas fa-search" style="color:var(--text-muted);font-size:13px;"></i>
                <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="font-size:13px;">
            </div>
            <button type="submit" class="btn btn-primary" style="height:40px;padding:0 16px;"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <div class="content-area" style="padding-top:24px;">

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon"><i class="fas fa-box"></i></div>
                <div class="stat-value"><?= $totalProducts ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="stat-value">Rs <?= number_format($totalSales,0) ?></div>
                <div class="stat-label">Total Sales</div>
            </div>
            <div class="stat-card purple" style="--accent-color:#a855f7;">
                <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-value">Rs <?= number_format($todaySales,0) ?></div>
                <div class="stat-label">Today's Sales</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                <div class="stat-value">Rs <?= number_format($totalExpenses,0) ?></div>
                <div class="stat-label">Total Expenses</div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-value"><?= $lowStock ?></div>
                <div class="stat-label">Low Stock Items</div>
            </div>
        </div>

        <!-- CHART + SLIP ROW -->
        <div class="dash-row">

            <!-- SALES CHART -->
            <div class="chart-card">
                <div class="card-title"><span class="card-dot"></span> Sales — Last 7 Days</div>
                <canvas id="salesChart" height="130"></canvas>
            </div>

            <!-- INLINE SALES SLIP PANEL -->
            <div class="slip-panel">
                <div class="card-title">
                    <span class="card-dot" style="background:var(--accent2);"></span>
                    Quick Sales Slip
                </div>

                <!-- Customer Info -->
                <input type="text" id="slip_cname" placeholder="👤 Customer Name (optional)">
                <input type="text" id="slip_cphone" placeholder="📞 Phone (optional)">

                <!-- Product Select -->
                <div style="position:relative;">
                    <input type="text" id="slipProductInput" placeholder="🔍 Search product or scan barcode..." autocomplete="off">
                    <div class="slip-dropdown" id="slipDropdown"></div>
                </div>

                <div style="display:flex;gap:8px;">
                    <input type="number" id="slipQty" value="1" min="1" style="width:80px;flex-shrink:0;">
                    <button onclick="slipAddToCart()" class="btn btn-primary" style="flex:1;justify-content:center;height:40px;">
                        <i class="fas fa-plus"></i> Add
                    </button>
                </div>

                <!-- Cart Items -->
                <div id="slipCartItems" style="max-height:220px;overflow-y:auto;"></div>

                <!-- Total -->
                <div class="cart-total-row">
                    <span style="font-size:13px;color:var(--text-muted);font-weight:600;">Total</span>
                    <span style="color:var(--accent2);" id="slipTotal">Rs 0</span>
                </div>

                <!-- PAYMENT METHOD -->
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--text-muted);letter-spacing:.5px;display:block;margin-bottom:6px;">PAYMENT METHOD</label>
                    <select id="slipPayment" class="pay-select" style="font-size:13px;padding:9px 12px;">
                        <option value="Cash" selected>💵 Cash</option>
                        <option value="Card">💳 Card</option>
                        <option value="JazzCash">📱 JazzCash</option>
                        <option value="EasyPaisa">📱 EasyPaisa</option>
                        <option value="Bank Transfer">🏦 Bank Transfer</option>
                        <option value="Credit">📒 Credit / Udhaar</option>
                    </select>
                </div>

                <!-- Actions -->
                <div style="display:flex;gap:8px;">
                    <button onclick="slipCheckout(true)" class="btn btn-success" style="flex:1;justify-content:center;">
                        <i class="fas fa-check"></i> Checkout & Print
                    </button>
                    <button onclick="slipCheckout(false)" class="btn btn-primary" style="flex:1;justify-content:center;">
                        <i class="fas fa-cash-register"></i> Only
                    </button>
                </div>
                <button onclick="slipClearCart()" class="btn btn-danger" style="width:100%;justify-content:center;">
                    <i class="fas fa-trash"></i> Clear Cart
                </button>
            </div>
        </div>

        <!-- PRODUCTS SECTION -->
        <div style="font-size:15px;font-weight:700;color:var(--text);margin-bottom:16px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-box" style="color:var(--accent);"></i> Products
        </div>
        <div class="product-grid">
        <?php
        $search = '';
        if(isset($_GET['search'])){ $search = $conn->real_escape_string($_GET['search']); $p = $conn->query("SELECT * FROM products WHERE name LIKE '%$search%'"); }
        else { $p = $conn->query("SELECT * FROM products ORDER BY name ASC"); }
        while($r = $p->fetch_assoc()):
            if($r['stock'] == 0) { $stockClass = 'stock-out'; $stockLabel = 'Out of Stock'; }
            elseif($r['stock'] <= 5) { $stockClass = 'stock-low'; $stockLabel = '⚠️ '.$r['stock']; }
            else { $stockClass = 'stock-ok'; $stockLabel = $r['stock'].' left'; }
        ?>
        <div class="product-card">
            <?php if(!empty($r['image']) && file_exists('uploads/'.$r['image'])): ?>
                <img class="product-card-img" src="uploads/<?= htmlspecialchars($r['image']) ?>" alt="<?= htmlspecialchars($r['name']) ?>">
            <?php else: ?>
                <div class="product-card-placeholder">📦</div>
            <?php endif; ?>
            <div class="product-card-body">
                <div class="pname" title="<?= htmlspecialchars($r['name']) ?>"><?= htmlspecialchars($r['name']) ?></div>
                <div class="pmeta">
                    <span class="pprice">Rs <?= number_format($r['price'],0) ?></span>
                    <span class="pstock <?= $stockClass ?>"><?= $stockLabel ?></span>
                </div>
                <div class="pbtn-row">
                    <?php if($r['stock'] > 0): ?>
                        <button class="pbtn pbtn-add" onclick="location.href='?add=<?= $r['id'] ?>'">+ Cart</button>
                    <?php else: ?>
                        <button class="pbtn pbtn-add" disabled>Out</button>
                    <?php endif; ?>
                    <button class="pbtn pbtn-bc" onclick="printBarcode('<?= $r['barcode'] ?>')"><i class="fas fa-print"></i></button>
                    <button class="pbtn pbtn-del" onclick="if(confirm('Delete?'))location.href='?delete=<?= $r['id'] ?>'"><i class="fas fa-trash"></i></button>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
        </div>

        <div style="text-align:center;padding:24px 0 0;"><a href="https://hexora.great-site.net" style="color:var(--text-muted);font-size:12px;text-decoration:none;">Powered by Hexora</a></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
// ===== PRODUCTS DATA =====
const SLIP_PRODUCTS = <?php echo json_encode($productsJson); ?>;

// ===== CHART =====
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Sales (Rs)',
            data: <?= json_encode($chartData) ?>,
            backgroundColor: 'rgba(59,130,246,0.25)',
            borderColor: '#3b82f6',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => 'Rs ' + ctx.raw.toLocaleString() } }
        },
        scales: {
            x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#94a3b8', font: { size: 12 } } },
            y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#94a3b8', callback: v => 'Rs ' + v.toLocaleString() }, beginAtZero: true }
        }
    }
});

// ===== INLINE SALES SLIP =====
let slipCart = {};
let slipSelected = null;

const slipInput    = document.getElementById('slipProductInput');
const slipDropdown = document.getElementById('slipDropdown');

function slipShowDropdown(filter){
    filter = (filter||'').toLowerCase();
    const filtered = SLIP_PRODUCTS.filter(p => p.name.toLowerCase().includes(filter) || p.barcode.toLowerCase().includes(filter));
    if(!filtered.length){
        slipDropdown.innerHTML = '<div class="slip-opt" style="color:var(--text-muted);cursor:default;">No products found</div>';
    } else {
        slipDropdown.innerHTML = filtered.map(p =>
            `<div class="slip-opt" data-id="${p.id}">
                <span>${p.name}</span>
                <span style="color:var(--accent2);font-weight:700;font-size:12px;">Rs ${p.price} <span style="color:var(--text-muted);">(${p.stock})</span></span>
            </div>`
        ).join('');
        slipDropdown.querySelectorAll('.slip-opt[data-id]').forEach(el => {
            el.addEventListener('mousedown', function(e){
                e.preventDefault();
                const pid = parseInt(this.dataset.id);
                slipSelected = SLIP_PRODUCTS.find(p => p.id === pid);
                if(slipSelected) slipInput.value = slipSelected.name;
                slipDropdown.style.display = 'none';
            });
        });
    }
    slipDropdown.style.display = 'block';
}

slipInput.addEventListener('focus', () => slipShowDropdown(slipInput.value));
slipInput.addEventListener('input', () => {
    slipSelected = null;
    slipShowDropdown(slipInput.value);
    // barcode auto-match
    const val = slipInput.value.trim().toLowerCase();
    const match = SLIP_PRODUCTS.find(p => p.barcode.toLowerCase() === val);
    if(match){ slipSelected = match; slipInput.value = match.name; slipDropdown.style.display = 'none'; setTimeout(slipAddToCart, 80); }
});
slipInput.addEventListener('blur', () => setTimeout(() => { slipDropdown.style.display = 'none'; }, 150));

function slipAddToCart(){
    if(!slipSelected){
        const val = slipInput.value.trim().toLowerCase();
        slipSelected = SLIP_PRODUCTS.find(p => p.name.toLowerCase() === val) || null;
        if(!slipSelected){ alert('Please select a product from the list'); return; }
    }
    const qty = parseInt(document.getElementById('slipQty').value) || 1;
    const pid = String(slipSelected.id);
    if(slipCart[pid]) slipCart[pid].qty += qty;
    else slipCart[pid] = { name: slipSelected.name, price: slipSelected.price, qty: qty };

    fetch('sales_slip.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'add=1&product_id='+pid+'&qty='+qty });
    slipRenderCart();
    slipInput.value = ''; slipSelected = null;
    document.getElementById('slipQty').value = 1;
    slipInput.focus();
}

function slipRenderCart(){
    const container = document.getElementById('slipCartItems');
    let html = '', total = 0;
    Object.entries(slipCart).forEach(([pid, item]) => {
        const sub = item.price * item.qty; total += sub;
        html += `<div class="cart-item-row">
            <div>
                <div style="font-weight:600;font-size:13px;">${item.name}</div>
                <div style="color:var(--text-muted);font-size:11px;margin-top:2px;">x${item.qty} × Rs ${item.price}</div>
            </div>
            <div style="text-align:right;">
                <div style="color:var(--accent2);font-weight:700;font-size:13px;">Rs ${sub}</div>
                <span onclick="slipRemove('${pid}')" style="color:var(--danger);cursor:pointer;font-size:11px;">✕ Remove</span>
            </div>
        </div>`;
    });
    if(!html) html = '<div style="color:var(--text-muted);font-size:13px;padding:14px 0;text-align:center;">No items added</div>';
    container.innerHTML = html;
    document.getElementById('slipTotal').textContent = 'Rs ' + total.toLocaleString();
}

function slipRemove(pid){
    if(slipCart[pid]){
        fetch('sales_slip.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'remove_item=1&product_id='+pid+'&qty='+slipCart[pid].qty });
        delete slipCart[pid]; slipRenderCart();
    }
}

function slipClearCart(){
    slipCart = {};
    fetch('sales_slip.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'clear=1' });
    slipRenderCart();
}

function slipCheckout(doPrint){
    if(!Object.keys(slipCart).length){ alert('⚠️ Cart is empty! Enter a product first..'); return; }

    const payment = document.getElementById('slipPayment').value;
    const name    = document.getElementById('slip_cname').value.trim()  || 'Walk-in';
    const phone   = document.getElementById('slip_cphone').value.trim() || '-';
    const totalTxt = document.getElementById('slipTotal').textContent;

    const confirmed = confirm(
        "✅ Confirm checkout?\n\n" +
        "👤 Customer: " + name + "\n" +
        "📞 Phone: " + phone + "\n" +
        "💳 Payment: " + payment + "\n" +
        "💰 Total: " + totalTxt + "\n\n" +
        (doPrint ? "🖨️ The receipt will be printed." : "📋 Checkout only receipt will not print.")
    );
    if(!confirmed) return;

    fetch('sales_slip.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'checkout=1&customer_name='+encodeURIComponent(name)+'&customer_phone='+encodeURIComponent(phone)+'&payment_method='+encodeURIComponent(payment) })
    .then(r => r.json()).then(res => {
        if(res.status === 'ok'){
            if(doPrint) slipPrintReceipt(res, payment);
            else {
                const t = document.createElement('div');
                t.textContent = '✅ Checkout complete! Payment: ' + payment;
                t.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#10b981;color:#fff;padding:14px 22px;border-radius:10px;font-size:14px;font-weight:600;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,0.3);';
                document.body.appendChild(t); setTimeout(()=>t.remove(), 3500);
            }
            slipCart = {}; slipRenderCart();
            document.getElementById('slip_cname').value  = '';
            document.getElementById('slip_cphone').value = '';
            setTimeout(() => location.reload(), 1800);
        } else { alert('❌ Error: ' + res.message); }
    });
}

function slipPrintReceipt(data, payment){
    payment = payment || 'Cash';
    let html = '<style>*{margin:0;padding:0;box-sizing:border-box;}body{font-family:Arial,sans-serif;padding:20px;max-width:320px;margin:auto;font-size:13px;}h2{text-align:center;font-size:18px;margin-bottom:4px;}h2 span{display:block;font-size:11px;font-weight:400;color:#666;letter-spacing:2px;margin-bottom:12px;}.divider{border:none;border-top:1px dashed #ccc;margin:10px 0;}.row{display:flex;justify-content:space-between;margin:5px 0;}.row.total{font-size:15px;font-weight:700;margin-top:4px;}.pay-badge{text-align:center;margin-top:12px;background:#f0f9ff;border:1px solid #bae6fd;padding:6px;border-radius:6px;font-size:12px;color:#0284c7;font-weight:700;}.footer{text-align:center;margin-top:16px;font-size:10px;color:#aaa;}</style>';
    html += '<h2>SALES RECEIPT<span>STOCKORA POS</span></h2>';
    html += '<div class="row"><span>Customer:</span><span>'+data.customer_name+'</span></div>';
    html += '<div class="row"><span>Phone:</span><span>'+data.customer_phone+'</span></div>';
    html += '<div class="row"><span>Date:</span><span>'+new Date().toLocaleString('en-PK')+'</span></div>';
    html += '<hr class="divider">';
    let total = 0;
    data.items.forEach(i => { html += '<div class="row"><span>'+i.name+' x'+i.qty+'</span><span>Rs '+i.subtotal+'</span></div>'; total += i.subtotal; });
    html += '<hr class="divider"><div class="row total"><b>Total</b><b>Rs '+total+'</b></div>';
    html += '<div class="pay-badge">💳 Payment: '+payment+'</div>';
    html += '<div class="footer">Thank you for shopping! — Powered by Hexora</div>';
    const w = window.open('','','width=400,height=620'); w.document.write(html); w.document.close(); setTimeout(()=>w.print(), 300);
}

function printBarcode(code){
    const w = window.open("","","width=300,height=180");
    w.document.write(`<html><body style="text-align:center"><svg id="b"></svg><script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script><script>JsBarcode("#b","${code}",{width:2,height:60,displayValue:true});setTimeout(()=>window.print(),500);<\/script></body></html>`);
}

// TOP BAR SCAN
function topBarScan(e){
    e.preventDefault();
    const val = document.getElementById('topBarInput').value.trim();
    if(!val) return;
    let found = SLIP_PRODUCTS.find(p => p.barcode.toLowerCase() === val.toLowerCase());
    if(!found) found = SLIP_PRODUCTS.find(p => p.name.toLowerCase().includes(val.toLowerCase()));
    if(found){
        const pid = String(found.id);
        if(slipCart[pid]) slipCart[pid].qty += 1;
        else slipCart[pid] = {name: found.name, price: found.price, qty: 1};
        fetch('sales_slip.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'add=1&product_id='+pid+'&qty=1' });
        slipRenderCart();
        const inp = document.getElementById('topBarInput');
        inp.style.borderColor = '#10b981'; inp.value = '';
        setTimeout(() => { inp.style.borderColor = ''; }, 600);
    } else {
        const inp = document.getElementById('topBarInput');
        inp.style.borderColor = '#ef4444';
        setTimeout(() => { inp.style.borderColor = ''; inp.value = ''; }, 800);
    }
    document.getElementById('topBarInput').focus();
}

// Pre-populate cart from PHP session
<?php if(!empty($_SESSION['cart'])): foreach($_SESSION['cart'] as $sid => $sqty): $sp = $conn->query("SELECT id,name,price FROM products WHERE id=$sid")->fetch_assoc(); if($sp): ?>
slipCart[<?= $sp['id'] ?>] = {name: <?= json_encode($sp['name']) ?>, price: <?= $sp['price'] ?>, qty: <?= $sqty ?>};
<?php endif; endforeach; endif; ?>
slipRenderCart();
</script>
</body>
</html>

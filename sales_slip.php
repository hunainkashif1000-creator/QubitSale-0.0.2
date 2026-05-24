<?php
session_start();
include 'db.php';
require_once 'auth.php';
date_default_timezone_set('Asia/Karachi');
$conn->query("SET time_zone = '+05:00'");
$conn->set_charset("utf8mb4");
$branch_url  = 'https://qubitsale.free.nf/receive_sale.php'; // ← System B ka URL
$branch_name = 'Main Branch';  // ← Apni branch ka naam
if(isset($_POST['clear'])){ unset($_SESSION['cart']); unset($_SESSION['customer_name']); unset($_SESSION['customer_phone']); echo "ok"; exit; }
if(isset($_POST['add'])){ $pid=(int)$_POST['product_id']; $qty=(int)$_POST['qty']; if($pid>0&&$qty>0) $_SESSION['cart'][$pid]=($_SESSION['cart'][$pid]??0)+$qty; echo "ok"; exit; }
if(isset($_POST['remove_item'])){ $pid=(int)$_POST['product_id']; if(isset($_SESSION['cart'][$pid])){ unset($_SESSION['cart'][$pid]); } echo "ok"; exit; }

if(isset($_POST['checkout'])&&!empty($_SESSION['cart'])){
    $customer_name=!empty($_POST['customer_name'])?trim($_POST['customer_name']):'Walk-in';
    $customer_phone=!empty($_POST['customer_phone'])?trim($_POST['customer_phone']):'-';
    $payment_method=!empty($_POST['payment_method'])?trim($_POST['payment_method']):'Cash';
    $conn->begin_transaction();
    try{
        $cashier_id   = (int)($_SESSION['user_id'] ?? 0);
        $cashier_name = $conn->real_escape_string($_SESSION['username'] ?? 'unknown');
        $select=$conn->prepare("SELECT name,price,stock FROM products WHERE id=?");
        $insert=$conn->prepare("INSERT INTO sales (product_id,qty,price,customer_name,customer_phone,cashier_id,cashier_name,payment_method,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
        $update=$conn->prepare("UPDATE products SET stock=stock-? WHERE id=?");
        $cart_items=[];
        foreach($_SESSION['cart'] as $pid=>$qty){
            $select->bind_param("i",$pid); $select->execute(); $p=$select->get_result()->fetch_assoc();
            if(!$p) throw new Exception("Product not found");
            if($p['stock']<$qty) throw new Exception("Insufficient stock for: ".$p['name']);
            $insert->bind_param("iidssiss",$pid,$qty,$p['price'],$customer_name,$customer_phone,$cashier_id,$cashier_name,$payment_method); $insert->execute();
            $update->bind_param("ii",$qty,$pid); $update->execute();
            $cart_items[]=['name'=>$p['name'],'qty'=>$qty,'price'=>$p['price'],'subtotal'=>$p['price']*$qty];
        }
        $conn->commit();
        unset($_SESSION['cart']); unset($_SESSION['customer_name']); unset($_SESSION['customer_phone']);
        echo json_encode(['status'=>'ok','customer_name'=>$customer_name,'customer_phone'=>$customer_phone,'items'=>$cart_items,'payment_method'=>$payment_method]);
        exit;
    }catch(Exception $e){ $conn->rollback(); echo json_encode(['status'=>'error','message'=>$e->getMessage()]); exit; }
}

if(isset($_GET['load_cart'])){
    $total=0;
    if(!empty($_SESSION['cart'])){
        foreach($_SESSION['cart'] as $id=>$qty){
            $p=$conn->query("SELECT * FROM products WHERE id=$id")->fetch_assoc();
            if(!$p) continue;
            $sub=$p['price']*$qty; $total+=$sub;
            echo "<div class='cart-row'><span class='item-name'>".htmlspecialchars($p['name'])." <span style='color:var(--text-muted)'>x{$qty}</span></span><span style='color:var(--accent2);font-weight:700;'>Rs {$sub}</span></div>";
        }
        echo "<div class='cart-total-row'><span>Total</span><span>Rs {$total}</span></div>";
    } else { echo "<div style='text-align:center;padding:20px;color:var(--text-muted);'>No items in cart</div>"; }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Sales Slip - Stockora</title>
<meta charset="UTF-8">
<?php include 'sidebar.php'; ?>
<style>
/* Payment dropdown dark mode fix */
.pay-select {
    width: 100%;
    padding: 10px 14px;
    background: var(--bg2) !important;
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text) !important;
    font-size: 14px;
    font-family: inherit;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
}
.pay-select option {
    background: var(--bg2) !important;
    color: var(--text) !important;
}
.slip-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 20px;
    align-items: start;
}
@media(max-width: 960px){ .slip-layout { grid-template-columns: 1fr; } }

/* SLIP CARD */
.slip-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
}
.slip-card:last-child { margin-bottom: 0; }

.cart-row { display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid rgba(51,65,85,0.5);font-size:14px; }
.cart-total-row { display:flex;justify-content:space-between;padding:12px 0 0;font-weight:800;font-size:16px;color:var(--accent2); }
.item-name { font-weight:500; }

/* PRODUCT DROPDOWN */
.custom-options {
    position: absolute;
    bottom: 100%;
    left: 0; width: 100%;
    border: 1px solid var(--accent);
    max-height: 220px;
    overflow-y: auto;
    background: var(--card);
    border-radius: 8px;
    display: none;
    z-index: 10;
    box-shadow: 0 -8px 24px rgba(0,0,0,0.3);
}
.option {
    padding: 10px 14px;
    cursor: pointer;
    font-size: 13px;
    color: var(--text);
    border-bottom: 1px solid rgba(51,65,85,0.4);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.option:last-child { border-bottom: none; }
.option:hover { background: rgba(59,130,246,0.1); }

/* RIGHT PANEL - PRODUCTS LIST */
.products-panel {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px;
    position: sticky;
    top: 20px;
    max-height: calc(100vh - 60px);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.products-panel-header {
    font-size: 13px;
    font-weight: 700;
    color: var(--text-muted);
    letter-spacing: 0.5px;
    margin-bottom: 10px;
    flex-shrink: 0;
}
.products-search-wrap {
    position: relative;
    margin-bottom: 10px;
    flex-shrink: 0;
}
.products-search-wrap i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 13px;
}
.products-search-wrap input {
    padding-left: 32px;
    font-size: 13px;
    height: 36px;
}
.products-list {
    overflow-y: auto;
    flex: 1;
}
.product-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 9px 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: background .15s;
.p-thumb {
    width: 36px;
    height: 36px;
    border-radius: 6px;
    object-fit: cover;
    flex-shrink: 0;
    background: var(--border);
    border: 1px solid var(--border);
}
.p-thumb-placeholder {
    width: 36px;
    height: 36px;
    border-radius: 6px;
    background: rgba(59,130,246,0.1);
    border: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
    border-bottom: 1px solid rgba(51,65,85,0.4);
    gap: 8px;
}
.product-item:last-child { border-bottom: none; }
.product-item:hover { background: rgba(59,130,246,0.08); }
.product-item .p-name { font-size: 13px; font-weight: 600; color: var(--text); flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.product-item .p-price { font-size: 12px; font-weight: 700; color: var(--accent2); white-space: nowrap; }
.product-item .p-stock { font-size: 11px; font-weight: 600; padding: 2px 7px; border-radius: 20px; white-space: nowrap; flex-shrink: 0; }
.stock-ok  { background: rgba(16,185,129,0.15); color: var(--accent2); }
.stock-low { background: rgba(245,158,11,0.15); color: #f59e0b; }
.stock-out { background: rgba(239,68,68,0.15); color: var(--danger); }
.p-add-btn {
    width: 28px; height: 28px;
    background: var(--accent);
    border: none;
    border-radius: 6px;
    color: #fff;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: opacity .15s;
}
.p-add-btn:hover { opacity: .8; }
.p-add-btn:disabled { background: var(--border); color: var(--text-muted); cursor: not-allowed; opacity: 1; }
</style>
</head>
<body>
<div class="main-content">
  <div class="page-header">
    <div class="page-title">Sales Slip</div>
    <div class="page-subtitle">Create and process customer sales</div>
  </div>
  <div class="content-area">
    <div class="slip-layout">

      <!-- LEFT: SLIP FORM -->
      <div>
        <!-- CUSTOMER INFO -->
        <div class="slip-card">
          <div style="font-size:12px;font-weight:700;color:var(--text-muted);letter-spacing:0.5px;margin-bottom:12px;">CUSTOMER INFO</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <input id="cname" placeholder="Customer Name (optional)">
            <input id="cphone" placeholder="Phone (optional)">
          </div>
        </div>

        <!-- ADD PRODUCT -->
        <div class="slip-card">
          <div style="font-size:12px;font-weight:700;color:var(--text-muted);letter-spacing:0.5px;margin-bottom:12px;">ADD PRODUCT</div>
          <div style="position:relative;margin-bottom:10px;">
            <input type="text" id="productInput" placeholder="Search product or scan barcode...">
            <div id="productOptions" class="custom-options">
            <?php $q=$conn->query("SELECT * FROM products ORDER BY name ASC"); while($p=$q->fetch_assoc()): ?>
            <div class="option" data-value="<?php echo $p['id']; ?>" data-barcode="<?php echo $p['barcode']; ?>">
              <span><?php echo htmlspecialchars($p['name']); ?></span>
              <span style="color:var(--accent2);font-weight:700;font-size:12px;">Rs <?php echo $p['price']; ?> <span style="color:var(--text-muted);font-weight:400;">(Stock: <?php echo $p['stock']; ?>)</span></span>
            </div>
            <?php endwhile; ?>
            </div>
          </div>
          <div style="display:flex;gap:10px;">
            <input type="number" id="qty" min="1" value="1" style="max-width:100px;">
            <button type="button" class="btn btn-primary" onclick="addToCart()"><i class="fas fa-plus"></i> Add to Cart</button>
          </div>
        </div>

        <!-- CART -->
        <div class="slip-card">
          <div style="font-size:12px;font-weight:700;color:var(--text-muted);letter-spacing:0.5px;margin-bottom:12px;">CART</div>
          <div id="cartList"><div style="text-align:center;padding:16px;color:var(--text-muted);">Loading...</div></div>

          <!-- PAYMENT METHOD -->
          <div style="margin-top:14px;">
            <label style="font-size:11px;font-weight:700;color:var(--text-muted);letter-spacing:0.5px;display:block;margin-bottom:6px;">PAYMENT METHOD</label>
            <select id="paymentMethod" class="pay-select">
              <option value="Cash" selected>💵 Cash</option>
              <option value="Card">💳 Card</option>
              <option value="JazzCash">📱 JazzCash</option>
              <option value="EasyPaisa">📱 EasyPaisa</option>
              <option value="Bank Transfer">🏦 Bank Transfer</option>
              <option value="Credit">📒 Credit / Udhaar</option>
            </select>
          </div>

          <div style="display:flex;flex-direction:column;gap:8px;margin-top:12px;">
            <div style="display:flex;gap:8px;">
              <button type="button" class="btn btn-success" onclick="checkout(true)" style="flex:1;justify-content:center;"><i class="fas fa-check"></i> Checkout & Print</button>
              <button type="button" class="btn btn-primary" onclick="checkout(false)" style="flex:1;justify-content:center;"><i class="fas fa-cash-register"></i> Checkout Only</button>
            </div>
            <button type="button" class="btn btn-danger" onclick="clearCart()" style="width:100%;justify-content:center;"><i class="fas fa-trash"></i> Clear Cart</button>
          </div>
        </div>
      </div>

      <!-- RIGHT: PRODUCTS PANEL -->
      <div class="products-panel">
        <div class="products-panel-header">ALL PRODUCTS</div>
        <div class="products-search-wrap">
          <i class="fas fa-search"></i>
          <input type="text" id="panelSearch" placeholder="Search products..." oninput="filterPanel(this.value)">
        </div>
        <div class="products-list" id="productsList">
          <?php
          $allProducts = $conn->query("SELECT * FROM products ORDER BY name ASC");
          while($r = $allProducts->fetch_assoc()):
            if($r['stock'] == 0){ $sc = 'stock-out'; $sl = 'Out'; }
            elseif($r['stock'] <= 5){ $sc = 'stock-low'; $sl = '⚠️ '.$r['stock']; }
            else { $sc = 'stock-ok'; $sl = $r['stock']; }
            $imgSrc = !empty($r['image']) ? 'uploads/' . htmlspecialchars($r['image']) : '';
          ?>
          <div class="product-item" data-name="<?php echo strtolower(htmlspecialchars($r['name'])); ?>">
            <?php if($imgSrc): ?>
              <img class="p-thumb" src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($r['name']); ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
              <div class="p-thumb-placeholder" style="display:none;">&#128230;</div>
            <?php else: ?>
              <div class="p-thumb-placeholder">&#128230;</div>
            <?php endif; ?>
            <span class="p-name" title="<?php echo htmlspecialchars($r['name']); ?>"><?php echo htmlspecialchars($r['name']); ?></span>
            <span class="p-stock <?php echo $sc; ?>"><?php echo $sl; ?></span>
            <span class="p-price">Rs <?php echo number_format($r['price'],0); ?></span>
            <?php if($r['stock'] > 0): ?>
            <button class="p-add-btn" onclick="quickAdd(<?php echo $r['id']; ?>, '<?php echo addslashes($r['name']); ?>', <?php echo $r['price']; ?>)" title="Add to cart">+</button>
            <?php else: ?>
            <button class="p-add-btn" disabled title="Out of stock">✕</button>
            <?php endif; ?>
          </div>
          <?php endwhile; ?>
        </div>
      </div>

    </div><!-- end slip-layout -->
  </div>
</div>

<script>
// ===== PRODUCT DROPDOWN =====
const customInput  = document.getElementById("productInput");
const optionsDiv   = document.getElementById("productOptions");

customInput.addEventListener("click", () => { optionsDiv.style.display = "block"; });
customInput.addEventListener("keyup", () => {
  let value = customInput.value.trim().toLowerCase();
  optionsDiv.querySelectorAll(".option").forEach(opt => {
    const nameMatch = opt.textContent.toLowerCase().includes(value);
    const barcodeMatch = (opt.dataset.barcode||"").toLowerCase() === value;
    opt.style.display = (nameMatch || barcodeMatch) ? "" : "none";
    if(value !== "" && barcodeMatch){
      customInput.value = opt.querySelector('span').textContent;
      customInput.dataset.value = opt.dataset.value;
      optionsDiv.style.display = "none";
      setTimeout(() => addToCart(), 100);
    }
  });
});
document.addEventListener("click", e => { if(!e.target.closest('#productInput') && !e.target.closest('#productOptions')) optionsDiv.style.display = "none"; });
optionsDiv.addEventListener("click", e => {
  const opt = e.target.closest(".option");
  if(opt){ customInput.value = opt.querySelector('span').textContent.trim(); customInput.dataset.value = opt.dataset.value; optionsDiv.style.display = "none"; }
});

// ===== CART =====
function addToCart(){
  let pid = customInput.dataset.value, qty = document.getElementById('qty').value;
  if(!pid) return alert("Please select a product.");
  fetch("sales_slip.php", { method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"}, body:"add=1&product_id="+pid+"&qty="+qty })
  .then(() => { loadCart(); document.getElementById('qty').value = 1; customInput.value = ''; delete customInput.dataset.value; customInput.focus(); });
}

function loadCart(){
  fetch("sales_slip.php?load_cart=1").then(r => r.text()).then(html => { document.getElementById('cartList').innerHTML = html; });
}

function checkout(doPrint){
  // ── Cart empty check
  fetch("sales_slip.php?load_cart=1").then(r=>r.text()).then(html=>{
    if(html.includes('No items in cart')){ alert("⚠️Cart is empty! Enter a product first."); return; }

    // ── Confirmation alert
    const payment = document.getElementById('paymentMethod').value;
    const n = document.getElementById('cname').value.trim() || 'Walk-in';
    const p = document.getElementById('cphone').value.trim() || '-';
    const cartDiv = document.getElementById('cartList');
    const totalEl = cartDiv.querySelector('.cart-total-row span:last-child');
    const totalText = totalEl ? totalEl.textContent : '';

    const confirmed = confirm(
      "✅ Confirm checkout?\n\n" +
      "👤 Customer: " + n + "\n" +
      "📞 Phone: " + p + "\n" +
      "💳 Payment: " + payment + "\n" +
      "💰 Total: " + totalText + "\n\n" +
      (doPrint ? "🖨️ The receipt will be printed." : "📋 Checkout only receipt will not print.")
    );
    if(!confirmed) return;

    fetch("sales_slip.php", { method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"},
      body:"checkout=1&customer_name="+encodeURIComponent(n)+"&customer_phone="+encodeURIComponent(p)+"&payment_method="+encodeURIComponent(payment) })
    .then(r => r.json()).then(res => {
      if(res.status === 'ok'){
        if(doPrint) printReceipt(res, payment);
        else { showSuccessToast("✅ Checkout complete! Payment: " + payment); }
        loadCart();
      } else { alert("❌ Error: " + res.message); }
    });
  });
}

function showSuccessToast(msg){
  const t = document.createElement('div');
  t.textContent = msg;
  t.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#10b981;color:#fff;padding:14px 22px;border-radius:10px;font-size:14px;font-weight:600;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,0.3);animation:slideUp .3s ease;';
  document.body.appendChild(t);
  setTimeout(()=>t.remove(), 3500);
}

function printReceipt(data, payment){
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
  let w = window.open('','','width=400,height=620'); w.document.write(html); w.document.close(); setTimeout(()=>w.print(), 300);
}

function clearCart(){
  fetch("sales_slip.php", { method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"}, body:"clear=1" }).then(() => loadCart());
}

// ===== RIGHT PANEL - QUICK ADD =====
function quickAdd(pid, name, price){
  fetch("sales_slip.php", { method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"}, body:"add=1&product_id="+pid+"&qty=1" })
  .then(() => { loadCart(); });
  // Visual feedback
  event.target.style.background = '#10b981';
  setTimeout(() => { event.target.style.background = ''; }, 400);
}

// ===== PANEL SEARCH FILTER =====
function filterPanel(val){
  val = val.toLowerCase();
  document.querySelectorAll('#productsList .product-item').forEach(item => {
    item.style.display = item.dataset.name.includes(val) ? '' : 'none';
  });
}

window.onload = loadCart;
</script>
</body>
</html>
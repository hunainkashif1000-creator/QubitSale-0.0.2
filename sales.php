<?php
session_start();
include 'db.php';
$conn->set_charset("utf8mb4");
date_default_timezone_set('Asia/Karachi');
$conn->query("SET time_zone = '+05:00'");

$isAdmin = ($_SESSION['role'] ?? '') === 'admin';

// ── Handle Delete ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_sale_id']) && $isAdmin) {
    $deleteId     = (int)$_POST['delete_sale_id'];
    $deleteReason = trim($_POST['delete_reason'] ?? '');

    // Log the deletion reason (optional — requires a sale_deletions table)
    // $conn->prepare("INSERT INTO sale_deletions (sale_id, reason, deleted_by, deleted_at) VALUES (?,?,?,NOW())")
    //       ->bind_param('iss', $deleteId, $deleteReason, $_SESSION['username'])->execute();

    $stmt = $conn->prepare("DELETE FROM sales WHERE id = ?");
    $stmt->bind_param('i', $deleteId);
    $stmt->execute();

    // Redirect to avoid resubmission
    $qs = http_build_query(array_filter([
        'search'    => $_GET['search']    ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to'   => $_GET['date_to']   ?? '',
    ]));
    header("Location: sales.php" . ($qs ? "?$qs" : ''));
    exit;
}

// ── Read filters ──────────────────────────────────────────────────────────────
$search   = trim($_GET['search']    ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to']   ?? '';

$where  = [];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = 's.customer_name LIKE ?';
    $params[] = '%' . $search . '%';
    $types   .= 's';
}
if ($dateFrom !== '') {
    $where[]  = 'DATE(s.created_at) >= ?';
    $params[] = $dateFrom;
    $types   .= 's';
}
if ($dateTo !== '') {
    $where[]  = 'DATE(s.created_at) <= ?';
    $params[] = $dateTo;
    $types   .= 's';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── Export URL params ─────────────────────────────────────────────────────────
$exportParams = http_build_query(array_filter([
    'search'    => $search,
    'date_from' => $dateFrom,
    'date_to'   => $dateTo,
    'export'    => 'excel',
]));

$sql = "SELECT s.id, s.customer_name, s.customer_phone, s.qty, s.price,
               s.created_at, s.payment_method, p.name AS product_name
        FROM sales s
        LEFT JOIN products p ON p.id = s.product_id
        $whereSql
        ORDER BY s.created_at DESC";

// ── CSV Export ────────────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $key = ($row['customer_name'] ?: 'Walk-in') . '|' .
               ($row['customer_phone'] ?: '') . '|' .
               substr($row['created_at'], 0, 16);
        if (!isset($transactions[$key])) {
            $transactions[$key] = [
                'time'           => $row['created_at'],
                'customer_name'  => $row['customer_name']  ?: 'Walk-in',
                'customer_phone' => $row['customer_phone'] ?: '-',
                'payment_method' => $row['payment_method'] ?: 'Cash',
                'products'       => [],
                'total'          => 0,
            ];
        }
        $transactions[$key]['products'][] = $row['product_name'] . ' (x' . $row['qty'] . ')';
        $transactions[$key]['total']     += $row['qty'] * $row['price'];
    }

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="sales_report_' . date('Y-m-d') . '.csv"');
    header('Cache-Control: max-age=0');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['Sales Report - Stockora']);
    fputcsv($out, ['Generated: ' . date('d-m-Y h:i A')]);
    fputcsv($out, []);
    fputcsv($out, ['#', 'Sale Time', 'Customer', 'Phone', 'Products', 'Payment Method', 'Amount (Rs)']);

    $serial = 1; $grandTotal = 0;
    foreach ($transactions as $tx) {
        $grandTotal += $tx['total'];
        fputcsv($out, [
            $serial++,
            date('d-m-Y h:i A', strtotime($tx['time'])),
            $tx['customer_name'],
            $tx['customer_phone'],
            implode(' | ', $tx['products']),
            $tx['payment_method'],
            number_format($tx['total'], 2),
        ]);
    }
    fputcsv($out, []);
    fputcsv($out, ['', '', '', '', '', 'Grand Total', number_format($grandTotal, 2)]);
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Sale Records - Stockora</title>
<meta charset="UTF-8">
<?php include 'sidebar.php'; ?>
<style>
.popup-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:500;}
.popup-box{background:var(--card);border:1px solid var(--border);width:340px;padding:24px;border-radius:12px;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);}
.popup-box h4{margin:0 0 14px;font-size:15px;color:var(--text);}
.popup-box button{margin-top:14px;padding:8px 16px;background:var(--accent);color:white;border:none;border-radius:8px;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif;font-weight:600;}
.qty-btn{color:var(--accent);cursor:pointer;font-weight:600;font-size:12px;background:rgba(59,130,246,0.1);padding:4px 10px;border-radius:20px;border:none;font-family:'Plus Jakarta Sans',sans-serif;}
.qty-btn:hover{background:rgba(59,130,246,0.2);}
.whatsapp-btn{color:#25d366;font-weight:600;text-decoration:none;font-size:12px;}
.export-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:#16a34a;color:#fff;border:none;border-radius:8px;font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;font-size:13px;cursor:pointer;text-decoration:none;transition:background .2s;}
.export-btn:hover{background:#15803d;}
.grand-box{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:16px 20px;margin-top:16px;display:flex;justify-content:space-between;align-items:center;}
body:not(.light-mode) .grand-box span:last-child { color:#f59e0b; }
body.light-mode .grand-box span:last-child { color:#b45309; }
.filters-bar{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin-bottom:16px;}
.filter-group{display:flex;flex-direction:column;gap:4px;}
.filter-group label{font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;}
.filter-input{padding:8px 12px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--text);font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;min-width:170px;}
.filter-input:focus{outline:none;border-color:var(--accent);}
.filter-btn{padding:9px 16px;border-radius:8px;border:none;background:var(--accent);color:#fff;font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;font-size:13px;cursor:pointer;align-self:flex-end;}
.filter-btn-reset{background:transparent;color:var(--text-muted);border:1px solid var(--border);text-decoration:none;display:inline-flex;align-items:center;}
.filter-btn-reset:hover{background:rgba(128,128,128,0.1);}

/* Delete button */
.delete-btn{color:#ef4444;cursor:pointer;font-weight:600;font-size:12px;background:rgba(239,68,68,0.1);padding:4px 10px;border-radius:20px;border:none;font-family:'Plus Jakarta Sans',sans-serif;}
.delete-btn:hover{background:rgba(239,68,68,0.22);}

/* Delete reason popup */
.del-popup-box{background:var(--card);border:1px solid var(--border);width:380px;padding:26px;border-radius:14px;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);}
.del-popup-box h4{margin:0 0 6px;font-size:16px;color:#ef4444;}
.del-popup-box p{margin:0 0 14px;font-size:13px;color:var(--text-muted);}
.del-reason-input{width:100%;box-sizing:border-box;padding:10px 12px;border-radius:8px;border:1px solid var(--border);background:var(--bg,#0f172a);color:var(--text);font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;resize:vertical;min-height:80px;}
.del-reason-input:focus{outline:none;border-color:#ef4444;}
.del-popup-actions{display:flex;gap:10px;margin-top:14px;}
.del-confirm-btn{flex:1;padding:9px;background:#ef4444;color:#fff;border:none;border-radius:8px;font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;font-size:13px;cursor:pointer;}
.del-confirm-btn:hover{background:#dc2626;}
.del-cancel-btn{flex:1;padding:9px;background:transparent;color:var(--text-muted);border:1px solid var(--border);border-radius:8px;font-family:'Plus Jakarta Sans',sans-serif;font-weight:600;font-size:13px;cursor:pointer;}
.del-cancel-btn:hover{background:rgba(128,128,128,0.1);}
</style>
</head>
<body>
<div class="main-content">
  <div class="page-header">
    <div class="page-title">Sale Records</div>
    <div class="page-subtitle">Customer sales history</div>
  </div>
  <div class="content-area">

    <form method="GET" action="">
      <div class="filters-bar">
        <div class="filter-group">
          <label>Customer Name</label>
          <input class="filter-input" type="text" name="search" placeholder="Search customer…" value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="filter-group">
          <label>Date From</label>
          <input class="filter-input" type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
        </div>
        <div class="filter-group">
          <label>Date To</label>
          <input class="filter-input" type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
        </div>
        <button class="filter-btn" type="submit">🔍 Filter</button>
        <?php if ($search || $dateFrom || $dateTo): ?>
          <a href="sales.php" class="filter-btn filter-btn-reset">✕ Clear</a>
        <?php endif; ?>
        <a class="export-btn" href="sales.php?<?php echo htmlspecialchars($exportParams); ?>">📥 Export Excel</a>
      </div>
    </form>

    <div class="card">
    <table>
    <thead><tr>
      <th>#</th><th>Sale Time</th><th>Customer</th><th>Phone</th>
      <th>Products</th><th>Payment</th><th>Amount</th><th>WhatsApp</th>
      <?php if ($isAdmin): ?><th>Action</th><?php endif; ?>
    </tr></thead>
    <tbody>
    <?php
    $i=1; $grandTotal=0;
    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $q = $stmt->get_result();
    if($q && $q->num_rows>0): while($row=$q->fetch_assoc()): $grandTotal+=$row['qty']*$row['price']; ?>
    <tr>
      <td><?php echo $i++; ?></td>
      <td style="font-size:12px;color:var(--text-muted);"><?php echo date("d-m-Y h:i A",strtotime($row['created_at'])); ?></td>
      <td><?php echo htmlspecialchars($row['customer_name']?:'Walk-in'); ?></td>
      <td><?php echo htmlspecialchars($row['customer_phone']?:'-'); ?></td>
      <td>
        <span class="product-data" style="display:none;"><?php echo htmlspecialchars($row['product_name'].' ('.$row['qty'].')'); ?></span>
        <button class="qty-btn" data-customer="<?php echo htmlspecialchars($row['customer_name']); ?>" data-phone="<?php echo htmlspecialchars($row['customer_phone']); ?>" data-time="<?php echo $row['created_at']; ?>" data-qty="<?php echo (int)$row['qty']; ?>">👁 View</button>
      </td>
      <td><?php
        $pm = $row['payment_method'] ?: 'Cash';
        $pmIcons  = ['Cash'=>'💵','Card'=>'💳','JazzCash'=>'📱','EasyPaisa'=>'📱','Bank Transfer'=>'🏦','Credit'=>'📒'];
        $pmColors = ['Cash'=>'#10b981','Card'=>'#3b82f6','JazzCash'=>'#f97316','EasyPaisa'=>'#10b981','Bank Transfer'=>'#6366f1','Credit'=>'#ef4444'];
        $icon  = $pmIcons[$pm]  ?? '💳';
        $color = $pmColors[$pm] ?? '#94a3b8';
        echo "<span style='background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;color:{$color};white-space:nowrap;'>{$icon} {$pm}</span>";
      ?></td>
      <td style="font-weight:700;color:var(--accent2);">Rs <?php echo number_format($row['qty']*$row['price'],2); ?></td>
      <td><?php if(!empty($row['customer_phone'])): ?><a class="whatsapp-btn" target="_blank" href="https://wa.me/92<?php echo ltrim($row['customer_phone'],'0'); ?>?text=Thank%20you%20for%20shopping%20with%20us%20%F0%9F%98%8A">💬 WhatsApp</a><?php else: echo '-'; endif; ?></td>
      <?php if ($isAdmin): ?>
      <td>
        <button class="delete-btn"
          data-id="<?php echo (int)$row['id']; ?>"
          data-customer="<?php echo htmlspecialchars($row['customer_name'] ?: 'Walk-in'); ?>"
          data-time="<?php echo date('d-m-Y h:i A', strtotime($row['created_at'])); ?>">
          🗑 Delete
        </button>
      </td>
      <?php endif; ?>
    </tr>
    <?php endwhile; else: ?>
    <tr><td colspan="<?php echo $isAdmin ? 9 : 8; ?>" style="text-align:center;padding:30px;color:var(--text-muted);">No sales found</td></tr>
    <?php endif; ?>
    </tbody>
    </table>
    </div>
    <div class="grand-box">
      <span style="font-weight:600;color:var(--text-muted);">Grand Total Sales</span>
      <span style="font-size:20px;font-weight:800;">Rs <?php echo number_format($grandTotal,2); ?></span>
    </div>
  </div>
</div>

<!-- View Products Popup -->
<div class="popup-overlay" id="popup">
  <div class="popup-box">
    <h4>🧾 Products Detail</h4>
    <div id="popup-content"></div>
    <button onclick="document.getElementById('popup').style.display='none'">Close</button>
  </div>
</div>

<!-- Delete Reason Popup -->
<?php if ($isAdmin): ?>
<div class="popup-overlay" id="deletePopup">
  <div class="del-popup-box">
    <h4>🗑 Delete Sale</h4>
    <p id="deletePopupDesc">Are you sure you want to delete this sale?</p>
    <form method="POST" action="sales.php?<?php echo htmlspecialchars(http_build_query(array_filter(['search'=>$search,'date_from'=>$dateFrom,'date_to'=>$dateTo]))); ?>" id="deleteForm">
      <input type="hidden" name="delete_sale_id" id="deleteSaleId">
      <textarea class="del-reason-input" name="delete_reason" id="deleteReason" placeholder="Enter reason for deletion (required)…"></textarea>
      <div class="del-popup-actions">
        <button type="button" class="del-cancel-btn" id="deleteCancelBtn">Cancel</button>
        <button type="submit" class="del-confirm-btn" id="deleteConfirmBtn">Yes, Delete</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
// ── View Products popup ───────────────────────────────────────────────────────
document.querySelectorAll('.qty-btn').forEach(btn=>{
  btn.addEventListener('click',function(){
    let c=this.dataset.customer,ph=this.dataset.phone,t=this.dataset.time;
    let products=[],totalQty=0;
    document.querySelectorAll('.qty-btn').forEach(b=>{
      if(b.dataset.customer===c&&b.dataset.phone===ph&&b.dataset.time===t){
        let td=b.closest('td'),pt=td.querySelector('.product-data').innerText;
        products.push(pt); totalQty+=parseInt(b.dataset.qty);
      }
    });
    document.getElementById('popup-content').innerHTML=products.join('<br>')+'<hr><b>Total Qty:</b> '+totalQty;
    document.getElementById('popup').style.display='block';
  });
});

// ── Delete popup ──────────────────────────────────────────────────────────────
<?php if ($isAdmin): ?>
document.querySelectorAll('.delete-btn').forEach(btn=>{
  btn.addEventListener('click', function(){
    const id       = this.dataset.id;
    const customer = this.dataset.customer;
    const time     = this.dataset.time;
    document.getElementById('deleteSaleId').value  = id;
    document.getElementById('deleteReason').value  = '';
    document.getElementById('deletePopupDesc').textContent =
      'Delete sale for "' + customer + '" on ' + time + '? Please state a reason.';
    document.getElementById('deletePopup').style.display = 'block';
  });
});

document.getElementById('deleteCancelBtn').addEventListener('click', ()=>{
  document.getElementById('deletePopup').style.display = 'none';
});

document.getElementById('deleteForm').addEventListener('submit', function(e){
  const reason = document.getElementById('deleteReason').value.trim();
  if(!reason){
    e.preventDefault();
    document.getElementById('deleteReason').style.borderColor = '#ef4444';
    document.getElementById('deleteReason').placeholder = 'Reason is required before deleting!';
    return;
  }
  document.getElementById('deleteReason').style.borderColor = '';
});

// Close delete popup when clicking overlay background
document.getElementById('deletePopup').addEventListener('click', function(e){
  if(e.target === this) this.style.display = 'none';
});
<?php endif; ?>

// ── Amount grouping & row dedup ───────────────────────────────────────────────
window.addEventListener('load',()=>{
  let seen={},amtMap={};
  document.querySelectorAll('table tr').forEach((row,idx)=>{
    if(idx===0)return;
    let btn=row.querySelector('.qty-btn'); if(!btn)return;
    let tk=btn.dataset.time.substring(0,16),key=(btn.dataset.customer||'w')+'|'+(btn.dataset.phone||'n')+'|'+tk;
    let amt=parseFloat(row.children[6].innerText.replace('Rs','').replace(/,/g,'').trim())||0;
    amtMap[key]=(amtMap[key]||0)+amt;
  });
  document.querySelectorAll('table tr').forEach((row,idx)=>{
    if(idx===0||row.style.display==='none')return;
    let btn=row.querySelector('.qty-btn'); if(!btn)return;
    let tk=btn.dataset.time.substring(0,16),key=(btn.dataset.customer||'w')+'|'+(btn.dataset.phone||'n')+'|'+tk;
    row.children[6].innerText='Rs '+amtMap[key].toFixed(2);
  });
  document.querySelectorAll('table tr').forEach((row,idx)=>{
    if(idx===0)return;
    let btn=row.querySelector('.qty-btn'); if(!btn)return;
    let tk=btn.dataset.time.substring(0,16),key=(btn.dataset.customer||'w')+'|'+(btn.dataset.phone||'n')+'|'+tk;
    if(seen[key])row.style.display='none'; else seen[key]=true;
  });
  document.querySelectorAll('.qty-btn').forEach(btn=>{
    let c=btn.dataset.customer,ph=btn.dataset.phone,t=btn.dataset.time,sum=0;
    document.querySelectorAll('.qty-btn').forEach(b=>{ if(b.dataset.customer===c&&b.dataset.phone===ph&&b.dataset.time===t)sum+=parseInt(b.dataset.qty); });
    btn.innerHTML='👁 View ('+sum+')';
  });
});
</script>
</body>
</html>
<?php
session_start();
include 'db.php';
require_once 'auth.php';
require_once 'roles.php';
allow_roles(['admin']);
$conn->set_charset("utf8mb4");
date_default_timezone_set('Asia/Karachi');
$conn->query("SET time_zone = '+05:00'");

$filter = $_GET['filter'] ?? 'all';
$from   = $_GET['from']   ?? '';
$to     = $_GET['to']     ?? '';

$w = "";
if($filter==='today')   $w = "WHERE DATE(s.created_at)=CURDATE()";
elseif($filter==='7d')  $w = "WHERE s.created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)";
elseif($filter==='30d') $w = "WHERE s.created_at>=DATE_SUB(NOW(),INTERVAL 30 DAY)";
elseif($filter==='custom'&&$from&&$to)
    $w = "WHERE DATE(s.created_at) BETWEEN '".$conn->real_escape_string($from)."' AND '".$conn->real_escape_string($to)."'";

$usersData = [];
$q = $conn->query("SELECT s.cashier_name, s.cashier_id,
    COUNT(DISTINCT CONCAT(s.customer_name,'|',DATE_FORMAT(s.created_at,'%Y-%m-%d %H:%i'))) AS total_transactions,
    SUM(s.qty) AS total_items,
    SUM(s.qty*s.price) AS total_revenue
    FROM sales s $w
    GROUP BY s.cashier_name, s.cashier_id
    ORDER BY total_revenue DESC");
if($q) while($r=$q->fetch_assoc()) $usersData[]=$r;

$trendData=[]; $trendLabels=[];
for($i=6;$i>=0;$i--){
    $date=date('Y-m-d',strtotime("-$i days"));
    $trendLabels[]=date('D d',strtotime($date));
    $trendData[$date]=[];
}
$qt=$conn->query("SELECT cashier_name, DATE(created_at) as sd, SUM(qty*price) as rev FROM sales WHERE created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY) GROUP BY cashier_name,sd");
if($qt) while($r=$qt->fetch_assoc()) $trendData[$r['sd']][$r['cashier_name']]=(float)$r['rev'];

$palette=['#3b82f6','#10b981','#f59e0b','#a855f7','#ef4444','#06b6d4','#f97316','#ec4899'];
$userColors=[];
foreach($usersData as $i=>$u) $userColors[$u['cashier_name']]=$palette[$i%count($palette)];

$topUser=$usersData[0]??null;
$totalRevAll=array_sum(array_column($usersData,'total_revenue'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Performance - Stockora</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?php include 'sidebar.php'; ?>
<style>
.perf-stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:24px;}
.perf-stat{background:var(--card);border:1px solid var(--border);border-radius:var(--brad,12px);padding:20px;position:relative;overflow:hidden;}
.perf-stat::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--c,var(--accent));}
.perf-stat-val{font-size:26px;font-weight:800;color:var(--text);}
.perf-stat-lbl{font-size:12px;color:var(--text-muted);margin-top:4px;font-weight:500;}
.perf-stat-icon{position:absolute;right:16px;top:16px;font-size:24px;opacity:.2;}
.charts-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;}
@media(max-width:900px){.charts-row{grid-template-columns:1fr;}}
.chart-card{background:var(--card);border:1px solid var(--border);border-radius:var(--brad,12px);padding:22px;}
.chart-title{font-size:14px;font-weight:700;color:var(--text);margin-bottom:4px;}
.chart-sub{font-size:12px;color:var(--text-muted);margin-bottom:20px;}
.user-row{display:flex;align-items:center;gap:14px;padding:14px 0;border-bottom:1px solid rgba(51,65,85,0.5);}
.user-row:last-child{border-bottom:none;}
.user-rank{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0;}
.rank-1{background:linear-gradient(135deg,#f59e0b,#fbbf24);color:#000;}
.rank-2{background:rgba(148,163,184,0.2);color:#94a3b8;}
.rank-3{background:rgba(180,120,60,0.2);color:#cd7f32;}
.rank-n{background:rgba(255,255,255,0.06);color:var(--text-muted);}
.user-avatar{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:800;color:#fff;flex-shrink:0;}
.user-info{flex:1;min-width:0;}
.uname{font-size:14px;font-weight:700;color:var(--text);}
.umeta{font-size:12px;color:var(--text-muted);margin-top:2px;}
.ubar-wrap{flex:1;max-width:200px;background:rgba(255,255,255,0.06);border-radius:100px;height:6px;}
.ubar{height:100%;border-radius:100px;}
.urev{font-size:15px;font-weight:800;color:var(--accent2);white-space:nowrap;min-width:100px;text-align:right;}
.utxn{font-size:12px;color:var(--text-muted);text-align:right;min-width:70px;}
.filter-bar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:24px;}
.filter-btn{padding:8px 18px;background:var(--card);border:1px solid var(--border);border-radius:100px;font-size:13px;font-weight:600;color:var(--text-muted);cursor:pointer;text-decoration:none;transition:all .2s;font-family:inherit;}
.filter-btn:hover,.filter-btn.active{background:var(--accent);border-color:var(--accent);color:#fff;}
.trophy-card{background:linear-gradient(135deg,rgba(245,158,11,.15),rgba(251,191,36,.05));border:1px solid rgba(245,158,11,.3);border-radius:var(--brad,12px);padding:16px 20px;display:flex;align-items:center;gap:16px;margin-bottom:24px;}
.trophy-icon{font-size:36px;}
.trophy-text{flex:1;}
.trophy-label{font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#f59e0b;margin-bottom:4px;}
.trophy-name{font-size:20px;font-weight:800;color:var(--text);}
.trophy-sub{font-size:13px;color:var(--text-muted);margin-top:2px;}
.trophy-rev{font-size:28px;font-weight:800;color:#f59e0b;white-space:nowrap;}
.empty-state{text-align:center;padding:60px 20px;color:var(--text-muted);}
.empty-state .e-icon{font-size:48px;margin-bottom:16px;}
.notice{background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.25);border-radius:10px;padding:14px 18px;font-size:13px;color:#f59e0b;margin-bottom:20px;line-height:1.6;}
.notice code{background:rgba(0,0,0,.2);padding:2px 6px;border-radius:4px;font-size:12px;}
</style>
</head>
<body>
<div class="main-content">
  <div class="page-header">
    <div class="page-title">User Performance</div>
    <div class="page-subtitle">Sales breakdown and graphs per cashier</div>
  </div>
  <div class="content-area">

   <!--  <div class="notice">
      ⚠️ <strong>One-time setup required:</strong> Run <code>add_cashier_column.sql</code> in PhpMyAdmin first, then replace <code>sales_slip.php</code>. After that, new sales will be tracked per user automatically.
    </div>

    <!-- FILTER -->
    <div class="filter-bar">
      <?php foreach(['all'=>'All Time','today'=>'Today','7d'=>'Last 7 Days','30d'=>'Last 30 Days'] as $v=>$l): ?>
      <a href="?filter=<?=$v?>" class="filter-btn<?=$filter===$v?' active':''?>"><?=$l?></a>
      <?php endforeach; ?>
      <form method="get" style="display:flex;gap:8px;align-items:center;">
        <input type="hidden" name="filter" value="custom">
        <input type="date" name="from" value="<?=htmlspecialchars($from)?>" style="width:140px;height:36px;font-size:13px;">
        <span style="color:var(--text-muted);font-size:13px;">—</span>
        <input type="date" name="to" value="<?=htmlspecialchars($to)?>" style="width:140px;height:36px;font-size:13px;">
        <button type="submit" class="filter-btn<?=$filter==='custom'?' active':''?>">Apply</button>
      </form>
    </div>

    <?php if(empty($usersData)): ?>
    <div class="card">
      <div class="empty-state">
        <div class="e-icon">📊</div>
        <p style="font-size:16px;font-weight:700;color:var(--text);margin-bottom:8px;">No data yet</p>
        <p>Complete the setup steps above, then make some sales to see performance data here.</p>
      </div>
    </div>
    <?php else: ?>

    <!-- SUMMARY STATS -->
    <div class="perf-stats">
      <div class="perf-stat" style="--c:var(--accent);">
        <div class="perf-stat-icon">👥</div>
        <div class="perf-stat-val"><?=count($usersData)?></div>
        <div class="perf-stat-lbl">Active Cashiers</div>
      </div>
      <div class="perf-stat" style="--c:var(--accent2);">
        <div class="perf-stat-icon">💰</div>
        <div class="perf-stat-val">Rs <?=number_format($totalRevAll,0)?></div>
        <div class="perf-stat-lbl">Total Revenue</div>
      </div>
      <div class="perf-stat" style="--c:#f59e0b;">
        <div class="perf-stat-icon">🧾</div>
        <div class="perf-stat-val"><?=number_format(array_sum(array_column($usersData,'total_transactions')))?></div>
        <div class="perf-stat-lbl">Total Transactions</div>
      </div>
      <div class="perf-stat" style="--c:#a855f7;">
        <div class="perf-stat-icon">📦</div>
        <div class="perf-stat-val"><?=number_format(array_sum(array_column($usersData,'total_items')))?></div>
        <div class="perf-stat-lbl">Total Items Sold</div>
      </div>
    </div>

    <!-- TROPHY -->
    <?php if($topUser): ?>
    <div class="trophy-card">
      <div class="trophy-icon">🏆</div>
      <div class="trophy-text">
        <div class="trophy-label">Top Performer</div>
        <div class="trophy-name"><?=htmlspecialchars($topUser['cashier_name']?:'Unknown')?></div>
        <div class="trophy-sub"><?=number_format($topUser['total_transactions'])?> transactions &nbsp;·&nbsp; <?=number_format($topUser['total_items'])?> items sold</div>
      </div>
      <div class="trophy-rev">Rs <?=number_format($topUser['total_revenue'],0)?></div>
    </div>
    <?php endif; ?>

    <!-- CHARTS ROW -->
    <div class="charts-row">
      <div class="chart-card">
        <div class="chart-title">Revenue Share</div>
        <div class="chart-sub">Each user's contribution to total sales</div>
        <canvas id="donutChart" height="240"></canvas>
      </div>
      <div class="chart-card">
        <div class="chart-title">Transactions per User</div>
        <div class="chart-sub">Number of checkouts completed</div>
        <canvas id="barChart" height="240"></canvas>
      </div>
    </div>

    <!-- LINE TREND -->
    <div class="chart-card" style="margin-bottom:24px;">
      <div class="chart-title">7-Day Sales Trend per User</div>
      <div class="chart-sub">Daily revenue comparison across all cashiers</div>
      <canvas id="lineChart" height="100"></canvas>
    </div>

    <!-- LEADERBOARD -->
    <div class="card">
      <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:16px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-medal" style="color:#f59e0b;"></i> Leaderboard
      </div>
      <?php foreach($usersData as $i=>$u):
        $rankClass=$i===0?'rank-1':($i===1?'rank-2':($i===2?'rank-3':'rank-n'));
        $rankSymbol=$i===0?'🥇':($i===1?'🥈':($i===2?'🥉':($i+1)));
        $color=$userColors[$u['cashier_name']]??'#3b82f6';
        $pct=$totalRevAll>0?($u['total_revenue']/$totalRevAll*100):0;
        $init=strtoupper(substr($u['cashier_name']?:'U',0,1));
      ?>
      <div class="user-row">
        <div class="user-rank <?=$rankClass?>"><?=$rankSymbol?></div>
        <div class="user-avatar" style="background:<?=$color?>;"><?=$init?></div>
        <div class="user-info">
          <div class="uname"><?=htmlspecialchars($u['cashier_name']?:'Unknown')?></div>
          <div class="umeta"><?=number_format($u['total_items'])?> items &nbsp;·&nbsp; avg Rs <?=number_format($u['total_transactions']>0?$u['total_revenue']/$u['total_transactions']:0,0)?>/txn</div>
        </div>
        <div class="ubar-wrap"><div class="ubar" style="width:<?=$pct?>%;background:<?=$color?>;"></div></div>
        <div class="utxn"><?=number_format($u['total_transactions'])?> txns</div>
        <div class="urev">Rs <?=number_format($u['total_revenue'],0)?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php endif; ?>
  </div>
</div>

<script>
<?php if(!empty($usersData)): ?>
const COLORS = <?=json_encode(array_values($userColors))?>;
const NAMES  = <?=json_encode(array_column($usersData,'cashier_name'))?>;
const REVS   = <?=json_encode(array_map(fn($u)=>(float)$u['total_revenue'],$usersData))?>;
const TXNS   = <?=json_encode(array_map(fn($u)=>(int)$u['total_transactions'],$usersData))?>;

new Chart(document.getElementById('donutChart'),{
    type:'doughnut',
    data:{labels:NAMES,datasets:[{data:REVS,backgroundColor:COLORS,borderWidth:2,borderColor:'#1e293b'}]},
    options:{cutout:'65%',plugins:{
        legend:{position:'right',labels:{color:'#94a3b8',font:{size:12},padding:16}},
        tooltip:{callbacks:{label:ctx=>' Rs '+ctx.raw.toLocaleString()}}
    }}
});

new Chart(document.getElementById('barChart'),{
    type:'bar',
    data:{labels:NAMES,datasets:[{label:'Transactions',data:TXNS,backgroundColor:COLORS,borderRadius:8,borderSkipped:false}]},
    options:{plugins:{legend:{display:false},tooltip:{callbacks:{label:ctx=>' '+ctx.raw+' transactions'}}},
        scales:{x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#94a3b8'}},
                y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#94a3b8'},beginAtZero:true}}}
});

const trendLabels = <?=json_encode($trendLabels)?>;
const trendRaw    = <?=json_encode($trendData)?>;
const dates       = Object.keys(trendRaw);
const lineDatasets = NAMES.map((name,ni)=>({
    label:name, data:dates.map(d=>trendRaw[d][name]??0),
    borderColor:COLORS[ni], backgroundColor:COLORS[ni]+'22',
    fill:true, tension:0.4, pointRadius:5, pointHoverRadius:7, borderWidth:2
}));
new Chart(document.getElementById('lineChart'),{
    type:'line',
    data:{labels:trendLabels,datasets:lineDatasets},
    options:{interaction:{mode:'index',intersect:false},
        plugins:{legend:{labels:{color:'#94a3b8',font:{size:12},padding:16}},
            tooltip:{callbacks:{label:ctx=>' '+ctx.dataset.label+': Rs '+ctx.raw.toLocaleString()}}},
        scales:{x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#94a3b8'}},
                y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#94a3b8',callback:v=>'Rs '+v.toLocaleString()},beginAtZero:true}}}
});
<?php endif; ?>
</script>
</body>
</html>

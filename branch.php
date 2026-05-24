<?php
/**
 * branch.php
 * Upload karo: https://qubitsale.free.nf/branch.php
 * Yeh file ct.ws API se sales data fetch karke dikhata hai
 */

define('API_URL',    'https://qubitsale.ct.ws/sales_api.php');
define('API_SECRET', 'qubit2025secret');   // sales_api.php se same hona chahiye

// ── API call ──────────────────────────────────────────────────────────────────
$url      = API_URL . '?token=' . API_SECRET;
$context  = stream_context_create(['http' => ['timeout' => 10]]);
$response = @file_get_contents($url, false, $context);

$sales      = [];
$fetchError = '';

if ($response === false) {
    $fetchError = 'Could not connect to API server. Sales_API.php uploaded?';
} else {
    $decoded = json_decode($response, true);
    if (!$decoded || !$decoded['success']) {
        $fetchError = $decoded['error'] ?? 'API ne invalid response diya';
    } else {
        $sales = $decoded['data'] ?? [];
    }
}

// ── Totals ────────────────────────────────────────────────────────────────────
$totalQty   = array_sum(array_column($sales, 'qty'));
$totalPrice = array_sum(array_map(fn($s) => ($s['qty'] ?? 0) * ($s['price'] ?? 0), $sales));
?>
<!DOCTYPE html>
<html lang="ur" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QUBITsale — Branch View</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f0f4f8;
            color: #333;
            padding: 24px;
        }

        header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        header h1 {
            font-size: 1.5rem;
            color: #1a3c5e;
        }

        header span {
            background: #2980b9;
            color: #fff;
            font-size: 0.75rem;
            padding: 2px 10px;
            border-radius: 20px;
            font-weight: 600;
        }

        /* ─── Summary Cards ────────────────────────────────── */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            padding: 18px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #2980b9;
        }

        .card .label { font-size: 0.78rem; color: #888; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px; }
        .card .value { font-size: 1.6rem; font-weight: 700; color: #1a3c5e; }

        /* ─── Table ────────────────────────────────────────── */
        .table-wrap {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .table-wrap h2 {
            padding: 16px 20px;
            font-size: 1rem;
            color: #1a3c5e;
            border-bottom: 1px solid #eee;
        }

        .scroll { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        thead { background: #1a3c5e; color: #fff; }
        th, td { padding: 11px 14px; text-align: left; font-size: 0.86rem; border-bottom: 1px solid #f0f0f0; }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #f7fafd; }

        .badge {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 12px;
            font-size: 0.76rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .badge.cash   { background: #d4edda; color: #155724; }
        .badge.card   { background: #cce5ff; color: #004085; }
        .badge.online { background: #fff3cd; color: #856404; }
        .badge.other  { background: #e2e3e5; color: #383d41; }

        .total-row td { font-weight: 700; background: #eaf2fb; }

        /* ─── States ───────────────────────────────────────── */
        .no-data, .error-msg {
            text-align: center;
            padding: 40px;
            color: #888;
            font-size: 0.95rem;
        }
        .error-msg { color: #c0392b; }

        /* ─── Refresh button ───────────────────────────────── */
        .refresh-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 8px 20px;
            background: #2980b9;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
        }
        .refresh-btn:hover { background: #1a5c85; }
    </style>
</head>
<body>

<header>
    <h1>🧾 QUBITsale</h1>
    <span>Branch View</span>
</header>

<a href="" class="refresh-btn">🔄 Refresh</a>

<?php if ($fetchError): ?>
    <div class="table-wrap">
        <p class="error-msg">⚠️ <?= htmlspecialchars($fetchError) ?></p>
    </div>

<?php elseif (empty($sales)): ?>

    <!-- ─── Summary Cards (zeroes) ─── -->
    <div class="cards">
        <div class="card"><div class="label">Total Sales</div><div class="value">0</div></div>
        <div class="card"><div class="label">Total Qty</div><div class="value">0</div></div>
        <div class="card"><div class="label">Total Amount</div><div class="value">0.00</div></div>
    </div>

    <div class="table-wrap">
        <p class="no-data">Koi sale record nahi mila.</p>
    </div>

<?php else: ?>

    <!-- ─── Summary Cards ─── -->
    <div class="cards">
        <div class="card">
            <div class="label">Total Sales</div>
            <div class="value"><?= count($sales) ?></div>
        </div>
        <div class="card">
            <div class="label">Total Qty</div>
            <div class="value"><?= number_format($totalQty) ?></div>
        </div>
        <div class="card">
            <div class="label">Total Amount</div>
            <div class="value"><?= number_format($totalPrice, 2) ?></div>
        </div>
    </div>

    <!-- ─── Table ─── -->
    <div class="table-wrap">
        <h2>📋 Sales List (<?= count($sales) ?> records)</h2>
        <div class="scroll">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>ID</th>
                    <th>Product ID</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Cashier</th>
                    <th>Payment</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $i => $s):
                    $pm    = strtolower($s['payment_method'] ?? '');
                    $cls   = in_array($pm, ['cash','card','online']) ? $pm : 'other';
                    $total = ($s['qty'] ?? 0) * ($s['price'] ?? 0);
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($s['id']             ?? '—') ?></td>
                    <td><?= htmlspecialchars($s['product_id']     ?? '—') ?></td>
                    <td><?= htmlspecialchars($s['qty']            ?? '—') ?></td>
                    <td><?= number_format($s['price'] ?? 0, 2) ?></td>
                    <td><?= number_format($total, 2) ?></td>
                    <td><?= htmlspecialchars($s['customer_name']  ?? '—') ?></td>
                    <td><?= htmlspecialchars($s['customer_phone'] ?? '—') ?></td>
                    <td><?= htmlspecialchars(($s['cashier_name'] ?? '—') . ' (' . ($s['cashier_id'] ?? '') . ')') ?></td>
                    <td><span class="badge <?= $cls ?>"><?= htmlspecialchars($s['payment_method'] ?? '—') ?></span></td>
                    <td><?= htmlspecialchars($s['created_at'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3">Total (<?= count($sales) ?> records)</td>
                    <td><?= number_format($totalQty) ?></td>
                    <td>—</td>
                    <td><?= number_format($totalPrice, 2) ?></td>
                    <td colspan="5"></td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>

<?php endif; ?>

</body>
</html>

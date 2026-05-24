<?php
/**
 * sales_api.php
 * Upload karo: https://qubitsale.ct.ws/sales_api.php
 */

define('API_SECRET', 'qubit2026secret');   // branch.php mein bhi yahi likhna hai

// ── Database config (ct.ws ka localhost) ──────────────────────────────────────
define('DB_HOST', 'sql112.infinityfree.com');
define('DB_USER', 'if0_41890867');       // ← cPanel > MySQL se copy karo
define('DB_PASS', 'VqPJfCytFgIcrAA');   // ← cPanel > MySQL se copy karo
define('DB_NAME', 'if0_41890867_Qubitsale');      // ← cPanel > MySQL se copy karo

// ── Token check ───────────────────────────────────────────────────────────────
header('Content-Type: application/json');

$token = $_GET['token'] ?? '';
if ($token !== API_SECRET) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// ── DB connect ────────────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'DB Error: ' . $e->getMessage()]);
    exit;
}

// ── Sales fetch karo ──────────────────────────────────────────────────────────
$stmt = $pdo->query("SELECT * FROM sales ORDER BY created_at DESC");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'data' => $data]);
?>

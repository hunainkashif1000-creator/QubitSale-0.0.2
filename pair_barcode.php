<?php
/**
 * pair_barcode.php
 * ─────────────────────────────────────────────────────────────────────────
 * GET  ?barcode=XXX   → look up barcode in products table
 *                        returns { product_id, name }   if found
 *                        returns { error: "unknown" }   if not found
 *
 * POST body JSON: { barcode: "XXX", product_id: 123 }
 *               → update products SET barcode = 'XXX' WHERE id = 123
 *                        returns { success: true, name: "Product Name" }
 *                        returns { success: false, error: "..." }
 * ─────────────────────────────────────────────────────────────────────────
 */

session_start();
include 'db.php';
$conn->set_charset("utf8mb4");

header('Content-Type: application/json');

// ── GET: barcode lookup ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : '';

    if ($barcode === '') {
        echo json_encode(['error' => 'no_barcode']);
        exit;
    }

    $barcode = $conn->real_escape_string($barcode);
    $row = $conn->query(
        "SELECT id, name FROM products WHERE barcode = '$barcode' LIMIT 1"
    )->fetch_assoc();

    if ($row) {
        echo json_encode(['product_id' => (int)$row['id'], 'name' => $row['name']]);
    } else {
        echo json_encode(['error' => 'unknown']);
    }
    exit;
}

// ── POST: update barcode in DB ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input      = json_decode(file_get_contents('php://input'), true);
    $barcode    = isset($input['barcode'])    ? trim($input['barcode'])      : '';
    $product_id = isset($input['product_id']) ? intval($input['product_id']) : 0;

    // Basic validation
    if ($barcode === '' || $product_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
        exit;
    }

    $barcode = $conn->real_escape_string($barcode);

    // Check: is this barcode already on a DIFFERENT product?
    $existing = $conn->query(
        "SELECT id, name FROM products
         WHERE barcode = '$barcode' AND id != $product_id
         LIMIT 1"
    )->fetch_assoc();

    if ($existing) {
        echo json_encode([
            'success' => false,
            'error'   => 'Barcode already assigned to "' . htmlspecialchars($existing['name']) . '"'
        ]);
        exit;
    }

    // All clear — update the barcode
    $conn->query(
        "UPDATE products SET barcode = '$barcode' WHERE id = $product_id LIMIT 1"
    );

    if ($conn->errno) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
        exit;
    }

    // Return the product name for the success toast
    $product = $conn->query(
        "SELECT name FROM products WHERE id = $product_id LIMIT 1"
    )->fetch_assoc();

    echo json_encode(['success' => true, 'name' => $product['name']]);
    exit;
}

// ── Any other HTTP method ──────────────────────────────────────────────────
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

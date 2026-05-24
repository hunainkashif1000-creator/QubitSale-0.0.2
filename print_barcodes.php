<?php
include 'db.php';
$conn->set_charset("utf8mb4");

$q = $conn->query("SELECT name, TRIM(barcode) as barcode FROM products");
?>
<!DOCTYPE html>
<html>
<head>
<title>Print Barcodes</title>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
}

.print-btn {
    display: block;
    margin: 20px auto;
    padding: 10px 30px;
    font-size: 16px;
    cursor: pointer;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 6px;
}

.barcode-box {
    width: 100%;
    height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    page-break-after: always;
    gap: 12px;
}

.barcode-box h2 {
    font-size: 22px;
}

.barcode-box svg {
    width: 300px;
    height: 130px;
}

@media print {
    .print-btn { display: none; }
    .barcode-box:last-child { page-break-after: auto; }
}
</style>
</head>
<body>

<button class="print-btn" onclick="window.print()">🖨 Print</button>

<?php while ($p = $q->fetch_assoc()):
    $barcode = trim($p['barcode']);
    $name    = htmlspecialchars($p['name']);
?>
    <div class="barcode-box">
        <h2><?= $name ?></h2>
        <svg class="barcode"
             jsbarcode-format="CODE128"
             jsbarcode-value="<?= htmlspecialchars($barcode) ?>"
             jsbarcode-textmargin="0"
             jsbarcode-fontsize="18"
             jsbarcode-margin="10">
        </svg>
    </div>
<?php endwhile; ?>

<script>
    JsBarcode(".barcode").init();
</script>

</body>
</html>
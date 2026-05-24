<?php
include 'db.php';
require_once 'auth.php';
require_once 'roles.php';
allow_roles(['admin']);
ini_set('display_errors',1); error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html>
<head>
<title>Bulk Upload - Stockora</title>
<meta charset="UTF-8">
<?php include 'sidebar.php'; ?>
</head>
<body>
<div class="main-content">
  <div class="page-header">
    <div class="page-title">Bulk Upload</div>
    <div class="page-subtitle">Upload products via CSV file</div>
  </div>
  <div class="content-area">
    <div class="card" style="max-width:500px;">
      <div style="font-size:13px;font-weight:700;color:var(--text-muted);margin-bottom:6px;">CSV FORMAT</div>
      <div style="background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:8px;padding:12px;font-family:monospace;font-size:13px;color:var(--text-muted);margin-bottom:20px;">name, price, stock<br>Product A, 100, 50<br>Product B, 250, 30</div>
      <form method="post" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:12px;">
        <div>
          <label style="font-size:12px;color:var(--text-muted);display:block;margin-bottom:6px;">SELECT CSV FILE</label>
          <input type="file" name="excel_file" accept=".csv" required style="background:rgba(255,255,255,0.04);">
        </div>
        <button type="submit" name="upload" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Products</button>
      </form>
      <?php
      if(isset($_POST['upload'])){
          if(!isset($_FILES['excel_file'])||$_FILES['excel_file']['error']!=0){ echo "<div style='margin-top:12px;padding:12px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;color:#ef4444;'>Please select a valid CSV file.</div>"; }
          else {
              $file=$_FILES['excel_file']['tmp_name']; $inserted=0;
              if(($handle=fopen($file,"r"))!==FALSE){
                  $row=0;
                  while(($data=fgetcsv($handle,1000,","))!==FALSE){
                      if($row==0){$row++;continue;} if(count($data)<3)continue;
                      $name=$conn->real_escape_string(trim($data[0])); $price=$conn->real_escape_string(trim($data[1])); $stock=$conn->real_escape_string(trim($data[2])); $barcode=uniqid();
                      if($conn->query("INSERT INTO products(name,price,stock,barcode) VALUES('$name','$price','$stock','$barcode')")) $inserted++;
                      $row++;
                  }
                  fclose($handle);
                  if($inserted>0) echo "<div style='margin-top:12px;padding:12px;background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:8px;color:#10b981;'><i class=\"fas fa-check\"></i> {$inserted} products uploaded successfully!</div>";
                  else echo "<div style='margin-top:12px;padding:12px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;color:#ef4444;'>No products uploaded.</div>";
              }
          }
      }
      ?>
    </div>
  </div>
</div>
</body>
</html>

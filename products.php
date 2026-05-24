<?php
session_start();
include 'db.php';
require_once 'auth.php';
require_once 'roles.php';
allow_roles(['admin']);
$conn->set_charset("utf8mb4");

// ADD PRODUCT
if(isset($_POST['add'])){
    $name  = $conn->real_escape_string($_POST['name']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $barcode = rand(1000000000, 9999999999);
    $image = "";

    // Handle optional image upload
    if(isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0){
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        if(in_array($_FILES['product_image']['type'], $allowed)){
            $ext      = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $filename = 'prod_' . time() . '_' . rand(100,999) . '.' . $ext;
            $dest     = 'uploads/' . $filename;
            if(move_uploaded_file($_FILES['product_image']['tmp_name'], $dest)){
                $image = $filename;
            }
        }
    }

    $conn->query("INSERT INTO products(name,price,stock,barcode,image) VALUES('$name','$price','$stock','$barcode','$image')");
    header("Location: products.php");
    exit;
}

// DELETE PRODUCT
if(isset($_GET['delete'])){
    // Also delete image file if exists
    $prod = $conn->query("SELECT image FROM products WHERE id=".(int)$_GET['delete'])->fetch_assoc();
    if($prod && !empty($prod['image']) && file_exists('uploads/'.$prod['image'])){
        unlink('uploads/'.$prod['image']);
    }
    $conn->query("DELETE FROM products WHERE id=".(int)$_GET['delete']);
    header("Location: products.php");
    exit;
}

// UPDATE STOCK & PRICE (with optional new image)
if(isset($_POST['update_stock'])){
    $pid   = (int)$_POST['product_id'];
    $ns    = (int)$_POST['new_stock'];
    $np    = (float)$_POST['new_price'];

    if($pid > 0 && $ns >= 0 && $np >= 0){
        // Check if new image uploaded
        if(isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] === 0){
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            if(in_array($_FILES['edit_image']['type'], $allowed)){
                // Delete old image
                $old = $conn->query("SELECT image FROM products WHERE id=$pid")->fetch_assoc();
                if($old && !empty($old['image']) && file_exists('uploads/'.$old['image'])){
                    unlink('uploads/'.$old['image']);
                }
                $ext      = pathinfo($_FILES['edit_image']['name'], PATHINFO_EXTENSION);
                $filename = 'prod_' . time() . '_' . rand(100,999) . '.' . $ext;
                $dest     = 'uploads/' . $filename;
                if(move_uploaded_file($_FILES['edit_image']['tmp_name'], $dest)){
                    $conn->query("UPDATE products SET stock=$ns, price=$np, image='$filename' WHERE id=$pid");
                    header("Location: products.php");
                    exit;
                }
            }
        }
        $conn->query("UPDATE products SET stock=$ns, price=$np WHERE id=$pid");
    }
    header("Location: products.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Products - Stockora</title>
<meta charset="UTF-8">
<?php include 'sidebar.php'; ?>
<style>
.two-panel{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;}
@media(max-width:700px){.two-panel{grid-template-columns:1fr;}}
.panel-title{font-size:13px;font-weight:700;color:var(--text-muted);letter-spacing:0.5px;margin-bottom:14px;}
.form-row{display:flex;flex-direction:column;gap:10px;}

/* Image Upload Area */
.img-upload-wrap {
    position: relative;
}
.img-upload-label {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: rgba(255,255,255,0.04);
    border: 1px dashed var(--border);
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    color: var(--text-muted);
    transition: border-color .2s, color .2s;
}
.img-upload-label:hover { border-color: var(--accent); color: var(--text); }
.img-upload-label i { font-size: 16px; color: var(--accent); }
.img-upload-label input[type="file"] { display: none; }
.img-preview {
    width: 100%;
    max-height: 120px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid var(--border);
    display: none;
    margin-top: 8px;
}

/* Product Grid */
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:14px;}
.product-card{background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden;transition:border-color 0.2s;}
.product-card:hover{border-color:var(--accent);}
.product-card-img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    background: rgba(255,255,255,0.04);
    display: block;
}
.product-card-img-placeholder {
    width: 100%;
    height: 120px;
    background: rgba(255,255,255,0.03);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    color: var(--border);
}
.product-card-body { padding: 12px 14px; text-align: center; }
.product-card strong{display:block;font-size:14px;font-weight:700;margin-bottom:4px;}
.product-card .price{color:var(--accent2);font-weight:700;font-size:14px;}
.product-card .stk{color:var(--text-muted);font-size:12px;margin-bottom:12px;}
.product-card .card-btns{display:flex;flex-direction:column;gap:6px;}
.product-card .card-btns button{width:100%;padding:7px;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif;}
.btn-barcode-sm{background:rgba(255,255,255,0.06);color:var(--text-muted);}
.btn-del-sm{background:rgba(239,68,68,0.12);color:var(--danger);border:1px solid rgba(239,68,68,0.2)!important;}
</style>
</head>
<body>
<div class="main-content">
  <div class="page-header">
    <div class="page-title">Products</div>
    <div class="page-subtitle">Add, edit, and manage products</div>
  </div>
  <div class="content-area">
    <div class="two-panel">

      <!-- ADD PRODUCT -->
      <div class="card">
        <div class="panel-title">ADD NEW PRODUCT</div>
        <form method="post" enctype="multipart/form-data" class="form-row">
          <input name="name" placeholder="Product Name" required>
          <input name="price" placeholder="Price (Rs)" type="number" step="0.01" required>
          <input name="stock" placeholder="Stock Quantity" type="number" required>

          <!-- Optional Image -->
          <div class="img-upload-wrap">
            <label class="img-upload-label">
              <i class="fas fa-image"></i>
              <span id="addImgLabel">Product Image (optional)</span>
              <input type="file" name="product_image" accept="image/*" onchange="previewImg(this,'addPreview','addImgLabel')">
            </label>
            <img id="addPreview" class="img-preview">
          </div>

          <button type="submit" name="add" class="btn btn-success"><i class="fas fa-plus"></i> Add Product</button>
        </form>
      </div>

      <!-- EDIT STOCK -->
      <div class="card">
        <div class="panel-title">EDIT STOCK & PRICE</div>
        <form method="POST" enctype="multipart/form-data" class="form-row" id="editStockForm">
          <input type="hidden" name="product_id" id="productSelectHidden">

          <div style="position:relative;" id="editDropWrap">
            <input type="text" id="editSearchInput" placeholder="🔍 Search product..." autocomplete="off">
            <div id="editDropdown" style="display:none;position:absolute;top:calc(100% + 4px);left:0;width:100%;max-height:220px;overflow-y:auto;background:var(--card);border:1px solid var(--accent);border-radius:8px;z-index:999;box-shadow:0 8px 24px rgba(0,0,0,0.3);">
              <?php
              $editProducts = $conn->query("SELECT id, name, stock, price FROM products ORDER BY name ASC");
              if($editProducts && $editProducts->num_rows > 0):
                while($row = $editProducts->fetch_assoc()):
              ?>
              <div class="edit-opt"
                data-id="<?php echo $row['id']; ?>"
                data-stock="<?php echo $row['stock']; ?>"
                data-price="<?php echo $row['price']; ?>"
                data-name="<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>"
                style="padding:10px 14px;cursor:pointer;font-size:13px;color:var(--text);border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                <span><?php echo htmlspecialchars($row['name']); ?></span>
                <span style="color:var(--accent2);font-size:12px;font-weight:600;">Rs <?php echo $row['price']; ?> &nbsp;<span style="color:var(--text-muted);">Stk:<?php echo $row['stock']; ?></span></span>
              </div>
              <?php endwhile; endif; ?>
            </div>
          </div>

          <div id="selectedBadge" style="display:none;background:rgba(59,130,246,0.1);border:1px solid var(--accent);border-radius:8px;padding:8px 14px;font-size:13px;color:var(--accent);font-weight:600;"></div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div><label style="font-size:12px;color:var(--text-muted);display:block;margin-bottom:5px;">New Stock</label><input type="number" name="new_stock" id="newStock" placeholder="0" required></div>
            <div><label style="font-size:12px;color:var(--text-muted);display:block;margin-bottom:5px;">New Price</label><input type="number" step="0.01" name="new_price" id="newPrice" placeholder="0.00" required></div>
          </div>

          <!-- Optional new image for edit -->
          <div class="img-upload-wrap">
            <label class="img-upload-label">
              <i class="fas fa-image"></i>
              <span id="editImgLabel">Change Image (optional)</span>
              <input type="file" name="edit_image" accept="image/*" onchange="previewImg(this,'editPreview','editImgLabel')">
            </label>
            <img id="editPreview" class="img-preview">
          </div>

          <button type="submit" name="update_stock" class="btn btn-primary"><i class="fas fa-save"></i> Update Stock & Price</button>
        </form>
      </div>
    </div>

    <!-- PRODUCT LIST -->
    <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:14px;">All Products</div>
    <div class="product-grid">
    <?php
    $allProducts = $conn->query("SELECT * FROM products ORDER BY id DESC");
    while($r = $allProducts->fetch_assoc()):
    ?>
    <div class="product-card">
      <!-- Image or Placeholder -->
      <?php if(!empty($r['image']) && file_exists('uploads/'.$r['image'])): ?>
        <img class="product-card-img" src="uploads/<?php echo htmlspecialchars($r['image']); ?>" alt="<?php echo htmlspecialchars($r['name']); ?>">
      <?php else: ?>
        <div class="product-card-img-placeholder">📦</div>
      <?php endif; ?>

      <div class="product-card-body">
        <strong><?php echo htmlspecialchars($r['name']); ?></strong>
        <div class="price">Rs <?php echo number_format($r['price'],0); ?></div>
        <div class="stk">Stock: <?php echo $r['stock']; ?></div>
        <div class="card-btns">
          <button class="btn-barcode-sm" onclick="printBarcode('<?php echo $r['barcode']; ?>')">🖨 Print Barcode</button>
          <button class="btn-del-sm" onclick="if(confirm('Delete this product?'))location.href='?delete=<?php echo $r['id']; ?>'">🗑 Delete</button>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
    </div>

    <div style="text-align:center;padding:20px 0 0;color:var(--text-muted);font-size:12px;"><a href="https://hexora.great-site.net" style="color:var(--text-muted);">Powered by Hexora</a></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
// Image preview function
function previewImg(input, previewId, labelId){
    const preview = document.getElementById(previewId);
    const label   = document.getElementById(labelId);
    if(input.files && input.files[0]){
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
            label.textContent = input.files[0].name;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function printBarcode(code){
    const w = window.open("","","width=300,height=180");
    w.document.write('<html><body style="text-align:center"><svg id="b"></svg><script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script><script>JsBarcode("#b","'+code+'",{width:2,height:60,displayValue:false});setTimeout(()=>window.print(),500);<\/script></body></html>');
}

// Edit dropdown logic
const editInput    = document.getElementById('editSearchInput');
const editDropdown = document.getElementById('editDropdown');
const editHidden   = document.getElementById('productSelectHidden');
const editBadge    = document.getElementById('selectedBadge');

editInput.addEventListener('focus', () => { filterEditDropdown(''); editDropdown.style.display = 'block'; });
editInput.addEventListener('input', () => { filterEditDropdown(editInput.value); editDropdown.style.display = 'block'; editHidden.value = ''; editBadge.style.display = 'none'; });
editInput.addEventListener('blur', () => { setTimeout(() => { editDropdown.style.display = 'none'; }, 180); });

function filterEditDropdown(val){
    val = val.toLowerCase();
    editDropdown.querySelectorAll('.edit-opt').forEach(opt => {
        opt.style.display = opt.dataset.name.toLowerCase().includes(val) ? '' : 'none';
    });
}

editDropdown.querySelectorAll('.edit-opt').forEach(opt => {
    opt.addEventListener('mousedown', function(e){
        e.preventDefault();
        editHidden.value = this.dataset.id;
        document.getElementById('newStock').value = this.dataset.stock;
        document.getElementById('newPrice').value  = this.dataset.price;
        editInput.value = this.dataset.name;
        editBadge.textContent = '✓ ' + this.dataset.name + ' — Stock: ' + this.dataset.stock + ' | Price: Rs ' + this.dataset.price;
        editBadge.style.display = 'block';
        editDropdown.style.display = 'none';
    });
    opt.addEventListener('mouseover', function(){ this.style.background = 'rgba(59,130,246,0.12)'; });
    opt.addEventListener('mouseout',  function(){ this.style.background = ''; });
});

document.getElementById('editStockForm').addEventListener('submit', function(e){
    if(!editHidden.value){
        e.preventDefault();
        editInput.style.borderColor = '#ef4444';
        editInput.placeholder = '⚠️ Please select a product first!';
        setTimeout(() => { editInput.style.borderColor = ''; editInput.placeholder = '🔍 Search product...'; }, 2000);
    }
});
</script>
</body>
</html>
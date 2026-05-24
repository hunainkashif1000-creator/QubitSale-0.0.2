<?php
session_start();
include 'db.php';
require_once 'auth.php';
require_once 'roles.php';
$conn->set_charset("utf8mb4");
date_default_timezone_set('Asia/Karachi');
$conn->query("SET time_zone = '+05:00'");

if(isset($_POST['add_expense'])){
    $description=$conn->real_escape_string($_POST['description']);
    $amount=(float)$_POST['amount'];
    $created_at=date('Y-m-d H:i:s');
    if($description && $amount > 0){
        $conn->query("INSERT INTO expenses (description,amount,created_at) VALUES ('$description',$amount,'$created_at')");
        echo "ok";
        exit;
    } else {
        echo "error";
        exit;
    }
}

if(isset($_POST['delete_expense'])){
    if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
        echo "unauthorized";
        exit;
    }
    $id=(int)$_POST['id'];
    $conn->query("DELETE FROM expenses WHERE id=$id");
    echo "ok";
    exit;
}

if(isset($_GET['load_expenses'])){
    $total=0;
    $res=$conn->query("SELECT * FROM expenses ORDER BY created_at DESC");
    if($res->num_rows > 0){
        while($row=$res->fetch_assoc()){
            $total += $row['amount'];
            echo "<tr>
                <td>{$row['id']}</td>
                <td>".htmlspecialchars($row['description'])."</td>
                <td style='color:#10b981;font-weight:700;'>Rs ".number_format($row['amount'],2)."</td>
                <td style='color:#94a3b8;font-size:12px;'>".date('d-m-Y h:i A',strtotime($row['created_at']))."</td>
                <td>";
            if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin')
                echo "<button class='delete-btn' data-id='{$row['id']}'>🗑 Delete</button>";
            else
                echo "-";
            echo "</td></tr>";
        }
        echo "<tr>
            <td colspan='2' style='font-weight:700;'>Total Expenses</td>
            <td colspan='3' style='font-weight:800;color:#ef4444;font-size:15px;'>Rs ".number_format($total,2)."</td>
        </tr>";
    } else {
        echo "<tr><td colspan='5' style='text-align:center;padding:30px;color:#94a3b8;'>No expenses found</td></tr>";
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Expenses - Stockora</title>
<meta charset="UTF-8">
<?php include 'sidebar.php'; ?>
<style>
.delete-btn{background:rgba(239,68,68,0.15);color:#ef4444;border:1px solid rgba(239,68,68,0.2);padding:5px 10px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;font-family:'Plus Jakarta Sans',sans-serif;}
.delete-btn:hover{background:rgba(239,68,68,0.25);}
.add-form{display:grid;grid-template-columns:1fr 200px auto;gap:12px;align-items:end;}
@media(max-width:600px){.add-form{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="main-content">
  <div class="page-header">
    <div class="page-title">Expense Management</div>
    <div class="page-subtitle">Track and manage your business expenses</div>
  </div>
  <div class="content-area">
    <div class="card" style="margin-bottom:20px;">
      <div style="font-size:13px;font-weight:700;color:var(--text-muted);margin-bottom:14px;">ADD EXPENSE</div>
      <div class="add-form">
        <div><label style="font-size:12px;color:var(--text-muted);margin-bottom:6px;display:block;">Description</label><input type="text" id="exp_description" placeholder="Expense description..."></div>
        <div><label style="font-size:12px;color:var(--text-muted);margin-bottom:6px;display:block;">Amount (Rs)</label><input type="number" id="exp_amount" placeholder="0.00" min="0" step="0.01"></div>
        <div style="padding-bottom:0;"><button class="btn btn-success" onclick="addExpense()" style="height:42px;"><i class="fas fa-plus"></i> Add</button></div>
      </div>
    </div>
    <div class="card">
      <table id="expensesTable">
        <thead><tr><th>ID</th><th>Description</th><th>Amount</th><th>Date & Time</th><th>Action</th></tr></thead>
        <tbody><tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted);">Loading...</td></tr></tbody>
      </table>
    </div>
  </div>
</div>
<script>
function loadExpenses(){
  fetch("expenses.php?load_expenses=1")
    .then(r => r.text())
    .then(html => {
      document.querySelector("#expensesTable tbody").innerHTML = html;
      attachDeleteEvents();
    });
}

function addExpense(){
  let d = document.getElementById('exp_description').value.trim();
  let a = document.getElementById('exp_amount').value;
  if(!d || !a) return alert("Description and amount are required.");
  fetch("expenses.php", {
    method: "POST",
    headers: {"Content-Type": "application/x-www-form-urlencoded"},
    body: "add_expense=1&description=" + encodeURIComponent(d) + "&amount=" + encodeURIComponent(a)
  })
  .then(r => r.text())
  .then(res => {
    if(res.trim() === 'ok'){
      document.getElementById('exp_description').value = '';
      document.getElementById('exp_amount').value = '';
      loadExpenses();
    } else {
      alert("Failed to add expense. Please try again.");
      console.error("Server response:", res);
    }
  })
  .catch(err => {
    alert("Network error. Please try again.");
    console.error(err);
  });
}

function attachDeleteEvents(){
  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function(){
      if(!confirm("Delete this expense?")) return;
      fetch("expenses.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "delete_expense=1&id=" + this.dataset.id
      })
      .then(r => r.text())
      .then(res => {
        if(res.trim() === 'ok'){
          loadExpenses();
        } else {
          alert("Failed to delete expense.");
          console.error("Server response:", res);
        }
      })
      .catch(err => {
        alert("Network error. Please try again.");
        console.error(err);
      });
    });
  });
}

window.onload = loadExpenses;
</script>
</body>
</html>
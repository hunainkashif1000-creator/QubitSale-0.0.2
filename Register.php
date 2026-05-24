<?php
require_once 'auth.php';
require_once 'roles.php';
include 'db.php';
$message=""; $success=false;
if($_SERVER["REQUEST_METHOD"]=="POST"){
    $username=trim($_POST['username']); $password=$_POST['password'];
    if($username==""||$password=="") $message="All fields are required!";
    else {
        $check=$conn->prepare("SELECT id FROM users WHERE user=?"); $check->bind_param("s",$username); $check->execute(); $result=$check->get_result();
        if($result->num_rows>0) $message="Username already exists!";
        else {
            $hashed=password_hash($password,PASSWORD_DEFAULT);
            $stmt=$conn->prepare("INSERT INTO users (user,pass) VALUES (?,?)"); $stmt->bind_param("ss",$username,$hashed);
            if($stmt->execute()){ $message="User registered successfully!"; $success=true; } else $message="Error: ".$stmt->error;
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Register User - Stockora</title>
<meta charset="UTF-8">
<?php include 'sidebar.php'; ?>
</head>
<body>
<div class="main-content">
  <div class="page-header">
    <div class="page-title">Register New User</div>
    <div class="page-subtitle">Create a new system user</div>
  </div>
  <div class="content-area">
    <div class="card" style="max-width:400px;">
      <?php if($message!=""): ?>
      <div style="padding:12px 16px;border-radius:8px;margin-bottom:16px;<?php echo $success?'background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);color:#10b981;':'background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#ef4444;'; ?>">
        <?php echo $message; ?>
      </div>
      <?php endif; ?>
      <div style="font-size:13px;font-weight:700;color:var(--text-muted);margin-bottom:16px;">USER DETAILS</div>
      <form method="post" style="display:flex;flex-direction:column;gap:12px;">
        <div>
          <label style="font-size:12px;color:var(--text-muted);display:block;margin-bottom:6px;">Username</label>
          <input type="text" name="username" placeholder="Enter username" required>
        </div>
        <div>
          <label style="font-size:12px;color:var(--text-muted);display:block;margin-bottom:6px;">Password</label>
          <input type="password" name="password" placeholder="Enter password" required>
        </div>
        <button type="submit" class="btn btn-success"><i class="fas fa-user-plus"></i> Create User</button>
      </form>
      <div style="margin-top:14px;">
        <a href="user_list.php" style="color:var(--text-muted);font-size:13px;text-decoration:none;"><i class="fas fa-arrow-left"></i> Back to Users</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>

<?php
require_once 'auth.php';
require_once 'roles.php';
allow_roles(['admin']);
ob_start();
include 'db.php';

if(isset($_GET['delete_id'])){
    $id=$_GET['delete_id'];
    $del=$conn->prepare("DELETE FROM users WHERE id=?");
    $del->bind_param("i",$id);
    if($del->execute()){ header("Location: user_list.php?msg=deleted"); exit(); }
}

$query="SELECT id, user, role FROM users ORDER BY id DESC";
$result=$conn->query($query);
?>
<!DOCTYPE html>
<html>
<head>
<title>Users - Stockora</title>
<meta charset="UTF-8">
<?php include 'sidebar.php'; ?>
<style>
.role-badge{padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;}
.role-admin{background:rgba(59,130,246,0.15);color:#3b82f6;}
.role-cashier{background:rgba(16,185,129,0.15);color:#10b981;}
.role-viewer{background:rgba(148,163,184,0.15);color:#94a3b8;}
.actions{display:flex;gap:8px;align-items:center;}
select{width:auto;padding:6px 10px;font-size:12px;}
</style>
</head>
<body>
<div class="main-content">
  <div class="page-header">
    <div class="page-title">User Management</div>
    <div class="page-subtitle">Manage system users and their roles</div>
  </div>
  <div class="content-area">
    <?php if(isset($_GET['msg'])&&$_GET['msg']=='deleted'): ?>
    <div style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);color:#10b981;padding:12px 16px;border-radius:8px;margin-bottom:16px;">✅ User deleted successfully.</div>
    <?php endif; ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
      <div style="font-size:14px;font-weight:700;">Registered Users</div>
      <a href="Register.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add User</a>
    </div>
    <div class="card">
    <table>
      <thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if($result&&$result->num_rows>0): while($row=$result->fetch_assoc()): ?>
      <tr>
        <td style="color:var(--text-muted);"><?php echo $row['id']; ?></td>
        <td style="font-weight:600;"><?php echo htmlspecialchars($row['user']); ?></td>
        <td>
          <form method="post" action="update_role.php">
            <select name="role" onchange="this.form.submit()" style="background:var(--bg2);border:1px solid var(--border);color:var(--text);border-radius:6px;">
              <option value="admin" <?php echo $row['role']=='admin'?'selected':''; ?>>Admin</option>
              <option value="cashier" <?php echo $row['role']=='cashier'?'selected':''; ?>>Cashier</option>
              <option value="viewer" <?php echo $row['role']=='viewer'?'selected':''; ?>>Viewer</option>
            </select>
            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
          </form>
        </td>
        <td>
          <a href="user_list.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger" style="font-size:12px;padding:6px 12px;text-decoration:none;" onclick="return confirm('Delete this user?')">
            <i class="fas fa-trash"></i> Delete
          </a>
        </td>
      </tr>
      <?php endwhile; else: ?>
      <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--text-muted);">No users found</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>
</body>
</html>

<?php
session_start();
require 'db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uname = trim($_POST['username'] ?? '');
    $pass  = trim($_POST['password'] ?? '');
    if ($uname && $pass) {
        $stmt = $conn->prepare("SELECT id,username,password,role,unit,full_name FROM users WHERE username=? AND role='admin'");
        $stmt->bind_param('s', $uname);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = 'admin';
            $_SESSION['unit']      = $user['unit'];
            $_SESSION['full_name'] = $user['full_name'];
            header('Location: gsd_docs.php');
            exit;
        } else {
            $error = 'Invalid admin credentials. Please try again.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Login — Document Portal Demo</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="wrap">
  <div class="login-wrap">
    <div class="login-card">
      <div class="lc-head" style="background:linear-gradient(135deg,#4a0e0e,var(--red));">
        <div class="lc-icon">🛡️</div>
        <h3>Admin Login</h3>
        <p>Department administrators only / केवल विभाग एडमिन</p>
      </div>
      <div class="lc-body">
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <div class="alert alert-info" style="font-size:12px;">ℹ️ Each admin manages documents only for their assigned unit.</div>
        <form method="POST">
          <div class="form-group">
            <label>Admin Username</label>
            <input type="text" name="username" placeholder="Enter admin username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autocomplete="username">
          </div>
          <div class="form-group">
            <label>Password</label>
            <div style="position:relative;">
              <input type="password" name="password" id="password" placeholder="Enter password" required autocomplete="current-password" style="width:100%;padding-right:40px;">
              <span onclick="togglePassword()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:16px;user-select:none;" id="eyeIcon">👁️</span>
            </div>
          </div>
          <button type="submit" class="btn-primary" style="background:linear-gradient(135deg,#4a0e0e,var(--red));">Login as Admin</button>
        </form>
        <a href="index.php" class="btn-secondary">← Back to Home</a>
      </div>
    </div>
  </div>
</div>
<script>
function togglePassword() {
  var pwd = document.getElementById('password');
  var icon = document.getElementById('eyeIcon');
  if (pwd.type === 'password') {
    pwd.type = 'text';
    icon.textContent = '🙈';
  } else {
    pwd.type = 'password';
    icon.textContent = '👁️';
  }
}
</script>
</body>
</html>

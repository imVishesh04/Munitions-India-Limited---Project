<?php
session_start();
if (isset($_SESSION['user_id'])) {
    // Already logged in → go to GSD docs (role-aware)
    header('Location: gsd_docs.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Document Portal Demo — Document Portal</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="wrap">
  <div class="role-wrap">
    <h2>Document Management Portal</h2>
    <p>Please select your login type to continue / कृपया लॉगिन प्रकार चुनें</p>
    <div class="role-cards">
      <a href="admin_login.php" class="role-card">
        <div class="rc-icon">🛡️</div>
        <h3>Admin</h3>
        <p>Department-wise document management &amp; upload</p>
      </a>
      <a href="user_login.php" class="role-card">
        <div class="rc-icon">👤</div>
        <h3>User</h3>
        <p>View &amp; access all documents</p>
      </a>
    </div>
  </div>
</div>
</body>
</html>

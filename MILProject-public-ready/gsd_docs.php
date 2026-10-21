<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
require 'db.php';

$role       = $_SESSION['role'];
$admin_unit = $_SESSION['unit'] ?? null;
$message    = '';
$msg_type   = '';

// Handle delete
if ($role === 'admin' && isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $doc_id = (int)$_GET['delete'];
    $chk = $conn->prepare("SELECT filename, unit FROM documents WHERE id=?");
    $chk->bind_param('i', $doc_id); $chk->execute();
    $doc = $chk->get_result()->fetch_assoc(); $chk->close();
    if ($doc && $doc['unit'] === $admin_unit) {
        $fp = __DIR__ . '/uploads/' . $doc['filename'];
        if (file_exists($fp)) unlink($fp);
        $del = $conn->prepare("DELETE FROM documents WHERE id=?");
        $del->bind_param('i', $doc_id); $del->execute(); $del->close();
        $message = '✅ Document deleted successfully.'; $msg_type = 'success';
    } else {
        $message = '❌ Permission denied.'; $msg_type = 'error';
    }
}

$view      = $_GET['view'] ?? 'folders';
$open_unit = trim($_GET['unit'] ?? '');

// All units
$all_units = [
    'DEPT-01'             => ['icon'=>'🏭','color'=>'#1a6b9a'],
    'DEPT-02'             => ['icon'=>'🏭','color'=>'#1a6b9a'],
    'DEPT-03'             => ['icon'=>'🏭','color'=>'#1a6b9a'],
    'DEPT-04'             => ['icon'=>'🏭','color'=>'#1a6b9a'],
    'DEPT-05'             => ['icon'=>'🏭','color'=>'#1a6b9a'],
    'QUALITY-01'          => ['icon'=>'📋','color'=>'#1a6b3c'],
    'SAFETY-01'          => ['icon'=>'🦺','color'=>'#8a6b1a'],
    'STORES-01'           => ['icon'=>'🗄️','color'=>'#1a3a6e'],
    'ARCHIVE-01'             => ['icon'=>'📁','color'=>'#4a4a8a'],
    'ARCHIVE-02'             => ['icon'=>'📁','color'=>'#4a4a8a'],
    'LAB-01'              => ['icon'=>'🔬','color'=>'#6b1a8a'],
    'STORES-02'      => ['icon'=>'📦','color'=>'#8a5a1a'],
    
];

// Doc counts
$counts = [];
$res = $conn->query("SELECT unit, COUNT(*) as c FROM documents GROUP BY unit");
while ($row = $res->fetch_assoc()) $counts[$row['unit']] = (int)$row['c'];

// Unit docs
$unit_docs = [];
if ($view === 'unit_docs' && $open_unit) {
    $stmt = $conn->prepare("SELECT id, subject, original_name, filename, upload_date FROM documents WHERE unit=? ORDER BY upload_date DESC");
    $stmt->bind_param('s', $open_unit); $stmt->execute();
    $unit_docs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
}

if ($view === 'unit_docs') $breadcrumb = [['Home','gsd_docs.php'],[$open_unit,'']];
else $breadcrumb = [['Home','index.php'],['Department Documents','']];
?>
<!DOCTYPE html>
<html lang="hi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $view==='unit_docs' ? htmlspecialchars($open_unit) : 'Department Documents' ?> — Document Portal Demo</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="wrap">

<?php if ($message): ?>
  <div class="alert alert-<?= $msg_type ?>"><?= $message ?></div>
<?php endif; ?>

<!-- TOPBAR -->
<div class="dash-topbar">
  <div class="dash-topbar-left">
    <?php if ($view === 'unit_docs'): ?>
      <h2>📁 <?= htmlspecialchars($open_unit) ?></h2>
      <p><?= count($unit_docs) ?> document<?= count($unit_docs)!=1?'s':'' ?> in this unit</p>
    <?php else: ?>
      <h2>📂 Department Documents</h2>
      <p>Welcome, <strong><?= htmlspecialchars($_SESSION['full_name'] ?: $_SESSION['username']) ?></strong></p>
    <?php endif; ?>
  </div>
  <div class="dash-topbar-right">
    <?php if ($role === 'admin'): ?>
      <span class="badge-admin">🛡️ Admin: <?= htmlspecialchars($admin_unit) ?></span>
    <?php endif; ?>
    <?php if ($view === 'unit_docs'): ?>
      <?php if ($role==='admin' && $admin_unit===$open_unit): ?>
        <a href="upload.php" class="tbtn tbtn-green">+ Upload</a>
      <?php endif; ?>
      <a href="gsd_docs.php" class="tbtn tbtn-outline">← Back</a>
    <?php else: ?>
      <?php if ($role === 'admin'): ?>
        <a href="upload.php" class="tbtn tbtn-green">+ Upload Document</a>
      <?php endif; ?>
    <?php endif; ?>
    <a href="logout.php" class="tbtn tbtn-red">Logout</a>
  </div>
</div>

<!-- FOLDER GRID -->
<?php if ($view === 'folders'): ?>
  <div class="section-title">Select a Department / Folder</div>
  <div class="doc-grid">
    <?php foreach ($all_units as $unit => $info):
      $count   = $counts[$unit] ?? 0;
      $is_mine = ($role==='admin' && $admin_unit===$unit);
    ?>
    <a href="gsd_docs.php?view=unit_docs&unit=<?= urlencode($unit) ?>"
       class="doc-card <?= $is_mine ? 'mine' : '' ?>">
      <?php if ($is_mine): ?>
        <span class="upload-dot" title="Your unit — click to upload">+</span>
      <?php endif; ?>
      <span class="dc-icon"><?= $info['icon'] ?></span>
      <div class="dc-name" style="color:<?= $info['color'] ?>;"><?= htmlspecialchars($unit) ?></div>
      <div class="dc-count"><?= $count ?> doc<?= $count!=1?'s':'' ?></div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php if ($role === 'admin'): ?>
    <div class="notice-box">
      ⚠️ As admin of <strong><?= htmlspecialchars($admin_unit) ?></strong>, you can upload or delete documents only within your assigned department.
    </div>
  <?php endif; ?>

<!-- UNIT DOCS -->
<?php elseif ($view === 'unit_docs'): ?>
  <?php if (empty($unit_docs)): ?>
    <div class="empty-state">
      <div class="es-icon">📭</div>
      <p>No documents uploaded yet for <strong><?= htmlspecialchars($open_unit) ?></strong>.</p>
      <?php if ($role==='admin' && $admin_unit===$open_unit): ?>
        <a href="upload.php" class="tbtn tbtn-green" style="margin-top:14px;display:inline-block;">+ Upload First Document</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="table-card">
      <table>
        <thead>
          <tr><th>#</th><th>Subject / Title</th><th>File Name</th><th>Date</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php foreach ($unit_docs as $i => $doc): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td>📄 <?= htmlspecialchars($doc['subject']) ?></td>
            <td class="fn"><?= htmlspecialchars($doc['original_name']) ?></td>
            <td><?= ($doc['upload_date'] && $doc['upload_date'] !== '0000-00-00') ? date('d/m/Y', strtotime($doc['upload_date'])) : '-' ?></td>
            <td>
              <a href="uploads/<?= htmlspecialchars($doc['filename']) ?>" target="_blank" class="t-vbtn">View</a>
              <?php if ($role==='admin' && $admin_unit===$open_unit): ?>
                <button class="t-dbtn" onclick="confirmDelete(<?= $doc['id'] ?>,'<?= addslashes($doc['subject']) ?>')">Delete</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
<?php endif; ?>

</div>
<div id="toast"></div>
<script>
function confirmDelete(id, s) {
  if (confirm('Delete:\n"'+s+'"\n\nCannot be undone.'))
    window.location.href='gsd_docs.php?view=unit_docs&unit=<?= urlencode($open_unit) ?>&delete='+id;
}
<?php if ($message && $msg_type==='success'): ?>
window.onload=function(){showToast('<?= addslashes($message) ?>');}
<?php endif; ?>
function showToast(m){var t=document.getElementById('toast');t.textContent=m;t.classList.add('show');setTimeout(function(){t.classList.remove('show');},3500);}
</script>
</body>
</html>

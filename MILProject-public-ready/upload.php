<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php'); exit;
}
require 'db.php';

$admin_unit = $_SESSION['unit'];
$message    = '';
$msg_type   = '';
$uploaded   = false;

$all_units = [
    'QUALITY-01','SAFETY-01','STORES-01',
    'ARCHIVE-01','ARCHIVE-02','LAB-01','STORES-02',
    'DEPT-01','DEPT-02','DEPT-03','DEPT-04','DEPT-05',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $day   = str_pad((int)($_POST['upload_day']   ?? 0), 2, '0', STR_PAD_LEFT);
    $month = str_pad((int)($_POST['upload_month'] ?? 0), 2, '0', STR_PAD_LEFT);
    $year  = (int)($_POST['upload_year'] ?? 0);

    // Always build as YYYY-MM-DD — correct for DB and for comparison
    $upload_date = ($day && $month && $year) ? "$year-$month-$day" : '';

    $selected_unit = trim($_POST['unit']    ?? '');
    $subject       = trim($_POST['subject'] ?? '');

    // ── Validation — all checks in one if/elseif chain ──────────────
    $today_ymd = date('Y-m-d');

    if (!$upload_date) {
        $message  = '❌ Please select a valid date.';
        $msg_type = 'error';

    } elseif ($upload_date > $today_ymd) {
        $message  = '❌ Upload date cannot be a future date. Today is ' . date('d/m/Y') . '.';
        $msg_type = 'error';

    } elseif ($selected_unit !== $admin_unit) {
        $message  = '❌ Access Denied: You can only upload to your assigned department (' . htmlspecialchars($admin_unit) . ').';
        $msg_type = 'error';

    } elseif (!$subject) {
        $message  = '❌ Please enter document subject / title.';
        $msg_type = 'error';

    } elseif (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        $message  = '❌ Please select a PDF file to upload.';
        $msg_type = 'error';

    } else {
        $file      = $_FILES['document'];
        $orig_name = basename($file['name']);
        $ext       = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

        if ($ext !== 'pdf') {
            $message  = '❌ Only PDF files (.pdf) are allowed.';
            $msg_type = 'error';

        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $mb       = round($file['size'] / 1024 / 1024, 2);
            $message  = "❌ File too large ({$mb}MB). Maximum allowed is 2MB.";
            $msg_type = 'error';

        } else {
            // ── Save file ────────────────────────────────────────────
            $unit_folder = strtolower(preg_replace('/[^a-z0-9]/i', '_', $admin_unit));
            $new_file    = 'doc_' . $unit_folder . '_' . time() . '.pdf';
            $dir         = __DIR__ . '/uploads/' . $unit_folder . '/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $stored_path = $unit_folder . '/' . $new_file;

            if (move_uploaded_file($file['tmp_name'], $dir . $new_file)) {
                $stmt = $conn->prepare(
                    "INSERT INTO documents (unit,folder,subject,filename,original_name,upload_date,uploaded_by)
                     VALUES (?,?,?,?,?,?,?)"
                );
                $stmt->bind_param(
                    'ssssssi',
                    $admin_unit, $admin_unit, $subject,
                    $stored_path, $orig_name,
                    $upload_date,               // YYYY-MM-DD — correct for MySQL DATE column
                    $_SESSION['user_id']
                );
                if ($stmt->execute()) {
                    $uploaded = true;
                    $message  = '✅ Document "' . htmlspecialchars($subject) . '" uploaded successfully!';
                    $msg_type = 'success';
                } else {
                    $message  = '❌ Database error. Please try again.';
                    $msg_type = 'error';
                }
                $stmt->close();
            } else {
                $message  = '❌ File move failed. Check folder permissions on /uploads/';
                $msg_type = 'error';
            }
        }
    }
}

$breadcrumb = [
    ['Home','gsd_docs.php'],
    ['Upload Document','']
];
?>
<!DOCTYPE html>
<html lang="hi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Upload Document — Document Portal Demo</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="wrap">

  <div class="dash-topbar" style="margin-bottom:22px;">
    <div class="dash-topbar-left">
      <h2>📤 Upload Document</h2>
      <p>Add a new document to your unit's repository</p>
    </div>
    <div class="dash-topbar-right">
      <span class="badge-admin">🛡️ Admin: <?= htmlspecialchars($admin_unit) ?></span>
      <a href="logout.php" class="tbtn tbtn-red">Logout</a>
    </div>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $msg_type ?>"><?= $message ?></div>
  <?php endif; ?>

  <?php if ($uploaded): ?>
    <div style="text-align:center;padding:30px 20px;">
      <div style="font-size:60px;margin-bottom:16px;">✅</div>
      <h3 style="color:var(--green);font-size:20px;margin-bottom:8px;">Upload Successful!</h3>
      <p style="color:var(--muted);margin-bottom:24px;">
        The document has been uploaded to <strong><?= htmlspecialchars($admin_unit) ?></strong> and is now visible to all users.
      </p>
      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="upload.php" class="btn-primary" style="background:var(--green);max-width:200px;">Upload Another</a>
        <a href="gsd_docs.php?view=unit_docs&unit=<?= urlencode($admin_unit) ?>" class="btn-secondary" style="max-width:200px;">View My Documents</a>
        <a href="gsd_docs.php" class="btn-secondary" style="max-width:200px;">🏠 Home</a>
      </div>
    </div>

  <?php else: ?>

  <div class="upload-layout">

    <!-- LEFT: Instructions -->
    <div class="instr-box">
      <h3>📋 Upload Instructions</h3>
      <ul>
        <li>
          <strong>File Format:</strong> Only <strong>PDF</strong> files (.pdf) are accepted.
          Word, Excel, images, and other formats are rejected.
        </li>
        <li>
          <strong>File Size:</strong> Maximum size is <strong>2 MB</strong> per file.
          Files exceeding this limit will be automatically rejected.
        </li>
        <li>
          <strong>Department Access:</strong> You are authorised to upload only to
          <strong><?= htmlspecialchars($admin_unit) ?></strong>.
          Other units are locked for you.
        </li>
        <li>
          <strong>Subject / Title:</strong> Enter a clear, descriptive name so users can easily identify the document.
        </li>
      </ul>
      <div class="warn">
        ⚠️ Ensure the document is approved before uploading. Once uploaded, it is immediately visible to all users.
      </div>
    </div>

    <!-- RIGHT: Upload Form -->
    <div class="upload-form-card">
      <h3>Document Details — Unit: <span style="color:var(--blue);"><?= htmlspecialchars($admin_unit) ?></span></h3>

      <form method="POST" enctype="multipart/form-data">

        <!-- Date — DD / MM / YYYY dropdowns -->
        <div class="form-group">
          <label>Upload Date (DD / MM / YYYY) <span style="color:var(--red)">*</span></label>
          <div style="display:flex;gap:8px;">

            <!-- Day -->
            <select name="upload_day" id="dd_day"
              style="flex:1;padding:9px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;background:#fff;color:var(--text);" required>
              <?php
                $sel_day = $_POST['upload_day'] ?? date('d');
                for ($d = 1; $d <= 31; $d++) {
                    $v = str_pad($d, 2, '0', STR_PAD_LEFT);
                    $sel = ($v == $sel_day) ? 'selected' : '';
                    echo "<option value='$v' $sel>$v</option>";
                }
              ?>
            </select>

            <!-- Month -->
            <select name="upload_month" id="dd_month"
              style="flex:2;padding:9px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;background:#fff;color:var(--text);" required>
              <?php
                $months  = ['01'=>'January','02'=>'February','03'=>'March','04'=>'April',
                            '05'=>'May','06'=>'June','07'=>'July','08'=>'August',
                            '09'=>'September','10'=>'October','11'=>'November','12'=>'December'];
                $sel_mon = $_POST['upload_month'] ?? date('m');
                foreach ($months as $num => $name) {
                    $sel = ($num == $sel_mon) ? 'selected' : '';
                    echo "<option value='$num' $sel>$name</option>";
                }
              ?>
            </select>

            <!-- Year -->
            <select name="upload_year" id="dd_year"
              style="flex:1;padding:9px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;background:#fff;color:var(--text);" required>
              <?php
                $sel_yr = $_POST['upload_year'] ?? date('Y');
                for ($y = 2020; $y <= date('Y'); $y++) {
                    $sel = ($y == $sel_yr) ? 'selected' : '';
                    echo "<option value='$y' $sel>$y</option>";
                }
              ?>
            </select>

          </div>
        </div>

        <!-- Unit dropdown -->
        <div class="form-group">
          <label>Unit <span style="color:var(--red)">*</span></label>
          <div class="dd-wrap" id="unitWrap">
            <button type="button" class="dd-btn" id="ddBtn" onclick="toggleDD()">
              <span id="ddLabel"><?= htmlspecialchars($admin_unit) ?></span>
              <span>▾</span>
            </button>
            <div class="dd-list" id="ddList">
              <?php foreach ($all_units as $u): ?>
                <label class="<?= ($u !== $admin_unit) ? 'disabled' : '' ?>">
                  <input type="radio" name="unit" value="<?= htmlspecialchars($u) ?>"
                         <?= ($u === $admin_unit) ? 'checked' : 'disabled' ?>>
                  <?= htmlspecialchars($u) ?>
                  <?php if ($u === $admin_unit): ?>
                    <span style="font-size:11px;color:var(--green);margin-left:6px;">✔ Your Unit</span>
                  <?php else: ?>
                    <span style="font-size:10px;color:var(--muted);margin-left:4px;">🔒 No Access</span>
                  <?php endif; ?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Subject -->
        <div class="form-group">
          <label for="subject">Subject / Document Title <span style="color:var(--red)">*</span></label>
          <input type="text" id="subject" name="subject"
                 placeholder="e.g. Safety Manual 2024, Work Instructions Rev-3"
                 value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" required>
        </div>

        <!-- File picker -->
        <div class="form-group">
          <label>Select PDF File <span style="color:var(--red)">*</span></label>
          <div class="file-drop" id="fileDrop" onclick="document.getElementById('docFile').click()">
            <div class="fd-icon">📄</div>
            <p id="fileLabel">Click here to choose a PDF file</p>
            <small>PDF only &nbsp;•&nbsp; Max 2 MB</small>
          </div>
          <input type="file" id="docFile" name="document" accept=".pdf,application/pdf"
                 style="display:none" onchange="updateLabel(this)">
        </div>

        <button type="submit" class="btn-upload">📤 Upload Document</button>
        <a href="gsd_docs.php" class="btn-home-link">🏠 Back to Home</a>

      </form>
    </div>
  </div>
  <?php endif; ?>

</div>
<div id="toast"></div>

<script>
// ── File label updater (was broken before — function keyword was missing) ──
function updateLabel(input) {
  var lbl = document.getElementById('fileLabel');
  if (input.files && input.files[0]) {
    var f  = input.files[0];
    var mb = (f.size / 1048576).toFixed(2);
    if (f.size > 2097152) {
      lbl.textContent = '⚠️ ' + f.name + ' (' + mb + ' MB) — Too large!';
      lbl.style.color = 'var(--red)';
    } else {
      lbl.textContent = '✅ ' + f.name + ' (' + mb + ' MB)';
      lbl.style.color = 'var(--green)';
    }
  }
}

// ── Unit dropdown toggle ──
function toggleDD() {
  document.getElementById('ddList').classList.toggle('open');
}
document.addEventListener('click', function(e) {
  var w = document.getElementById('unitWrap');
  if (w && !w.contains(e.target)) document.getElementById('ddList').classList.remove('open');
});

// ── Block future months/days in dropdowns (client-side guard) ──
(function() {
  var now  = new Date();
  var curY = now.getFullYear();
  var curM = now.getMonth() + 1;  // 1–12
  var curD = now.getDate();

  var ddY = document.getElementById('dd_year');
  var ddM = document.getElementById('dd_month');
  var ddD = document.getElementById('dd_day');

  function enforce() {
    var selY = parseInt(ddY.value);
    var selM = parseInt(ddM.value);

    // Disable future months when current year is selected
    for (var i = 0; i < ddM.options.length; i++) {
      var mv = parseInt(ddM.options[i].value);
      if (selY === curY && mv > curM) {
        ddM.options[i].disabled = true;
        ddM.options[i].style.color = '#ccc';
      } else {
        ddM.options[i].disabled = false;
        ddM.options[i].style.color = '';
      }
    }
    // If selected month became invalid, clamp it
    if (selY === curY && selM > curM) {
      ddM.value = String(curM).padStart(2, '0');
      selM = curM;
    }

    // Disable future days when current year+month is selected
    for (var j = 0; j < ddD.options.length; j++) {
      var dv = parseInt(ddD.options[j].value);
      if (selY === curY && selM === curM && dv > curD) {
        ddD.options[j].disabled = true;
        ddD.options[j].style.color = '#ccc';
      } else {
        ddD.options[j].disabled = false;
        ddD.options[j].style.color = '';
      }
    }
    // If selected day became invalid, clamp it
    if (selY === curY && selM === curM && parseInt(ddD.value) > curD) {
      ddD.value = String(curD).padStart(2, '0');
    }
  }

  ddY.addEventListener('change', enforce);
  ddM.addEventListener('change', enforce);
  enforce(); // run once on page load
})();
</script>
</body>
</html>

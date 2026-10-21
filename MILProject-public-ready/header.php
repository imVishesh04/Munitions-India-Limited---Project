<?php
// Generic header for the portfolio demo edition.
?>
<header class="site-header">
  <div class="hdr-main">
    <div class="hdr-logo-left-rect" aria-hidden="true"><span class="generic-logo">📁</span></div>
    <div class="hdr-center">
      <div class="hin">दस्तावेज़ प्रबंधन पोर्टल</div>
      <div class="eng">Document Management Portal</div>
      <div class="sub">Department-wise document sharing demo</div>
    </div>
    <div class="hdr-logo-right-text">
      <div class="rht-line1">PORTFOLIO</div>
      <div class="rht-line2">DEMO</div>
    </div>
  </div>
  <div class="hdr-strip">
    <div class="strip-eng">Demonstration project — use only synthetic sample documents and accounts</div>
  </div>
  <?php if (!empty($breadcrumb)): ?>
  <div class="breadcrumb">
    <?php foreach ($breadcrumb as $i => $item): ?>
      <?php if ($i > 0): ?><span class="sep">›</span><?php endif; ?>
      <?php if ($i < count($breadcrumb) - 1): ?>
        <a href="<?= htmlspecialchars($item[1]) ?>"><?= htmlspecialchars($item[0]) ?></a>
      <?php else: ?>
        <span class="cur"><?= htmlspecialchars($item[0]) ?></span>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</header>

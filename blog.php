<?php
require_once __DIR__ . '/admin/includes/db.php';

$pdo = be_db();
$posts = $pdo->query("
    SELECT title, slug, excerpt, featured_image, category, created_at
    FROM posts WHERE status = 'published'
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

$pageTitle = 'Blog — Bytes Encrypt';
$pageDescription = 'Notes on penetration testing, cloud security, compliance, and security awareness from the Bytes Encrypt team.';
include __DIR__ . '/includes/site-header.php';
?>

<div class="wrap breadcrumb">
  <a href="index.html">Home</a><span>/</span>Blog
</div>

<section class="subhero">
  <div class="wrap">
    <div class="eyebrow">Blog</div>
    <h1>Notes from the field</h1>
    <p class="lede">Write-ups on findings, methodology, and the security topics our clients actually ask about.</p>
  </div>
</section>

<section class="content-block">
  <div class="wrap">
    <?php if (!$posts): ?>
      <p class="section-sub">No posts published yet — check back soon.</p>
    <?php else: ?>
      <div class="tier-grid">
        <?php foreach ($posts as $p): ?>
          <a href="blog-post.php?slug=<?= urlencode($p['slug']) ?>" class="tier-card" style="text-decoration:none; color:inherit; display:block;">
            <?php if (!empty($p['featured_image'])): ?>
              <img src="admin/<?= h($p['featured_image']) ?>" alt="<?= h($p['title']) ?>" style="width:100%; height:150px; object-fit:cover; border-radius:4px; margin-bottom:14px;">
            <?php endif; ?>
            <?php if (!empty($p['category'])): ?>
              <div class="badge" style="margin-bottom:12px;"><?= h($p['category']) ?></div>
            <?php endif; ?>
            <h4 style="margin-bottom:10px;"><?= h($p['title']) ?></h4>
            <p style="font-size:13.5px; color:var(--muted); line-height:1.6; margin:0 0 10px;"><?= h($p['excerpt']) ?></p>
            <span style="font-family:var(--mono); font-size:11px; color:var(--muted);"><?= h(date('M j, Y', strtotime($p['created_at']))) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section id="contact">
  <div class="ctaband">
    <h2>Have a question about your own risk?</h2>
    <p>Skip the blog post — talk to a consultant directly.</p>
    <a class="btn-primary" href="contact.html">Book a consultation →</a>
  </div>
</section>

<?php include __DIR__ . '/includes/site-footer.php'; ?>

<?php
require_once __DIR__ . '/admin/includes/db.php';

$pdo = be_db();
$jobs = $pdo->query("
    SELECT title, slug, department, location, employment_type, summary, created_at
    FROM jobs WHERE status = 'published'
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

$pageTitle = 'Careers — Bytes Encrypt';
$pageDescription = 'Open roles at Bytes Encrypt — join a team doing hands-on offensive security, cloud security, and compliance work.';
include __DIR__ . '/includes/site-header.php';
?>

<div class="wrap breadcrumb">
  <a href="index.html">Home</a><span>/</span>Careers
</div>

<section class="subhero">
  <div class="wrap">
    <div class="eyebrow">Careers</div>
    <h1>Work on real engagements, not busywork</h1>
    <p class="lede">We're a small team that does the work ourselves — no bench of juniors doing the testing while consultants take the credit. If that's how you want to work too, take a look below.</p>
  </div>
</section>

<section class="content-block">
  <div class="wrap">
    <?php if (!$jobs): ?>
      <p class="section-sub">No open roles right now — check back soon, or send us your resume anyway at <a href="mailto:contact@bytesencrypt.com" style="color:var(--ink);">contact@bytesencrypt.com</a>.</p>
    <?php else: ?>
      <div style="display:flex; flex-direction:column; gap:1px; background:var(--line); border:1px solid var(--line); border-radius:4px; overflow:hidden;">
        <?php foreach ($jobs as $j): ?>
          <a href="career-post.php?slug=<?= urlencode($j['slug']) ?>" style="display:flex; justify-content:space-between; align-items:center; gap:20px; background:var(--paper-2); padding:24px 26px; text-decoration:none; color:inherit; flex-wrap:wrap;">
            <div>
              <h3 style="font-family:var(--display); font-size:19px; margin:0 0 6px;"><?= h($j['title']) ?></h3>
              <p style="margin:0; font-size:13.5px; color:var(--muted);"><?= h($j['summary']) ?></p>
              <div style="margin-top:10px; display:flex; gap:14px; flex-wrap:wrap;">
                <?php if ($j['department']): ?><span style="font-family:var(--mono); font-size:11.5px; color:var(--accent-2);"><?= h($j['department']) ?></span><?php endif; ?>
                <?php if ($j['location']): ?><span style="font-family:var(--mono); font-size:11.5px; color:var(--muted);"><?= h($j['location']) ?></span><?php endif; ?>
                <?php if ($j['employment_type']): ?><span style="font-family:var(--mono); font-size:11.5px; color:var(--muted);"><?= h($j['employment_type']) ?></span><?php endif; ?>
              </div>
            </div>
            <span style="font-family:var(--mono); font-size:13px; color:var(--accent-2); white-space:nowrap;">View role →</span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section id="contact">
  <div class="ctaband">
    <h2>Don't see the right role?</h2>
    <p>Send us your resume anyway — we're always open to hearing from good people.</p>
    <a class="btn-primary" href="mailto:contact@bytesencrypt.com">Email us →</a>
  </div>
</section>

<?php include __DIR__ . '/includes/site-footer.php'; ?>

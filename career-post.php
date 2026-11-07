<?php
require_once __DIR__ . '/admin/includes/db.php';

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

$slug = $_GET['slug'] ?? '';
$pdo = be_db();
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE slug = :slug AND status = 'published'");
$stmt->execute(['slug' => $slug]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    http_response_code(404);
    $pageTitle = 'Role not found — Bytes Encrypt';
    include __DIR__ . '/includes/site-header.php';
    echo '<section class="content-block"><div class="wrap"><h1 class="section-title">Role not found</h1><p class="section-sub">This posting may have closed or moved. <a href="careers.php">Back to careers →</a></p></div></section>';
    include __DIR__ . '/includes/site-footer.php';
    exit;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'bytesencrypt.example';
$canonicalUrl = "{$scheme}://{$host}/career-post.php?slug=" . urlencode($job['slug']);

$pageTitle = $job['title'] . ' — Careers — Bytes Encrypt';
$pageDescription = $job['summary'] ?: $job['title'];

$ogTags = '';
$ogTags .= '<meta property="og:type" content="website">' . "\n";
$ogTags .= '<meta property="og:title" content="' . h($pageTitle) . '">' . "\n";
$ogTags .= '<meta property="og:description" content="' . h($pageDescription) . '">' . "\n";
$ogTags .= '<meta property="og:url" content="' . h($canonicalUrl) . '">' . "\n";

$jsonLd = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'JobPosting',
    'title' => $job['title'],
    'description' => $job['description'],
    'datePosted' => $job['created_at'],
    'employmentType' => $job['employment_type'] ?: null,
    'hiringOrganization' => ['@type' => 'Organization', 'name' => 'Bytes Encrypt', 'sameAs' => "{$scheme}://{$host}/index.html"],
    'jobLocation' => $job['location'] ? [
        '@type' => 'Place',
        'address' => ['@type' => 'PostalAddress', 'addressLocality' => $job['location']],
    ] : null,
], JSON_UNESCAPED_SLASHES);
$ogTags .= '<script type="application/ld+json">' . $jsonLd . '</script>' . "\n";

include __DIR__ . '/includes/site-header.php';
?>

<div class="wrap breadcrumb">
  <a href="index.html">Home</a><span>/</span><a href="careers.php">Careers</a><span>/</span><?= h($job['title']) ?>
</div>

<section class="subhero">
  <div class="wrap">
    <?php if ($job['department']): ?><div class="eyebrow"><?= h($job['department']) ?></div><?php endif; ?>
    <h1><?= h($job['title']) ?></h1>
    <?php if ($job['summary']): ?><p class="lede"><?= h($job['summary']) ?></p><?php endif; ?>
    <div style="display:flex; gap:16px; flex-wrap:wrap; margin-bottom:28px;">
      <?php if ($job['location']): ?><span style="font-family:var(--mono); font-size:12px; color:var(--muted); border:1px solid var(--line); border-radius:20px; padding:7px 14px;"><?= h($job['location']) ?></span><?php endif; ?>
      <?php if ($job['employment_type']): ?><span style="font-family:var(--mono); font-size:12px; color:var(--muted); border:1px solid var(--line); border-radius:20px; padding:7px 14px;"><?= h($job['employment_type']) ?></span><?php endif; ?>
    </div>
    <a class="btn-primary" href="#apply">Apply for this role →</a>
  </div>
</section>

<section class="content-block">
  <div class="wrap" style="max-width:760px;">
    <div class="prose">
      <?= $job['description'] ?>
    </div>
  </div>
</section>

<section id="apply" class="content-block">
  <div class="wrap">
    <div class="eyebrow">How to apply</div>
    <h2 class="section-title" style="font-size:24px;">Apply for this role</h2>
    <?php if ($job['how_to_apply']): ?>
      <p class="section-sub" style="max-width:640px;"><?= nl2br(h($job['how_to_apply'])) ?></p>
    <?php else: ?>
      <p class="section-sub" style="max-width:640px;">Send your resume and a short note to <a href="mailto:contact@bytesencrypt.com?subject=Application: <?= rawurlencode($job['title']) ?>" style="color:var(--ink); font-weight:600;">contact@bytesencrypt.com</a> with the role title in the subject line.</p>
    <?php endif; ?>
    <a class="btn-primary" href="mailto:contact@bytesencrypt.com?subject=Application: <?= rawurlencode($job['title']) ?>">Email your application →</a>
  </div>
</section>

<?php include __DIR__ . '/includes/site-footer.php'; ?>

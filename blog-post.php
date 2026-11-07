<?php
require_once __DIR__ . '/admin/includes/db.php';

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/** Same extractor used by the admin form, duplicated locally to avoid loading admin auth. */
function be_public_youtube_id(string $url): ?string
{
    $url = trim($url);
    if ($url === '') {
        return null;
    }
    $host = parse_url($url, PHP_URL_HOST) ?? '';
    if (!preg_match('/(^|\.)youtube\.com$|(^|\.)youtu\.be$/i', $host)) {
        return null;
    }
    if (preg_match('/[?&]v=([A-Za-z0-9_-]{6,})/', $url, $m)) {
        return $m[1];
    }
    if (preg_match('#youtu\.be/([A-Za-z0-9_-]{6,})#', $url, $m)) {
        return $m[1];
    }
    if (preg_match('#youtube\.com/embed/([A-Za-z0-9_-]{6,})#', $url, $m)) {
        return $m[1];
    }
    return null;
}

$slug = $_GET['slug'] ?? '';
$pdo = be_db();
$stmt = $pdo->prepare("SELECT * FROM posts WHERE slug = :slug AND status = 'published'");
$stmt->execute(['slug' => $slug]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404);
    $pageTitle = 'Post not found — Bytes Encrypt';
    include __DIR__ . '/includes/site-header.php';
    echo '<section class="content-block"><div class="wrap"><h1 class="section-title">Post not found</h1><p class="section-sub">This post may have been unpublished or moved. <a href="blog.php">Back to the blog →</a></p></div></section>';
    include __DIR__ . '/includes/site-footer.php';
    exit;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'bytesencrypt.example';
$canonicalUrl = "{$scheme}://{$host}/blog-post.php?slug=" . urlencode($post['slug']);

$metaTitle = $post['meta_title'] ?: $post['title'];
$metaDescription = $post['meta_description'] ?: $post['excerpt'];

$ogImage = !empty($post['featured_image']) ? "{$scheme}://{$host}/admin/" . $post['featured_image'] : '';
$ogTags = '';
$ogTags .= '<meta property="og:type" content="article">' . "\n";
$ogTags .= '<meta property="og:title" content="' . h($metaTitle) . '">' . "\n";
$ogTags .= '<meta property="og:description" content="' . h($metaDescription) . '">' . "\n";
$ogTags .= '<meta property="og:url" content="' . h($canonicalUrl) . '">' . "\n";
if ($ogImage) {
    $ogTags .= '<meta property="og:image" content="' . h($ogImage) . '">' . "\n";
}
$ogTags .= '<meta name="twitter:card" content="summary_large_image">' . "\n";

$jsonLd = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $post['title'],
    'description' => $metaDescription,
    'datePublished' => $post['created_at'],
    'dateModified' => $post['updated_at'],
    'image' => $ogImage ?: null,
    'author' => ['@type' => 'Organization', 'name' => 'Bytes Encrypt'],
    'publisher' => ['@type' => 'Organization', 'name' => 'Bytes Encrypt'],
], JSON_UNESCAPED_SLASHES);
$ogTags .= '<script type="application/ld+json">' . $jsonLd . '</script>' . "\n";

$pageTitle = $metaTitle . ' — Bytes Encrypt';
$pageDescription = $metaDescription;
include __DIR__ . '/includes/site-header.php';

$ytId = !empty($post['youtube_url']) ? be_public_youtube_id($post['youtube_url']) : null;
?>

<div class="wrap breadcrumb">
  <a href="index.html">Home</a><span>/</span><a href="blog.php">Blog</a><span>/</span><?= h($post['title']) ?>
</div>

<section class="subhero">
  <div class="wrap">
    <?php if (!empty($post['category'])): ?><div class="eyebrow"><?= h($post['category']) ?></div><?php endif; ?>
    <h1><?= h($post['title']) ?></h1>
    <p class="lede"><?= h($post['excerpt']) ?></p>
    <span style="font-family:var(--mono); font-size:12px; color:var(--muted);">
      Published <?= h(date('F j, Y', strtotime($post['created_at']))) ?>
    </span>
  </div>
</section>

<?php if (!empty($post['featured_image'])): ?>
<section class="content-block" style="padding-bottom:0;">
  <div class="wrap">
    <img src="admin/<?= h($post['featured_image']) ?>" alt="<?= h($post['title']) ?>" style="width:100%; max-height:420px; object-fit:cover; border-radius:6px;">
  </div>
</section>
<?php endif; ?>

<?php if ($ytId): ?>
<section class="content-block" style="padding-bottom:0;">
  <div class="wrap">
    <div style="position:relative; padding-bottom:56.25%; height:0; border-radius:6px; overflow:hidden;">
      <iframe src="https://www.youtube.com/embed/<?= h($ytId) ?>" title="<?= h($post['title']) ?>" style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="content-block">
  <div class="wrap" style="max-width:760px;">
    <div class="prose">
      <?= $post['content'] ?>
    </div>
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

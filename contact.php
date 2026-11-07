<?php
$sent  = isset($_GET['sent']);
$error = $_GET['error'] ?? '';

$errorMessages = [
    'missing'  => 'Please fill in your name, email, and a short message.',
    'invalid_email' => 'That email address doesn\'t look valid — please check it and try again.',
    'spam'     => 'Your message looked like spam and was blocked. If that\'s wrong, please email us directly.',
    'rate'     => 'Too many requests from this connection. Please try again in a few minutes, or email us directly.',
    'csrf'     => 'Your session expired — please reload the page and try again.',
];
$errorMessage = $errorMessages[$error] ?? '';

$pageTitle = 'Contact Us — Bytes Encrypt';
$pageDescription = 'Book a free, no-obligation consultation with Bytes Encrypt. Call, email, or send us a message and we\'ll get back to you within one business day.';
include __DIR__ . '/includes/site-header.php';
?>

<div class="wrap breadcrumb">
  <a href="index.html">Home</a><span>/</span>Contact
</div>

<section class="subhero">
  <div class="wrap">
    <div class="eyebrow">Free · No obligation</div>
    <h1>Talk to a consultant, not a salesperson</h1>
    <p class="lede">Book a short call with the team. We'll ask what's actually keeping you up at night, and tell you honestly where your biggest exposure is — whether or not you hire us afterward.</p>
    <div class="hero-ctas">
      <a class="btn-primary" href="#book">Book a call →</a>
      <a class="btn-secondary" href="index.html">← Back to home</a>
    </div>
    <div class="hero-stats">
      <div><div class="num">30 min</div><div class="lbl">Focused, no fluff</div></div>
      <div><div class="num">Free</div><div class="lbl">No cost, no catch</div></div>
      <div><div class="num">24h</div><div class="lbl">Proposal turnaround if needed</div></div>
    </div>
  </div>
</section>

<section class="content-block">
  <div class="wrap">
    <div class="eyebrow">What happens on the call</div>
    <h2 class="section-title">Four steps, one conversation</h2>
    <p class="section-sub">No forms to fill out beforehand — just a direct conversation with someone who can actually answer your questions.</p>
    <div class="tier-grid-4">
      <div class="tier-card">
        <h4>1. Where you stand</h4>
        <ul><li>We ask about your systems, your worries, and any compliance pressure you're under — plain conversation, no checklist.</li></ul>
      </div>
      <div class="tier-card">
        <h4>2. A real opinion</h4>
        <ul><li>You're talking to the person who'd actually do the work, not a salesperson reading from a script.</li></ul>
      </div>
      <div class="tier-card">
        <h4>3. Next steps</h4>
        <ul><li>You leave with a short list of what matters most, ranked by risk and effort — useful even if you never hire us.</li></ul>
      </div>
      <div class="tier-card">
        <h4>4. A real quote</h4>
        <ul><li>If it makes sense to work together, you get a binding fixed-price proposal within one business day.</li></ul>
      </div>
    </div>
  </div>
</section>

<section id="book" class="content-block">
  <div class="wrap two-col">
    <div>
      <div class="eyebrow">Book a consultation</div>
      <h2 class="section-title">Tell us a bit about what you need</h2>
      <p class="section-sub">Send this and we'll reply within one business day to schedule your call.</p>

      <?php if ($sent): ?>
        <div class="form-success-box">
          <div class="lbl">Message sent</div>
          <p>Thanks — we've got your request and will reply within one business day. If it's urgent, call us at <a href="tel:+919113962011" style="color:var(--ink); font-weight:600;">+91 9113962011</a>.</p>
        </div>
      <?php else: ?>
        <?php if ($errorMessage): ?>
          <div class="form-error-box">
            <p><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
          </div>
        <?php endif; ?>
        <form action="contact-submit.php" method="post" class="contact-form" novalidate>
          <div class="field-row">
            <div class="field">
              <label for="cf-name">Name</label>
              <input type="text" id="cf-name" name="name" required maxlength="120">
            </div>
            <div class="field">
              <label for="cf-email">Email</label>
              <input type="email" id="cf-email" name="email" required maxlength="160">
            </div>
          </div>
          <div class="field-row">
            <div class="field">
              <label for="cf-phone">Phone <span class="optional">(optional)</span></label>
              <input type="text" id="cf-phone" name="phone" maxlength="40">
            </div>
            <div class="field">
              <label for="cf-company">Company <span class="optional">(optional)</span></label>
              <input type="text" id="cf-company" name="company" maxlength="160">
            </div>
          </div>
          <div class="field">
            <label for="cf-service">Service you're interested in <span class="optional">(optional)</span></label>
            <select id="cf-service" name="service_interest">
              <option value="">Not sure yet</option>
              <option>Penetration Testing</option>
              <option>Red Teaming</option>
              <option>Vulnerability Scanning</option>
              <option>AI Penetration Testing</option>
              <option>Secure Code Review</option>
              <option>Cloud Security</option>
              <option>ISMS / ISO 27001</option>
              <option>Virtual CISO</option>
              <option>Security Awareness Training</option>
              <option>NIS-2 / Compliance Training</option>
              <option>Other</option>
            </select>
          </div>
          <div class="field">
            <label for="cf-message">Message</label>
            <textarea id="cf-message" name="message" rows="5" required maxlength="4000"></textarea>
          </div>
          <!-- Honeypot field — real users never see or fill this in -->
          <div style="position:absolute; left:-9999px;" aria-hidden="true">
            <label>Leave this field blank
              <input type="text" name="website" tabindex="-1" autocomplete="off">
            </label>
          </div>
          <button type="submit" class="btn-primary">Send message →</button>
        </form>
      <?php endif; ?>
    </div>
    <div>
      <div class="eyebrow">Prefer to reach out directly?</div>
      <h2 class="section-title" style="font-size:22px;">Contact details</h2>
      <ul class="check-list">
        <li><a href="mailto:contact@bytesencrypt.com" style="color:var(--ink);">contact@bytesencrypt.com</a></li>
        <li><a href="tel:+919113962011" style="color:var(--ink);">+91 9113962011</a></li>
        <li>Kalyan Nagar, Bangalore, KAR-560043</li>
        <li>Mon–Fri, 9:00 AM – 6:00 PM IST</li>
      </ul>
    </div>
  </div>
</section>

<section class="content-block">
  <div class="wrap">
    <p class="section-sub" style="margin:0;">Trusted by teams across finance, healthcare, SaaS, manufacturing, and the public sector. Placeholder — swap in real certifications and client references.</p>
  </div>
</section>

<?php include __DIR__ . '/includes/site-footer.php'; ?>

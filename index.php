<?php
define('NEDA', true);
require __DIR__ . '/includes/init.php';

if (!neda_installed()) {
    neda_redirect('install.php');
}

if (neda_user()) {
    neda_redirect('app.php');
}

require __DIR__ . '/includes/layout.php';
neda_head('مهسا — گفتگوی صوتی و تصویری');
?>
<main class="shell landing">
  <div class="filament" aria-hidden="true"></div>
  <section class="hero-card">
    <p class="eyebrow">خط خصوصی</p>
    <h1>مهسا</h1>
    <p class="lede">یک شناسه می‌گیرید، آن را به طرف مقابل می‌دهید، و همان لحظه متن، صدا یا تصویر رد و بدل می‌شود. بدون دفترچه مخاطب و بدون سرور تماس جداگانه.</p>
    <div class="hero-actions">
      <a class="btn btn-primary" href="register.php">ساخت حساب</a>
      <a class="btn btn-ghost" href="login.php">ورود</a>
    </div>
    <ol class="steps">
      <li>حساب بسازید و شناسه شش‌رقمی بگیرید.</li>
      <li>شناسه را برای طرف مقابل بفرستید.</li>
      <li>گفتگو، تماس صوتی یا تصویری را شروع کنید.</li>
    </ol>
  </section>
</main>
<?php neda_foot();
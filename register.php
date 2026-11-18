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
neda_head('ثبت‌نام — مهسا');
?>
<main class="shell auth">
  <form class="panel" id="register-form" action="api/register.php" method="post" novalidate>
    <p class="eyebrow">خط تازه</p>
    <h1>ساخت حساب</h1>
    <label>
      <span>نام کاربری</span>
      <input name="username" autocomplete="username" required minlength="3" maxlength="40">
    </label>
    <label>
      <span>رمز عبور</span>
      <input name="password" type="password" autocomplete="new-password" required minlength="8">
      <small>حداقل ۸ نویسه</small>
    </label>
    <p class="form-error" id="form-error" hidden></p>
    <input type="hidden" name="_csrf" value="<?php echo neda_h(neda_csrf_token()); ?>">
    <button class="btn btn-primary" type="submit">ساخت حساب و دریافت شناسه</button>
    <p class="aside">قبلاً حساب دارید؟ <a href="login.php">وارد شوید</a></p>
  </form>
</main>
<script src="assets/js/auth.js"></script>
<?php neda_foot();
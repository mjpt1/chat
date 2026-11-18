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
neda_head('ورود — مهسا');
?>
<main class="shell auth">
  <form class="panel" id="login-form" action="api/login.php" method="post" novalidate>
    <p class="eyebrow">بازگشت به خط</p>
    <h1>ورود</h1>
    <label>
      <span>نام کاربری</span>
      <input name="username" autocomplete="username" required maxlength="40">
    </label>
    <label>
      <span>رمز عبور</span>
      <input name="password" type="password" autocomplete="current-password" required>
    </label>
    <p class="form-error" id="form-error" hidden></p>
    <input type="hidden" name="_csrf" value="<?php echo neda_h(neda_csrf_token()); ?>">
    <button class="btn btn-primary" type="submit">ورود</button>
    <p class="aside">حساب ندارید؟ <a href="register.php">ثبت‌نام کنید</a></p>
  </form>
</main>
<script src="assets/js/auth.js"></script>
<?php neda_foot();
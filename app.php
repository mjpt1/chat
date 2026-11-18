<?php
define('NEDA', true);
require __DIR__ . '/includes/init.php';
if (!neda_installed()) {
    neda_redirect('install.php');
}
$me = neda_require_user();
require __DIR__ . '/includes/layout.php';
neda_head('اتاق گفتگو — مهسا');
?>
<div class="app" id="app"
     data-username="<?php echo neda_h($me['username']); ?>"
     data-public-id="<?php echo neda_h($me['public_id']); ?>"
     data-user-id="<?php echo (int) $me['id']; ?>">
  <aside class="rail">
    <div class="brand">
      <span class="lamp" id="lamp" aria-hidden="true"></span>
      <strong>مهسا</strong>
    </div>
    <div class="my-line">
      <p>شناسه شما</p>
      <button type="button" class="public-id" id="copy-id" title="کپی شناسه">
        <span dir="ltr"><?php echo neda_h(neda_fa_digits($me['public_id'])); ?></span>
      </button>
      <small id="copy-hint">برای شروع گفتگو این عدد را بدهید</small>
    </div>
    <form class="dial" id="dial-form">
      <label>
        <span>شناسه طرف مقابل</span>
        <input id="peer-id" name="public_id" inputmode="numeric" maxlength="6" dir="ltr" placeholder="۴۸۲۹۱۷" required>
      </label>
      <button class="btn btn-primary" type="submit">باز کردن گفتگو</button>
    </form>
    <nav class="threads" id="threads" aria-label="گفتگوهای اخیر"></nav>
    <a class="logout" href="logout.php">خروج از حساب</a>
  </aside>

  <section class="stage" id="stage" hidden>
    <header class="stage-head">
      <div>
        <h2 id="peer-name">—</h2>
        <p id="peer-meta" class="meta"></p>
      </div>
      <div class="call-actions">
        <button type="button" class="icon-btn" id="btn-audio" title="تماس صوتی">
          <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.4 11.4 0 0 0 .57 3.6 1 1 0 0 1-.25 1z"/></svg>
          <span>صوتی</span>
        </button>
        <button type="button" class="icon-btn" id="btn-video" title="تماس تصویری">
          <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M17 10.5V7a2 2 0 0 0-2-2H5A2 2 0 0 0 3 7v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3.5l4 4v-11z"/></svg>
          <span>تصویری</span>
        </button>
      </div>
    </header>

    <div class="videos" id="videos" hidden>
      <video id="remote-video" autoplay playsinline></video>
      <video id="local-video" autoplay playsinline muted></video>
      <p class="call-status" id="call-status"></p>
      <button type="button" class="btn btn-danger hangup" id="btn-hangup">قطع تماس</button>
    </div>

    <div class="log" id="log"></div>
    <form class="composer" id="composer">
      <label class="sr-only" for="msg">پیام</label>
      <input id="msg" maxlength="2000" placeholder="پیام بنویسید…" autocomplete="off">
      <button class="btn btn-primary" type="submit">ارسال</button>
    </form>
  </section>

  <section class="empty" id="empty">
    <div class="empty-card">
      <span class="lamp big" aria-hidden="true"></span>
      <h2>هنوز خطی باز نشده</h2>
      <p>شناسه شش‌رقمی طرف مقابل را وارد کنید تا گفتگو، تماس صوتی یا تصویری شروع شود.</p>
    </div>
  </section>
</div>

<div class="overlay" id="incoming" hidden>
  <div class="incoming-card">
    <span class="lamp pulse" aria-hidden="true"></span>
    <p class="eyebrow">تماس ورودی</p>
    <h2 id="incoming-name">—</h2>
    <p id="incoming-media" class="meta"></p>
    <div class="hero-actions">
      <button type="button" class="btn btn-primary" id="btn-accept">پذیرش</button>
      <button type="button" class="btn btn-ghost" id="btn-reject">رد</button>
    </div>
  </div>
</div>

<script src="assets/js/app.js?v=call-fs1"></script>
<?php neda_foot();
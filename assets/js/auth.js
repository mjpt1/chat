(function () {
  const form = document.querySelector("#login-form, #register-form");
  if (!form) return;
  const errorBox = document.getElementById("form-error");
  const csrf = document.querySelector('meta[name="csrf-token"]').content;

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    errorBox.hidden = true;
    const data = Object.fromEntries(new FormData(form).entries());
    try {
      const res = await fetch(form.action, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": csrf,
        },
        body: JSON.stringify(data),
      });
      const json = await res.json();
      if (!json.ok) {
        errorBox.textContent = json.error || "درخواست انجام نشد.";
        errorBox.hidden = false;
        return;
      }
      window.location.href = json.redirect || "app.php";
    } catch (err) {
      errorBox.textContent = "اتصال برقرار نشد. دوباره تلاش کنید.";
      errorBox.hidden = false;
    }
  });
})();

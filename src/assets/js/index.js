// Flash auto-dismiss
const flash = document.getElementById("flash-message");
if (flash) {
  setTimeout(() => {
    flash.style.transition = "opacity 0.4s ease, transform 0.4s ease";
    flash.style.opacity = "0";
    flash.style.transform = "translateX(120%)";
    setTimeout(() => flash.remove(), 400);
  }, 4000);
}

// Image upload preview
document.querySelectorAll(".upload-zone").forEach((zone) => {
  const input = zone.querySelector('input[type="file"]');
  const preview = zone.querySelector(".upload-preview");
  const label = zone.querySelector(".upload-label");

  zone.addEventListener("click", () => input?.click());

  zone.addEventListener("dragover", (e) => {
    e.preventDefault();
    zone.style.borderColor = "var(--accent)";
  });

  zone.addEventListener("dragleave", () => {
    zone.style.borderColor = "";
  });

  zone.addEventListener("drop", (e) => {
    e.preventDefault();
    zone.style.borderColor = "";
    if (input && e.dataTransfer.files[0]) {
      input.files = e.dataTransfer.files;
      showPreview(e.dataTransfer.files[0]);
    }
  });

  input?.addEventListener("change", () => {
    if (input.files[0]) showPreview(input.files[0]);
  });

  function showPreview(file) {
    if (!preview) return;
    const reader = new FileReader();
    reader.onload = (e) => {
      preview.src = e.target.result;
      preview.style.display = "block";
      if (label) label.textContent = file.name;
    };
    reader.readAsDataURL(file);
  }
});

// Confirm delete
document.querySelectorAll("[data-confirm]").forEach((el) => {
  el.addEventListener("click", (e) => {
    const msg = el.dataset.confirm || "Are you sure?";
    if (!confirm(msg)) e.preventDefault();
  });
});

// Active nav highlight fallback ───────────────────
document.querySelectorAll(".navbar-links a").forEach((link) => {
  if (link.href === window.location.href) {
    link.classList.add("active");
  }
});

// User dropdown toggle
document.addEventListener("click", (e) => {
  const navbarUser = document.querySelector(".navbar-user");
  const navbarUserDropdown = navbarUser.querySelector(".navbar-dropdown");

  if (navbarUser.contains(e.target))
    if (navbarUser.classList.contains("open"))
      navbarUser.classList.remove("open");
    else navbarUser.classList.add("open");
  else if (navbarUser.classList.contains("open"))
    navbarUser.classList.remove("open");
});

// Role toggle
document.querySelectorAll(".role-btn").forEach((btn) => {
  btn.addEventListener("click", () => {
    document
      .querySelectorAll(".role-btn")
      .forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");
    document.getElementById("role-input").value = btn.dataset.role;
  });
});

// Password visibility toggle
document.querySelectorAll(".eye-btn").forEach((btn) => {
  btn.addEventListener("click", () => {
    const input = document.getElementById(btn.dataset.target);
    input.type = input.type === "password" ? "text" : "password";
    btn.textContent = input.type === "password" ? "👁" : "🙈";
  });
});

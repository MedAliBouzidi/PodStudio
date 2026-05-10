const studioRateEl = document.getElementById("studio-rate");
const durationEl = document.getElementById("duration-display");
const totalEl = document.getElementById("total-price");
const pkgRow = document.getElementById("pkg-row");
const pkgPriceEl = document.getElementById("pkg-price");
const packageSelect = document.getElementById("package-select");
const startInput = document.getElementById("start_time");
const endInput = document.getElementById("end_time");

studioRateEl.textContent = pricePerHour.toLocaleString() + " TND/hr";

function updatePrice() {
  const start = startInput.value;
  const end = endInput.value;

  if (!start || !end || start >= end) {
    durationEl.textContent = "—";
    totalEl.textContent = "—";
    pkgRow.style.display = "none";
    return;
  }

  const startMin =
    parseInt(start.split(":")[0]) * 60 + parseInt(start.split(":")[1]);
  const endMin = parseInt(end.split(":")[0]) * 60 + parseInt(end.split(":")[1]);
  const hours = (endMin - startMin) / 60;

  let total = hours * pricePerHour;

  const selectedOpt = packageSelect.options[packageSelect.selectedIndex];
  const pkgPrice = parseFloat(selectedOpt.dataset.price || 0);

  if (pkgPrice > 0) {
    total += pkgPrice;
    pkgRow.style.display = "flex";
    pkgPriceEl.textContent = pkgPrice.toLocaleString() + " TND";
  } else {
    pkgRow.style.display = "none";
  }

  durationEl.textContent = hours + "h";
  totalEl.textContent = total.toLocaleString() + " TND";
}

startInput.addEventListener("change", updatePrice);
endInput.addEventListener("change", updatePrice);
packageSelect.addEventListener("change", updatePrice);
updatePrice();

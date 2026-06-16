/* Admin JS — Survey System */
"use strict";

/* ---- MODAL HELPERS ---- */
function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add("active");
}
function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove("active");
}
// Close on overlay click
document.addEventListener("click", function (e) {
  if (e.target.classList.contains("modal-overlay")) {
    e.target.classList.remove("active");
  }
});
// ESC closes
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    document
      .querySelectorAll(".modal-overlay.active")
      .forEach((m) => m.classList.remove("active"));
  }
});

/* ---- DELETE CONFIRM ---- */
function confirmDelete(formId, itemName) {
  if (confirm('Delete "' + itemName + '"? This cannot be undone.')) {
    document.getElementById(formId).submit();
  }
}

/* ---- OPTION BUILDER (question form) ---- */
let optionCount = 0;

function addOption() {
  const container = document.getElementById("options-container");
  if (!container) return;
  optionCount++;
  const div = document.createElement("div");
  div.className = "option-row";
  div.style.cssText =
    "display:flex;gap:8px;align-items:center;margin-bottom:8px;";
  div.innerHTML =
    '<input type="text" name="options[]" placeholder="Option ' +
    optionCount +
    '" class="form-control" required>' +
    '<button type="button" onclick="this.parentElement.remove()" class="btn btn-danger btn-sm">✕</button>';
  container.appendChild(div);
}

/* ---- TOGGLE OPTION FIELDS BASED ON QUESTION TYPE ---- */
function toggleQuestionType(type) {
  const optBlock = document.getElementById("options-block");
  if (!optBlock) return;
  optBlock.style.display = type === "mcq" ? "block" : "none";
  // Make options required only for mcq
  document
    .querySelectorAll('#options-container input[name="options[]"]')
    .forEach((inp) => {
      inp.required = type === "mcq";
    });
}

document.addEventListener("DOMContentLoaded", function () {
  const typeSelect = document.getElementById("questionType");
  if (typeSelect) {
    toggleQuestionType(typeSelect.value);
    typeSelect.addEventListener("change", () =>
      toggleQuestionType(typeSelect.value),
    );
  }

  // Flash auto-hide
  const flash = document.querySelector(".flash");
  if (flash) {
    setTimeout(() => {
      flash.style.opacity = "0";
      flash.style.transition = "opacity .5s";
    }, 4000);
  }

  // Sortable table headers (visual only — actual sort is server-side via URL params)
  document.querySelectorAll("th[data-sort]").forEach((th) => {
    th.style.cursor = "pointer";
  });
});

/* ---- PRINT REPORT ---- */
function printReport() {
  window.print();
}

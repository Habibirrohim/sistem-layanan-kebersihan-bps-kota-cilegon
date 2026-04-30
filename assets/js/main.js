// Global variables
let currentRole = "admin";
let verificationStatus = {};

// Sample data for demonstration
const scheduleData = {
  "2024-01-15": { petugas: "Ahmad Rizki", ruangan: "Ruang Kerja A" },
  "2024-01-16": { petugas: "Ahmad Rizki", ruangan: "Ruang Meeting" },
  "2024-01-17": { petugas: "Siti Nurhaliza", ruangan: "Ruang Kerja B" },
};

// Initialize page
document.addEventListener("DOMContentLoaded", function () {
  // Set current date for checklist form
  const today = new Date().toISOString().split("T")[0];
  const checklistDate = document.getElementById("checklistDate");
  if (checklistDate) {
    checklistDate.value = today;
  }

  // Check and load today's schedule
  loadTodaySchedule();

  // Show schedule reminder notification
  showScheduleReminder();

  // Initialize sidebar toggle
  initSidebar();
});

// Initialize sidebar functionality
function initSidebar() {
  const sidebarToggle = document.getElementById("sidebarToggle");
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("overlay");

  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", function () {
      sidebar.classList.toggle("-translate-x-full");
      overlay.classList.toggle("hidden");
    });
  }

  if (overlay) {
    overlay.addEventListener("click", function () {
      sidebar.classList.add("-translate-x-full");
      overlay.classList.add("hidden");
    });
  }
}

// Load today's schedule for checklist form
function loadTodaySchedule() {
  const today = new Date().toISOString().split("T")[0];
  const todaySchedule = scheduleData[today];

  const ruanganSelect = document.getElementById("checklistRuangan");
  const scheduleAlert = document.getElementById("scheduleAlert");

  if (ruanganSelect && todaySchedule && currentRole === "petugas") {
    ruanganSelect.innerHTML = `<option value="${todaySchedule.ruangan}">${todaySchedule.ruangan}</option>`;
    ruanganSelect.value = todaySchedule.ruangan;
    if (scheduleAlert) scheduleAlert.classList.add("hidden");
  } else if (ruanganSelect && currentRole === "petugas") {
    ruanganSelect.innerHTML =
      '<option value="">Tidak ada jadwal hari ini</option>';
    if (scheduleAlert) scheduleAlert.classList.remove("hidden");
  }
}

// Show schedule reminder notification
function showScheduleReminder() {
  if (currentRole === "petugas") {
    const today = new Date().toISOString().split("T")[0];
    const todaySchedule = scheduleData[today];

    if (todaySchedule) {
      showNotification(
        "info",
        "Pengingat Jadwal",
        `Anda memiliki jadwal piket hari ini di ${todaySchedule.ruangan}`,
        5000
      );
    }
  }
}

// Show notification
function showNotification(type, title, message, duration = 3000) {
  const container = document.getElementById("notificationContainer");
  if (!container) return;

  const notification = document.createElement("div");

  const bgColor =
    type === "success"
      ? "bg-green-500"
      : type === "error"
      ? "bg-red-500"
      : "bg-blue-500";
  const icon =
    type === "success"
      ? "fa-check-circle"
      : type === "error"
      ? "fa-exclamation-circle"
      : "fa-info-circle";

  notification.className = `notification ${bgColor} text-white p-4 rounded-lg shadow-lg`;
  notification.innerHTML = `
        <div class="flex items-start">
            <i class="fas ${icon} text-xl mr-3 mt-1"></i>
            <div class="flex-1">
                <h4 class="font-semibold">${title}</h4>
                <p class="text-sm opacity-90">${message}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

  container.appendChild(notification);

  // Show notification
  setTimeout(() => notification.classList.add("show"), 100);

  // Auto remove
  setTimeout(() => {
    notification.classList.remove("show");
    setTimeout(() => notification.remove(), 300);
  }, duration);
}

// Open image modal
function openImageModal(src) {
  const modal = document.getElementById("imageModal");
  const modalImage = document.getElementById("modalImage");
  if (modal && modalImage) {
    modalImage.src = src;
    modal.classList.remove("hidden");
  }
}

// Close image modal
function closeImageModal() {
  const modal = document.getElementById("imageModal");
  if (modal) {
    modal.classList.add("hidden");
  }
}

// Toggle kendala fields
function toggleKendala(item, show) {
  const kendalaDiv = document.getElementById(`kendala-${item}`);
  if (kendalaDiv) {
    if (show) {
      kendalaDiv.classList.remove("hidden");
    } else {
      kendalaDiv.classList.add("hidden");
    }
  }
}

// Set verification status
function setVerification(item, action) {
  verificationStatus[item] = action;

  const buttonContainer = document.getElementById(`buttons-${item}`);
  if (!buttonContainer) return;

  if (action === "terima") {
    buttonContainer.innerHTML = `
            <button class="bg-green-700 text-white px-3 py-1 rounded">
                <i class="fas fa-check mr-1"></i>Diterima
            </button>
        `;
  } else if (action === "tolak") {
    buttonContainer.innerHTML = `
            <button class="bg-red-700 text-white px-3 py-1 rounded">
                <i class="fas fa-times mr-1"></i>Ditolak
            </button>
        `;
  }
}

// Verify report
function verifyReport() {
  showNotification("success", "Berhasil!", "Laporan berhasil diverifikasi!");
  setTimeout(() => {
    window.location.href = "laporan.php";
  }, 1500);
}

// Submit checklist
function submitChecklist(event) {
  event.preventDefault();

  const today = new Date().toISOString().split("T")[0];
  const todaySchedule = scheduleData[today];

  if (!todaySchedule || currentRole !== "petugas") {
    showNotification(
      "error",
      "Gagal Submit",
      "Anda tidak dapat mengisi checklist di luar jadwal piket yang ditentukan!"
    );
    return;
  }

  showNotification(
    "success",
    "Berhasil!",
    "Checklist berhasil disubmit dan menunggu verifikasi admin!"
  );
  setTimeout(() => {
    window.location.href = "laporan.php";
  }, 1500);
}

// Save schedule
function saveSchedule(event) {
  event.preventDefault();
  showNotification("success", "Berhasil!", "Jadwal piket berhasil disimpan!");
  setTimeout(() => {
    window.location.href = "jadwal.php";
  }, 1500);
}

// Edit schedule
function editSchedule(scheduleId) {
  showNotification("info", "Info", "Fitur edit jadwal akan segera tersedia!");
}

// Delete schedule
function deleteSchedule(scheduleId) {
  if (confirm("Apakah Anda yakin ingin menghapus jadwal ini?")) {
    showNotification("success", "Berhasil!", "Jadwal berhasil dihapus!");
  }
}

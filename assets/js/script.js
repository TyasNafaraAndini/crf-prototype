/* =====================================================================
   CRF Prototype - script.js
   Validasi client-side ringan + interaksi form.
   Validasi asli tetap dilakukan di server (lihat actions/*.php).
   ===================================================================== */

document.addEventListener('DOMContentLoaded', function () {

  /* -----------------------------------------------------------------
   * 1. Tampilkan field "Detail Kategori" sesuai kategori yang dipilih
   * ----------------------------------------------------------------- */
  var categorySelect = document.getElementById('change_category');
  var categoryDetailWrap = document.getElementById('category-detail-wrap');
  var categoryDetailLabel = document.getElementById('category-detail-label');
  var categoryDetailInput = document.getElementById('change_category_detail');

  var categoryHints = {
    'Aplikasi': 'Nama Aplikasi / Modul (contoh: CL, PKS, PKWT, Absensi)',
    'Infrastruktur': 'Jenis Infrastruktur (contoh: LAN, WAN)',
    'Proses': 'Proses yang dimaksud (contoh: Payroll)',
    'Security': 'Jenis permintaan (contoh: User ID, Password, Kewenangan Menu)',
    'Lainnya': 'Jelaskan kategori perubahan yang dimaksud'
  };

  function updateCategoryDetail() {
    if (!categorySelect) { return; }
    var val = categorySelect.value;

    if (val && categoryHints[val]) {
      categoryDetailWrap.classList.remove('d-none');
      categoryDetailLabel.textContent = 'Detail Kategori - ' + val;
      categoryDetailInput.placeholder = categoryHints[val];
      // Sesuai brief butir 14: hanya kategori "Lainnya" yang wajib diisi.
      categoryDetailInput.required = (val === 'Lainnya');
    } else {
      categoryDetailWrap.classList.add('d-none');
      categoryDetailInput.required = false;
    }
  }

  if (categorySelect) {
    categorySelect.addEventListener('change', updateCategoryDetail);
    updateCategoryDetail(); // set kondisi awal (misalnya saat edit draft)
  }

  /* -----------------------------------------------------------------
   * 2. Nominal anggaran hanya wajib jika salah satu pilihan biaya dipilih
   * ----------------------------------------------------------------- */
  var budgetRadios = document.querySelectorAll('input[name="budget_type"]');
  var budgetAmountInput = document.getElementById('budget_amount');

  function toggleBudgetAmount() {
    var anySelected = false;
    budgetRadios.forEach(function (radio) {
      if (radio.checked) { anySelected = true; }
    });
    if (budgetAmountInput) {
      budgetAmountInput.disabled = !anySelected;
    }
  }

  if (budgetRadios.length) {
    budgetRadios.forEach(function (radio) {
      radio.addEventListener('change', toggleBudgetAmount);
    });
    toggleBudgetAmount();
  }

  /* -----------------------------------------------------------------
   * 3. Tampilkan nama file yang dipilih pada input upload
   * ----------------------------------------------------------------- */
  var fileInput = document.getElementById('attachments');
  var fileList = document.getElementById('file-list-preview');

  if (fileInput && fileList) {
    fileInput.addEventListener('change', function () {
      fileList.innerHTML = '';
      if (fileInput.files.length === 0) { return; }

      var ul = document.createElement('ul');
      ul.className = 'mb-0 ps-3';

      Array.prototype.forEach.call(fileInput.files, function (file) {
        var li = document.createElement('li');
        var sizeKb = Math.round(file.size / 1024);
        li.textContent = file.name + ' (' + sizeKb + ' KB)';
        ul.appendChild(li);
      });

      fileList.appendChild(ul);
    });
  }

  /* -----------------------------------------------------------------
   * 4. Validasi Bootstrap standar untuk form yang butuh validasi
   * ----------------------------------------------------------------- */
  var formsToValidate = document.querySelectorAll('.needs-validation');

  formsToValidate.forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });

  /* -----------------------------------------------------------------
   * 5. Konfirmasi sebelum admin mengubah status ke Solve / Cancel
   * ----------------------------------------------------------------- */
  var statusSelect = document.getElementById('status');

  if (statusSelect) {
    statusSelect.addEventListener('change', function () {
      if (statusSelect.value === 'Solve') {
        if (!confirm('Yakin ingin menandai pengajuan ini sebagai Solve (Selesai)?')) {
          statusSelect.value = statusSelect.dataset.previous || 'Dalam Proses';
        }
      } else if (statusSelect.value === 'Cancel') {
        if (!confirm('Yakin ingin membatalkan pengajuan ini?')) {
          statusSelect.value = statusSelect.dataset.previous || 'Dalam Proses';
        }
      }
    });
    statusSelect.dataset.previous = statusSelect.value;
  }

});

document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('crfForm');
  const validationAlert = document.getElementById('validationAlert');
  const validationList = document.getElementById('validationList');

  if (!form || !validationAlert || !validationList) {
    return;
  }

  form.addEventListener('submit', function (event) {
    const errors = [];

    // Bersihkan pesan sebelumnya
    validationList.innerHTML = '';
    validationAlert.classList.add('d-none');

    // Helper untuk mengecek field
    function checkField(id, label) {
      const field = document.getElementById(id);

      if (!field || field.value.trim() === '') {
        errors.push(label);
      }
    }

    // Informasi Pengajuan
    checkField('full_name', 'Nama Lengkap');
    checkField('phone', 'No. Handphone/WA');
    checkField('email', 'Email');
    checkField('from_department', 'Departemen');
    checkField('from_division', 'Divisi');

    // Change Request Description
    checkField('change_description', 'Rincian Permohonan Perubahan');
    checkField('benefit', 'Benefit dari Perubahan yang Diharapkan');
    checkField('impact', 'Dampak Jika Tidak Dilakukan Perubahan');
    checkField('reason', 'Alasan Permohonan Perubahan');

    // Bukti pendukung
    const attachments = document.getElementById('attachments');

    if (!attachments || attachments.files.length === 0) {
      errors.push('Bukti dan Informasi Pendukung');
    }

        // Biaya / Anggaran
    const budgetType = document.querySelector(
      'input[name="budget_type"]:checked'
    );

    const budgetAmount = document.getElementById('budget_amount');

    // Jenis anggaran wajib dipilih
    if (!budgetType) {
      errors.push('Biaya / Anggaran');
    }

    // Nominal wajib diisi hanya jika jenis anggaran sudah dipilih
    if (
      budgetType &&
      (!budgetAmount || budgetAmount.value.trim() === '')
    ) {
      errors.push('Nominal Anggaran');
    }

    // Kategori
    checkField('change_category', 'Kategori Perubahan');

    const categoryDetail = document.getElementById('change_category_detail');

    if (!categoryDetail || categoryDetail.value.trim() === '') {
      errors.push('Detail Kategori');
    }

    // Change Request Action
    checkField('alternative_suggestion', 'Saran Alternatif');

    // Jika ada error
    if (errors.length > 0) {
      event.preventDefault();

      errors.forEach(function (error) {
        const li = document.createElement('li');
        li.textContent = error + ' belum diisi.';
        validationList.appendChild(li);
      });

      validationAlert.classList.remove('d-none');

      // Scroll ke pesan error
      validationAlert.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });

      return;
    }

    // Jika semua lengkap, form lanjut submit normal
  });
});

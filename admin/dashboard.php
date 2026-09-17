<?php
/**
 * admin/dashboard.php
 * ---------------------------------------------------------------
 * Menampilkan semua CRF yang sudah diajukan (lihat brief butir 19).
 * Prototype ini belum punya halaman login admin terpisah - halaman
 * ini bisa diakses langsung untuk keperluan pengujian alur CRF.
 * ---------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$pdo = getConnection();

$search        = trim($_GET['q'] ?? '');
$statusFilter   = $_GET['status'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

$allowedStatuses = ['Belum Ditindak Lanjuti', 'Dalam Proses', 'Solve', 'Cancel'];
$allowedCategories = ['Aplikasi', 'Infrastruktur', 'Proses', 'Security', 'Lainnya'];

$where  = [];
$params = [];

if ($search !== '') {
    $where[] = '(cr.request_number LIKE :search_request OR cr.full_name LIKE :search_name)';
    $params['search_request'] = '%' . $search . '%';
    $params['search_name'] = '%' . $search . '%';
}
if (in_array($statusFilter, $allowedStatuses, true)) {
    $where[] = 'cr.status = :status';
    $params['status'] = $statusFilter;
}
if (in_array($categoryFilter, $allowedCategories, true)) {
    $where[] = 'cr.change_category = :category';
    $params['category'] = $categoryFilter;
}

$sql = 'SELECT cr.*
        FROM change_requests cr';

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY cr.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$summaryStmt = $pdo->query(
    "SELECT
        COUNT(*) AS total,
        SUM(status = 'Belum Ditindak Lanjuti') AS pending,
        SUM(status = 'Dalam Proses') AS processing,
        SUM(status = 'Solve') AS solved,
        SUM(status = 'Cancel') AS cancelled
     FROM change_requests"
);
$summary = $summaryStmt->fetch() ?: [];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle = 'Dashboard Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="crf-page">
  <div class="container">

    <div class="crf-page-header">
      <h1>Dashboard Change Request Form</h1>
      <p>Ringkasan pengajuan Change Request milik Anda</p>
    </div>

    <div class="crf-stat-grid">
      <div class="crf-stat-card"><span>Total Pengajuan</span><strong><?= (int) ($summary['total'] ?? 0) ?></strong></div>
      <div class="crf-stat-card"><span>Belum Ditindak Lanjuti</span><strong><?= (int) ($summary['pending'] ?? 0) ?></strong></div>
      <div class="crf-stat-card"><span>Dalam Proses</span><strong><?= (int) ($summary['processing'] ?? 0) ?></strong></div>
      <div class="crf-stat-card"><span>Selesai</span><strong><?= (int) ($summary['solved'] ?? 0) ?></strong></div>
      <div class="crf-stat-card"><span>Dibatalkan</span><strong><?= (int) ($summary['cancelled'] ?? 0) ?></strong></div>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= h($flash['type']) ?> crf-alert" role="alert">
        <?= h($flash['message']) ?>
      </div>
    <?php endif; ?>

    <div class="crf-table-card">
      <div class="crf-table-heading">
        <h2>Pengajuan Terbaru</h2>
        <a href="dashboard.php" class="btn btn-sm btn-crf-outline">Lihat Semua</a>
      </div>

      <form method="GET" class="row g-2 mb-3">
        <div class="col-md-5">
          <input type="text" name="q" class="form-control" placeholder="Cari Nomor Register atau nama pengaju..."
                 value="<?= h($search) ?>">
        </div>
        <div class="col-md-3">
          <select name="status" class="form-select">
            <option value="">Semua Status</option>
            <?php foreach ($allowedStatuses as $s): ?>
              <option value="<?= h($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= h(statusLabel($s)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <select name="category" class="form-select">
            <option value="">Semua Kategori</option>
            <?php foreach ($allowedCategories as $c): ?>
              <option value="<?= h($c) ?>" <?= $categoryFilter === $c ? 'selected' : '' ?>><?= h($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-1">
          <button type="submit" class="btn btn-crf-primary w-100"><i class="bi bi-search"></i></button>
        </div>
      </form>

      <div class="table-responsive">
        <table class="table crf-table align-middle">
          <thead>
            <tr>
              <th>No</th>
              <th>Nomor Register</th>
              <th>Tanggal</th>
              <th>Pengaju</th>
              <th>Departemen</th>
              <th>Divisi</th>
              <th>Kategori</th>
              <th>Level Complain</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$requests): ?>
              <tr>
                <td colspan="10" class="text-center text-muted py-4">Belum ada CRF yang cocok dengan pencarian/filter ini.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($requests as $i => $row): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><strong><?= h($row['request_number']) ?></strong></td>
                  <td><?= h(date('d-m-Y', strtotime($row['submission_date']))) ?></td>
                  <td><?= h($row['full_name']) ?></td>
                  <td><?= h($row['from_department']) ?></td>
                  <td><?= h($row['from_division'] ?? '-') ?></td>
                  <td><?= h($row['change_category'] ?? '-') ?></td>
                  <td>
                    <span class="crf-badge <?= levelBadgeClass($row['level']) ?>">
                      <?= h($row['level'] ?? 'Belum ditentukan') ?>
                    </span>
                  </td>
                  <td>
                    <span class="crf-badge <?= statusBadgeClass($row['status']) ?>">
                      <?= h(statusLabel($row['status'])) ?>
                    </span>
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <a
                        href="detail.php?id=<?= (int) $row['id'] ?>"
                        class="btn btn-sm btn-crf-outline"
                      >
                        <i class="bi bi-eye"></i> Detail
                      </a>

                      <a
                        href="edit.php?id=<?= (int) $row['id'] ?>"
                        class="btn btn-sm btn-crf-primary"
                      >
                        <i class="bi bi-gear"></i> Kelola
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

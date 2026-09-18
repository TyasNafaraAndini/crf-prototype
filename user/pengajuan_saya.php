<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

$user = getCurrentUser();
$pdo = getConnection();

$stmt = $pdo->prepare("
    SELECT
        id,
        request_number,
        full_name,
        submission_date,
        level,
        status,
        change_description,
        tanggapan_tindak_lanjut
    FROM change_requests
    WHERE user_id = :user_id
    ORDER BY id DESC
");

$stmt->execute([
    'user_id' => $user['id']
]);

$pengajuan = $stmt->fetchAll();

/*
 * Judul halaman untuk browser dan breadcrumb.
 */
$pageTitle = 'Pengajuan Saya';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="crf-page pt-4">
    <div class="container">

        <!-- HEADER HALAMAN -->
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div class="crf-page-header">
                <h1>Pengajuan Saya</h1>

                <p class="text-muted mb-0">
                    Daftar Change Request Form yang telah Anda ajukan.
                </p>
            </div>

            <a
                href="form_crf.php"
                class="btn btn-primary"
            >
                + Buat Pengajuan
            </a>

        </div>


        <!-- TABEL PENGAJUAN -->
        <div class="card">

            <div class="card-body">

                <?php if (empty($pengajuan)): ?>

                    <div class="text-center py-5">

                        <p class="text-muted mb-3">
                            Belum ada pengajuan CRF.
                        </p>

                        <a
                            href="form_crf.php"
                            class="btn btn-primary"
                        >
                            Buat Pengajuan
                        </a>

                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle">

                            <thead>

                                <tr>
                                    <th>No</th>
                                    <th>Nomor Register</th>
                                    <th>Pengaju</th>
                                    <th>Tanggal Pengajuan</th>
                                    <th>Level Complain</th>
                                    <th>Status</th>
                                    <th>Perubahan yang Diminta</th>
                                    <th>Tanggapan / Tindak Lanjut</th>
                                    <th>Aksi</th>
                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach ($pengajuan as $index => $row): ?>

                                <tr>

                                    <!-- NO -->
                                    <td>
                                        <?= $index + 1 ?>
                                    </td>


                                    <!-- NOMOR REGISTER -->
                                    <td style="white-space: nowrap;">
                                        <?= h($row['request_number']) ?>
                                    </td>


                                    <!-- NAMA PENGAJU -->
                                    <td style="min-width: 120px; max-width: 140px;">
                                        <?= h($row['full_name']) ?>
                                    </td>


                                    <!-- TANGGAL -->
                                    <td style="white-space: nowrap;">
                                        <?php if (!empty($row['submission_date'])): ?>
                                            <?= date(
                                                'd-m-Y',
                                                strtotime($row['submission_date'])
                                            ) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>


                                    <!-- LEVEL -->
                                    <td style="white-space: nowrap;">
                                        <?= h($row['level'] ?? '-') ?>
                                    </td>


                                    <!-- STATUS -->
                                    <td style="white-space: nowrap;">

                                        <?php
                                        $status = $row['status'];

                                       if ($status === 'Belum Ditindak Lanjuti') {
                                            $badge = 'secondary';
                                        } elseif ($status === 'Perlu Revisi') {
                                            $badge = 'warning';
                                        } elseif ($status === 'Dalam Proses') {
                                            $badge = 'warning';
                                        } elseif ($status === 'Solve') {
                                            $badge = 'success';
                                        } elseif ($status === 'Cancel') {
                                            $badge = 'danger';
                                        } else {
                                            $badge = 'secondary';
                                        }
                                        ?>

                                        <span class="badge bg-<?= $badge ?>">
                                            <?= h($status) ?>
                                        </span>

                                    </td>


                                    <!-- PERUBAHAN -->
                                    <td style="min-width: 150px; max-width: 180px;">
                                        <?= nl2br(
                                            h($row['change_description'] ?? '-')
                                        ) ?>
                                    </td>


                                    <!-- TANGGAPAN -->
                                    <td style="min-width: 150px; max-width: 180px;">

                                        <?php if (!empty($row['tanggapan_tindak_lanjut'])): ?>

                                            <?= nl2br(
                                                h($row['tanggapan_tindak_lanjut'])
                                            ) ?>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                Belum ada tanggapan.
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                   <!-- AKSI -->
                                    <td style="white-space: nowrap;">

                                        <div class="d-flex gap-2">

                                            <a
                                                href="detail.php?id=<?= (int) $row['id'] ?>"
                                                class="btn btn-sm btn-primary"
                                            >
                                                Detail
                                            </a>

                                            <?php if (
                                                $row['status'] === 'Draft'
                                                || $row['status'] === 'Perlu Revisi'
                                            ): ?>

                                                <a
                                                    href="form_crf.php?id=<?= (int) $row['id'] ?>"
                                                    class="btn btn-sm btn-warning"
                                                >
                                                    <?= $row['status'] === 'Perlu Revisi'
                                                        ? 'Edit & Kirim Ulang'
                                                        : 'Edit'
                                                    ?>
                                                </a>

                                            <?php endif; ?>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
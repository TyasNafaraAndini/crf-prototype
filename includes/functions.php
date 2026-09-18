<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

/**
 * includes/functions.php
 * ---------------------------------------------------------------
 * Kumpulan fungsi bantu yang dipakai di beberapa halaman:
 * generator Nomor Register, format tanggal Indonesia, format Rupiah,
 * dan label untuk kategori/status/level.
 * ---------------------------------------------------------------
 */

/**
 * Membuat Nomor Register otomatis.
 *
 * Format mengikuti contoh pada dokumen CRF perusahaan:
 *     PPU-02.4.1234.07.26
 *
 * Dokumen perusahaan hanya memberi CONTOH format ini dan tidak
 * menjelaskan arti tiap segmen angka. Sesuai brief (butir 10), kita
 * TIDAK mengarang arti "02" dan "4" - keduanya dipertahankan persis
 * seperti contoh. Bagian yang dibuat dinamis (agar nomor unik) adalah:
 *   - 4 digit urut, diambil dari next AUTO_INCREMENT tabel change_requests
 *   - bulan & tahun pengajuan (2 digit)
 */
function generateRequestNumber(PDO $pdo, DateTime $date): string
{
    /*
     * Ambil nomor urut terbesar yang benar-benar sudah tersimpan
     * di tabel change_requests.
     *
     * Format:
     * PPU-02.4.0016.09.26
     *
     * Bagian nomor urut adalah segmen ke-3.
     */
    $stmt = $pdo->query("
        SELECT COALESCE(
            MAX(
                CAST(
                    SUBSTRING_INDEX(
                        SUBSTRING_INDEX(request_number, '.', 3),
                        '.',
                        -1
                    ) AS UNSIGNED
                )
            ),
            0
        ) AS max_sequence
        FROM change_requests
    ");

    $maxSequence = (int) $stmt->fetchColumn();

    $nextSequence = $maxSequence + 1;

    if ($nextSequence > 9999) {
        throw new RuntimeException(
            'Nomor register sudah mencapai batas maksimum 9999.'
        );
    }

    $month = $date->format('m');
    $year  = $date->format('y');

    /*
     * Pastikan nomor benar-benar unik.
     */
    do {
        $sequence = str_pad(
            (string) $nextSequence,
            4,
            '0',
            STR_PAD_LEFT
        );

        $requestNumber = sprintf(
            'PPU-02.4.%s.%s.%s',
            $sequence,
            $month,
            $year
        );

        $checkStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM change_requests
            WHERE request_number = :request_number
        ");

        $checkStmt->execute([
            'request_number' => $requestNumber
        ]);

        $exists = (int) $checkStmt->fetchColumn() > 0;

        if ($exists) {
            $nextSequence++;
        }

    } while ($exists);

    return $requestNumber;
}

/**
 * Format tanggal ke format Indonesia, contoh:
 *     Selasa, 08 September 2026
 */
function formatTanggalIndonesia(DateTime $date): string
{
    $hari  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu'];
    $bulan = [
        '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    $namaHari  = $hari[(int) $date->format('w')];
    $tanggal   = $date->format('d');
    $namaBulan = $bulan[(int) $date->format('n')];
    $tahun     = $date->format('Y');

    return "{$namaHari}, {$tanggal} {$namaBulan} {$tahun}";
}

/**
 * Format nominal ke Rupiah, contoh: Rp 15.000.000
 */
function formatRupiah($amount): string
{
    if ($amount === null || $amount === '') {
        return '-';
    }
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

/**
 * Label untuk pilihan Biaya / Anggaran (lihat brief butir 13).
 */
function budgetTypeLabel(?string $key): string
{
    switch ($key) {
        case 'rkap':
            return 'RKAP tahun berjalan';
        case 'boq_pks':
            return 'Tercantum dalam BoQ PKS';
        case 'anggaran_baru':
            return 'Akan diajukan anggaran baru';
        default:
            return '-';
    }
}

/**
 * Label tampilan Status. Solve/Cancel ditampilkan sebagai
 * "Selesai" / "Dibatalkan" 
 */
function statusLabel(string $status): string
{
    switch ($status) {
        case 'Solve':
            return 'Selesai';
        case 'Cancel':
            return 'Dibatalkan';
        default:
            return $status; 
    }
}

function statusBadgeClass(string $status): string
{
    switch ($status) {
        case 'Belum Ditindak Lanjuti':
            return 'badge-status-belum';

        case 'Dalam Proses':
            return 'badge-status-proses';

        case 'Solve':
            return 'badge-status-solve';

        case 'Cancel':
            return 'badge-status-cancel';

        default:
            return 'badge-status-belum';
    }
}

function levelBadgeClass(?string $level): string
{
    switch ($level) {
        case 'Tinggi':
            return 'badge-level-tinggi';

        case 'Normal':
            return 'badge-level-sedang';

        case 'Rendah':
            return 'badge-level-kecil';

        default:
            return 'badge-level-none';
    }
}

/**
 * Konfigurasi upload file (lihat brief butir 12).
 */
const CRF_ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
const CRF_ALLOWED_MIME_TYPES = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
];
const CRF_MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB per file

/**
 * Memproses upload lampiran (Bukti dan Informasi Pendukung).
 * Melakukan validasi extension, MIME type, dan ukuran file sebelum
 * memindahkan file ke folder uploads/ dan mencatatnya ke tabel attachments.
 *
 * @return string[] daftar pesan error (kosong jika semua berhasil / tidak ada file)
 */
function handleAttachmentUploads(PDO $pdo, int $crfId, array $filesInput): array
{
    $errors = [];

    if (empty($filesInput['name']) || empty($filesInput['name'][0])) {
        return $errors;
    }

    // Ambil konfigurasi Wasabi
    $wasabiConfig = require __DIR__ . '/../config/wasabi.php';

    // Inisialisasi S3 Client
    $s3Client = new S3Client([
        'version' => $wasabiConfig['version'],
        'region' => $wasabiConfig['region'],
        'endpoint' => $wasabiConfig['endpoint'],
        'credentials' => $wasabiConfig['credentials'],
        'use_path_style_endpoint' => $wasabiConfig['use_path_style_endpoint'],
    ]);

    $bucket = $wasabiConfig['bucket'];
    $uploadPath = rtrim($wasabiConfig['upload_path'], '/') . '/';

    $total = count($filesInput['name']);

    for ($i = 0; $i < $total; $i++) {

        if ($filesInput['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($filesInput['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = 'Gagal mengupload file "' . $filesInput['name'][$i] . '".';
            continue;
        }

        $originalName = basename($filesInput['name'][$i]);
        $tmpPath = $filesInput['tmp_name'][$i];
        $size = (int) $filesInput['size'][$i];

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Validasi extension
        if (!in_array($ext, CRF_ALLOWED_EXTENSIONS, true)) {
            $errors[] = 'Jenis file "' . $originalName . '" tidak diizinkan.';
            continue;
        }

        // Validasi ukuran
        if ($size > CRF_MAX_FILE_SIZE) {
            $errors[] = 'File "' . $originalName . '" melebihi batas ukuran 5 MB.';
            continue;
        }

        // Validasi MIME type
        $mimeType = function_exists('mime_content_type')
            ? mime_content_type($tmpPath)
            : null;

        if (
            $mimeType !== null &&
            $mimeType !== false &&
            !in_array($mimeType, CRF_ALLOWED_MIME_TYPES, true)
        ) {
            $errors[] = 'Format file "' . $originalName . '" tidak valid.';
            continue;
        }

        // Buat nama file unik
        $storedName = uniqid('crf_' . $crfId . '_', true) . '.' . $ext;

        // Path file di Wasabi
        $key = $uploadPath . $storedName;

        try {

            // Upload file ke Wasabi
            $result = $s3Client->putObject([
                'Bucket' => $bucket,
                'Key' => $key,
                'SourceFile' => $tmpPath,
                'ACL' => 'public-read',
                'ContentType' => $mimeType ?: 'application/octet-stream',
            ]);

            // URL file di Wasabi
            $fileUrl = $result['ObjectURL'];

            // Simpan informasi file ke database
            $stmt = $pdo->prepare(
                'INSERT INTO attachments
                (
                    change_request_id,
                    original_name,
                    stored_name,
                    file_path,
                    file_type,
                    file_size
                )
                VALUES
                (
                    :crf_id,
                    :original_name,
                    :stored_name,
                    :file_path,
                    :file_type,
                    :file_size
                )'
            );

            $stmt->execute([
                'crf_id' => $crfId,
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'file_path' => $fileUrl,
                'file_type' => $mimeType ?: null,
                'file_size' => $size,
            ]);

        } catch (AwsException $e) {

            $errors[] =
                'Gagal mengupload file "' .
                $originalName .
                '" ke Wasabi: ' .
                $e->getMessage();

            continue;
        }
    }

    return $errors;
}

/**
 * Daftar kategori perubahan beserta contoh placeholder untuk field
 * "Detail Kategori" (lihat brief butir 14).
 */
function categoryPlaceholder(string $category): string
{
    switch ($category) {
        case 'Aplikasi':
            return 'Contoh: Modul CL / PKS / PKWT / Absensi';
        case 'Infrastruktur':
            return 'Contoh: Jaringan Lokal (LAN) / Jaringan Internet Publik (WAN)';
        case 'Proses':
            return 'Contoh: Modul Payroll - perubahan proses pembuatan Payroll';
        case 'Security':
            return 'Contoh: Perubahan Kewenangan Menu / User ID / Password';
        case 'Lainnya':
            return 'Jelaskan kategori perubahan yang dimaksud';
        default:
            return '';
    }
}
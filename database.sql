-- =====================================================================
-- CRF PROTOTYPE - DATABASE SCHEMA
-- PT Persona Prima Utama (PPU)
-- =====================================================================
-- Cara pakai:
--   1. Buka phpMyAdmin
--   2. Buat/klik database baru bernama: crf_prototype
--      (atau langsung import file ini, karena file ini sudah membuat
--       databasenya sendiri lewat CREATE DATABASE di bawah)
--   3. Klik tab "Import", pilih file database.sql ini, klik Go
-- =====================================================================

CREATE DATABASE IF NOT EXISTS crf_prototype
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE crf_prototype;

-- ---------------------------------------------------------------------
-- Tabel: users
-- Menyimpan data user. Prototype belum punya halaman login, tapi tabel
-- ini tetap dibuat rapi supaya nanti gampang disambungkan ke SIAP PPU.
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)        NOT NULL,
    phone       VARCHAR(30)         NULL,
    email       VARCHAR(150)        NULL,
    department  VARCHAR(150)        NOT NULL,
    division    VARCHAR(150)        NULL,
    role        ENUM('user','admin') NOT NULL DEFAULT 'user',
    created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                     ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: change_requests
-- Tabel utama, satu baris = satu pengajuan Change Request Form.
-- ---------------------------------------------------------------------
CREATE TABLE change_requests (
    id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_number              VARCHAR(50)     NOT NULL UNIQUE,
    user_id                     INT UNSIGNED    NOT NULL,

    submission_date             DATE            NOT NULL,

    to_department               VARCHAR(150)    NOT NULL,
    to_division                 VARCHAR(150)    NULL,

    from_department             VARCHAR(150)    NOT NULL,
    from_division               VARCHAR(150)    NULL,

    -- Catatan: change_description, benefit, impact, reason, dan
    -- change_category dibuat NULLABLE di level database supaya status
    -- "Draft" (lihat brief butir 18) bisa menyimpan data yang belum
    -- lengkap. Field-field ini WAJIB diisi hanya ketika user menekan
    -- "Submit CRF" - aturan wajib itu ditegakkan di kode PHP
    -- (actions/submit_crf.php), bukan di skema database.
    change_description          TEXT            NULL,
    benefit                     TEXT            NULL,
    impact                      TEXT            NULL,
    reason                      TEXT            NULL,

    budget_type                 ENUM('rkap','boq_pks','anggaran_baru') NULL,
    budget_amount                DECIMAL(18,2)  NULL,

    change_category             ENUM('Aplikasi','Infrastruktur','Proses','Security','Lainnya') NULL,
    change_category_detail      VARCHAR(255)    NULL,

    alternative_suggestion      TEXT            NULL,

    post_implementation_review  TEXT            NULL,
    implementation              TEXT            NULL,

    level                       ENUM('Kecil','Sedang','Tinggi') NULL DEFAULT NULL,
    status                      ENUM('Draft','Dalam Proses','Solve','Cancel') NOT NULL DEFAULT 'Draft',

    solved_at                   DATETIME        NULL,
    cancelled_at                DATETIME        NULL,

    created_at                  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                 ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_crf_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: attachments
-- Bukti dan informasi pendukung yang diupload user. Satu CRF bisa
-- punya beberapa file, karena itu dipisah ke tabel sendiri.
-- ---------------------------------------------------------------------
CREATE TABLE attachments (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    change_request_id   INT UNSIGNED    NOT NULL,
    original_name       VARCHAR(255)    NOT NULL,
    stored_name         VARCHAR(255)    NOT NULL,
    file_path           VARCHAR(500)    NOT NULL,
    file_type           VARCHAR(100)    NULL,
    file_size           INT UNSIGNED    NULL,
    uploaded_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_attachment_crf
        FOREIGN KEY (change_request_id) REFERENCES change_requests(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- DATA DUMMY
-- ---------------------------------------------------------------------

-- User biasa (id = 1) -> disimulasikan sebagai user yang sedang login
INSERT INTO users (id, name, phone, email, department, division, role) VALUES
(1, 'User Demo', '081234567890', 'userdemo@ptppu.co.id',
 'Departemen Teknologi Informasi', 'IT Support', 'user');

-- Admin dummy (id = 2)
INSERT INTO users (id, name, phone, email, department, division, role) VALUES
(2, 'Admin CRF', NULL, 'admincrf@ptppu.co.id',
 'Departemen Operasional', 'Divisi Otomasi', 'admin');

-- Catatan:
-- - Karena kolom id di atas diisi manual, AUTO_INCREMENT untuk tabel
--   users akan otomatis dilanjutkan dari nilai tertinggi (3) oleh MySQL.
-- - Belum ada contoh data di change_requests / attachments; data akan
--   terisi dengan sendirinya saat prototype dipakai (submit form).

-- =====================================================================
-- CRF PROTOTYPE - DATABASE SCHEMA
-- PT Persona Prima Utama (PPU)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS crf_prototype
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE crf_prototype;

-- ---------------------------------------------------------------------
-- Tabel: users
-- prototype CRF.
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    userid          VARCHAR(50)     NULL,
    password        VARCHAR(255)    NOT NULL,
    password_new    VARCHAR(75)     NULL,

    nama            VARCHAR(75)     NULL,
    dept            VARCHAR(75)     NULL,
    divisi          VARCHAR(50)     NULL,

    email           VARCHAR(150)    NULL,
    no_wa           VARCHAR(25)     NULL,

    tgl_insert      TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP,
    lastlogin       DATETIME        NULL,

    ganti_password  ENUM('1','2')   NULL,
    gender          VARCHAR(7)      NULL,

    atasan_id       VARCHAR(10)     NULL,
    atasan_nama     VARCHAR(75)     NULL,
    atasan_telp     VARCHAR(25)     NULL,

    kpu_kode        VARCHAR(3)      NULL,
    kpu_nama        VARCHAR(75)     NULL,
    npp             VARCHAR(50)     NULL,

    status_wa       ENUM('BLM','SDH') NOT NULL DEFAULT 'BLM',
    pusat           ENUM('YES','NO')  NOT NULL DEFAULT 'NO',

    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
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
    -- change_category dibuat NULLABLE di level database supaya
    -- validasi kelengkapan data dapat ditegakkan oleh kode PHP.
    -- Field-field tersebut WAJIB diisi ketika user menekan
    -- "Submit CRF" - aturan wajib ditegakkan di kode PHP
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

    level                       ENUM('Tinggi','Normal','Rendah') NULL DEFAULT NULL,
    status                      ENUM(
                                    'Belum Ditindak Lanjuti',
                                    'Dalam Proses',
                                    'Solve',
                                    'Cancel'
                                ) NOT NULL DEFAULT 'Belum Ditindak Lanjuti',

    tanggapan_tindak_lanjut     TEXT            NULL,

    approval_at                 DATETIME        NULL,
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

-- User 
INSERT INTO users (
    id,
    userid,
    password,
    nama,
    dept,
    divisi,
    email,
    no_wa
) VALUES (
    1,
    'USER001',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC8T9hS2YqJYQ1Q8q7i',
    'User Demo',
    'Departemen Teknologi Informasi',
    'IT Support',
    'user@ppu.test',
    '081234567890'
);

-- Akun demo kedua
INSERT INTO users (
    id,
    userid,
    password,
    nama,
    dept,
    divisi,
    email,
    no_wa
) VALUES (
    2,
    'ADMIN001',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC8T9hS2YqJYQ1Q8q7i',
    'Admin CRF',
    'Departemen Operasional',
    'Divisi Otomasi',
    'admin@ppu.test',
    NULL
);



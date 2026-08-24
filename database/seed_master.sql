-- =====================================================================
-- FinAccChain - master/reference data + demo accounts
-- Run AFTER schema.sql
-- Demo password for ALL demo accounts below: Demo@12345
-- =====================================================================
USE finaccchain;

-- ---------------------------------------------------------------------
-- roles
-- ---------------------------------------------------------------------
INSERT INTO roles (code, name, description) VALUES
('admin', 'Admin/Researcher', 'Mengelola pengguna, UMKM, validator, aturan, dan dashboard riset'),
('msme', 'UMKM', 'Pelaku UMKM: transaksi keuangan, akuntansi, dan laporan akuntabilitas'),
('validator', 'Validator/Auditor', 'Memverifikasi transaksi dan audit trail serta memberi penilaian ahli');

-- ---------------------------------------------------------------------
-- demo users  (password: Demo@12345)
-- ---------------------------------------------------------------------
INSERT INTO users (id, role_id, name, email, password_hash, is_active, is_demo) VALUES
(1, 1, 'Peneliti Admin', 'admin@finaccchain.demo', '$2y$10$h2jup.ftt77UxTvkaKEnleVbvAqiyhlfsZnPr3P9SoLt5UU70D8s2', 1, 1),
(2, 2, 'Siti Rahma (Kedai Kopi Deli)', 'umkm1@finaccchain.demo', '$2y$10$h2jup.ftt77UxTvkaKEnleVbvAqiyhlfsZnPr3P9SoLt5UU70D8s2', 1, 1),
(3, 2, 'Budi Santoso (Konveksi Medan Jaya)', 'umkm2@finaccchain.demo', '$2y$10$h2jup.ftt77UxTvkaKEnleVbvAqiyhlfsZnPr3P9SoLt5UU70D8s2', 1, 1),
(4, 2, 'Maria Simanjuntak (Toko Kerupuk Belawan)', 'umkm3@finaccchain.demo', '$2y$10$h2jup.ftt77UxTvkaKEnleVbvAqiyhlfsZnPr3P9SoLt5UU70D8s2', 1, 1),
(5, 3, 'Dr. Andi Pratama, Ak. (Validator/Auditor)', 'validator@finaccchain.demo', '$2y$10$h2jup.ftt77UxTvkaKEnleVbvAqiyhlfsZnPr3P9SoLt5UU70D8s2', 1, 1);

-- ---------------------------------------------------------------------
-- demo msmes
-- ---------------------------------------------------------------------
INSERT INTO msmes (id, user_id, business_name, owner_name, sector, address, business_age_years, employee_count, monthly_turnover_category, digital_payment_usage, fintech_usage, accounting_method, business_status, is_demo) VALUES
(1, 2, 'Kedai Kopi Deli', 'Siti Rahma', 'Food & Beverage', 'Jl. Setia Budi, Medan Selayang, Kota Medan', 3, 4, '10-50jt', 'full', 'payment_financing', 'spreadsheet', 'active', 1),
(2, 3, 'Konveksi Medan Jaya', 'Budi Santoso', 'Manufaktur/Konveksi', 'Jl. Marelan Raya, Medan Marelan, Kota Medan', 6, 9, '50-300jt', 'partial', 'payment_only', 'manual', 'active', 1),
(3, 4, 'Toko Kerupuk Belawan', 'Maria Simanjuntak', 'Industri Rumahan/Makanan Olahan', 'Jl. Belawan Bahagia, Medan Belawan, Kota Medan', 2, 2, '5-10jt', 'partial', 'none', 'none', 'active', 1);

-- ---------------------------------------------------------------------
-- chart of accounts
-- ---------------------------------------------------------------------
INSERT INTO accounts (code, name, type, normal_balance) VALUES
('1000', 'Kas', 'asset', 'debit'),
('1010', 'Bank', 'asset', 'debit'),
('1020', 'E-Wallet / Dompet Digital', 'asset', 'debit'),
('1100', 'Piutang Usaha', 'asset', 'debit'),
('1200', 'Persediaan Barang', 'asset', 'debit'),
('2000', 'Utang Usaha', 'liability', 'credit'),
('2100', 'Utang Pembiayaan Digital (Fintech Lending)', 'liability', 'credit'),
('3000', 'Modal Pemilik', 'equity', 'credit'),
('4000', 'Pendapatan Penjualan', 'revenue', 'credit'),
('4900', 'Pendapatan Lain-lain', 'revenue', 'credit'),
('5000', 'Beban Pokok Penjualan / Pembelian', 'expense', 'debit'),
('5100', 'Beban Operasional', 'expense', 'debit'),
('5900', 'Beban Lain-lain', 'expense', 'debit');

-- ---------------------------------------------------------------------
-- smart contract rule engine configuration (deterministic, simulated)
-- ---------------------------------------------------------------------
INSERT INTO smart_contract_rules (rule_code, rule_name, description, rule_type, parameters_json, is_active, sort_order) VALUES
('RC001', 'Data Completeness Check', 'Memastikan field wajib (tanggal, jenis, jumlah, pihak, channel pembayaran) terisi lengkap.', 'completeness', '{"required_fields":["transaction_date","type","amount","payment_channel"]}', 1, 1),
('RC002', 'Duplicate Transaction Check', 'Mendeteksi kemungkinan transaksi duplikat (UMKM, jumlah, tanggal, dan pihak yang sama dalam rentang waktu berdekatan).', 'duplicate', '{"window_days":1}', 1, 2),
('RC003', 'Authorization Check', 'Memastikan transaksi diinput oleh pengguna dengan hak akses UMKM yang berwenang atas data tersebut.', 'authorization', '{"allowed_roles":["msme","admin"]}', 1, 3),
('RC004', 'Evidence Attachment Check', 'Mewajibkan lampiran bukti transaksi untuk nominal di atas ambang batas tertentu.', 'evidence', '{"required_above_amount":1000000}', 1, 4),
('RC005', 'Amount Threshold Check', 'Menandai transaksi bernilai besar untuk perhatian/validasi tambahan.', 'threshold', '{"warning_amount":10000000,"validator_required_amount":5000000}', 1, 5),
('RC006', 'Accounting Classification Check', 'Menentukan klasifikasi akun debit/kredit otomatis berdasarkan jenis transaksi.', 'classification', '{}', 1, 6),
('RC007', 'Validator Approval Requirement', 'Mewajibkan persetujuan validator/auditor untuk transaksi di atas ambang batas nilai tertentu.', 'validator_approval', '{"required_when_amount_gte":5000000}', 1, 7);

-- ---------------------------------------------------------------------
-- research settings (configurable accountability weights + thresholds)
-- ---------------------------------------------------------------------
INSERT INTO research_settings (setting_key, setting_value, description) VALUES
('accountability_weight_completeness', '15', 'Bobot indikator Completeness (%)'),
('accountability_weight_accuracy', '15', 'Bobot indikator Accuracy (%)'),
('accountability_weight_transparency', '10', 'Bobot indikator Transparency (%)'),
('accountability_weight_traceability', '15', 'Bobot indikator Traceability (%)'),
('accountability_weight_timeliness', '10', 'Bobot indikator Timeliness (%)'),
('accountability_weight_authorization', '10', 'Bobot indikator Authorization (%)'),
('accountability_weight_internal_control', '15', 'Bobot indikator Internal Control (%)'),
('accountability_weight_auditability', '10', 'Bobot indikator Auditability (%)'),
('accountability_formula_version', 'v1.0-research-draft', 'Versi formula indeks akuntabilitas (belum divalidasi ahli sampai data validasi ahli tersedia)'),
('evidence_required_amount', '1000000', 'Ambang nominal wajib lampiran bukti (Rp)'),
('validator_required_amount', '5000000', 'Ambang nominal wajib persetujuan validator (Rp)'),
('app_disclaimer', 'Prototipe riset TKT 3 - simulasi rule engine dan hash-chain, bukan jaringan blockchain produksi maupun koneksi API fintech nyata.', 'Disclaimer yang ditampilkan pada UI'),
('timeliness_max_days', '3', 'Batas hari wajar antara tanggal transaksi dan tanggal input untuk indikator Timeliness');

-- ---------------------------------------------------------------------
-- demo questionnaire
-- ---------------------------------------------------------------------
INSERT INTO questionnaires (title, description, target_role, questions_json, is_active) VALUES
('Kuesioner Kesiapan Digital & Akuntabilitas UMKM', 'Instrumen riset untuk menilai adopsi fintech dan praktik akuntabilitas keuangan UMKM di Kota Medan.', 'msme',
'[{"code":"Q1","text":"Seberapa sering UMKM Anda menggunakan pembayaran digital (QRIS/e-wallet/transfer bank)?","type":"scale_1_5"},{"code":"Q2","text":"Apakah UMKM Anda mencatat transaksi keuangan secara rutin?","type":"scale_1_5"},{"code":"Q3","text":"Seberapa yakin Anda terhadap keakuratan catatan transaksi Anda saat ini?","type":"scale_1_5"},{"code":"Q4","text":"Apakah Anda pernah menggunakan layanan pembiayaan digital (fintech lending)?","type":"yes_no"},{"code":"Q5","text":"Seberapa penting menurut Anda transparansi jejak audit transaksi bagi kepercayaan mitra usaha/pemberi modal?","type":"scale_1_5"},{"code":"Q6","text":"Kendala utama apa yang Anda hadapi dalam pencatatan akuntansi UMKM?","type":"text"}]', 1),
('Instrumen Penilaian Ahli Model FinAccChain', 'Instrumen expert judgment untuk menilai relevansi, kelayakan, dan kontribusi model integrasi smart contract-fintech terhadap akuntabilitas keuangan UMKM.', 'validator',
'[{"code":"E1","text":"Relevansi model terhadap kebutuhan akuntabilitas keuangan UMKM","type":"scale_1_5"},{"code":"E2","text":"Kejelasan alur proses rule engine dan hash-chain","type":"scale_1_5"},{"code":"E3","text":"Kelayakan implementasi pada konteks UMKM Kota Medan","type":"scale_1_5"}]', 1);

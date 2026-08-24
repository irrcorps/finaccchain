-- =====================================================================
-- FinAccChain - Smart Financial Accountability for MSMEs
-- Research prototype (TKT 3) database schema
-- Penelitian: Perancangan Model Integrasi Smart Contract Berbasis Fintech
-- untuk Penguatan Akuntabilitas Keuangan UMKM dalam Ekosistem Hilirisasi
-- Ekonomi Digital di Kota Medan.
--
-- NOTE: "smart_contract_rules", "transaction_validations", and the hash
-- chain in audit_trails implement a DETERMINISTIC RULE ENGINE simulation,
-- not a real blockchain / mainnet / smart contract deployment.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS finaccchain;
CREATE DATABASE finaccchain CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE finaccchain;

-- ---------------------------------------------------------------------
-- 1. roles
-- ---------------------------------------------------------------------
CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,      -- admin, msme, validator
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. users
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_demo TINYINT(1) NOT NULL DEFAULT 0,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id),
    INDEX idx_users_role (role_id)
) ENGINE=InnoDB;

CREATE TABLE password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(100) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pwreset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. msmes
-- ---------------------------------------------------------------------
CREATE TABLE msmes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    business_name VARCHAR(150) NOT NULL,
    owner_name VARCHAR(150) NOT NULL,
    sector VARCHAR(100) NOT NULL,
    address VARCHAR(255) NULL,
    business_age_years TINYINT UNSIGNED NULL,
    employee_count SMALLINT UNSIGNED NULL,
    monthly_turnover_category ENUM('<5jt','5-10jt','10-50jt','50-300jt','>300jt') NOT NULL DEFAULT '<5jt',
    digital_payment_usage ENUM('none','partial','full') NOT NULL DEFAULT 'partial',
    fintech_usage ENUM('none','payment_only','payment_financing','full_integration') NOT NULL DEFAULT 'none',
    accounting_method ENUM('manual','spreadsheet','accounting_app','none') NOT NULL DEFAULT 'manual',
    business_status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    is_demo TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_msme_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_msme_sector (sector)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. accounts (chart of accounts)
-- ---------------------------------------------------------------------
CREATE TABLE accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    type ENUM('asset','liability','equity','revenue','expense') NOT NULL,
    normal_balance ENUM('debit','credit') NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. transactions
-- ---------------------------------------------------------------------
CREATE TABLE transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    msme_id INT UNSIGNED NOT NULL,
    transaction_uid VARCHAR(40) NOT NULL UNIQUE,   -- e.g. TRX-20260824-0001
    transaction_date DATE NOT NULL,
    type ENUM('sales','purchase','operating_expense','receivable','payable','capital','financing','other_income','other_expense') NOT NULL,
    party_name VARCHAR(150) NULL,
    description VARCHAR(255) NULL,
    amount DECIMAL(18,2) NOT NULL,
    payment_channel ENUM('cash','bank_transfer','qr_payment','e_wallet','digital_financing') NOT NULL DEFAULT 'cash',
    debit_account_id INT UNSIGNED NULL,
    credit_account_id INT UNSIGNED NULL,
    status ENUM('draft','pending','validated','rejected','recorded','reversed') NOT NULL DEFAULT 'draft',
    rejected_reason VARCHAR(255) NULL,
    reversal_of_id INT UNSIGNED NULL,
    created_by INT UNSIGNED NOT NULL,
    approved_by INT UNSIGNED NULL,
    is_demo TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_trx_msme FOREIGN KEY (msme_id) REFERENCES msmes(id) ON DELETE CASCADE,
    CONSTRAINT fk_trx_debit_acc FOREIGN KEY (debit_account_id) REFERENCES accounts(id),
    CONSTRAINT fk_trx_credit_acc FOREIGN KEY (credit_account_id) REFERENCES accounts(id),
    CONSTRAINT fk_trx_reversal FOREIGN KEY (reversal_of_id) REFERENCES transactions(id),
    CONSTRAINT fk_trx_creator FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_trx_approver FOREIGN KEY (approved_by) REFERENCES users(id),
    INDEX idx_trx_msme (msme_id),
    INDEX idx_trx_status (status),
    INDEX idx_trx_date (transaction_date),
    INDEX idx_trx_type (type)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. transaction_evidence
-- ---------------------------------------------------------------------
CREATE TABLE transaction_evidence (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NULL,
    file_size INT UNSIGNED NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evidence_trx FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    CONSTRAINT fk_evidence_user FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_evidence_trx (transaction_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. fintech_transactions (simulated payment channel metadata)
-- ---------------------------------------------------------------------
CREATE TABLE fintech_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT UNSIGNED NOT NULL,
    channel ENUM('bank_transfer','qr_payment','e_wallet','digital_financing') NOT NULL,
    reference_id VARCHAR(60) NOT NULL UNIQUE,
    payment_status ENUM('pending','success','failed') NOT NULL DEFAULT 'success',
    metadata_json TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_fintech_trx FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    INDEX idx_fintech_channel (channel)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. smart_contract_rules (conceptual rule-engine configuration)
-- ---------------------------------------------------------------------
CREATE TABLE smart_contract_rules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rule_code VARCHAR(50) NOT NULL UNIQUE,
    rule_name VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL,
    rule_type ENUM('completeness','duplicate','authorization','evidence','threshold','classification','validator_approval') NOT NULL,
    parameters_json TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. transaction_validations (per-rule pipeline execution log)
-- ---------------------------------------------------------------------
CREATE TABLE transaction_validations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT UNSIGNED NOT NULL,
    rule_id INT UNSIGNED NOT NULL,
    step_name VARCHAR(100) NOT NULL,
    result ENUM('pass','fail','warning') NOT NULL,
    notes VARCHAR(255) NULL,
    validated_by INT UNSIGNED NULL,
    validated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_val_trx FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    CONSTRAINT fk_val_rule FOREIGN KEY (rule_id) REFERENCES smart_contract_rules(id),
    CONSTRAINT fk_val_user FOREIGN KEY (validated_by) REFERENCES users(id),
    INDEX idx_val_trx (transaction_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 10. journals
-- ---------------------------------------------------------------------
CREATE TABLE journals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT UNSIGNED NOT NULL,
    journal_date DATE NOT NULL,
    reference_no VARCHAR(40) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_journal_trx FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    INDEX idx_journal_date (journal_date)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 11. journal_details
-- ---------------------------------------------------------------------
CREATE TABLE journal_details (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    journal_id INT UNSIGNED NOT NULL,
    account_id INT UNSIGNED NOT NULL,
    debit DECIMAL(18,2) NOT NULL DEFAULT 0,
    credit DECIMAL(18,2) NOT NULL DEFAULT 0,
    description VARCHAR(255) NULL,
    CONSTRAINT fk_jd_journal FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE CASCADE,
    CONSTRAINT fk_jd_account FOREIGN KEY (account_id) REFERENCES accounts(id),
    INDEX idx_jd_journal (journal_id),
    INDEX idx_jd_account (account_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 12. audit_trails (hash-chain simulation)
-- ---------------------------------------------------------------------
CREATE TABLE audit_trails (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT UNSIGNED NOT NULL,
    action VARCHAR(60) NOT NULL,          -- submitted, validated, rejected, recorded, reversed
    actor_id INT UNSIGNED NULL,
    current_hash CHAR(64) NOT NULL,
    previous_hash CHAR(64) NULL,
    payload_snapshot TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_trx FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    CONSTRAINT fk_audit_actor FOREIGN KEY (actor_id) REFERENCES users(id),
    INDEX idx_audit_trx (transaction_id),
    INDEX idx_audit_hash (current_hash)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 13. accountability_assessments
-- ---------------------------------------------------------------------
CREATE TABLE accountability_assessments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    msme_id INT UNSIGNED NOT NULL,
    assessment_date DATE NOT NULL,
    period_label VARCHAR(30) NULL,
    overall_score DECIMAL(5,2) NOT NULL,
    computed_by INT UNSIGNED NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_assess_msme FOREIGN KEY (msme_id) REFERENCES msmes(id) ON DELETE CASCADE,
    CONSTRAINT fk_assess_user FOREIGN KEY (computed_by) REFERENCES users(id),
    INDEX idx_assess_msme (msme_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 14. accountability_details
-- ---------------------------------------------------------------------
CREATE TABLE accountability_details (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT UNSIGNED NOT NULL,
    indicator_code ENUM('completeness','accuracy','transparency','traceability','timeliness','authorization','internal_control','auditability') NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    weight DECIMAL(5,2) NOT NULL,
    notes VARCHAR(255) NULL,
    CONSTRAINT fk_detail_assess FOREIGN KEY (assessment_id) REFERENCES accountability_assessments(id) ON DELETE CASCADE,
    INDEX idx_detail_assess (assessment_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 15. expert_validations
-- ---------------------------------------------------------------------
CREATE TABLE expert_validations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expert_user_id INT UNSIGNED NOT NULL,
    msme_id INT UNSIGNED NULL,
    relevance TINYINT UNSIGNED NOT NULL,
    clarity TINYINT UNSIGNED NOT NULL,
    feasibility TINYINT UNSIGNED NOT NULL,
    accounting_adequacy TINYINT UNSIGNED NOT NULL,
    technological_adequacy TINYINT UNSIGNED NOT NULL,
    fintech_integration TINYINT UNSIGNED NOT NULL,
    smart_contract_logic TINYINT UNSIGNED NOT NULL,
    accountability_contribution TINYINT UNSIGNED NOT NULL,
    usefulness TINYINT UNSIGNED NOT NULL,
    comments TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_expval_user FOREIGN KEY (expert_user_id) REFERENCES users(id),
    CONSTRAINT fk_expval_msme FOREIGN KEY (msme_id) REFERENCES msmes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 16. questionnaires
-- ---------------------------------------------------------------------
CREATE TABLE questionnaires (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL,
    target_role ENUM('msme','validator','general') NOT NULL DEFAULT 'general',
    questions_json TEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 17. questionnaire_responses
-- ---------------------------------------------------------------------
CREATE TABLE questionnaire_responses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    questionnaire_id INT UNSIGNED NOT NULL,
    respondent_user_id INT UNSIGNED NULL,
    respondent_name VARCHAR(150) NULL,
    respondent_type VARCHAR(50) NULL,
    answers_json TEXT NOT NULL,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_qresp_q FOREIGN KEY (questionnaire_id) REFERENCES questionnaires(id) ON DELETE CASCADE,
    CONSTRAINT fk_qresp_user FOREIGN KEY (respondent_user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 18. research_settings (configurable formula weights, disclaimers, etc.)
-- ---------------------------------------------------------------------
CREATE TABLE research_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    description VARCHAR(255) NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

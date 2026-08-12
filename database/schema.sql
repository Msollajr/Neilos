-- ============================================================
-- Neilos Partner & Contractor Portal — Full Unified Database Schema
-- MySQL 8.0+
-- Run: mysql -u root -p neilos_portal < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS neilos_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE neilos_portal;

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. partners
-- ------------------------------------------------------------
DROP TABLE IF EXISTS partners;
CREATE TABLE partners (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(200) NOT NULL,
    trading_name        VARCHAR(200),
    partner_type        ENUM('ISP','Reseller','VAR','Enterprise','Government','Other') NOT NULL DEFAULT 'ISP',
    kyc_type            ENUM('Partner','Contractor') NOT NULL DEFAULT 'Partner',
    customer_category   VARCHAR(100),
    industry_sector     VARCHAR(100),
    nature_of_business  TEXT,
    registration_number VARCHAR(100),
    tin                 VARCHAR(100),
    vat_vrn             VARCHAR(100),
    address             TEXT,
    city_region         VARCHAR(100),
    country             VARCHAR(100) DEFAULT 'Tanzania',
    status              ENUM('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_kyc_type (kyc_type)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. users
-- ------------------------------------------------------------
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id          INT UNSIGNED NULL COMMENT 'NULL = admin/internal user',
    full_name           VARCHAR(200) NOT NULL,
    username            VARCHAR(100) NOT NULL UNIQUE,
    email               VARCHAR(200) NOT NULL UNIQUE,
    password            VARCHAR(255) NOT NULL,
    mobile              VARCHAR(30) NOT NULL,
    role                ENUM(
                            'System Admin',
                            'KAM',
                            'BSA',
                            'Management',
                            'Project Manager',
                            'Commercial',
                            'Director',
                            'NOC Support',
                            'NOC Core',
                            'NOC Level 3',
                            'Billing',
                            'Project Team',
                            'Engineering Coordinator',
                            'Partner User',
                            'Contractor User'
                        ) NOT NULL DEFAULT 'Partner User',
    is_first_login      TINYINT(1) NOT NULL DEFAULT 1,
    otp_secret          VARCHAR(32) NULL,
    otp_enabled         TINYINT(1) NOT NULL DEFAULT 0,
    otp_verified        TINYINT(1) NOT NULL DEFAULT 0,
    profile_picture     VARCHAR(500) NULL,
    status              ENUM('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE SET NULL,
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. partner_kyc_applications
-- ------------------------------------------------------------
DROP TABLE IF EXISTS partner_kyc_applications;
CREATE TABLE partner_kyc_applications (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id              INT UNSIGNED NOT NULL,
    kyc_type                ENUM('Partner','Contractor') NOT NULL DEFAULT 'Partner',
    registered_name         VARCHAR(200) NOT NULL,
    trading_name            VARCHAR(200),
    registration_number     VARCHAR(100) NOT NULL,
    tin                     VARCHAR(100) NOT NULL,
    vat_vrn                 VARCHAR(100),
    customer_category       VARCHAR(100),
    industry_sector         VARCHAR(100),
    nature_of_business      TEXT,
    office_address          TEXT NOT NULL,
    city_region             VARCHAR(100) NOT NULL,
    country                 VARCHAR(100) DEFAULT 'Tanzania',
    website                 VARCHAR(200),
    main_contact_name       VARCHAR(200) NULL,
    main_contact_phone      VARCHAR(50)  NULL,
    main_contact_email      VARCHAR(200) NULL,
    ops_contact_name        VARCHAR(200) NULL,
    ops_contact_phone       VARCHAR(50)  NULL,
    ops_contact_email       VARCHAR(200) NULL,
    tech_supervisor_name    VARCHAR(200) NULL,
    tech_supervisor_phone   VARCHAR(50)  NULL,
    tech_supervisor_email   VARCHAR(200) NULL,
    escalation_contact_name VARCHAR(200) NULL,
    escalation_contact_phone VARCHAR(50) NULL,
    escalation_contact_email VARCHAR(200) NULL,
    bank_name               VARCHAR(200) NULL,
    bank_branch             VARCHAR(200) NULL,
    bank_account_name       VARCHAR(200) NULL,
    bank_account_number     VARCHAR(100) NULL,
    bank_payment_terms      VARCHAR(200) NULL,
    cap_ftth_install        TINYINT(1) DEFAULT 0,
    cap_sme_install         TINYINT(1) DEFAULT 0,
    cap_enterprise_install  TINYINT(1) DEFAULT 0,
    cap_site_survey         TINYINT(1) DEFAULT 0,
    cap_maintenance         TINYINT(1) DEFAULT 0,
    cap_remote_support      TINYINT(1) DEFAULT 0,
    service_regions         TEXT NULL,
    business_license        VARCHAR(500) NULL,
    hse_certificate         VARCHAR(500) NULL,
    trca_certificate        VARCHAR(500) NULL,
    custom_fields           JSON NULL COMMENT 'IT-added custom fields',
    status                  ENUM('Draft','Submitted','Under Review','Approved','Rejected','Changes Requested') NOT NULL DEFAULT 'Draft',
    rejection_reason        TEXT,
    changes_requested_note  TEXT,
    submitted_at            DATETIME,
    reviewed_at             DATETIME,
    reviewed_by             INT UNSIGNED,
    created_at              DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_kyc_type (kyc_type)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. kyc_documents
-- ------------------------------------------------------------
DROP TABLE IF EXISTS kyc_documents;
CREATE TABLE kyc_documents (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id  INT UNSIGNED NOT NULL,
    doc_type        ENUM('certificate_of_incorporation','tin_certificate','vrn_certificate','business_license','tax_clearance','bank_proof','id_copy','other') NOT NULL,
    file_name       VARCHAR(255) NOT NULL,
    file_path       VARCHAR(500) NOT NULL,
    file_size       INT UNSIGNED,
    uploaded_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES partner_kyc_applications(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. services
-- ------------------------------------------------------------
DROP TABLE IF EXISTS services;
CREATE TABLE services (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150) NOT NULL,
    code            VARCHAR(50) NOT NULL UNIQUE,
    category        ENUM('Internet','Ethernet','IP Transit','Colocation','Cloud','Voice','Managed Services') NOT NULL,
    description     TEXT,
    unit            VARCHAR(50) DEFAULT 'Mbps',
    base_nrc        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    base_mrc        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. orders
-- ------------------------------------------------------------
DROP TABLE IF EXISTS orders;
CREATE TABLE orders (
    id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number                VARCHAR(50) NOT NULL UNIQUE,
    partner_id                  INT UNSIGNED NOT NULL,
    user_id                     INT UNSIGNED NOT NULL,
    service_id                  INT UNSIGNED NOT NULL,
    capacity                    INT UNSIGNED NOT NULL,
    capacity_unit               VARCHAR(20) DEFAULT 'Mbps',
    pop_location                VARCHAR(200) NOT NULL,
    customer_name               VARCHAR(200) NOT NULL,
    end_user_address            TEXT NOT NULL,
    city_region                 VARCHAR(100) NOT NULL,
    gps_coordinates             VARCHAR(100),
    site_category               ENUM('Home (SDU)','SME','Enterprise','Apartment (MDU)','Corporate','Tower','Datacenter') NULL,
    interface_type              VARCHAR(50) DEFAULT 'SFP',
    connector_type              VARCHAR(50) DEFAULT 'LC',
    media_type                  VARCHAR(50) DEFAULT 'Single-mode Fiber',
    power_type                  VARCHAR(50) DEFAULT 'AC 220V',
    rack_space                  VARCHAR(50),
    nrc_usd                     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    mrc_usd                     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    vat_amount                  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_mrc_incl_vat          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    standard_mrc                DECIMAL(12,2) NULL,
    revised_mrc                 DECIMAL(12,2) NULL,
    mrc_justification           TEXT NULL,
    kam_approved_at             DATETIME NULL,
    kam_approved_by             INT UNSIGNED NULL,
    management_approved_at      DATETIME NULL,
    management_approved_by      INT UNSIGNED NULL,
    management_approved_price   DECIMAL(12,2) NULL,
    management_remarks          TEXT NULL,
    management_remarks_visible  TINYINT(1) DEFAULT 0,
    sof_generated_at            DATETIME NULL,
    sof_uploaded_at             DATETIME NULL,
    sof_signed_file             VARCHAR(500) NULL,
    sof_signed_filename         VARCHAR(255) NULL,
    countersigned_sof_at        DATETIME NULL,
    countersigned_sof_file      VARCHAR(500) NULL,
    countersigned_sof_filename  VARCHAR(255) NULL,
    countersigned_sof_by        INT UNSIGNED NULL,
    sof_return_comments         TEXT NULL,
    return_reason               VARCHAR(100) NULL,
    return_remarks              TEXT NULL,
    return_route                ENUM('BSA','KAM') NULL,
    returned_by                 INT UNSIGNED NULL,
    returned_at                 DATETIME NULL,
    contract_term_months        INT UNSIGNED DEFAULT 12,
    service_type                VARCHAR(100) NOT NULL DEFAULT 'FTTH',
    status                      ENUM(
                                    'Feasibility Review',
                                    'Await Commercial Approval',
                                    'Management Approval',
                                    'Pending SOF',
                                    'SOF Review',
                                    'Installation',
                                    'Testing',
                                    'UAT',
                                    'Closed',
                                    'Submitted',
                                    'Awaiting BSA Approval',
                                    'Awaiting Commercial Approval',
                                    'Awaiting Management Approval',
                                    'Approved',
                                    'Provisioning',
                                    'UAT - Awaiting Confirmation',
                                    'Activated',
                                    'Billing Triggered',
                                    'Cancelled',
                                    'Not Feasible'
                                ) NOT NULL DEFAULT 'Feasibility Review',
    bsa_reviewed_by             INT UNSIGNED,
    bsa_reviewed_at             DATETIME,
    bsa_feasi_approved          TINYINT(1),
    bsa_remarks                 TEXT,
    bsa_assigned_engineer       VARCHAR(150),
    bsa_target_completion_date  DATE,
    bsa_special_conditions      TEXT,
    bsa_technical_result       ENUM('Technically Feasible','Technically Not Feasible') NULL,
    bsa_not_feasible_reason     TEXT NULL,
    standard_nrc                DECIMAL(12,2) DEFAULT 60.00,
    revised_nrc                 DECIMAL(12,2) NULL,
    nrc_justification           TEXT NULL,
    bsa_approved_at             DATETIME NULL,
    bsa_approved_by             INT UNSIGNED NULL,
    bsa_revision_note           TEXT NULL,
    kam_reviewed_by             INT UNSIGNED,
    kam_reviewed_at             DATETIME,
    kam_remarks                 TEXT,
    commercial_discount_pct     DECIMAL(5,2) DEFAULT 0.00,
    closed_date                 DATE NULL,
    billing_start_date          DATE NULL,
    billing_start_adjusted_by   INT UNSIGNED NULL,
    billing_start_adjusted_at   DATETIME NULL,
    uat_submitted_at            DATETIME,
    uat_accepted_at             DATETIME,
    uat_accepted_by             INT UNSIGNED NULL,
    uat_rejected_at             DATETIME,
    uat_rejection_reason        TEXT,
    uat_return_reason           TEXT NULL,
    uat_signoff_file            VARCHAR(500),
    billing_trigger_date        DATE,
    cancellation_reason         TEXT,
    created_at                  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at                  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (service_id) REFERENCES services(id),
    FOREIGN KEY (bsa_reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (bsa_approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (kam_reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (kam_approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (management_approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (countersigned_sof_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (returned_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (uat_accepted_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (billing_start_adjusted_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_partner (partner_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. order_timeline
-- ------------------------------------------------------------
DROP TABLE IF EXISTS order_timeline;
CREATE TABLE order_timeline (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    status          VARCHAR(100) NOT NULL,
    action          VARCHAR(200) NOT NULL,
    notes           TEXT,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 8. order_documents
-- ------------------------------------------------------------
DROP TABLE IF EXISTS order_documents;
CREATE TABLE order_documents (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED NOT NULL,
    doc_type        ENUM('sof','feasibility_report','diagram','uat_signoff','completion_certificate','invoice','other') NOT NULL,
    file_name       VARCHAR(255) NOT NULL,
    file_path       VARCHAR(500) NOT NULL,
    file_size       INT UNSIGNED,
    uploaded_by     INT UNSIGNED NOT NULL,
    uploaded_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 9. invoices
-- ------------------------------------------------------------
DROP TABLE IF EXISTS invoices;
CREATE TABLE invoices (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number  VARCHAR(50) NOT NULL UNIQUE,
    order_id        INT UNSIGNED NOT NULL,
    partner_id      INT UNSIGNED NOT NULL,
    invoice_type    ENUM('NRC','MRC','NRC_MRC','Usage') NOT NULL DEFAULT 'NRC_MRC',
    amount_usd      DECIMAL(12,2) NOT NULL,
    vat_usd         DECIMAL(12,2) NOT NULL,
    total_usd       DECIMAL(12,2) NOT NULL,
    amount_tzs      DECIMAL(14,2) NOT NULL,
    vat_tzs         DECIMAL(14,2) NOT NULL,
    total_tzs       DECIMAL(14,2) NOT NULL,
    exchange_rate   DECIMAL(10,4) NOT NULL DEFAULT 2550.0000,
    period_start    DATE NOT NULL,
    period_end      DATE NOT NULL,
    due_date        DATE NOT NULL,
    status          ENUM('Draft','Issued','Paid','Partially Paid','Overdue','Cancelled') NOT NULL DEFAULT 'Draft',
    pdf_path        VARCHAR(500),
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (partner_id) REFERENCES partners(id),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 10. notifications
-- ------------------------------------------------------------
DROP TABLE IF EXISTS notifications;
CREATE TABLE notifications (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    title           VARCHAR(200) NOT NULL,
    message         TEXT NOT NULL,
    link            VARCHAR(500),
    is_read         TINYINT(1) NOT NULL DEFAULT 0,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 11. contractor_assignments
-- ------------------------------------------------------------
DROP TABLE IF EXISTS contractor_assignments;
CREATE TABLE contractor_assignments (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id              INT UNSIGNED NOT NULL,
    contractor_partner_id INT UNSIGNED NOT NULL COMMENT 'partners.id where kyc_type=Contractor',
    contractor_user_id    INT UNSIGNED NULL COMMENT 'Specific contractor user assigned',
    assigned_by           INT UNSIGNED NOT NULL COMMENT 'Project Manager user id',
    assigned_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
    target_date           DATE NULL,
    work_order_notes      TEXT NULL,
    status                ENUM('Assigned','Accepted','In Progress','Completed Submitted','Returned','Completed') DEFAULT 'Assigned',
    accepted_by           INT UNSIGNED NULL,
    accepted_at           DATETIME NULL,
    completed_at          DATETIME NULL,
    completion_remarks    TEXT NULL,
    FOREIGN KEY (order_id)              REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (contractor_partner_id) REFERENCES partners(id),
    FOREIGN KEY (contractor_user_id)    REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_by)           REFERENCES users(id),
    FOREIGN KEY (accepted_by)           REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order (order_id),
    INDEX idx_contractor (contractor_partner_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 12. contractor_progress_updates
-- ------------------------------------------------------------
DROP TABLE IF EXISTS contractor_progress_updates;
CREATE TABLE contractor_progress_updates (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id   INT UNSIGNED NOT NULL,
    order_id        INT UNSIGNED NOT NULL,
    updated_by      INT UNSIGNED NOT NULL,
    progress_status ENUM('In Progress','Delayed','Blocked','Completed') NOT NULL DEFAULT 'In Progress',
    delay_reason    ENUM('Customer Unavailable','Access Denied','Weather','Missing Materials','Technical Issue','Other') NULL,
    notes           TEXT NOT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES contractor_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id)      REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by)    REFERENCES users(id),
    INDEX idx_assignment (assignment_id),
    INDEX idx_order (order_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 13. contractor_evidence
-- ------------------------------------------------------------
DROP TABLE IF EXISTS contractor_evidence;
CREATE TABLE contractor_evidence (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id   INT UNSIGNED NOT NULL,
    order_id        INT UNSIGNED NOT NULL,
    evidence_type   ENUM(
                        'Site Photo',
                        'ONT/ONU Serial',
                        'Signal Test',
                        'Speed Test',
                        'Latency Test',
                        'UAT Sign-off',
                        'Installation Remarks',
                        'Other'
                    ) NOT NULL,
    serial_number   VARCHAR(200) NULL COMMENT 'For ONT/ONU serial number',
    notes           TEXT NULL,
    file_name       VARCHAR(255) NULL,
    file_path       VARCHAR(500) NULL,
    file_size       INT UNSIGNED NULL,
    uploaded_by     INT UNSIGNED NOT NULL,
    uploaded_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_mandatory    TINYINT(1) DEFAULT 0,
    FOREIGN KEY (assignment_id) REFERENCES contractor_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id)      REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by)   REFERENCES users(id),
    INDEX idx_assignment (assignment_id),
    INDEX idx_order (order_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 14. order_returns
-- ------------------------------------------------------------
DROP TABLE IF EXISTS order_returns;
CREATE TABLE order_returns (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED NOT NULL,
    returned_by     INT UNSIGNED NOT NULL,
    returned_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    from_status     VARCHAR(100) NOT NULL,
    to_status       VARCHAR(100) NOT NULL,
    return_reason   VARCHAR(200) NOT NULL,
    return_remarks  TEXT NULL,
    routed_to       ENUM('BSA','KAM','Project Manager','Contractor','Partner') NOT NULL,
    old_nrc         DECIMAL(12,2) NULL,
    new_nrc         DECIMAL(12,2) NULL,
    old_mrc         DECIMAL(12,2) NULL,
    new_mrc         DECIMAL(12,2) NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (returned_by) REFERENCES users(id),
    INDEX idx_order (order_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 15. evidence_checklist_config
-- ------------------------------------------------------------
DROP TABLE IF EXISTS evidence_checklist_config;
CREATE TABLE evidence_checklist_config (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_type    VARCHAR(100) NOT NULL,
    evidence_type   ENUM(
                        'Site Photo',
                        'ONT/ONU Serial',
                        'Signal Test',
                        'Speed Test',
                        'Latency Test',
                        'UAT Sign-off',
                        'Installation Remarks',
                        'Other'
                    ) NOT NULL,
    is_mandatory    TINYINT(1) DEFAULT 1,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_service_evidence (service_type, evidence_type)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 16. sla_config
-- ------------------------------------------------------------
DROP TABLE IF EXISTS sla_config;
CREATE TABLE sla_config (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sla_name        VARCHAR(200) NOT NULL,
    sla_type        VARCHAR(100) NOT NULL COMMENT 'stage, site_type, service_type',
    target_hours    INT NOT NULL COMMENT 'Working hours',
    description     TEXT NULL,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- INITIAL SEED DATA
-- Default Passwords: Admin@1234
-- Hash: $2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u
-- ============================================================

-- Seed Partners (Partner & Contractor)
INSERT INTO partners (id, name, trading_name, partner_type, kyc_type, registration_number, tin, address, city_region, status) VALUES
(1, 'FastNet Communications Ltd', 'FastNet', 'ISP', 'Partner', 'REG-100234', '100-234-567', 'Plot 12, New Bagamoyo Rd, Kijitonyama', 'Dar es Salaam', 'Active'),
(2, 'Afrilink Solutions Ltd', 'Afrilink', 'Reseller', 'Partner', 'REG-100567', '100-567-890', 'Victoria House, 4th Floor, Victoria', 'Dar es Salaam', 'Active'),
(3, 'Kilimanjaro Telecom Ltd', 'KiliTel', 'VAR', 'Partner', 'REG-100890', '100-890-123', 'Sokoine Road, Central Area', 'Arusha', 'Active'),
(4, 'Fiber Works Ltd', 'FiberWorks', 'Other', 'Contractor', 'REG-CONT-001', 'CONT-998877', '45 Industrial Area, Dar es Salaam', 'Dar es Salaam', 'Active');

-- Seed Users
INSERT INTO users (id, partner_id, full_name, username, email, password, mobile, role, is_first_login, otp_verified) VALUES
(1, NULL, 'System Administrator', 'admin', 'admin@neilosnetwork.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000001', 'System Admin', 0, 1),
(2, NULL, 'John KAM', 'kam_john', 'john.kam@neilosnetwork.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000002', 'KAM', 0, 1),
(3, NULL, 'Sarah BSA', 'bsa_sarah', 'sarah.bsa@neilosnetwork.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000003', 'BSA', 0, 1),
(4, NULL, 'NOC Support Lead', 'noc_support', 'noc.support@neilosnetwork.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000004', 'NOC Support', 0, 1),
(5, NULL, 'Commercial Director', 'commercial_dir', 'commercial@neilosnetwork.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000005', 'Commercial', 0, 1),
(6, 1, 'Partner Admin (FastNet)', 'partner_fastnet', 'admin@fastnet.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000006', 'Partner User', 0, 1),
(7, 2, 'Partner Admin (Afrilink)', 'partner_afrilink', 'admin@afrilink.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000007', 'Partner User', 0, 1),
(10, NULL, 'Operations Manager', 'manager', 'manager@neilosnetwork.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000010', 'Management', 0, 1),
(11, NULL, 'Project Manager', 'pm', 'pm@neilosnetwork.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000011', 'Project Manager', 0, 1),
(12, NULL, 'Finance / Billing', 'billing', 'billing@neilosnetwork.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000012', 'Billing', 0, 1),
(13, 4, 'Contractor Admin', 'contractor1', 'admin@fiberworks.co.tz', '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000013', 'Contractor User', 0, 1);

-- Seed Services
INSERT INTO services (id, name, code, category, description, unit, base_nrc, base_mrc) VALUES
(1, 'Dedicated Internet Access (DIA)', 'DIA-STD', 'Internet', 'High-speed, symmetrical, uncontended DIA with guaranteed SLA.', 'Mbps', 300.00, 15.00),
(2, 'Layer 2 Ethernet Point-to-Point', 'EPL-STD', 'Ethernet', 'Point-to-point Layer 2 Ethernet connection over optical fiber.', 'Mbps', 250.00, 12.00),
(3, 'Colocation Rack Space (Full Rack)', 'COLO-FULL', 'Colocation', 'Full 42U rack space with dual A+B power feeds and precision cooling.', 'Rack', 500.00, 800.00),
(4, 'IP Transit Service', 'IPT-STD', 'IP Transit', 'BGP IP Transit with multiple Tier-1 upstream paths.', 'Mbps', 200.00, 8.00),
(5, 'FTTH Wholesale Fiber Connection', 'FTTH-WHOLESALE', 'Internet', 'Wholesale last-mile FTTH connection for ISPs and Resellers.', 'Mbps', 60.00, 8.00);

-- Seed Evidence Checklist
INSERT INTO evidence_checklist_config (service_type, evidence_type, is_mandatory) VALUES
('FTTH', 'Site Photo',           1),
('FTTH', 'ONT/ONU Serial',       1),
('FTTH', 'Signal Test',          1),
('FTTH', 'Speed Test',           1),
('FTTH', 'Latency Test',         0),
('FTTH', 'UAT Sign-off',         1),
('FTTH', 'Installation Remarks', 1),
('FTTB', 'Site Photo',           1),
('FTTB', 'ONT/ONU Serial',       1),
('FTTB', 'Signal Test',          1),
('FTTB', 'Speed Test',           1),
('FTTB', 'Latency Test',         1),
('FTTB', 'UAT Sign-off',         1),
('FTTB', 'Installation Remarks', 1),
('DIA', 'Site Photo',            1),
('DIA', 'ONT/ONU Serial',        1),
('DIA', 'Signal Test',           1),
('DIA', 'Speed Test',            1),
('DIA', 'Latency Test',          1),
('DIA', 'UAT Sign-off',          1),
('DIA', 'Installation Remarks',  1),
('Dedicated Layer 2', 'Site Photo',           1),
('Dedicated Layer 2', 'Signal Test',          1),
('Dedicated Layer 2', 'Speed Test',           1),
('Dedicated Layer 2', 'Latency Test',         1),
('Dedicated Layer 2', 'UAT Sign-off',         1),
('Dedicated Layer 2', 'Installation Remarks', 1),
('Remote Hands Only', 'Site Photo',           1),
('Remote Hands Only', 'Installation Remarks', 1);

-- Seed SLA Config
INSERT INTO sla_config (sla_name, sla_type, target_hours, description) VALUES
('Feasibility Acknowledgement',        'stage',     8,  'BSA must acknowledge within 1 working day'),
('In Coverage Installation',           'site_type',  16, 'Standard serviceable location - 2 working days'),
('Apartment without MDU',              'site_type',  24, 'MDU readiness required - 3 working days'),
('Extension above 200m',              'site_type',  40, 'Extension work required - 5 working days'),
('Pole + Extension',                  'site_type',  56, 'Pole and extension required - 7 working days'),
('Testing Review after Completion',   'stage',      8,  'PM/BSA reviews same day or next working day'),
('Partner UAT Response',              'stage',      48, '48 hours for partner UAT acceptance');

SET FOREIGN_KEY_CHECKS = 1;

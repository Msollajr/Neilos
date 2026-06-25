-- ============================================================
-- Neilos Partner Portal — Full Database Schema
-- MySQL 8.0+
-- Run: mysql -u root -p neilos_portal < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS neilos_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE neilos_portal;

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- partners
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS partners (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(200) NOT NULL,
    trading_name    VARCHAR(200),
    partner_type    ENUM('ISP','Reseller','VAR','Enterprise','Government','Other') NOT NULL DEFAULT 'ISP',
    customer_category VARCHAR(100),
    industry_sector VARCHAR(100),
    nature_of_business TEXT,
    registration_number VARCHAR(100),
    tin             VARCHAR(100),
    vat_vrn         VARCHAR(100),
    address         TEXT,
    city_region     VARCHAR(100),
    country         VARCHAR(100) DEFAULT 'Tanzania',
    status          ENUM('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id      INT UNSIGNED NULL COMMENT 'NULL = admin/internal user',
    full_name       VARCHAR(200) NOT NULL,
    username        VARCHAR(100) NOT NULL UNIQUE,
    email           VARCHAR(200) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    mobile          VARCHAR(30) NOT NULL,
    role            ENUM(
                        'System Admin',
                        'KAM',
                        'BSA',
                        'Commercial',
                        'Director',
                        'NOC Support',
                        'NOC Core',
                        'NOC Level 3',
                        'Billing',
                        'Project Team',
                        'Engineering Coordinator',
                        'Partner User'
                    ) NOT NULL DEFAULT 'Partner User',
    is_first_login  TINYINT(1) NOT NULL DEFAULT 1,
    otp_verified    TINYINT(1) NOT NULL DEFAULT 0,
    otp_code        VARCHAR(10) NULL,
    otp_expires_at  DATETIME NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    last_login      DATETIME NULL,
    created_by      INT UNSIGNED NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    profile_picture VARCHAR(300) NULL,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE SET NULL,
    INDEX idx_role (role),
    INDEX idx_partner (partner_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- orders
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number            VARCHAR(20) NOT NULL UNIQUE,
    partner_id              INT UNSIGNED NOT NULL,
    kam_id                  INT UNSIGNED NULL,
    -- Customer Info
    customer_name           VARCHAR(200) NOT NULL,
    customer_location       VARCHAR(300),
    gps_coordinates         VARCHAR(100),
    building_name           VARCHAR(200),
    floor_number            VARCHAR(50),
    apartment_number        VARCHAR(50),
    customer_contact_name   VARCHAR(200),
    customer_contact_phone  VARCHAR(50),
    customer_contact_email  VARCHAR(200),
    -- Service Info
    service_type            ENUM('FTTH','FTTB','DIA','Dedicated Layer 2','Remote Hands Only') NOT NULL,
    fttx_package            VARCHAR(50) COMMENT '20Mbps, 30Mbps, etc.',
    bandwidth               VARCHAR(50) COMMENT 'For DIA: e.g. 100 Mbps',
    nni_location            VARCHAR(200) COMMENT 'For L2',
    aggregate_capacity      VARCHAR(50) COMMENT 'For L2: 1 Gbps, etc.',
    contract_term           VARCHAR(100),
    special_requirements    TEXT,
    -- Assigned KAM name (stored for display)
    assigned_kam_name       VARCHAR(200),
    -- Commercials
    usd_tzs_rate            DECIMAL(10,4) DEFAULT 2585.0000,
    base_nrc_usd            DECIMAL(12,2) DEFAULT 60.00,
    remote_hands_nrc_usd    DECIMAL(12,2) DEFAULT 0.00,
    nrc_subtotal_usd        DECIMAL(12,2) DEFAULT 0.00,
    vat_on_nrc              DECIMAL(12,2) DEFAULT 0.00,
    total_nrc_incl_vat      DECIMAL(12,2) DEFAULT 0.00,
    base_mrc                DECIMAL(12,2) DEFAULT 0.00,
    mrc_currency            ENUM('USD','TZS') DEFAULT 'TZS',
    discount_pct            DECIMAL(5,2) DEFAULT 0.00,
    discount_amount         DECIMAL(12,2) DEFAULT 0.00,
    vat_on_mrc              DECIMAL(12,2) DEFAULT 0.00,
    total_mrc_incl_vat      DECIMAL(12,2) DEFAULT 0.00,
    -- Workflow
    status                  ENUM(
                                'Submitted',
                                'Feasibility Review',
                                'Awaiting BSA Approval',
                                'Awaiting Commercial Approval',
                                'Awaiting Management Approval',
                                'Approved',
                                'Provisioning',
                                'Installation',
                                'Testing',
                                'UAT',
                                'UAT - Awaiting Confirmation',
                                'Activated',
                                'Billing Triggered',
                                'Closed',
                                'Cancelled'
                            ) NOT NULL DEFAULT 'Submitted',
    current_owner_id        INT UNSIGNED NULL,
    current_owner_role      VARCHAR(100),
    circuit_id              VARCHAR(100),
    service_id              VARCHAR(100),
    -- BSA Solution Design fields
    bsa_feasibility_status  ENUM('Serviceable','Capacity Upgrade Required','Construction Required','No Coverage') NULL,
    bsa_delivery_method     VARCHAR(200),
    bsa_delivery_cost       DECIMAL(12,2) NULL,
    bsa_sla_level           VARCHAR(100),
    bsa_lead_time           VARCHAR(100),
    bsa_special_conditions  TEXT,
    bsa_reviewed_by         INT UNSIGNED NULL,
    bsa_reviewed_at         DATETIME NULL,
    -- UAT
    uat_notified_at         DATETIME NULL,
    uat_deadline            DATETIME NULL,
    uat_accepted_at         DATETIME NULL,
    uat_rejected_at         DATETIME NULL,
    uat_rejection_reason    TEXT,
    -- Activation
    activation_date         DATE NULL,
    billing_trigger_date    DATE NULL,
    -- Meta
    created_by              INT UNSIGNED NOT NULL,
    created_at              DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners(id),
    FOREIGN KEY (kam_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (current_owner_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_partner (partner_id),
    INDEX idx_status (status),
    INDEX idx_service_type (service_type),
    INDEX idx_order_number (order_number)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- order_timeline
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_timeline (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id    INT UNSIGNED NOT NULL,
    status      VARCHAR(100),
    note        TEXT,
    changed_by  INT UNSIGNED NULL,
    changed_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order (order_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- order_documents
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_documents (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED NOT NULL,
    document_type   VARCHAR(100),
    file_name       VARCHAR(255),
    file_path       VARCHAR(500),
    file_size       INT UNSIGNED,
    uploaded_by     INT UNSIGNED NULL,
    uploaded_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order (order_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- bulk_upload_batches
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bulk_upload_batches (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_number    VARCHAR(30) NOT NULL UNIQUE,
    partner_id      INT UNSIGNED NOT NULL,
    uploaded_by     INT UNSIGNED NOT NULL,
    file_name       VARCHAR(255),
    file_path       VARCHAR(500),
    total_rows      INT DEFAULT 0,
    valid_rows      INT DEFAULT 0,
    invalid_rows    INT DEFAULT 0,
    orders_created  INT DEFAULT 0,
    status          ENUM('Processing','Completed','Failed') DEFAULT 'Processing',
    error_log       TEXT,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- partner_kyc_applications
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS partner_kyc_applications (
    id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id                  INT UNSIGNED NOT NULL UNIQUE,
    -- Partner Details
    registered_name             VARCHAR(200),
    trading_name                VARCHAR(200),
    partner_type                VARCHAR(100),
    customer_category           VARCHAR(100),
    industry_sector             VARCHAR(100),
    nature_of_business          TEXT,
    registration_number         VARCHAR(100),
    tin                         VARCHAR(100),
    vat_vrn                     VARCHAR(100),
    address                     TEXT,
    city_region                 VARCHAR(100),
    country                     VARCHAR(100) DEFAULT 'Tanzania',
    -- Authorized Signatory
    auth_signatory_name         VARCHAR(200),
    auth_signatory_title        VARCHAR(100),
    auth_signatory_dept         VARCHAR(100),
    auth_signatory_id_type      VARCHAR(100),
    auth_signatory_id_number    VARCHAR(100),
    auth_signatory_mobile       VARCHAR(30),
    auth_signatory_email        VARCHAR(200),
    -- Finance & Billing Contact
    finance_contact_name        VARCHAR(200),
    finance_contact_title       VARCHAR(100),
    finance_contact_mobile      VARCHAR(30),
    finance_contact_email       VARCHAR(200),
    billing_email               VARCHAR(200),
    -- Technical Contact
    tech_contact_name           VARCHAR(200),
    tech_contact_title          VARCHAR(100),
    tech_contact_mobile         VARCHAR(30),
    tech_contact_email          VARCHAR(200),
    -- Countersigned KYC
    countersigned_kyc_file      VARCHAR(500),
    countersigned_kyc_filename  VARCHAR(255),
    countersigned_kyc_date      DATETIME NULL,
    -- Workflow
    status                      ENUM('Draft','Submitted','Under Review','Approved','Rejected') DEFAULT 'Draft',
    submitted_at                DATETIME NULL,
    reviewed_by                 INT UNSIGNED NULL,
    reviewed_at                 DATETIME NULL,
    review_notes                TEXT,
    created_at                  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at                  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- partner_kyc_application_documents
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS partner_kyc_application_documents (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kyc_application_id  INT UNSIGNED NOT NULL,
    document_type       VARCHAR(200) NOT NULL,
    is_mandatory        TINYINT(1) DEFAULT 0,
    status              ENUM('Not Uploaded','Uploaded','Verified','Rejected') DEFAULT 'Not Uploaded',
    file_name           VARCHAR(255),
    file_path           VARCHAR(500),
    upload_date         DATETIME NULL,
    remarks             TEXT,
    FOREIGN KEY (kyc_application_id) REFERENCES partner_kyc_applications(id) ON DELETE CASCADE,
    INDEX idx_kyc_app (kyc_application_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- active_services
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS active_services (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id          VARCHAR(100) NOT NULL UNIQUE COMMENT 'e.g. SVC-260619-001',
    order_id            INT UNSIGNED NULL,
    partner_id          INT UNSIGNED NOT NULL,
    customer_name       VARCHAR(200),
    service_type        ENUM('FTTH','FTTB','DIA','Dedicated Layer 2','Remote Hands Only') NOT NULL,
    circuit_id          VARCHAR(100),
    bandwidth_capacity  VARCHAR(100),
    location            VARCHAR(300),
    building_name       VARCHAR(200),
    kam_id              INT UNSIGNED NULL,
    activation_date     DATE,
    billing_start_date  DATE,
    status              ENUM('Active','Suspended','Terminated') DEFAULT 'Active',
    monitoring_status   ENUM('Online','Offline','Degraded','Unknown') DEFAULT 'Unknown',
    onu_serial          VARCHAR(100),
    router_serial       VARCHAR(100),
    ip_address          VARCHAR(50),
    notes               TEXT,
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners(id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (kam_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_partner (partner_id),
    INDEX idx_service_type (service_type),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- trouble_tickets
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS trouble_tickets (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_number           VARCHAR(20) NOT NULL UNIQUE,
    -- Service Link
    active_service_id       INT UNSIGNED NOT NULL,
    service_id              VARCHAR(100),
    partner_id              INT UNSIGNED NOT NULL,
    customer_name           VARCHAR(200),
    service_type            VARCHAR(50),
    circuit_id              VARCHAR(100),
    bandwidth_capacity      VARCHAR(100),
    location                VARCHAR(300),
    kam_id                  INT UNSIGNED NULL,
    activation_date         DATE NULL,
    -- Ticket Details
    fault_category          ENUM(
                                'Network Outage',
                                'Power Issue',
                                'Fiber Cut',
                                'High Latency',
                                'Packet Loss',
                                'Bandwidth Degradation',
                                'ONU / ONT Fault',
                                'CPE Fault',
                                'Configuration Issue',
                                'NNI Issue',
                                'IP Transit Issue',
                                'Peering Issue',
                                'Remote Hands Request',
                                'Service Activation Issue',
                                'Billing Related',
                                'Other'
                            ) NOT NULL,
    severity                ENUM('Sev 1','Sev 2','Sev 3','Sev 4','Critical','Standard','Planned') NOT NULL,
    description             TEXT NOT NULL,
    -- Queue & Status
    current_queue           ENUM('NOC Support','NOC Core','NOC Level 3','Director') DEFAULT 'NOC Support',
    status                  ENUM(
                                'Open',
                                'Assigned',
                                'In Progress',
                                'Resolved - Awaiting Customer Confirmation',
                                'Closed',
                                'Reopened'
                            ) DEFAULT 'Open',
    -- Opener
    opened_by               INT UNSIGNED NOT NULL,
    opened_by_type          ENUM('Partner','NOC','Admin') DEFAULT 'Partner',
    assigned_to             INT UNSIGNED NULL,
    -- SLA
    response_time_mins      INT COMMENT 'SLA target in minutes',
    resolution_time_mins    INT COMMENT 'SLA target in minutes',
    sla_status              ENUM('Normal','Warning','Breached','Critical Breach') DEFAULT 'Normal',
    sla_pct_consumed        DECIMAL(5,2) DEFAULT 0.00,
    responded_at            DATETIME NULL,
    -- Resolution
    resolved_at             DATETIME NULL,
    noc_resolution_time_mins INT NULL COMMENT 'Excludes customer wait time',
    customer_wait_time_mins INT NULL,
    sla_clock_stopped_at    DATETIME NULL COMMENT 'When sent to awaiting confirmation',
    sla_clock_consumed_mins INT DEFAULT 0 COMMENT 'Accumulated NOC time before any pause',
    -- Customer Closure
    awaiting_confirmation_since DATETIME NULL,
    auto_close_at           DATETIME NULL COMMENT 'awaiting_confirmation_since + 24 hrs',
    reopen_reason           TEXT,
    -- Meta
    created_at              DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (active_service_id) REFERENCES active_services(id),
    FOREIGN KEY (partner_id) REFERENCES partners(id),
    FOREIGN KEY (kam_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (opened_by) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ticket_number (ticket_number),
    INDEX idx_partner (partner_id),
    INDEX idx_status (status),
    INDEX idx_queue (current_queue),
    INDEX idx_sla_status (sla_status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- ticket_timeline
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ticket_timeline (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id   INT UNSIGNED NOT NULL,
    action      VARCHAR(200),
    status      VARCHAR(100),
    queue       VARCHAR(100),
    note        TEXT,
    changed_by  INT UNSIGNED NULL,
    changed_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES trouble_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ticket (ticket_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- ticket_notes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ticket_notes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id   INT UNSIGNED NOT NULL,
    note        TEXT NOT NULL,
    note_type   ENUM('Internal','Partner Visible') DEFAULT 'Internal',
    created_by  INT UNSIGNED NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES trouble_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ticket (ticket_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- ticket_escalations
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ticket_escalations (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    esc_number          VARCHAR(20) NOT NULL UNIQUE,
    ticket_id           INT UNSIGNED NOT NULL,
    escalation_level    TINYINT NOT NULL COMMENT '1=80%, 2=100%, 3=125%',
    from_queue          VARCHAR(100),
    to_queue            VARCHAR(100),
    sla_pct             DECIMAL(5,2),
    notification_status ENUM('Pending','Sent','Failed') DEFAULT 'Pending',
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES trouble_tickets(id) ON DELETE CASCADE,
    INDEX idx_ticket (ticket_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- ticket_notifications
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ticket_notifications (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id           INT UNSIGNED NOT NULL,
    escalation_id       INT UNSIGNED NULL,
    notification_type   ENUM('Email','WhatsApp','SMS') DEFAULT 'Email',
    recipient           VARCHAR(200),
    subject             VARCHAR(300),
    message             TEXT,
    status              ENUM('Queued','Sent','Failed') DEFAULT 'Queued',
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    sent_at             DATETIME NULL,
    FOREIGN KEY (ticket_id) REFERENCES trouble_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (escalation_id) REFERENCES ticket_escalations(id) ON DELETE SET NULL,
    INDEX idx_ticket (ticket_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- projects
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS projects (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id                INT UNSIGNED NOT NULL UNIQUE,
    partner_id              INT UNSIGNED NOT NULL,
    project_name            VARCHAR(300),
    status                  ENUM('Not Started','In Progress','On Hold','Completed','Cancelled') DEFAULT 'Not Started',
    start_date              DATE NULL,
    target_date             DATE NULL,
    actual_completion_date  DATE NULL,
    assigned_to             INT UNSIGNED NULL,
    notes                   TEXT,
    created_at              DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (partner_id) REFERENCES partners(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_partner (partner_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- project_tasks
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS project_tasks (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id      INT UNSIGNED NOT NULL,
    task_name       VARCHAR(300) NOT NULL,
    description     TEXT,
    assigned_to     INT UNSIGNED NULL,
    status          ENUM('Pending','In Progress','Completed','Blocked') DEFAULT 'Pending',
    due_date        DATE NULL,
    completed_at    DATETIME NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- project_milestones
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS project_milestones (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id      INT UNSIGNED NOT NULL,
    milestone_name  VARCHAR(300) NOT NULL,
    target_date     DATE NULL,
    actual_date     DATE NULL,
    status          ENUM('Pending','Achieved','Missed') DEFAULT 'Pending',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- assets (inventory)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS assets (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id          INT UNSIGNED NULL,
    active_service_id   INT UNSIGNED NULL,
    order_id            INT UNSIGNED NULL,
    asset_type          ENUM('Router','ONU','Switch','SFP','Other') NOT NULL,
    serial_number       VARCHAR(200),
    model               VARCHAR(200),
    customer_name       VARCHAR(200),
    site_location       VARCHAR(300),
    status              ENUM('In Stock','Deployed','Faulty','Returned','Retired') DEFAULT 'In Stock',
    notes               TEXT,
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE SET NULL,
    FOREIGN KEY (active_service_id) REFERENCES active_services(id) ON DELETE SET NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_partner (partner_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- audit_logs
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,
    action      VARCHAR(200) NOT NULL,
    module      VARCHAR(100),
    record_id   INT UNSIGNED NULL,
    old_value   TEXT,
    new_value   TEXT,
    ip_address  VARCHAR(50),
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_module (module),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default admin partner (internal Neilos staff)
INSERT INTO partners (id, name, trading_name, partner_type, status) VALUES
(1, 'Neilos Network', 'Neilos', 'Other', 'Active'),
(2, 'Savanna ISP Ltd', 'Savanna ISP', 'ISP', 'Active'),
(3, 'TechConnect Tanzania', 'TechConnect', 'Reseller', 'Active');

-- Users (passwords are bcrypt of 'Admin@1234' and 'Partner@1234')
-- Admin@1234 for internal users, password for partners
INSERT INTO users (id, partner_id, full_name, username, email, password, mobile, role, is_first_login, otp_verified) VALUES
(1,  NULL, 'System Administrator', 'admin',    'admin@neilosnetwork.co.tz',       '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000001', 'System Admin',  0, 1),
(2,  NULL, 'Gloria Entebbe',       'gloria',   'gloria@neilosnetwork.co.tz',      '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000002', 'KAM',           0, 1),
(3,  NULL, 'Michael Corss',        'michael',  'michael@neilosnetwork.co.tz',     '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000003', 'KAM',           0, 1),
(4,  NULL, 'BSA Engineer',         'bsa',      'bsa@neilosnetwork.co.tz',         '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000004', 'BSA',           0, 1),
(5,  NULL, 'NOC Support Agent',    'noc1',     'noc1@neilosnetwork.co.tz',        '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000005', 'NOC Support',   0, 1),
(6,  NULL, 'NOC Core Engineer',    'noc_core', 'noc_core@neilosnetwork.co.tz',    '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000006', 'NOC Core',      0, 1),
(7,  NULL, 'Director',             'director', 'director@neilosnetwork.co.tz',    '$2y$10$AFWPBl7pldA0p2fypm36LeD/9R0Cxdkz/G/v9jiLyw.bNKUiM/Z9u', '0712000007', 'Director',      0, 1),
(8,  2,    'Savanna Partner User', 'savanna',  'partner@savannasp.co.tz',         '$2y$10$CCAHGOczvCh0WFb0ICoefOGgjZWEqwaiOJ9Uxj3Pq2pqVc6nRBaN6', '0712000008', 'Partner User',  0, 1),
(9,  3,    'TechConnect User',     'techuser', 'partner@techconnect.co.tz',       '$2y$10$CCAHGOczvCh0WFb0ICoefOGgjZWEqwaiOJ9Uxj3Pq2pqVc6nRBaN6', '0712000009', 'Partner User',  0, 1);
-- NOTE: Internal user passwords = 'Admin@1234', Partner passwords = 'password'
-- To generate: php -r "echo password_hash('Admin@1234', PASSWORD_BCRYPT);"

-- Sample KYC Application for Savanna ISP
INSERT INTO partner_kyc_applications (partner_id, registered_name, trading_name, partner_type, registration_number, tin, address, city_region, country, auth_signatory_name, auth_signatory_email, auth_signatory_mobile, billing_email, tech_contact_name, tech_contact_email, status) VALUES
(2, 'Savanna ISP Limited', 'Savanna ISP', 'ISP', 'REG-2019-001234', 'TIN-001234567', '123 Uhuru Street, Dar es Salaam', 'Dar es Salaam', 'Tanzania', 'John Savanna', 'john@savannasp.co.tz', '0712100001', 'billing@savannasp.co.tz', 'Tech Support', 'tech@savannasp.co.tz', 'Draft');

-- KYC documents for Savanna
INSERT INTO partner_kyc_application_documents (kyc_application_id, document_type, is_mandatory, status) VALUES
(1, 'Signed MSA', 1, 'Not Uploaded'),
(1, 'Signed SOF', 1, 'Not Uploaded'),
(1, 'Certificate of Incorporation / Compliance / Registration', 1, 'Not Uploaded'),
(1, 'TIN Certificate', 1, 'Not Uploaded'),
(1, 'Authorized Signatory ID', 1, 'Not Uploaded'),
(1, 'Business License', 0, 'Not Uploaded'),
(1, 'VAT Certificate', 0, 'Not Uploaded'),
(1, 'Memorandum and Articles of Association', 0, 'Not Uploaded'),
(1, 'Beneficial Ownership Declaration', 0, 'Not Uploaded'),
(1, 'Financial Statements', 0, 'Not Uploaded'),
(1, 'Tax Clearance Certificate', 0, 'Not Uploaded');

-- Sample active services
INSERT INTO active_services (service_id, partner_id, customer_name, service_type, circuit_id, bandwidth_capacity, location, kam_id, activation_date, status, monitoring_status) VALUES
('SVC-260101-001', 2, 'Acacia Apartments', 'FTTH', 'CKT-FTTH-001', '50 Mbps', 'Msasani, Dar es Salaam', 2, '2026-01-15', 'Active', 'Online'),
('SVC-260101-002', 2, 'Blue Ocean Hotel', 'DIA',  'CKT-DIA-001',  '100 Mbps','Kinondoni, Dar es Salaam', 2, '2026-02-01', 'Active', 'Online'),
('SVC-260101-003', 3, 'TechHub Office',   'Dedicated Layer 2', 'CKT-L2-001', '1 Gbps', 'CBD, Dar es Salaam', 3, '2026-03-01', 'Active', 'Degraded');

-- Sample order
INSERT INTO orders (order_number, partner_id, kam_id, customer_name, customer_location, service_type, fttx_package, assigned_kam_name, usd_tzs_rate, base_nrc_usd, base_mrc, mrc_currency, total_nrc_incl_vat, total_mrc_incl_vat, status, created_by) VALUES
('SO-260101-001', 2, 2, 'Sample Customer', 'Oyster Bay, Dar es Salaam', 'FTTH', '50 Mbps', 'Gloria Entebbe', 2585.0000, 60.00, 41929.70, 'TZS', 70.80, 49477.05, 'Activated', 8);

INSERT INTO order_timeline (order_id, status, note, changed_by) VALUES
(1, 'Submitted', 'Order submitted by partner.', 8),
(1, 'Feasibility Review', 'BSA reviewing feasibility.', 4),
(1, 'Approved', 'Order approved. Proceeding to provisioning.', 4),
(1, 'Activated', 'Service activated and confirmed.', 1);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- notification_queue
-- ============================================================
CREATE TABLE IF NOT EXISTS notification_queue (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    channel             ENUM('Email','SMS','WhatsApp') DEFAULT 'Email',
    recipient           VARCHAR(200),
    subject             VARCHAR(300),
    message             TEXT,
    context_type        VARCHAR(50),
    context_id          INT UNSIGNED,
    status              ENUM('Queued','Sent','Failed') DEFAULT 'Queued',
    attempts            INT UNSIGNED DEFAULT 0,
    error_message       TEXT,
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    sent_at             DATETIME NULL,
    INDEX idx_status (status),
    INDEX idx_context (context_type, context_id)
) ENGINE=InnoDB;

-- ============================================================
-- END OF SCHEMA
-- ============================================================

# Neilos Partner & Contractor Portal

Enterprise Partner Order Management, Service Delivery, and Operational Portal built for high-performance telecom and ISP service delivery.

---

## 🌟 Key System Architecture & Recent Highlights

### 1. Permanent White / Light Theme System
- **100% Light Mode**: System enforced with `data-theme="light"` across all views, modals, forms, and sidebars.
- **Enterprise Aesthetics**: Clean white cards (`#FFFFFF`), light neutral background (`#F7F7F8`), corporate dark blue accents (`#0F4C81`), and clear status badge colors (Green, Blue, Amber, Red).
- **No Dark Mode**: Dark mode toggles and auto-switching media queries have been removed for consistent visual styling.

### 2. System-Wide Adaptive & Responsive Design
- **Multi-Device Support**: Optimized for Mobile Phones (320px, 375px, 430px), Tablets (768px – 1024px), Laptops/Desktops (1280px – 1440px), and Large Monitor / TV Displays (1920px, 2560px+).
- **Adaptive Sidebar Navigation**:
  - **Desktop (> 1023px)**: Full sidebar navigation with icons, section headers, module names, and user footer.
  - **Tablet (768px – 1023px)**: Automatically collapses to an icon-only navigation bar.
  - **Mobile (< 768px)**: Hidden off-screen by default (`transform: translateX(-100%)`). Toggling the hamburger button opens the menu as an overlay drawer above content with a dark backdrop.
  - **Auto-Response on Resize**: Resizing windows or changing device orientation automatically updates the sidebar layout without requiring a page refresh.
- **Fluid Layout Reflow**: Form grids reflow dynamically to 1-column layouts on smaller viewports, tables feature internal horizontal scrolling (`.table-responsive`), and input fields utilize 16px typography to prevent mobile auto-zoom.

### 3. Analytics & Vertical Column Charts
- **Vertical Bar Graphs**: **Service Type Distribution**, **Order Status Distribution**, and **Network & Service Health** charts are rendered as vertical column bar graphs.
- **Bar Value Plugin**: Custom `barTopValuePlugin` renders exact numerical order counts directly **above** each vertical column for maximum visual clarity.
- **Interactive Drill-Down**: Clicking chart columns filters order views dynamically.

### 4. KYC Applications Module
- **Admin & Management Delete Action**: Authorized Admin and Management users can delete KYC applications with an interactive confirmation modal showing Partner Name, KYC Type, and Current Status.
- **Instant AJAX UI Update**: Deleting a record removes the row instantly from the DOM, updates application counts, and displays toast notifications without a page refresh.
- **Audit Logging & Security**: All deletions generate audit log entries (`audit_logs`) and enforce server-side 403 authorization checks.

### 5. Role & Permission Management
- **Role-Based Access Control (RBAC)**: Fine-grained permissions per role (`System Admin`, `Management`, `KAM`, `BSA`, `Project Manager`, `Partner`, `Contractor`, `Billing`).
- **Project Manager Permissions**: Configured to restrict `orders.create` while maintaining full access to Contractor assignment (`contractors.view`, `contractors.assign`).
- **Reports Module**: Removed for all users; access attempts return HTTP 403 Access Denied.

---

## 🛠️ Setup Instructions

1. **Import Database Schema**:
   ```bash
   mysql -u root -p < database/schema.sql
   ```

2. **Configure Database Connection**:
   Update `app/config/database.php` with your database credentials.

3. **Web Server Configuration**:
   Point your web server document root to the `/public` directory.

4. **Default System Credentials**:

   | Username | Password | Role | Full Name | Associated Partner / Entity |
   |----------|----------|------|-----------|-----------------------------|
   | admin | Admin@1234 | System Admin | System Administrator | Internal Admin |
   | manager | Admin@1234 | Management | Operations Manager | Internal Operations |
   | kam_john | Admin@1234 | KAM | John KAM | Key Account Management |
   | bsa_sarah | Admin@1234 | BSA | Sarah BSA | Business Solutions Architecture |
   | pm | Admin@1234 | Project Manager | Project Manager | Internal Delivery Lead |
   | noc_support | Admin@1234 | NOC Support | NOC Support Lead | NOC Operations |
   | commercial_dir | Admin@1234 | Commercial | Commercial Director | Executive / Commercial |
   | billing | Admin@1234 | Billing | Finance / Billing | Finance & Billing |
   | partner_fastnet | Admin@1234 | Partner User | Partner Admin (FastNet) | FastNet Communications Ltd |
   | partner_afrilink | Admin@1234 | Partner User | Partner Admin (Afrilink) | Afrilink Solutions Ltd |
   | contractor1 | Admin@1234 | Contractor User | Contractor Admin | Fiber Works Ltd |

---

## 📋 Core Modules

- **Dashboard**: Operational overview with vertical bar charts, KPI cards, order trend timelines, and recent activity logs.
- **New Service Order**: Multi-service order creation (FTTH, Layer 2 last mile, BIA, Remote Hands).
- **Bulk FTTH Upload**: CSV/Excel bulk order creation tool.
- **Order Tracking & Details**: Complete service order lifecycle management (Feasibility Review, BSA Approval, Commercial Approval, SOF Generation, Installation, UAT, Activation).
- **SLA Tracking**: Stage-by-stage duration tracking and breach monitoring.
- **Active Services**: Active service inventory and customer circuit management.
- **Trouble Tickets**: Incident management linked to active circuits with SLA clocks, escalation rules, and NOC queues.
- **KYC Applications**: Partner and contractor compliance management with deletion modals and review workflows.
- **Projects**: Field delivery project tracking and contractor job assignments.
- **Partner Management (Admin)**: Partner company profiles and status controls.
- **Contractors Management (Admin)**: Contractor company profiles, capabilities, and job assignments.
- **User Management (Admin)**: User accounts, role definitions, and custom permission overrides.
- **Activity Logs**: System-wide audit logging and user activity history.

---

## ⏰ Cron Jobs & Automated Background Operations

Add these entries to your server crontab for automated SLA evaluation and ticket workflows:

```cron
# Evaluate Ticket SLA every 5 minutes
*/5 * * * * curl http://localhost/Neilos/public/?page=tickets&action=evaluate_sla

# Auto-close tickets awaiting >24h customer confirmation every hour
0 * * * * curl http://localhost/Neilos/public/?page=tickets&action=auto_close
```

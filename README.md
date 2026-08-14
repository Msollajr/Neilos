<div align="center">

<img src="https://capsule-render.vercel.app/api?type=waving&amp;color=0F4C81&amp;height=200&amp;section=header&amp;text=Neilos%20Portal&amp;fontSize=60&amp;fontColor=FFFFFF&amp;animation=fadeIn&amp;fontAlignY=38&amp;desc=Enterprise%20Partner%20%26%20Contractor%20Management&amp;descAlignY=58&amp;descAlign=50&amp;descSize=18" width="100%"/>

<br/>

[![Typing SVG](https://readme-typing-svg.herokuapp.com?font=Fira+Code&size=22&pause=1000&color=0F4C81&center=true&vCenter=true&random=false&width=700&lines=🚀+Enterprise+Telecom+%26+ISP+Portal;📦+Multi-Role+Order+Management+System;📊+Real-Time+Analytics+%26+SLA+Tracking;🔐+Fine-Grained+Role+Based+Access+Control;⚡+Built+for+High-Performance+Service+Delivery)](https://git.io/typing-svg)

<br/>

<p align="center">
  <img src="https://img.shields.io/badge/Version-1.0-0F4C81?style=for-the-badge&amp;logo=v&amp;logoColor=white"/>
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&amp;logo=php&amp;logoColor=white"/>
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&amp;logo=mysql&amp;logoColor=white"/>
  <img src="https://img.shields.io/badge/XAMPP-Ready-FB7A24?style=for-the-badge&amp;logo=apache&amp;logoColor=white"/>
  <img src="https://img.shields.io/badge/Theme-Enterprise%20Light-28A745?style=for-the-badge&amp;logo=css3&amp;logoColor=white"/>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/RBAC-8%20Roles-DC143C?style=for-the-badge&amp;logo=shield&amp;logoColor=white"/>
  <img src="https://img.shields.io/badge/Responsive-All%20Devices-17A2B8?style=for-the-badge&amp;logo=google-chrome&amp;logoColor=white"/>
  <img src="https://img.shields.io/badge/SLA-Auto%20Monitoring-FFC107?style=for-the-badge&amp;logo=clockify&amp;logoColor=black"/>
  <img src="https://img.shields.io/badge/Charts-Interactive-6F42C1?style=for-the-badge&amp;logo=chartdotjs&amp;logoColor=white"/>
</p>

</div>

---

## 📌 Table of Contents

<div align="center">

| | Section | | Section |
|:---:|:---|:---:|:---|
| 🏗️ | [Architecture Overview](#️-architecture-overview) | 📋 | [Core Modules](#-core-modules) |
| 🛠️ | [Setup Instructions](#️-setup-instructions) | ⏰ | [Cron Jobs &amp; Automation](#-cron-jobs--automation) |
| 🔐 | [Default Credentials](#-default-system-credentials) | 🎨 | [Design System](#-design-system) |

</div>

---

## 🏗️ Architecture Overview

<div align="center">

```
┌─────────────────────────────────────────────────────────────────────┐
│                        NEILOS PORTAL                                 │
│                  Enterprise Service Delivery Platform                │
├──────────────┬──────────────┬──────────────┬────────────────────────┤
│   Partners   │  Contractors │    Admin     │    NOC / Billing       │
│  (Ordering)  │  (Delivery)  │  (Control)   │    (Operations)        │
├──────────────┴──────────────┴──────────────┴────────────────────────┤
│         RBAC Engine · 8 Roles · Fine-Grained Permissions            │
├──────────────────────────────────────────────────────────────────────┤
│  Orders │ KYC │ Projects │ Tickets │ SLA │ Analytics │ Audit Logs   │
├──────────────────────────────────────────────────────────────────────┤
│                     MySQL 8.x  ·  PHP 8.x                            │
└──────────────────────────────────────────────────────────────────────┘
```

</div>

---

## ✨ Key Features & Highlights

<details>
<summary><b>🎨 1. Permanent Enterprise Light Theme</b></summary>

<br/>

> A **100% consistent enterprise-grade UI** — no dark mode surprises, no theme toggling.

| Property | Value |
|:---------|:------|
| **Background** | `#F7F7F8` — light neutral canvas |
| **Cards** | `#FFFFFF` — clean white surfaces |
| **Primary Accent** | `#0F4C81` — corporate deep blue |
| **Status: Active** | 🟢 Green |
| **Status: Pending** | 🔵 Blue |
| **Status: Warning** | 🟡 Amber |
| **Status: Critical** | 🔴 Red |

- System-enforced with `data-theme="light"` across **all** views, modals, and sidebars
- Dark mode toggles and auto-switching media queries **permanently removed**

</details>

<details>
<summary><b>📱 2. Adaptive &amp; Fully Responsive Design</b></summary>

<br/>

| Breakpoint | Device | Sidebar Behavior |
|:-----------|:-------|:-----------------|
| `≥ 1024px` | Desktop / Large Monitor | Full sidebar — icons + labels + user footer |
| `768px – 1023px` | Tablet | Collapses to icon-only navigation bar |
| `< 768px` | Mobile Phone | Hidden off-screen; hamburger opens overlay drawer |

- **Auto-response on resize** — no page refresh required
- **Fluid form grids** reflow to 1-column layouts on smaller viewports
- **16px typography** on inputs prevents iOS auto-zoom
- Internal horizontal scrolling on data tables via `.table-responsive`

</details>

<details>
<summary><b>📊 3. Interactive Analytics &amp; Vertical Column Charts</b></summary>

<br/>

- **Service Type Distribution** — vertical bar graph
- **Order Status Distribution** — vertical bar graph
- **Network &amp; Service Health** — vertical bar graph
- Custom `barTopValuePlugin` renders exact counts **above** each bar
- **Click-to-filter** drill-down on chart columns filters orders dynamically

</details>

<details>
<summary><b>🪪 4. KYC Applications Module</b></summary>

<br/>

- Admin &amp; Management users can **delete** KYC applications via confirmation modal
- **Instant AJAX UI update** — row removed from DOM, count updated, toast displayed — no page refresh
- All deletions write to `audit_logs` with server-side **403 authorization enforcement**

</details>

<details>
<summary><b>🔐 5. Role-Based Access Control (RBAC)</b></summary>

<br/>

Eight fine-grained roles with individual permission matrices:

```
System Admin  ·  Management  ·  KAM  ·  BSA
Project Manager  ·  Partner  ·  Contractor  ·  Billing
```

- **Project Manager**: Restricted from `orders.create`; retains full contractor assignment access
- **Reports Module**: Removed for all roles; access attempts return `HTTP 403`

</details>

---

## 🛠️ Setup Instructions

> [!IMPORTANT]
> Ensure **PHP 8.x**, **MySQL 8.0**, and **Apache** (or XAMPP) are installed before proceeding.

### Step 1 — Import Database Schema

```bash
mysql -u root -p < database/schema.sql
```

### Step 2 — Configure Database Connection

Edit `app/config/database.php` with your credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'neilos');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
```

### Step 3 — Web Server Configuration

Point your document root to the **`/public`** directory:

```apache
DocumentRoot "/path/to/Neilos/public"
```

> [!TIP]
> If using XAMPP, place the project in `C:\xampp\htdocs\Neilos` and access via `http://localhost/Neilos/public/`

---

## 🔐 Default System Credentials

> [!WARNING]
> Change all default passwords immediately after first login in a production environment.

<div align="center">

| 👤 Username | 🔑 Password | 🎭 Role | 📛 Full Name | 🏢 Entity |
|:------------|:-----------|:--------|:------------|:---------|
| `admin` | `Admin@1234` | 🛡️ System Admin | System Administrator | Internal Admin |
| `manager` | `Admin@1234` | 📊 Management | Operations Manager | Internal Operations |
| `kam_john` | `Admin@1234` | 🤝 KAM | John KAM | Key Account Management |
| `bsa_sarah` | `Admin@1234` | 🔧 BSA | Sarah BSA | Business Solutions Architecture |
| `pm` | `Admin@1234` | 📋 Project Manager | Project Manager | Internal Delivery Lead |
| `noc_support` | `Admin@1234` | 🖥️ NOC Support | NOC Support Lead | NOC Operations |
| `commercial_dir` | `Admin@1234` | 💼 Commercial | Commercial Director | Executive / Commercial |
| `billing` | `Admin@1234` | 💰 Billing | Finance / Billing | Finance &amp; Billing |
| `partner_fastnet` | `Admin@1234` | 🌐 Partner User | Partner Admin (FastNet) | FastNet Communications Ltd |
| `partner_afrilink` | `Admin@1234` | 🌐 Partner User | Partner Admin (Afrilink) | Afrilink Solutions Ltd |
| `contractor1` | `Admin@1234` | 🔨 Contractor | Contractor Admin | Fiber Works Ltd |

</div>

---

## 📋 Core Modules

<div align="center">

| Module | Description |
|:-------|:------------|
| 🖥️ **Dashboard** | Operational overview — vertical bar charts, KPI cards, order trends, recent activity |
| 📦 **New Service Order** | Multi-service order creation (FTTH, Layer 2, BIA, Remote Hands) |
| 📤 **Bulk FTTH Upload** | CSV/Excel bulk order ingestion tool |
| 🔄 **Order Tracking** | Full lifecycle: Feasibility → BSA Approval → Commercial → SOF → Install → UAT → Activation |
| ⏱️ **SLA Tracking** | Stage-by-stage duration tracking and breach monitoring |
| 📡 **Active Services** | Active service inventory and customer circuit management |
| 🎫 **Trouble Tickets** | Incident management with SLA clocks, escalation rules, and NOC queues |
| 🪪 **KYC Applications** | Partner &amp; contractor compliance management with review workflows |
| 🏗️ **Projects** | Field delivery project tracking and contractor job assignments |
| 🤝 **Partner Management** | Partner company profiles and status controls *(Admin only)* |
| 🔨 **Contractors Management** | Contractor profiles, capabilities, and job assignments *(Admin only)* |
| 👥 **User Management** | User accounts, role definitions, and permission overrides *(Admin only)* |
| 📝 **Activity Logs** | System-wide audit logging and user activity history |

</div>

---

## ⏰ Cron Jobs & Automation

Add these entries to your server crontab for automated SLA evaluation and ticket workflows:

```cron
# ┌───────────── minute (0 - 59)
# │ ┌───────────── hour (0 - 23)
# │ │ ┌───────────── day of the month (1 - 31)
# │ │ │ ┌───────────── month (1 - 12)
# │ │ │ │ ┌───────────── day of the week (0 - 6)
# │ │ │ │ │

# Evaluate Ticket SLA every 5 minutes
*/5 * * * * curl http://localhost/Neilos/public/?page=tickets&action=evaluate_sla

# Auto-close tickets awaiting >24h customer confirmation — runs hourly
0 * * * * curl http://localhost/Neilos/public/?page=tickets&action=auto_close
```

---

## 🎨 Design System

<div align="center">

| Token | Hex | Role |
|:------|:----|:-----|
| 🟦 Primary Blue | `#0F4C81` | Navigation, CTA buttons, headings |
| ⬜ Background | `#F7F7F8` | Page canvas |
| 🟩 Card Surface | `#FFFFFF` | Cards, modals, panels |
| 🟢 Success | `#28A745` | Active status, confirmations |
| 🟡 Warning | `#FFC107` | Pending states, caution alerts |
| 🔴 Danger | `#DC3545` | Error states, critical badges |

</div>

---

<div align="center">

<img src="https://capsule-render.vercel.app/api?type=waving&amp;color=0F4C81&amp;height=120&amp;section=footer&amp;animation=fadeIn" width="100%"/>

<sub>Built with ❤️ for enterprise-grade telecom &amp; ISP service delivery operations.</sub>

</div>

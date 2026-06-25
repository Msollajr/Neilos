# Neilos Partner Portal

Partner Order Management & Service Delivery Portal.

## Setup

1. Import `database/schema.sql` into MySQL:
   ```
   mysql -u root -p < database/schema.sql
   ```

2. Configure `app/config/database.php` with your DB credentials.

3. Point your web server to `public/` as the document root.

4. Login credentials (from seed data):
   - Admin: `admin` / `Admin@1234`
   - Partner (Savanna ISP): `savanna` / `password`
   - Partner (TechConnect): `techuser` / `password`

## Modules

- Dashboard - Operational overview
- Coverage Check - Link to coverage portal
- New Service Order - Order creation (FTTH, FTTB, DIA, L2, Remote Hands)
- Bulk FTTH Upload - CSV/Excel bulk order creation
- Order Tracking - Service order management
- SLA Tracking - Stage duration tracking
- Active Services - Service inventory
- Trouble Tickets - Fault and incident management
- KYC Application - Partner compliance
- Projects - Delivery project management
- Reports - CSV exports
- Partner Management (Admin) - Partner accounts
- User Management (Admin) - User credentials and roles

## Trouble Ticket Module

### Features
- Ticket creation linked to Active Services (auto-populates Service ID, Customer, Service Type, Circuit ID, Bandwidth, Location, KAM, Activation Date)
- 16 fault categories + severity levels
- SLA tracking with calendar-hour clock
- Automatic escalations at 80%/100%/125% SLA consumption
- Customer confirmation workflow (Resolved → Confirm/Reopen)
- Auto-close after 24 hours awaiting customer confirmation
- Internal and Partner-visible notes
- Full audit timeline per ticket
- Queue management (NOC Support → NOC Core → NOC Level 3 → Director)
- Email/WhatsApp notification queue placeholders

### Cron Jobs
Add these to crontab for automated operations:

```
# Evaluate SLA every 5 minutes
*/5 * * * * curl http://localhost/Neilos/public/?page=tickets&action=evaluate_sla

# Auto-close tickets awaiting >24h confirmation every hour
0 * * * * curl http://localhost/Neilos/public/?page=tickets&action=auto_close
```

### Live Demo Credentials

| Username   | Password    | Role            |
|------------|-------------|-----------------|
| admin      | Admin@1234  | System Admin    |
| noc1       | Admin@1234  | NOC Support     |
| noc_core   | Admin@1234  | NOC Core        |
| gloria     | Admin@1234  | KAM             |
| bsa        | Admin@1234  | BSA             |
| director   | Admin@1234  | Director        |
| savanna    | password    | Partner User    |
| techuser   | password    | Partner User    |

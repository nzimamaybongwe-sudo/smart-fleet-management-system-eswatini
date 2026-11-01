# 🚛 Logico Fleet Enterprise - Intelligent Fleet & Cold-Chain Management System

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-9.1%20%2F%20InnoDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Leaflet](https://img.shields.io/badge/Leaflet-GPS%20Telemetry-199900?style=for-the-badge&logo=leaflet&logoColor=white)](https://leafletjs.com/)
[![Region](https://img.shields.io/badge/Region-Kingdom%20of%20Eswatini-blue?style=for-the-badge)](https://en.wikipedia.org/wiki/Eswatini)
[![License](https://img.shields.io/badge/License-Proprietary%20%2F%20Commercial-brightgreen?style=for-the-badge)](LICENSE)

An enterprise-grade, end-to-end Fleet Logistics and Cold-Chain Management System purpose-built for commercial distribution operations in the Kingdom of Eswatini. Tailored specifically for **Logico Unlimited**—headquartered at the Matsapha Industrial Distribution Centre—to manage high-volume FMCG (Fast-Moving Consumer Goods) distribution for multinational brands including **Unilever, Nestlé, Lactalis, and Parmalat**.

---

## 🌟 Executive Summary & Commercial Value

In regional supply chain distribution, delivery disputes, temperature non-compliance for refrigerated dairy/confectionery, unmonitored roadside breakdowns, and fuel shrinkage erode operating margins. 

**Logico Fleet Enterprise** transforms transport operations into a high-visibility, data-driven profit center:
- 📱 **Eliminates Billing Disputes with e-POD**: Touch-screen digital signature capture with timestamped recipient logging ensures 100% dispute-free invoices.
- ❄️ **Guarantees Cold-Chain Integrity**: Live temperature telemetry and automated alerts prevent costly spoilage for perishable refrigerated cargo.
- 📍 **Real-Time Fleet & Corridor Tracking**: Interactive Leaflet.js GPS mapping with real-world Eswatini arterial routes (MR3 Highway, MR9 Corridor, MR8 Sugar Belt).
- 🚨 **Rapid Roadside Incident Dispatch**: Instant breakdown alerts with GPS auto-pinning, priority routing to Matsapha Central DC, and direct roadside mechanical assistance.
- 📊 **Commercial Boardroom ROI Dashboard**: Built-in executive calculator proving tangible payback through fuel savings, reduced detention time, and preventive maintenance.

---

## 🖥️ System Architecture & Screenshots Tour

```
┌────────────────────────────────────────────────────────────────────────┐
│                        LOGICO FLEET ECOSYSTEM                          │
├──────────────────────────┬─────────────────────────────────────────────┤
│   Executive / Dispatch   │  • Fleet Telemetry & Interactive GPS Map   │
│      Command Centre      │  • Multi-Criteria Search & Filter Toolbars  │
│  (public/dashboard.php)  │  • One-Click CSV Data Export Engine         │
│                          │  • Cold-Chain Perishable Monitoring Desk    │
│                          │  • Roadside Incident Dispatch Console       │
│                          │  • Boardroom ROI & Payback Projections      │
├──────────────────────────┼─────────────────────────────────────────────┤
│     Mobile Driver &      │  • Visual 4-Step Route Progress Stepper     │
│       Field Portal       │  • Electronic Proof of Delivery (e-POD)     │
│ (driver_dashboard.php)   │  • Touch-Screen Signature Pad               │
│                          │  • 5-Point Pre-Trip Vehicle Checklist       │
│                          │  • One-Touch Emergency Breakdown Speed Dial │
│                          │  • Mobile Refueling Log Submission          │
├──────────────────────────┼─────────────────────────────────────────────┤
│   Roadside Incident &    │  • HTML5 Geo-location Auto-Coordinates      │
│     Breakdown Portal     │  • Severity Card Selectors (Low/Med/Crit)   │
│  (incident_report.php)   │  • Regional Eswatini Landmark Quick-Pills   │
├──────────────────────────┼─────────────────────────────────────────────┤
│   Account & Security     │  • Commercial Driver's License Card         │
│  (profile_edit.php)      │  • Phone & Credentials Management           │
│                          │  • Light / Dark Theme Mode Toggle           │
└──────────────────────────┴─────────────────────────────────────────────┘
```

---

## ⚡ Core Features

### 1. Executive Dispatcher Command Centre (`dashboard.php`)
- **Live GPS Telemetry**: Custom Leaflet.js map with custom pins for primary depot hubs (Matsapha DC, Mbabane Depot, Manzini Hub, Nhlangano Station, Siteki Depot, Piggs Peak Station, Simunye Sugar Depot).
- **Search & Filtering Engine**: Instant client-side search across all tables (Vehicles, Trips, Fuel, Maintenance, Incidents) with one-click status filter pills (`Active`, `In Transit`, `Maintenance`, `Critical`).
- **CSV Data Export**: Single-click export of any operational table directly to Microsoft Excel / CSV.
- **Top Bar `+ Quick Action` Hub**: Global modal launcher allowing dispatchers to schedule trips, log fuel refills, create incidents, or register vehicles without leaving their view.
- **Cold Chain Monitor**: Color-coded temperature telemetry (optimal `-22°C` to `4°C` for ice cream & fresh dairy) with automated anomaly warning badges.
- **Boardroom ROI Deck**: Interactive sliders calculating monthly fuel shrinkage recovery, detention time reduction, and fleet longevity ROI in Eswatini Emalangeni (`SZL` / `E`).

### 2. Driver & Delivery Portal (`driver_dashboard.php`)
- **Visual Route Stepper**: Clean 4-step progress tracker (`Scheduled` ➜ `Departed Depot` ➜ `En Route` ➜ `Delivered`).
- **Electronic Proof of Delivery (e-POD)**: Smooth HTML5 canvas signature pad with recipient name validation, delivery notes, and instant customer sign-off.
- **Pre-Trip Safety Inspection**: 5-point checklist covering brakes, tires, engine fluid, lights, and reefer cooling units before vehicle departure.
- **Fuel Refill Logging**: Mobile pump log with automated session driver attribution.
- **Emergency Dispatch Radio**: Direct speed dial to the Matsapha 24/7 Operations Room (`+268 2518 4000`).

### 3. Roadside Incident Reporting (`incident_report.php`)
- **GPS Auto-Detection**: Pulls exact latitude/longitude coordinates via HTML5 Geolocation API.
- **Eswatini Landmark Quick-Select**: Single-tap location tagging for notorious highway bottlenecks (Malagwane Hill steep descent, MR3 Matsapha Weighbridge, Phocweni Junction, Lozitha Interchange).
- **Visual Severity Cards**: Color-coded severity selection (Low, Medium, Critical Vehicle Breakdown).

### 4. Enterprise Architecture & Security
- **Modern UI & Theming**: Polished glassmorphism design system using `Outfit` and `Inter` typography, fully responsive across smartphones, tablets, and 4K desktop screens, with persistent Light/Dark mode.
- **Transactional Integrity**: Converted database tables to MySQL `InnoDB` with foreign key cascades and relational consistency.
- **Toast Notification Engine**: Non-intrusive floating toasts for async notifications and form validation.

---

## 🗄️ Database Schema & Architecture

The database `logico_fleet` includes normalized tables with complete transactional support:

| Table | Description |
|---|---|
| `users` | System accounts with secure `password_hash`, roles, and audit timestamps. |
| `roles` & `user_roles` | Granular Role-Based Access Control (`admin`, `fleet_manager`, `driver`). |
| `vehicles` | Fleet asset register with cold-chain indicators, telemetry coordinates, and odometer logs. |
| `drivers` | Commercial drivers linked to user accounts, with CDL numbers and license expirations. |
| `trips` | Manifests with consignments, client names, e-POD digital signatures, and cargo temperature. |
| `inspections` | 5-point vehicle safety & refrigeration compliance inspection checklists. |
| `incidents` | Roadside breakdown dispatch reports with GPS coordinates, severity, and resolution statuses. |
| `fuel_logs` | Refueling audit logs tracking litres, fuel grade, pump cost, and odometer readings. |
| `maintenance_logs` | Scheduled maintenance and emergency mechanical workshop service history. |
| `audit_logs` | Enterprise security event trails with JSON metadata and client IP tracking. |

---

## 🚀 Quick Start & Installation

### Prerequisites
- **Web Server**: Apache 2.4+ (WampServer, XAMPP, or LAMP stack)
- **PHP**: 8.0 or newer (tested with PHP 8.2)
- **Database**: MySQL 5.7+ / 8.0+ / 9.1+ or MariaDB 10.4+
- Modern Web Browser (Chrome, Firefox, Safari, Edge)

### Step 1: Clone or Copy the Repository
Place the project into your web server's root directory:
```bash
# For WampServer:
cd c:\wamp64\www\
git clone https://github.com/YOUR_USERNAME/logico-fleet.git "Fleet management  system/logico-fleet"

# Or standard path:
git clone https://github.com/YOUR_USERNAME/logico-fleet.git
```

### Step 2: Configure Environment
Copy the environment template:
```bash
cp .env.example .env
```
Ensure your database credentials in `config/database.php` or `.env` match your local environment:
- **Host**: `127.0.0.1`
- **Database**: `logico_fleet`
- **User**: `root`
- **Password**: *(empty by default in WAMP/XAMPP)*

### Step 3: Initialize & Migrate Database
Open your terminal and run the enterprise migration and seeding script:
```bash
php database/upgrade_enterprise.php
```
*Alternatively, you can import `database/schema.sql` via phpMyAdmin or MySQL CLI.*

### Step 4: Launch Application
Open your browser and navigate to:
```
http://localhost/Fleet%20management%20%20system/logico-fleet/public/login.php
```
*(Or your configured virtual host URL)*

---

## 🔑 Demo Login Accounts

Pre-seeded accounts are ready for instant demonstration:

| Role | Email | Password | Access Highlights |
|---|---|---|---|
| **Executive Admin** | `admin@logico.test` | `admin123` | Full Dispatcher Command Centre, GPS Map, ROI Calculator, Fleet CRUD, Incidents |
| **Fleet Operations** | `manager@logico.test` | `manager123` | Operational Dispatch, Route Planning, Consignment Manifests, Fuel & Service Logs |
| **Commercial Driver** | `driver1@logico.test` | `driver123` | Mobile Driver Portal, Route Stepper, Touch-screen e-POD Signature, Pre-Trip Checklist |

> **Pro-Tip**: The login page (`public/login.php`) includes **1-Click Demo Buttons** to instantly populate credentials for Admin or Driver testing!

---

## 📡 REST API Reference

The backend provides clean JSON endpoints for all frontend and mobile operations:

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/stats.php` | Summary KPI counts, fuel costs, cold-chain alerts, and depot coordinates |
| `GET` / `POST` | `/api/trips.php` | List manifests, create trips, or complete e-POD digital signature |
| `GET` / `POST` | `/api/vehicles.php` | Retrieve fleet inventory or register new delivery trucks |
| `GET` / `POST` | `/api/incidents.php` | Query breakdown logs or report roadside emergencies with GPS |
| `POST` | `/api/inspections.php` | Submit 5-point vehicle & refrigeration pre-trip checklist |
| `GET` / `POST` | `/api/fuel.php` | View refueling history or log litres, costs, and odometer readings |
| `POST` | `/api/drivers/update_profile.php` | Update driver mobile contact number and account password |

---

## 💼 Business Proposition for Logico Unlimited

Logico Unlimited commands the largest FMCG distribution network in Eswatini. Implementing this system delivers direct operational returns:
1. **DSO (Days Sales Outstanding) Reduction**: Instant digital e-POD proof means deliveries can be invoiced the same day without waiting for physical paper waybills.
2. **Cold-Chain Claim Protection**: Historical temperature compliance telemetry protects against wrongful spoiled goods claims from retail partners (Shoprite, Pick n Pay, Spar).
3. **Turnaround Time Optimization**: Live trip tracking cuts driver turnaround at regional border posts (Ngwenya, Lavumisa) and retail receiving bays.
4. **Maintenance Cost Prevention**: Automated pre-trip inspections detect mechanical and tire wear before costly roadside breakdowns occur on steep gradients like Malagwane.

---

## 📄 License & Intellectual Property

Developed by **Fullstack Engineering & Solutions**.  
Designed for enterprise deployment and commercial customization.  
All brand names and trademarks (Logico Unlimited, Nestlé, Unilever, Lactalis) belong to their respective holders and are referenced for operational demonstration purposes.

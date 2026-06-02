# IT Management System - Technical & User Documentation

Welcome to the official documentation for the **IT Management System (v2.0)**. This manual provides a comprehensive overview of the system's architecture, core modules, and operational procedures.

---

## 📂 1. Directory Structure

The project follows a modular, repository-based architecture for scalability and maintenance.

*   `Core/`: Base system classes (Database, Session, Environment, I18n).
*   `Modules/`: Feature-specific logic separated into folders (Auth, Equipment, Tasks, Network, etc.).
    *   `*Repository.php`: Direct database interactions.
    *   `*Controller.php`: Business logic and request handling.
*   `views/`: UI templates (PHP files using Tailwind/Alpine.js).
*   `fallback/`: Local backup of CSS/JS assets for offline reliability.
*   `storage/`: Secure storage for uploads, logs, and database backups.
*   `lang/`: Translation files for bilingual support.

---

## 🛠 2. Core Technical Concepts

### 2.1 Repository Pattern
All database queries are centralized in `Repository` classes. This ensures that the business logic (Controllers) remains clean and that the database schema can be updated in one place.

### 2.2 Dynamic Form Engine
The Equipment module uses a JSON-based form engine. 
1.  **Blocks:** Reusable groups of fields stored as JSON schemas.
2.  **Types:** Equipment categories that link multiple blocks and add type-specific fields.
3.  **Equipments:** Assets that store their data in a `custom_data` JSON column based on the linked type's schema.

### 2.3 Offline Fallback System
The system is built to survive internet outages. All layouts use the following logic:
*   Try loading **Tailwind/Alpine/Charts** from a CDN.
*   If the CDN fails, a JavaScript helper automatically injects the local file from the `/fallback` folder.

---

## 🚀 3. Operational Workflows

### 3.1 Equipment Onboarding
1.  **Define Blocks:** Create standardized sections like "Network Info" or "Physical Specs" in *Admin Tools*.
2.  **Create Type:** Create a type (e.g., "Server") and attach the relevant blocks.
3.  **Add Asset:** Go to *Equipment List* and click *Add Equipment*. The form will dynamically generate based on the Type you select.

### 3.2 Task Management & Recurrence
*   **Kanban Board:** Move tasks between statuses. Note: A task must be in "Doing" before it can be marked "Done".
*   **Auto-Recurrence:** If a task is set to "Weekly" or "Monthly", the system will automatically create the *next* occurrence in "Todo" status the moment the current one is completed.

### 3.3 Network Mapping
*   Network nodes (IPs) are managed independently.
*   You can "Assign" an equipment to an IP from either the *Network List* or the *Equipment Edit* page.

### 3.4 QR Code Labels & Inventory Tracking
The system includes a built-in QR code generator for physical asset tracking:
1.  **Selection:** Select assets from the Equipment List or click the QR icon on a specific record.
2.  **Configuration:** Choose between "Basic Info" (Name, S/N) or "Full Details" (IP, MAC, Location).
3.  **Physical Sizing:** Supports standard label sizes (0.5", 1.0", 1.5", and 2.0").
4.  **Direct Links:** QR codes include a system URL that technicians can scan with a smartphone to go directly to the asset's detail page.

### 3.5 Photo Gallery
Each asset supports a gallery of up to **3 high-resolution photos**. 
*   **Security:** Photos are stored in `storage/uploads/equipment/` with access restricted via `.htaccess` to authorized sessions only.
*   **Optimization:** The system automatically manages file sizes and naming to prevent storage bloat.

---

## 🔐 4. Security & Audit

### 4.1 Audit Logs (Revision History)
Every change to a user, task, or equipment is logged. You can view the **Revision History** on the detail page of any entity to see:
*   Who made the change.
*   The exact values before and after the update.
*   The IP address and timestamp.

### 4.2 Rate Limiting
To prevent brute-force attacks, the system locks login attempts for an IP address after 5 failures within 10 minutes.

---

## 💾 5. Maintenance Procedures

### 5.1 Database Backups
Go to **Admin Tools > Database Maintenance**. Click "Download Backup" to generate a full `.sql` dump of your system.

### 5.2 System Logs
The system captures all errors and high-level events. Logs are automatically cleared after **90 days** to keep the database performant. You can export them to CSV for compliance audits.

### 5.3 Optimizing Performance
If the system feels slow, use the "Optimize Database" tool in *Admin Tools* to run maintenance on all MySQL tables.



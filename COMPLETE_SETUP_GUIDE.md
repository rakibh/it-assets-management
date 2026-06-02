# IT Management System - Installation & Setup Guide

A comprehensive guide to deploying and configuring the IT Management System.

---

## 📋 System Requirements

Ensure your environment meets the following specifications:

*   **Operating System:** Windows (WAMP/XAMPP), Linux (LAMP), or macOS (MAMP).
*   **Web Server:** Apache 2.4+ (with `mod_rewrite` enabled).
*   **PHP:** Version **8.3.14 or higher** (Strict types required).
*   **Database:** MySQL **9.1.0** (or 8.0+ with `utf8mb4` support).
*   **PHP Extensions:**
    *   `pdo_mysql` (Database connectivity)
    *   `mbstring` (Internationalization)
    *   `json` (Dynamic schemas & custom fields)
    *   `fileinfo` (Secure uploads)
    *   `gd` or `imagick` (Profile photo processing)

---

## 🚀 Installation Steps

### 0. System Directory Structure
Ensure your deployment includes the following core structure:
```text
[PROJECT_ROOT]/
├── .env                  <-- CRITICAL: DB & App config
├── .htaccess              <-- CRITICAL: Routing & Security
├── index.php              <-- Entry point
├── install.sql            <-- Database schema
├── Core/                  <-- System core classes
├── Modules/               <-- Feature logic (Auth, Tasks, etc.)
├── views/                 <-- UI templates
├── fallback/              <-- Local CSS/JS (Offline backup)
├── lang/                  <-- Translation files
├── storage/               <-- Writable folder for uploads/logs
│   ├── backups/
│   ├── logs/
│   └── uploads/
│       ├── equipment/     <-- Mandatory for asset photos
│       ├── profiles/      <-- Mandatory for user photos
│       ├── tasks/         <-- Mandatory for task attachments
│       └── warranty/      <-- Mandatory for warranty documents
└── robots.txt             <-- SEO/Search crawler control
```

### 1. Database Setup
1.  Open your MySQL management tool (e.g., phpMyAdmin).
2.  Create a new database named `it_assets_management` (or your preferred name).
3.  Import the provided `install.sql` file located in the root directory.
    *   *Note: This will create all 14 tables and a default admin account.*

### 2. Environment Configuration
1.  Copy the `.env.example` file and rename it to `.env`.
2.  Open `.env` and configure your database credentials:
    ```env
    DB_HOST=localhost
    DB_NAME=it_assets_management
    DB_USER=root
    DB_PASS=your_password
    ```
3.  Update the `APP_URL` to match your local setup (e.g., `http://localhost/it_assets_management`).

### 3. File Permissions
Ensure the following directories are **writable** by the web server:
*   `storage/uploads/` (For equipment photos and task attachments)
*   `storage/logs/` (For system and error logs)
*   `storage/backups/` (For database backups)
*   `fallback/` (For offline asset loading)

---

## 🔐 Default Credentials

Once installed, log in with the following administrator account:

*   **Employee ID / Username:** `admin`
*   **Password:** `password`

> ⚠️ **IMPORTANT:** Change the administrator password immediately after your first login via the **Profile** page.

---

## 🛠 Post-Installation Configuration

### 1. System Settings
Navigate to **Admin Tools > System Settings** to configure:
*   **System Name:** Personalize your portal.
*   **Upload Limits:** Set max file sizes for attachments (default 10MB).
*   **Session Timeout:** Configure auto-logout duration for security.

### 2. Form Builder (Equipment Types)
The system uses a dynamic inventory system. Before adding assets:
1.  Go to **Equipment > Manage Blocks** to define reusable field groups (e.g., "Network Details").
2.  Go to **Equipment > Equipment Types** to create categories like "Laptop" or "Printer" using the graphical builder.

### 3. QR Code Labeling
You can generate printable QR codes for assets directly from the Equipment List. These codes link to the asset's detail page and can include branding, serial numbers, and network info.

---

## 📶 Offline & CDN Fallback

The system is designed to be reliable in offline environments. 
*   **CDN-First:** Loads high-performance libraries (Tailwind, Alpine.js, Chart.js) from CDNs when internet is available.
*   **Local Fallback:** Automatically switches to assets in the `fallback/` directory if the internet connection is lost. **Do not delete the `fallback/` folder.**

---

## 📝 Troubleshooting

*   **404 on Routes:** Ensure Apache's `AllowOverride All` is set for the project directory so `.htaccess` (if present) or routing works.
*   **Upload Failures:** Check PHP's `upload_max_filesize` and `post_max_size` in `php.ini` to match the settings defined in the app.
*   **Blank Charts:** Ensure your browser is not blocking the local JavaScript files in the `fallback/` folder.



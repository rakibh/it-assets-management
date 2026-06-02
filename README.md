# 🏢 IT Assets Management System

A comprehensive, enterprise-grade platform designed to centralize hardware inventory, network infrastructure, workplace tasks, and system maintenance. Built with a focus on **dynamic flexibility**, **real-time automation**, and **100% offline reliability**.

---

## ✨ Key Features Included

*   **🛠 Dynamic Equipment Inventory:** Graphical form builder for custom asset schemas and reusable field blocks.
*   **🖨 QR Code Labeling:** Integrated generator for printable asset tags with customizable sizes (0.5" to 2.0") and direct system links.
*   **📋 Kanban Task Board:** Visual workflow management with automatic recurring task generation (Daily/Weekly/Monthly).
*   **🌐 Network Infrastructure:** Detailed mapping of IP/MAC addresses to switches, patch panels, and hardware assets.
*   **🔔 Real-time Notifications:** AJAX polling and OS-native desktop alerts for system-wide and user-specific events.
*   **🛡 Advanced Security:** BCRYPT hashing, CSRF protection, rate limiting, and granular RBAC (Admin/User).
*   **📶 Offline Resilience:** CDN-First strategy with automatic local fallback for all CSS/JS assets.
*   **🌗 Modern UI:** Responsive design with native Dark Mode support and bilingual (English/Bengali) interface.

---

## 🚀 Quick Deployment Guide

To get the system running on your server, follow these 4 simple steps:

### 1. Database Setup
*   Create a new MySQL database named `it_assets_management`.
*   Import the `install.sql` file into this database.

### 2. Environment Configuration
*   Rename `.env.example` to `.env`.
*   Open `.env` and enter your database credentials (Host, User, Password).
*   Set your `APP_URL` (e.g., `http://localhost/it_assets_management`).

### 3. File Permissions
*   Ensure the `storage/` directory and its subfolders are **writable** by your web server (chmod 775 or 777).

### 4. Default Login
*   **Username:** `admin`
*   **Password:** `password`
*   *(Please change your password immediately after logging in!)*

---

## 📦 Requirements

*   **PHP:** 8.3.14 or higher
*   **Database:** MySQL 9.1.0 or higher
*   **Web Server:** Apache 2.4+ with `mod_rewrite` enabled

---

## 🛠 Tech Stack

| Tier | Technology |
| :--- | :--- |
| **Backend** | PHP 8.3 (Strict Types, Repository Pattern) |
| **Database** | MySQL 9.1 (InnoDB, JSON Storage) |
| **Frontend** | Tailwind CSS, Alpine.js, Vanilla JS |
| **Visualization** | Chart.js |
| **Icons** | Bootstrap Icons |

---

## 📖 Additional Resources

*   **[Complete Setup Guide](COMPLETE_SETUP_GUIDE.md):** Detailed step-by-step installation instructions.
*   **[Technical Documentation](DOCUMENTATION.md):** Information about the system architecture and folder structure.

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

--

*Built with ❤️ for efficient IT Operations.*

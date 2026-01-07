# 🎓 Student Feedback System

A robust, web-based Student Feedback System designed to collect, analyze, and manage feedback from students regarding their courses and teachers. Built with PHP and MySQL, focusing on performance, security, and ease of deployment.

![Status: Deployed](https://img.shields.io/badge/Status-Deployed-success?style=flat-square)
![Tech: PHP](https://img.shields.io/badge/Tech-PHP%208.x-blue?style=flat-square)
![DB: MySQL](https://img.shields.io/badge/DB-MySQL-orange?style=flat-square)

> **🚀 Live Demo:** [http://mgm-feedback.rf.gd/student_login.php](http://mgm-feedback.rf.gd/student_login.php)
>
> ⚠️ **Note:** As this demo is hosted on free shared hosting (`.rf.gd`), some browsers (especially mobile) may incorrectly flag the domain as "Dangerous". This is a known false positive due to the hosting provider's shared reputation and does not reflect the safety of this project's code.

![Login Page Preview](login_preview.png)

---

## 🚀 Key Features

### 👨‍🎓 For Students
- **Secure Login**: Individual student authentication.
- **Multi-Step Feedback Form**: Structured feedback for Theory and Practical courses.
- **Duplicate Prevention**: Smart validation ensuring only one submission per academic year.
- **Responsive Design**: Works on mobile and desktop.

### 👨‍🏫 For Administrators
- **Dashboard**: Comprehensive view of feedback statistics.
- **Teacher Performance Analytics**: Calculate appreciation percentages and ratings.
- **Automated Appreciation Emails**: One-click email sending to high-performing teachers using **PHPMailer (SMTP)**.
- **Filters**: Sort data by Department, Semester, and Academic Year.

---

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript (jQuery)
- **Backend**: Native PHP 7.4/8.0+
- **Database**: MySQL / MariaDB
- **Email Service**: PHPMailer 6.x (SMTP via Gmail)
- **Security**: Password Hashing, Prepared Statements (SQL Injection Prevention), Session Management

---

## 📥 Installation & Local Setup

1.  **Clone/Download** the repository to your XAMPP/WAMP `htdocs` folder.
2.  **Import Database**:
    *   Open phpMyAdmin (`http://localhost/phpmyadmin`).
    *   Create a database named `feedback_system`.
    *   Import `feedback_system.sql`.
3.  **Configure Database**:
    *   Edit `admin/config.php`.
    *   Set `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` relevant to your local setup.
4.  **Configure Email**:
    *   Edit `admin/config/mail_config.php`.
    *   Add your Gmail credentials (use an App Password).

---

## ☁️ Deployment (InfinityFree)

This project is optimized for zero-cost deployment on InfinityFree.

### Verified Configuration
- **Host**: `sql113.infinityfree.com`
- **PHP Version**: 7.4 / 8.2 (Supported)
- **Email**: Works via SMTP (Port 587) with Gmail App Passwords.

**Quick Deploy Steps:**
1.  Upload all files to `/htdocs` (Do not nest in subfolders).
2.  Import the modified SQL file (Triggers removed for compatibility).
3.  Update `admin/config.php` with VPanel credentials.

---

## 🔒 Security Measures

- **Authentication**: `password_hash()` used for storing credentials.
- **Database**: `mysqli` Prepared Statements used to prevent SQL Injection attempts.
- **Validation**: Server-side validation for all inputs.
- **Access Control**: Role-based access (Student vs Admin sessions).

---

## 📧 Email Configuration

The system uses **PHPMailer** to bypass hosting restrictions on the native `mail()` function.
- Configuration file: `admin/config/mail_config.php`
- Uses **Gmail SMTP** (smtp.gmail.com) on Port **587** (STARTTLS).
- Requires a Google **App Password** (2-Step Verification).

---

## 📬 Contributors
- **Developer**: Onkar Vyawahare
  
  [![GitHub](https://img.shields.io/badge/GitHub-Profile-181717?style=for-the-badge&logo=github)](https://github.com/onkarvyawahare04-jpg)

- **Developer**: Satyam Modi

  [![GitHub](https://img.shields.io/badge/GitHub-Profile-181717?style=for-the-badge&logo=github)](https://github.com/satyamodi555-oss)

## 📜 License

This project is licensed under the **Apache License 2.0**.
See [LICENSE.txt](LICENSE.txt) for more information.

[![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](https://opensource.org/licenses/Apache-2.0)

© 2025 MGM College. All Rights Reserved.

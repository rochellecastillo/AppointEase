Project Name: Untalan General Hospital - AppointEase System
Group Name: UIX ARCHITEXTS

Group Members
-------------------------------------------------------------------------------
1. Baluyot, Janna 
2. Castillo, Rochelle
3. Manguiat, Kit Michail

Installation Guide
Prerequisites
-------------------------------------------------------------------------------
1. XAMPP v8.0 or higher
2. Web Browser (Chrome/Firefox/Edge)
3. Internet connection for Tailwind CSS CDN (or local CSS if downloaded)

Step-by-Step Installation
-------------------------------------------------------------------------------
1. XAMPP Setup
- Download XAMPP v8.0+ from https://www.apachefriends.org
- Run installer and follow prompts
- Install to default location (C:\xampp)

2. Database Setup
- Start XAMPP Control Panel
- Start Apache and MySQL services
- Open browser: http://localhost/phpmyadmin
- Create new database:
  * Name: appointease_db
  * Collation: utf8mb4_general_ci
- Click 'Import' tab
- Choose file: database/appointease_db.sql
- Click 'Go' to import

3. Project Files Setup
- Navigate to C:\xampp\htdocs
- Create new folder: AppointEase
- Extract all project files to this folder
- Verify folder structure:
  * C:\xampp\htdocs\AppointEase\index.php
  * C:\xampp\htdocs\AppointEase\login.php
  * C:\xampp\htdocs\AppointEase\includes\

4. Access the System
- Ensure Apache & MySQL are running
- Open browser
- Go to: http://localhost/AppointEase

Login Credentials
-------------------------------------------------------------------------------
1. Super Admin / Registrar:
   - Username: admin
   - Password: Admin_12345
   - Role: Administrator
   - Access: User management, System settings

2. Doctor Account:
   - Username: Nina
   - Password: Nina_123
   - Role: Doctor
   - Access: View appointments, Manage schedule

3. Patient Account:
   - Username: ak123
   - Password: Ak12345678
   - Role: User / Patient
   - Access: Booking appointments, Viewing history

Access Points
-------------------------------------------------------------------------------
1. Main Landing Page:
   - URL: http://localhost/AppointEase/index.php
   - Features: Public info, Services, Doctor list

2. Login Portal:
   - URL: http://localhost/AppointEase/login.php
   - Features: Secure authentication, Role-based redirection

System Features
-------------------------------------------------------------------------------
1. Patient Panel:
   - Secure Registration with OTP logic
   - Appointment Booking Wizard
   - Real-time Doctor Availability
   - Medical History View

2. Doctor Panel:
   - Doctor Dashboard (Daily Appointments Overview)
   - Manage Availability & Schedule
   - View Patient Details
   - Add Medical Notes & Prescriptions
   - Appointment Status Updates (Complete/Cancel)

3. Admin Panel:
   - Analytical Dashboard (Total Patients, Appointments, Active Doctors)
   - User Management (Add/Edit/Delete Doctors & Staff)
   - Department & Services Management
   - System Audit Logs
   - Appointment Reports Generation

2. Security Features:
   - CSRF Protection
   - Session Hijacking Prevention
   - Password Encryption (Bcrypt)
   - Input Sanitization

Common Issues & Solutions
-------------------------------------------------------------------------------
1. Database Connection Error
   - Verify in includes/db.php:
   ```php
   $host = "localhost";
   $username = "root";
   $password = "";
   $database = "appointease_db";

   - Check XAMPP services are running
    - Verify database exists

Contact & Support
---------------------------------------------------------------------------------
For technical assistance:
Email: mkitmichail@gmail.com

Version Information
----------------------------------------------------------------------------------
Version: 1.0.0
Release Date: March 2025
Last Updated: Dec 2025
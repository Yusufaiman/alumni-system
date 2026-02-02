# Alumni Engagement System

## Project Overview
Alumni Engagement System is a web-based platform developed for the Software Engineering Fundamentals course.  
The system aims to strengthen engagement between students, alumni, career service officers, and administrators by providing a centralized platform for career development, mentorship, events, and job opportunities.

---

## System Roles
The system supports four main user roles:

- Student  
- Alumni  
- Career Service Officer  
- Administrator  

Each role has specific access rights and responsibilities within the system.

---

## Core Features

### Student
- View and register for events
- Apply for job opportunities
- Request mentorship from alumni
- View approved mentor
- Receive notifications and announcements
- Submit feedback

### Alumni
- Post job opportunities
- Share career updates
- View assigned mentees
- Manage posted jobs
- Receive notifications

### Career Service Officer
- Approve mentorship requests
- Manage events and announcements
- Review job applications
- Monitor student and alumni engagement

### Administrator
- Manage users
- View system reports and analytics
- Monitor activity logs
- Manage notifications and feedback
- Configure system settings

---

## Technology Stack
- Frontend: HTML, CSS
- Backend: PHP
- Database: MySQL
- Server Environment: XAMPP
- Version Control: GitHub

---

## Installation Guide

1. Install XAMPP
2. Clone the repository:
git clone https://github.com/Yusufaiman/alumni-system.git

csharp
Copy code
3. Move the project into:
C:\xampp\htdocs\

markdown
Copy code
4. Start Apache and MySQL via XAMPP Control Panel
5. Import the database using phpMyAdmin
6. Configure database connection in:
config/db.php

markdown
Copy code
7. Access the system via browser:
http://localhost/alumni-system

yaml
Copy code

---

## Database Setup
- Database name: alumni_system
- Import the provided SQL file via phpMyAdmin
- Ensure all required tables are created successfully

---

## Development Notes
- This project follows a role-based access control design
- Real-time notifications are handled through database-driven logic
- Mentorship requests require approval from Career Service Officer

---

## Contributors
- Muhammad Yusuf Aiman Bin Mohamad Salleh
- Asmaan B. Amry
- Wan Nur Adrianna Binti Mekar
- Yim Cheng Yong

---

## Course Information
Course: Software Engineering Fundamentals  
Institution: Multimedia University

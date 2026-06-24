# Event Registration System (ERS)

**Course:** SECJ3343 – Software Quality Assurance  
**Project:** Assignment 1  
**Institution:** Malaysia-Japan International Institute of Technology (MJIIT), UTM Kuala Lumpur

---

## Project Description

The Event Registration System (ERS) is a web-based application built with PHP and MySQL that allows users to register accounts, browse events, and manage their event registrations. The system includes a user portal for viewing and cancelling registrations, and an admin panel for creating, managing, and closing events — including attendee tracking and CSV export functionality.

---

## Tech Stack

| Layer      | Technology          |
|------------|---------------------|
| Frontend   | HTML, CSS           |
| Backend    | PHP 8.x             |
| Database   | MySQL / MariaDB     |
| Server     | Apache (XAMPP)      |
| DB Access  | PDO (prepared statements) |

---

## Project Structure

```
ers/
├── schema.sql                   ← Database schema + seed data
├── assets/
│   └── css/
│       └── style.css            ← Shared stylesheet
├── includes/
│   ├── db.php                   ← PDO database connection
│   ├── session.php              ← Session management
│   └── navbar.php               ← Shared navigation bar
├── register.php                 ← User registration
├── login.php                    ← User login
├── logout.php                   ← User logout
├── events.php                   ← Browse & register for events
├── my_registrations.php         ← View & cancel registrations
└── admin/
    ├── dashboard.php            ← Admin dashboard
    ├── create_event.php         ← Create new event
    ├── close_event.php          ← Close event registration
    ├── delete_event.php         ← Delete event
    └── attendees.php            ← View attendees & export CSV
```

---

## Setup Instructions

### Requirements
- XAMPP (Apache + MySQL)
- PHP 8.0 or higher
- Browser (Chrome / Firefox)

### Steps

**1. Start XAMPP**  
Open XAMPP Control Panel and start both **Apache** and **MySQL**.

**2. Import the database**  
- Go to `http://localhost/phpmyadmin`
- Click the **Import** tab
- Select `schema.sql` and click **Go**

**3. Place the project folder**  
Copy the `ers/` folder to:
```
C:/xampp/htdocs/ers/
```

**4. Open the application**  
```
http://localhost/ers/register.php
```

---

## Default Accounts

| Role  | Email           | Password     |
|-------|-----------------|--------------|
| Admin | admin@ers.com   | Admin@1234   |

New user accounts can be created via the registration page.

---

## Features

### User
- Register an account (name, email, password, age)
- Login and logout with session management
- Browse available events with seat availability
- Register for events (with validation checks)
- View personal registrations (Upcoming / Past)
- Cancel registrations (minimum 24 hours before event)

### Admin
- Secure admin-only dashboard
- Create events with full validation
- Close event registration
- Delete events (only if no registrations exist)
- View attendee list per event
- Export attendee list as CSV

---

## Functional Requirements Coverage

| FR ID | Description                        | File                        |
|-------|------------------------------------|-----------------------------|
| FR-01 | User Registration                  | register.php                |
| FR-02 | Inline Input Validation            | register.php                |
| FR-03 | User Login                         | login.php                   |
| FR-04 | Session Management (30 min)        | includes/session.php        |
| FR-05 | User Logout                        | logout.php                  |
| FR-06 | Duplicate Email Prevention         | register.php                |
| FR-07 | View Event List                    | events.php                  |
| FR-08 | Event Registration                 | events.php                  |
| FR-09 | Seat Limit Enforcement             | events.php                  |
| FR-10 | Duplicate Registration Prevention  | events.php                  |
| FR-11 | Registration Deadline Enforcement  | events.php                  |
| FR-12 | View My Registrations              | my_registrations.php        |
| FR-13 | Cancel Registration (24h rule)     | my_registrations.php        |
| FR-14 | Admin Role Gate                    | includes/session.php        |
| FR-15 | Create Event                       | admin/create_event.php      |
| FR-16 | Event Input Validation             | admin/create_event.php      |
| FR-17 | View Attendees                     | admin/attendees.php         |
| FR-18 | Close Event                        | admin/close_event.php       |
| FR-19 | Delete Event (no registrations)    | admin/delete_event.php      |
| FR-20 | Export Attendee CSV                | admin/attendees.php         |
| FR-21 | Duplicate Event Name Prevention    | admin/create_event.php      |

---

## Security

- Passwords hashed using `password_hash()` with bcrypt
- All database queries use PDO prepared statements (SQL injection safe)
- Sessions use `httponly` cookies (XSS protection)
- Account locked after 5 failed login attempts (15-minute lockout)
- Destructive actions (logout, cancel, delete) use POST requests only
- Admin pages protected by role-based access control

---

## Team

| Name                  | Matric No    | Role        |
|-----------------------|--------------|-------------|
| Muhieddin Ibrahim     | A23MJ4004    | Developer   |

# Event Registration System (ERS)
## SECJ3343 – Software Quality Assurance | Project 1

---

## Tech Stack
- **Frontend**: HTML, CSS (custom design system)
- **Backend**: PHP 8.x
- **Database**: MySQL 8.x (PDO)

---

## Project Structure

```
ers/
├── schema.sql                  ← Run this first in MySQL
├── assets/
│   └── css/style.css           ← Shared stylesheet
├── includes/
│   ├── db.php                  ← PDO database connection
│   ├── session.php             ← Session management (FR-04, FR-05)
│   └── navbar.php              ← Shared navigation bar
│
├── register.php                ← FR-01, FR-02, FR-06
├── login.php                   ← FR-03, FR-04 + account lock
├── logout.php                  ← FR-05
├── events.php                  ← FR-07, FR-08, FR-09, FR-10, FR-11
├── my_registrations.php        ← FR-12, FR-13
│
└── admin/
    ├── dashboard.php           ← FR-14 (admin gate + stats)
    ├── create_event.php        ← FR-15, FR-16, FR-21
    ├── close_event.php         ← FR-18
    ├── delete_event.php        ← FR-19
    └── attendees.php           ← FR-17, FR-20 (CSV export)
```

---

## Setup

### 1. Database
```bash
mysql -u root -p < schema.sql
```
This creates `ers_db`, all tables, a default admin account, and 3 sample events.

### 2. Configure DB credentials
Edit `includes/db.php`:
```php
define('DB_USER', 'root');   // your MySQL user
define('DB_PASS', '');        // your MySQL password
```

### 3. Web Server
Place the `ers/` folder in your web root (`htdocs` for XAMPP, `www` for WAMP):
```
C:/xampp/htdocs/ers/
```
Then open: `http://localhost/ers/register.php`

---

## Default Accounts

| Role  | Email           | Password    |
|-------|-----------------|-------------|
| Admin | admin@ers.com   | Admin@1234  |

Register a new user via `/register.php`.

---

## Functional Requirements Coverage

| FR ID | Description                      | File                      |
|-------|----------------------------------|---------------------------|
| FR-01 | User Registration                | register.php              |
| FR-02 | Input Validation (inline errors) | register.php              |
| FR-03 | User Login                       | login.php                 |
| FR-04 | Session Management (30 min)      | includes/session.php      |
| FR-05 | User Logout                      | logout.php                |
| FR-06 | Duplicate Email Prevention       | register.php              |
| FR-07 | View Event List                  | events.php                |
| FR-08 | Event Registration               | events.php                |
| FR-09 | Seat Limit Enforcement           | events.php                |
| FR-10 | Duplicate Registration           | events.php                |
| FR-11 | Deadline Enforcement             | events.php                |
| FR-12 | View My Registrations            | my_registrations.php      |
| FR-13 | Cancel Registration (24h rule)   | my_registrations.php      |
| FR-14 | Admin Authentication Gate        | includes/session.php      |
| FR-15 | Create Event                     | admin/create_event.php    |
| FR-16 | Event Input Validation           | admin/create_event.php    |
| FR-17 | View All Registrations           | admin/attendees.php       |
| FR-18 | Close Event                      | admin/close_event.php     |
| FR-19 | Delete Event (no registrations)  | admin/delete_event.php    |
| FR-20 | Export Attendee CSV              | admin/attendees.php       |
| FR-21 | Duplicate Event Name Prevention  | admin/create_event.php    |

---

## Security Notes
- Passwords hashed with `password_hash()` bcrypt cost 12 (NFR-01)
- All DB queries use PDO prepared statements (SQL injection safe)
- Sessions use `httponly` cookies (XSS protection)
- Account locks after 5 failed login attempts for 15 minutes (UC-02)
- POST-only for destructive actions (logout, cancel, close, delete)
- Admin gate enforced on every admin page via `require_admin()`

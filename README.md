# Legacy CRM System - Machine Round Test

## Setup Instructions

### 1. Prerequisites
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer

### 2. Installation

```bash
# Clone the repository
git clone https://github.com/YOUR-USERNAME/legacy-crm-test.git
cd legacy-crm-test

# Install dependencies
composer install

# Copy environment file
cp env .env

# Edit .env file with your database credentials
```

### 3. Database Setup

Edit `.env` file:
```
database.default.hostname = localhost
database.default.database = legacy_crm
database.default.username = root
database.default.password = 
database.default.DBDriver = MySQLi
```

Also set a JWT secret (any long random string) for the API:
```
jwt.secret = 'change-me-to-something-long-and-random'
jwt.ttl = 3600
```

Run the migrations and seeders:
```bash
php spark migrate
php spark db:seed DatabaseSeeder
php spark db:seed UserSeeder
```

This will create:
- `customers` table with 100 sample records
- `customer_activities` table with activity logs
- `users` table with the four accounts listed below

If you'd rather run plain SQL than the migrations, the schema changes and
seed users are in `migrations/your_changes.sql`.

### 4. Start Development Server

```bash
php spark serve
```

Visit: http://localhost:8080

### 5. Login Credentials

Login is by **email**, not username.

| Email | Password | Role | Sees |
|---|---|---|---|
| `admin@crm.test` | `admin123` | admin | everything, only role that can delete |
| `manager@crm.test` | `manager123` | manager | views all, edits own + team |
| `sales@crm.test` | `sales123` | sales | 40 customers, in the manager's team |
| `solo@crm.test` | `solo1234` | sales | 30 customers, outside the team |

10 customers are left unassigned on purpose — only admin sees those.

The same accounts work for `POST /api/login`.

### 6. Email (optional)

Nothing breaks without this — a failed email is logged and the customer is
still created. To actually send, fill in the SMTP block in `.env`
(Mailtrap works):

```
email.enabled = true
email.SMTPHost = 'sandbox.smtp.mailtrap.io'
email.SMTPUser = '...'
email.SMTPPass = '...'
email.SMTPPort = 2525
```

Preview a template without sending: `php spark email:preview 3 welcome`

### 7. API

See `docs/API.md` for the full reference, or import
`docs/legacy-crm.postman_collection.json` into Postman — run "Login" and the
token is stored automatically.

```bash
curl -X POST http://localhost:8080/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@crm.test","password":"admin123"}'
```

### What changed from the original

`CHANGES.md` has the full write-up — the 8 bugs, what was actually wrong with
each, and how the four features are put together.

## Project Structure

```
app/
├── Controllers/
│   ├── Auth.php          # Login/Logout
│   ├── Dashboard.php     # Home page
│   └── Customers.php     # Customer CRUD
├── Models/
│   ├── CustomerModel.php
│   └── ActivityModel.php
├── Views/
│   ├── auth/
│   ├── customers/
│   └── layout/
└── Database/
    ├── Migrations/
    └── Seeds/
```

## Your Task

This is a legacy system with **intentional bugs and missing features**. Your job is to:

1. **Find and fix 8 broken features** (search, delete, edit, dashboard, filters, export, validation, pagination)
2. **Build CSV Import feature** (with validation and error handling)
3. **Build Email Notification System** (SMTP integration, EmailService class, templates)
4. **Build Bulk Delete feature** (checkboxes, Select All, JavaScript, confirmation)
5. **Document everything** in `CHANGES.md` file

**Important:** You must test the application to find what's broken. We won't tell you exactly where each bug is - that's part of the test!

See full task description and evaluation criteria after you register on the portal.

Good luck!

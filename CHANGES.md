# CHANGES

What I fixed and what I built, in the order I did it.

Everything was done on top of the original repo. Test credentials and the
API instructions are at the bottom.

---

## Setup problem before anything else

The repo would not even boot after cloning:

```
Failed opening required '.../app/Config/Paths.php'
```

`.gitignore` was excluding CodeIgniter's own app skeleton — `Paths.php`,
`Constants.php`, `Mimes.php`, `UserAgents.php`, `Config/Boot/`,
`Views/errors/` and all of `writable/`. None of it had ever been committed,
so `php spark` had nothing to bootstrap from.

I restored those files from `vendor/codeigniter4/framework`, pointed
`Paths::$systemDirectory` at the Composer install path, and removed those
entries from `.gitignore` so the next person who clones this doesn't hit the
same wall. `writable/` keeps its folder structure through `.gitkeep` files.
Only `.env` and `vendor/` stay ignored.

---

## Part 1 — the 8 bugs

Each one is a separate commit.

### 1. Search did nothing

`Customers::index()` read `search` from the query string and passed it back
to the view (so the box kept its text and looked like it worked), but it was
never used in the query. Every search returned all 100 rows.

There was a second problem in the same three lines — the query was built with
`$this->customerModel->builder()` and `paginate()` only exists on the Model,
not on the Builder. So the page actually threw `BadMethodCallException`
before rendering anything.

Fixed by querying through the model and matching name OR email:

```php
$customers = $customers->groupStart()
    ->like('name', $search)
    ->orLike('email', $search)
    ->groupEnd();
```

The `groupStart()/groupEnd()` matters. Without it, once the status filter got
added in the next fix, SQL precedence would turn the WHERE into
`name LIKE ... OR (email LIKE ... AND status = ...)` and the status filter
would only half apply.

### 2. Status filter did nothing

Same shape as the search bug — read, echoed back into the dropdown, never
applied. Added a `where()`, skipped when "All Status" (empty value) is picked.

Checked against the seed data: active 42 + inactive 26 + pending 32 = 100.

### 3. Pagination links never showed

The controller passed `'pager' => null`, and the view guards on
`<?php if ($pager): ?>`. So the block never rendered and only the first 20 of
100 customers were reachable. Passed `$this->customerModel->pager` instead.

Page counts follow the active filters, and the links keep the query string,
so searching and then going to page 2 doesn't lose the search.

### 4. Edit updated the wrong rows

```php
$this->customerModel->update($customer, $data);   // $customer is the whole row
```

The first argument should be the id. When you hand CI4 an array it treats it
as a *list of ids*, so the WHERE became:

```sql
WHERE id IN ('101','Probe Row','probe@x.com','7','X','Y','active', ...)
```

The nasty part is that this usually looks fine — the row's own `id` is in
that array, and the other values cast to 0 in MySQL and match nothing. But
any column that happens to contain a number matching another row's id will
update that row too. I reproduced it: a customer with phone `"7"` overwrote
customer 7's name. Silent data corruption, not just a broken button.

Fixed to `update($id, $data)`.

While testing this I found a second bug behind it. `ActivityModel` had
`$updatedField = null` with `$useTimestamps = true`. CI4 checks
`$this->updatedField !== ''`, and `null !== ''` is true, so it tried to set
`$row[null] = $date` and produced an empty column name:

```sql
INSERT INTO customer_activities (..., `created_at`, ``) VALUES (...)
```

That is a SQL syntax error, which means **create and edit both 500'd** as
soon as they tried to log the activity. The table has no `updated_at` column
at all, so disabling it was right — the value just has to be `''`, not `null`.

### 5. Delete didn't delete

```php
// $this->customerModel->delete($id);
return redirect()->to('/customers')->with('success', 'Customer deleted successfully');
```

The delete was commented out but the success message wasn't. The UI told you
the customer was gone and it was still in the list. Uncommented it. The FK on
`customer_activities` is `ON DELETE CASCADE`, so activities go with it.

### 6. Dashboard counts were hardcoded

`'total_customers' => 0` and `'active_customers' => 0`. The model was already
being constructed for the recent-customers list, so it was just never used
for the counts. Replaced with `countAllResults()` — `COUNT(*)` in SQL rather
than pulling 100 rows into PHP to count them.

### 7. CSV export had no rows

`findAll()` fetched the customers, `fputcsv()` wrote the header, then the
file was closed. No loop over the data. The download worked and the filename
was right, which is probably why nobody noticed the file was one line long.

Added the loop. Output is 101 lines, 7 columns. Kept `fputcsv()` rather than
joining with commas so values containing commas don't break the file.

### 8. No validation at all

`$validationRules = []`. The `required` attributes in the form are browser
side only — remove them in DevTools, or POST directly, and anything saved.
Empty names, `abc` as an email, duplicate emails, a status of `archived`
which isn't even in the ENUM.

Added rules matching the actual column limits, plus readable messages. Also
added an error block to `layout/header.php` — without it the rules would
silently reject and the user would only see "Failed to create customer" with
no reason.

`is_unique[customers.email,id,{id}]` needed the id in the data on update,
otherwise editing a customer without changing their email would fail against
their own record.

---

## Part 4 — Role based access (I did this before the API on purpose)

I built RBAC before the REST API even though it's listed later, because
`customers.assigned_to` changes the queries in both the API and the
dashboard. Doing it last would have meant rewriting both.

### Schema

- `users` — name, email (unique), password, `role` ENUM(admin, manager, sales),
  `manager_id` self-referencing FK
- `customers.assigned_to` → `users.id`, nullable, indexed, ON DELETE SET NULL

`manager_id` is how "their team" is defined — a manager's team is every user
whose `manager_id` points at them.

### Where the rules live

All of it is in `app/Libraries/Permission.php`. The filter, the controllers
and the views all call the same methods, so there's no way for the button to
be hidden while the URL stays open.

The spec sentence is worth reading carefully: *"Manager: View all customers,
edit customers assigned to their team only"*. Viewing and editing have
different scopes, so there are two methods:

```
visibleUserIds()   admin: all   manager: all    sales: own
editableUserIds()  admin: all   manager: team   sales: own
```

Login also moved off the hardcoded `admin/admin123` to the users table with
`password_verify()`, and `session()->regenerate()` on login.

### What I checked

Hiding buttons isn't security, so I tested the URLs directly:

```
sales    GET  /customers/edit/50    403
manager  GET  /customers/edit/50    403   (outside team)
manager  GET  /customers/edit/10    200   (inside team)
manager  GET  /customers/view/50    200   (view is all)
sales    POST /customers/update/50  403   (straight POST, no form)
manager  GET  /customers/delete/75  403
```

Scoping applies to the list, the dashboard and the CSV export, not just the
list page.

I seeded a 4th user (`solo@crm.test`) on top of the three the spec asks for.
Without a sales user *outside* the manager's team, "manager sees everything"
and "manager sees only their team" produce identical results and the test
proves nothing.

---

## Part 2 — REST API

`firebase/php-jwt` for tokens. Secret and TTL come from `.env`.

`POST /api/login` returns the token, `expires_in` and `expires_at`.
Everything else needs `Authorization: Bearer <token>`.

Endpoints: list, show, create, update (PUT and PATCH), delete.
Query params on the list: `page`, `per_page`, `status`, `city`, `company`,
`search`, `sort`, `order`.

Two things worth calling out:

`sort` is checked against a whitelist and falls back to `id`, so the
parameter never reaches SQL. `per_page` is capped at 100 so nobody can ask
for 50,000 rows in one request.

The API reuses the same `Permission` class as the web UI. The JWT filter just
tells it who the caller is:

```php
Permission::actAs(['id' => $payload['sub'], 'role' => $payload['role']]);
```

Writing the permission rules a second time for the API is how these things
drift apart, so I didn't.

Token handling checked: no token 401, garbage 401, expired 401, signed with a
different secret 401, and a token with `role` changed to admin 401 — the role
is inside the signature.

Docs are in `docs/API.md` with curl examples, and there's a Postman
collection in `docs/legacy-crm.postman_collection.json`. Run "Login" in it
and the token is saved to a collection variable automatically.

---

## Part 3 — Email

`app/Services/EmailService.php`, templates in `app/Views/emails/` with a
shared `layout.php` so both emails have the same header and footer.

A welcome email goes out when a customer is created (web form and API), and a
second one when their status changes. Sending the same status twice does not
send a second email.

The requirement is that a failed email must not break the create flow, so
`send()` catches everything, logs it and returns false. I tested it with
deliberately broken SMTP settings:

```
POST /api/customers   201, customer created
log: ERROR --> Email "Welcome to Legacy CRM, ..." failed: ...
```

There are two layers — the `false` return from `Email::send()`, and a
`try/catch (Throwable)` around the whole thing for when the SMTP host can't
be reached at all and it throws instead.

`email.enabled = false` in `.env` skips sending entirely, which is handy when
you don't want to configure SMTP just to test something else.

To actually verify delivery rather than just "it didn't crash", I ran a local
SMTP server and pointed `.env` at it. Both emails arrived with the right
subject, from-address and HTML body.

Two CLI helpers:

```bash
php spark email:preview 3 welcome   # renders to writable/, sends nothing
php spark email:test 3              # sends for real
```

---

## Part 5 — Dashboard

Four cards (total, active, new this month, inactive), a 6 month growth line
chart, a status pie, a top-5-cities bar chart, and the last 10 activities.
Chart.js from the same CDN Bootstrap already uses.

All the aggregation is in `app/Services/DashboardService.php`. The four card
numbers come from one query, not four:

```sql
SELECT COUNT(*) AS total,
       SUM(status = 'active') AS active,
       SUM(created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS new_this_month
FROM customers WHERE assigned_to IN (3)
```

Growth uses `DATE_FORMAT(created_at, '%Y-%m')` with a GROUP BY, but I build
the 6 month range in PHP first and fill in the DB numbers. If you just render
what SQL returns, a month with no signups disappears from the axis and the
line chart lies about the trend.

Cached for an hour as asked, and the Refresh button clears it. The cache key
includes the scope:

```
admin, manager  ->  dashboard_all
sales           ->  dashboard_3
solo            ->  dashboard_4
```

This is the part I'd flag in review. A single cache key would have leaked one
role's numbers to another — the permission checks would all still pass, and
the wrong data would be served from cache. Admin and manager share a key
because they genuinely see the same set.

Header shows whether you're looking at cached or fresh data and when it was
generated, so it isn't a mystery why a new customer hasn't appeared yet.

Cards are `col-6 col-lg-3` (2x2 on phones), and every canvas sits in a fixed
height wrapper with `maintainAspectRatio = false` — without that Chart.js
grows the canvas on every resize on mobile.

---

## Things that gave me trouble

**The `update($customer, $data)` bug looked fine.** It passes a casual test
because the row's own id is inside the array it gets handed. I only caught
the cross-row corruption by deliberately giving a customer a phone number
that matched another customer's id.

**`in_array()` strict comparison.** `teamMemberIds()` returned ids as strings
from the DB, and `in_array((int) $assigned, $ids, true)` was comparing
`3 === '3'`. Managers couldn't edit their own team. The permission tests
caught it — reading the code, it looks correct. Fixed with
`array_map('intval', ...)`.

**`ActivityModel::$updatedField = null`.** Covered above. Reasonable looking
code, and CI4's check is against `''`, not `null`.

**Email verification.** "It returns true" isn't proof the mail is right. I
ran a throwaway SMTP server locally so I could see the actual message that
went over the wire.

---

## Test accounts

| Email | Password | Role | Notes |
|---|---|---|---|
| admin@crm.test | admin123 | admin | full access |
| manager@crm.test | manager123 | manager | 20 own, team of 1 |
| sales@crm.test | sales123 | sales | 40 customers, under the manager |
| solo@crm.test | solo1234 | sales | 30 customers, no manager |

10 customers are deliberately left unassigned — only admin sees those.

Same accounts work for `POST /api/login`.

## Running it

```bash
composer install
cp env .env          # then set database.default.* and jwt.secret
php spark migrate
php spark db:seed DatabaseSeeder
php spark db:seed UserSeeder
php spark serve
```

For email, fill in the SMTP settings in `.env` (Mailtrap works):

```
email.SMTPHost = 'sandbox.smtp.mailtrap.io'
email.SMTPUser = '...'
email.SMTPPass = '...'
email.SMTPPort = 2525
```

## API quick start

```bash
TOKEN=$(curl -s -X POST http://localhost:8080/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@crm.test","password":"admin123"}' | jq -r .data.token)

curl "http://localhost:8080/api/customers?status=active&sort=name&order=asc&per_page=5" \
  -H "Authorization: Bearer $TOKEN"

curl -X POST http://localhost:8080/api/customers \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"name":"New Customer","email":"new@example.com","phone":"9876543210"}'
```

Full reference with every parameter and status code: `docs/API.md`.

## Database changes

Two migrations:

- `2024-01-02-000001_CreateUsersTable`
- `2024-01-02-000002_AddAssignedToCustomers`

Plain SQL version of both, plus the seed data for the test users, is in
`migrations/your_changes.sql` if you'd rather run it directly than use spark.

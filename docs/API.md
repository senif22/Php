# Legacy CRM REST API

**Base URL (local):** `http://localhost:8080`

All API responses are returned in JSON format.

Except for the login endpoint, all API requests require a valid Bearer token in the `Authorization` header.

## Authentication

### Login

**POST `/api/login`**

Use the following request to log in:

```bash
curl -X POST http://localhost:8080/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@crm.test","password":"admin123"}'
```

A successful login returns a token:

```json
{
  "status": 200,
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "expires_at": "2026-08-23T09:36:18+00:00",
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@crm.test",
      "role": "admin"
    }
  }
}
```

### Login response codes

| Code | Description                        |
| ---- | ---------------------------------- |
| 200  | Login successful                   |
| 400  | Email or password was not provided |
| 401  | Invalid email or password          |

For all other API requests, send the token like this:

```http
Authorization: Bearer <token>
```

### Get Current User

**GET `/api/me`**

Returns the currently authenticated user's information.

The user's password is never included in the response.

---

# Customers

## List Customers

**GET `/api/customers`**

Returns a paginated list of customers.

The endpoint supports filtering, searching, sorting, and pagination.

### Query parameters

| Parameter  | Example              | Description                                                  |
| ---------- | -------------------- | ------------------------------------------------------------ |
| `page`     | `?page=2`            | Page number. Defaults to `1`.                                |
| `per_page` | `?per_page=50`       | Number of records per page. Defaults to `20`, maximum `100`. |
| `status`   | `?status=active`     | Filters by exact status.                                     |
| `city`     | `?city=Mumbai`       | Filters by exact city.                                       |
| `company`  | `?company=Data Corp` | Filters by exact company name.                               |
| `search`   | `?search=Customer 1` | Searches by customer name or email.                          |
| `sort`     | `?sort=name`         | Sort field.                                                  |
| `order`    | `?order=asc`         | Sort direction: `asc` or `desc`.                             |

Supported sort fields are:

* `id`
* `name`
* `email`
* `company`
* `city`
* `status`
* `created_at`
* `updated_at`

The sort field is checked against a whitelist before being used in the query. If an unsupported field is provided, the API falls back to `id`.

Example:

```bash
curl 'http://localhost:8080/api/customers?status=active&city=Mumbai&sort=name&order=asc&page=1&per_page=20' \
  -H "Authorization: Bearer $TOKEN"
```

Example response:

```json
{
  "status": 200,
  "data": [
    {
      "id": "93",
      "name": "Customer 93",
      "...": "..."
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 2,
    "total_pages": 1,
    "sort": "name",
    "order": "asc"
  }
}
```

The `total` value is calculated after applying the user's permission scope, so users only see counts for customers they are allowed to access.

---

## Get Customer

**GET `/api/customers/{id}`**

Returns a single customer by ID.

Possible responses:

* `200` — Customer found
* `403` — Customer is outside the user's allowed scope
* `404` — Customer does not exist

---

## Create Customer

**POST `/api/customers`**

Creates a new customer.

Example:

```bash
curl -X POST http://localhost:8080/api/customers \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"name":"API Created","email":"apicreate@x.com","phone":"9876543210","city":"Surat","status":"active"}'
```

### Required fields

* `name`
* `email`

If `status` is not provided, it defaults to `active`.

Admins can provide `assigned_to` to assign the customer to another user.

For other roles, the customer is automatically assigned to the currently logged-in user.

A successful request returns `201` with the newly created customer.

If validation fails, the API returns `400` with an `errors` object containing the validation errors.

---

## Update Customer

**PUT `/api/customers/{id}`**

Updates an existing customer.

Partial updates are supported, so you only need to send the fields you want to change.

`PATCH` requests are also handled by the same update logic.

Example:

```bash
curl -X PUT http://localhost:8080/api/customers/50 \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"city":"Rajkot"}'
```

### Response codes

| Code | Description                                           |
| ---- | ----------------------------------------------------- |
| 200  | Customer updated successfully                         |
| 400  | Validation failed or no updateable field was provided |
| 403  | User is not allowed to edit this customer             |
| 404  | Customer does not exist                               |

---

## Delete Customer

**DELETE `/api/customers/{id}`**

Deletes a customer.

Only users with the `admin` role can delete customers.

### Response codes

* `200` — Customer deleted successfully
* `403` — User does not have permission to delete
* `404` — Customer does not exist

---

# Permissions

The REST API follows the same permission rules used by the CRM web application.

| Role    | View          | Edit          | Delete |
| ------- | ------------- | ------------- | ------ |
| Admin   | All customers | All customers | Yes    |
| Manager | All customers | Own + team    | No     |
| Sales   | Own customers | Own customers | No     |

The permission scope is applied directly to the database query. This means pagination and the `total` count already respect the logged-in user's permissions.

For example, a sales user will not receive customers belonging to another sales user, even if those records exist in the database.

---

# HTTP Status Codes

| Code | Meaning                                                  |
| ---- | -------------------------------------------------------- |
| 200  | Request completed successfully                           |
| 201  | Resource created successfully                            |
| 400  | Bad request or validation failed                         |
| 401  | Token is missing, invalid, expired, malformed, or forged |
| 403  | User is authenticated but does not have permission       |
| 404  | Requested resource was not found                         |

---

# Test Accounts

The following accounts are available for testing the API:

| Email              | Password     | Role    |
| ------------------ | ------------ | ------- |
| `admin@crm.test`   | `admin123`   | admin   |
| `manager@crm.test` | `manager123` | manager |
| `sales@crm.test`   | `sales123`   | sales   |
| `solo@crm.test`    | `solo1234`   | sales   |

---

# JWT Configuration

JWT settings are stored in the `.env` file:

```env
jwt.secret = '<64-char random string>'
jwt.ttl    = 3600
```

The API uses **HS256** for signing JWT tokens.

The token contains the following information:

* `sub`
* `role`
* `email`
* `name`
* `manager_id`
* `iat`
* `exp`

The user's role is taken from the signed JWT payload. Because the token is signed, changing the role manually on the client side will invalidate the signature.

This prevents a user from simply modifying their token to gain additional permissions.

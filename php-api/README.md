# KaamSetu Core PHP API

Separate REST API backend for KaamSetu (users, roles, categories, services). Use with the Next.js app or any client.

## Requirements

- PHP 8.0+
- MySQL (same DB as Next.js or separate)
- Composer

## Setup

1. **Install dependencies**
   ```bash
   cd php-api
   composer install
   ```

2. **Database**
   - Create database if needed.
   - Run schema: `mysql -u user -p database < database/schema.sql`
   - Create first super admin (optional):
     ```sql
     INSERT INTO users (name, email, password, role) VALUES
     ('Super Admin', 'superadmin@kaamsetu.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');
     ```
     Default password for above hash: `password` (change after first login).

3. **Environment**
   ```bash
   cp .env.example .env
   # Edit .env: DB_*, JWT_SECRET
   ```

4. **Run**
   - **PHP built-in server (required):** use the router so all requests go through `index.php` and `getDb()` is available:
     ```bash
     php -S localhost:8080 -t . router.php
     ```
     Base URL: `http://localhost:8080`. Without `router.php`, URLs like `/api/categories` would be served directly by the built-in server and would skip config/database.php, causing "Call to undefined function getDb()".
   - **Apache:** Point document root to `php-api/`, ensure `mod_rewrite` is enabled (see `.htaccess`).

## Roles

| Role        | Description                    | Can do                                                                 |
|------------|---------------------------------|------------------------------------------------------------------------|
| super_admin| Full access                     | All endpoints, manage admins/staff/users, categories, services        |
| admin      | Admin panel                     | Manage users (create/update/delete), categories, services             |
| staff      | Support / ops                   | List users, manage categories & services, no user create/delete       |
| professional| Service provider (e.g. from approve) | Own profile (GET/PUT me), use public categories/services        |
| end_user   | Customer (books services)       | Own profile (GET/PUT me), use public categories/services             |

## API Endpoints

### Roles (no token)

- **GET** `/api/roles` — List roles: `super_admin`, `admin`, `staff`, `professional`, `end_user` (for dropdowns).

### Auth (no token)

- **POST** `/api/auth/register`  
  Body: `{ "name", "email", "password", "phone?", "role?" }`  
  Default role: `end_user`. Returns `user` + `token`.

- **POST** `/api/auth/login`  
  Body: `{ "email", "password" }`  
  Returns `user` + `token`.

### Users (Bearer token)

- **GET** `/api/users/me` — Current user (any role).
- **GET** `/api/users` — List users (staff+). Query: `role`, `search`, `page`, `limit`.
- **POST** `/api/users` — Create user (admin+). Body: `name`, `email`, `password`, `phone?`, `role`.
- **GET** `/api/users/{id}` — Get user (self or staff+).
- **PUT** `/api/users/{id}` — Update user (self: name/phone/password; admin+: all + role/is_active).
- **DELETE** `/api/users/{id}` — Delete user (admin+). Cannot delete self.

### Categories (public read; write = staff+)

- **GET** `/api/categories` — List. Query: `all=1` to include inactive.
- **GET** `/api/categories/{id}` — One category.
- **POST** `/api/categories` — Create (staff+). Body: `name`, `slug?`, `description?`, `sort_order?`, `is_active?`.
- **PUT** `/api/categories/{id}` — Update (staff+).
- **DELETE** `/api/categories/{id}` — Delete (staff+).

### Services (public read; write = staff+)

- **GET** `/api/services` — List. Query: `category_id`, `all=1`.
- **GET** `/api/services/{id}` — One service.
- **POST** `/api/services` — Create (staff+). Body: `category_id`, `name`, `slug?`, `description?`, `sort_order?`, `is_active?`.
- **PUT** `/api/services/{id}` — Update (staff+).
- **DELETE** `/api/services/{id}` — Delete (staff+).

## Request format

- **Auth:** Send JWT in header: `Authorization: Bearer <token>`.
- **JSON:** Request body and responses are JSON; set `Content-Type: application/json` for POST/PUT.

## Response format

- Success: `{ "success": true, "message": "...", "data": ... }`
- Error: `{ "success": false, "message": "..." }` with appropriate HTTP status (400, 401, 403, 404, 409, 500).

## CORS

Responses send `Access-Control-Allow-Origin: *`. Restrict in production if needed.

---

## Dashboard (Core PHP)

A **role-based admin dashboard** is in the `dashboard/` folder. It uses the same database and `users` table.

- **URL:** Run the server from `php-api/` and open `http://localhost:8080/dashboard/`
- **Roles:** Super Admin, Admin, Staff (manage users/categories/services/bookings/professionals), Professional (service provider; dashboard + profile), End User (customer; dashboard + profile).
- **Details:** See [dashboard/README.md](dashboard/README.md).

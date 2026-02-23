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
   - If `services` table already exists without an `icon` column, run: `mysql -u user -p database < database/add_service_icon.sql`
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

| Role         | Description                    | Can do                                                                 |
|-------------|---------------------------------|------------------------------------------------------------------------|
| super_admin | Super                           | Add **team leaders**; manage categories, services, bookings, professionals |
| team_leader | Team leader                     | Add **staff**; manage categories, services, bookings, professionals  |
| staff       | Staff                           | View and edit **bookings**, **professionals**, and **own profile** only (no users, no categories/services) |
| professional| Service provider                | Own profile; use public categories/services                           |
| end_user    | Customer (books services)       | Own profile; use public categories/services                            |

## API Endpoints

### Roles (no token)

- **GET** `/api/roles` — List roles: `super_admin`, `team_leader`, `staff`, `professional`, `end_user` (for dropdowns).

### Auth (no token)

- **POST** `/api/auth/register`  
  Body: `{ "name", "email", "password", "phone?", "role?" }`  
  Default role: `end_user`. Returns `user` + `token`.

- **POST** `/api/auth/login`  
  Body: `{ "email", "password" }`  
  Returns `user` + `token`.

### Users (Bearer token)

- **GET** `/api/users/me` — Current user (any role).
- **GET** `/api/users` — List users (super or team_leader only). Super sees team_leader; team_leader sees staff. Query: `search`, `page`, `limit`.
- **POST** `/api/users` — Create user: super can create `team_leader` only; team_leader can create `staff` only. Body: `name`, `email`, `password`, `phone?`, `role`.
- **GET** `/api/users/{id}` — Get user (self or super/team_leader within scope).
- **PUT** `/api/users/{id}` — Update user (self: name/phone/password; super/team_leader: users in their scope + role/is_active).
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
- **POST** `/api/services` — Create (staff+). Body: `category_id`, `name`, `slug?`, `description?`, `icon?`, `sort_order?`, `is_active?`.
- **PUT** `/api/services/{id}` — Update (staff+). Body may include `icon` (path string).
- **DELETE** `/api/services/{id}` — Delete (staff+).

## Request format

- **Auth:** Send JWT in header: `Authorization: Bearer <token>`.
- **JSON:** Request body and responses are JSON; set `Content-Type: application/json` for POST/PUT.

## Response format

- Success: `{ "success": true, "message": "...", "data": ... }`
- Error: `{ "success": false, "message": "..." }` with appropriate HTTP status (400, 401, 403, 404, 409, 500).

## Booking flow (assign & log)

- New bookings start with **status** `pending`. Run `database/migrations/003_booking_flow_assign_log.sql` to add: `status`, `assigned_to`, `assigned_by`, `assigned_at`, `created_by`, `created_at` on `bookings`, and the **booking_log** table.
- **Staff / team leader / super** can **assign** a booking to a **professional** in the dashboard (Bookings → select professional → Assign). The professional then sees that booking under their own Bookings list.
- **booking_log** records every action: `created` (with service, created_by), `assigned` (with assigned_to, assigned_by, etc.). In the dashboard, use the **Log** link next to a booking to view its history.

## Service icon / image

- Services can have an optional **icon** (image) stored in `services.icon` (path, e.g. `uploads/services/5.jpg`).
- In the **dashboard**, when creating or editing a service you can upload an image (JPG, PNG, GIF, WebP). If none is uploaded, a placeholder image is shown on the frontend and in the dashboard list.
- Uploads are stored under `php-api/uploads/services/`. The router serves `/uploads/` as static files.

## CORS

Responses send `Access-Control-Allow-Origin: *`. Restrict in production if needed.

---

## Dashboard (Core PHP)

A **role-based admin dashboard** is in the `dashboard/` folder. It uses the same database and `users` table.

- **URL:** Run the server from `php-api/` and open `http://localhost:8080/dashboard/`
- **Roles:** Super Admin, Admin, Staff (manage users/categories/services/bookings/professionals), Professional (service provider; dashboard + profile), End User (customer; dashboard + profile).
- **Details:** See [dashboard/README.md](dashboard/README.md).

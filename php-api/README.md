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
   # Optional: FRONTEND_URL=https://yourapp.com — used for referral links (Profile & Referrals pages)
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

---

## Referral & Wallet System (Technical Overview)

This section documents how the **staff/professional referral system** and future **wallet** are wired in the PHP API.

### Database columns & migrations

- `php-api/database/migrations/005_professionals_referral_code.sql`
  - Adds to `professionals`:
    - `referral_code` `VARCHAR(64)` NULL
    - `referred_by_user_id` `INT UNSIGNED` NULL
- `php-api/database/migrations/006_users_referral_code.sql`
  - Adds to `users`:
    - `referral_code` `VARCHAR(64)` NULL
    - Unique index `idx_users_referral_code` on `referral_code`

Run these after `database/schema.sql` and `001_professionals_status_user_id.sql`:

```bash
mysql -u user -p dbname < php-api/database/migrations/005_professionals_referral_code.sql
mysql -u user -p dbname < php-api/database/migrations/006_users_referral_code.sql
```

### Staff referral codes

- When a **staff** user is created:
  - Via dashboard (`dashboard/pages/users.php`) or API (`POST /api/users`),
  - If `users.referral_code` exists, a **static code** is generated once, e.g. `STFABC123`, and stored in `users.referral_code`.
- This code is never regenerated on update and is used to attribute new professionals to that staff user.

### Professional registration & referral resolution

Endpoint: `POST /api/professionals/register` (`api/professionals/register.php`)

- Request body (public):

```json
{
  "name": "Pro Name",
  "phone": "9876543210",
  "email": "optional@example.com",
  "city": "City",
  "state": "State",
  "pincode": "400001",
  "language": "Hindi",
  "services": "Plumbing, Electrical",
  "referral_code": "optional-code"
}
```

- Behaviour:
  - Validates required fields.
  - Generates `professionalId = 'PR' . time()`.
  - Detects presence of `status`, `referred_by_user_id`, `referral_code` columns on `professionals`.
  - **Referral resolution** when `referral_code` is provided:
    1. Look up `users.referral_code = :code`.
       - If found → `referred_by_user_id = users.id`.
    2. Else, look up `professionals.referral_code = :code` and use their `user_id` (if not null).
       - If found → `referred_by_user_id = professionals.user_id`.
    3. Else → `jsonError('Invalid referral code', 400)`.
  - Inserts into `professionals`:
    - Always: `professionalId, name, phone, email, city, state, pincode, language, services`.
    - Optionally:
      - `status = 'pending'` (if `status` column exists).
      - `referred_by_user_id` (if column exists and referral matched).
      - `referral_code` is left `NULL` on creation; it is set later on approval.

### Professional approval & own referral codes

- Dashboard page: `dashboard/pages/professionals.php`.
- When a super_admin or team_leader clicks **Approve**:
  1. Loads professional row (`professionalId`).
  2. If status is `pending`:
     - Creates or reuses a `users` row with role `professional`:
       - If email present: uses it as unique key.
       - If email missing but phone present: generates synthetic email `pro_<digits>@auto.kaamsetu` to satisfy `users.email` constraints, while login continues by phone.
     - Updates `professionals.status = 'approved'` and `professionals.user_id = users.id`.
  3. If `professionals.referral_code` exists and is currently NULL/empty:

```php
$proReferralCode = 'PRO' . strtoupper(bin2hex(random_bytes(3))); // fallback MD5 if random_bytes fails
UPDATE professionals
SET referral_code = :code
WHERE professionalId = :id AND (referral_code IS NULL OR referral_code = '');
```

- Result: every approved professional can later refer other professionals with their own `professionals.referral_code`.

### Internal referral creation from dashboard

`dashboard/pages/profile.php` allows **staff** and **professional** roles to:

- See their own referral code (from `users.referral_code` or `professionals.referral_code` for professionals).
- See a count of how many professionals they referred:

```sql
SELECT COUNT(*) FROM professionals WHERE referred_by_user_id = :current_user_id;
```

- Create a new professional **directly from the dashboard**:
  - Form fields: `pro_name`, `pro_phone`, `pro_email`, `pro_city`, `pro_state`, `pro_pincode`, `pro_language`, `pro_services`.
  - Inserts into `professionals` with:
    - `status = 'pending'` (if available).
    - `referred_by_user_id = current users.id` (staff or professional).
    - `referral_code = NULL` (auto-assigned on approval).

### Referral reporting / management

Dashboard page: `dashboard/pages/referrals.php` (super_admin & team_leader only).

- **Staff Referral Codes**

```sql
SELECT u.id, u.name, u.email, u.phone, u.referral_code,
       COUNT(p.professionalId) AS total_referred
FROM users u
LEFT JOIN professionals p ON p.referred_by_user_id = u.id
WHERE u.role = 'staff'
GROUP BY u.id, u.name, u.email, u.phone, u.referral_code
ORDER BY total_referred DESC, u.id DESC;
```

- **Professional Referral Codes**

```sql
SELECT p.professionalId, p.name, p.phone, p.email, p.referral_code,
       u.id AS user_id,
       COUNT(r.professionalId) AS total_referred
FROM professionals p
JOIN users u ON p.user_id = u.id
LEFT JOIN professionals r ON r.referred_by_user_id = u.id
GROUP BY p.professionalId, p.name, p.phone, p.email, p.referral_code, u.id
HAVING p.referral_code IS NOT NULL
ORDER BY total_referred DESC, p.professionalId DESC;
```

These queries drive the admin **Referrals** dashboard so you can see how many professionals each staff member or professional has onboarded.

### Wallet (placeholder)

- Dashboard page: `dashboard/pages/wallet.php`.
- Currently a **full-page “Coming Soon”** screen, visible for:
  - `super_admin`, `team_leader`, `staff`, `professional`.
- Intended future implementation:
  - Show balances and payout history derived from referral activity and bookings.
  - Use `professionals.referred_by_user_id` and bookings/commissions logic to compute earnings.

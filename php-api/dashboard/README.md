# KaamSetu Dashboard (Core PHP)

Role-based admin dashboard for KaamSetu. Manage users, categories, services, and view bookings & professionals.

## Requirements

- Same as PHP API: PHP 8+, MySQL, and the `php-api` database (with `users`, `categories`, `services` tables).
- Optional: `bookings` and `professionals` tables (from the Next.js app) for viewing customer bookings and registered professionals.

## Setup

1. Ensure the main API is set up: run `database/schema.sql` and have `.env` in `php-api/` with DB credentials.
2. Create at least one dashboard user in the `users` table (e.g. super_admin). Example:

   ```sql
   INSERT INTO users (name, email, password, role) VALUES
   ('Super Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');
   ```
   (Password for the hash above is typically `password` — change it after first login.)

3. Run the PHP server from the **php-api** directory so the dashboard is under `/dashboard`:

   ```bash
   cd php-api
   php -S localhost:8080 -t .
   ```

4. Open: **http://localhost:8080/dashboard/**

   Log in with the email and password of a user from the `users` table.

## Roles & Access

| Role         | Dashboard home | Users | Categories | Services | Bookings | Professionals | Profile |
|-------------|----------------|-------|------------|----------|----------|---------------|--------|
| super_admin | ✓              | Add team leaders | Full | Full | View/Edit | View/Edit | - |
| team_leader | ✓              | Add staff        | Full | Full | View/Edit | View/Edit | - |
| staff       | ✓              | -                | -    | -    | View/Edit | View/Edit | Own |
| professional| ✓              | -                | -    | -    | -         | -         | Own |
| end_user    | ✓              | -                | -    | -    | -         | -         | Own |

- **Super** can add **team leaders** only (Users page shows team leaders). Full access to categories, services, bookings, professionals.
- **Team leader** can add **staff** only (Users page shows staff). Full access to categories, services, bookings, professionals.
- **Staff** can only view and edit **bookings**, **professionals**, and **own profile**. No Users, Categories, or Services.
- **Professionals**: List from Next.js “Become a Professional” form. **Only super and team leader** can **Approve** or **Reject**. On **Approve**, a **user account** is created with role **professional** (service provider), not end_user, with default password. Staff can view and edit but not approve/reject.
- **Profile**: Staff, professionals, and end users can edit their own profile.

## Professionals: approve/reject and migration

1. Run the migration so the `professionals` table has `status` and `user_id`:
   ```bash
   mysql -u user -p database < php-api/database/migrations/001_professionals_status_user_id.sql
   ```
2. In `.env` (optional): `DEFAULT_PROFESSIONAL_PASSWORD=Welcome@123`
3. New registrations from the Next.js “Become a Professional” form are stored with `status = pending`. In the dashboard, admin/super_admin see **Approve** and **Reject**. **Approve** creates a row in `users` with role **professional** (service provider) and the default password, and sets the professional to `approved` and links `user_id`. (End users are customers who book services; they are not created from professional approval.)

## Pages

- **Dashboard** — Stats and welcome.
- **Users** — Super sees and manages team leaders only; team leader sees and manages staff only. Add/Edit (modal); Delete within scope.
- **Categories** — List; Add/Edit (modal); Delete.
- **Services** — List by category; Add/Edit (modal); Delete.
- **Bookings** — List from `bookings` table; search.
- **Professionals** — List from `professionals` table; search; **Approve** / **Reject** (admin/super_admin only); on Approve, user is created with default password.
- **My Profile** — End user: update name, phone, password.

## Security

- Session-based login; no JWT in the dashboard.
- Each page checks role; forbidden access redirects to dashboard with a message.
- Passwords stored with `password_hash()` (bcrypt).

## URL structure

- Login: `/dashboard/login.php`
- Logout: `/dashboard/logout.php`
- Dashboard: `/dashboard/index.php` or `/dashboard/`
- Other pages: `/dashboard/index.php?page=users`, `?page=categories`, etc.

Use the same base path when the app is in a subdirectory (e.g. `/php-api/dashboard/`).

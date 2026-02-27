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

---

## User Journeys & Referral Flow (Non‑technical)

This section explains, in plain language, how different users move through the system and how referrals work.

### Roles at a glance

- **Super Admin**
  - Sets up the system.
  - Creates **Team Leaders**.
  - Can see and manage everything (users, categories, services, bookings, professionals, referrals, wallet placeholder).

- **Team Leader**
  - Manages a team of **Staff**.
  - Can manage categories, services, bookings, professionals.
  - Can see referral stats and the wallet placeholder.

- **Staff**
  - Works on bookings and professionals.
  - Has their own **staff referral code** to invite new professionals.
  - Can log in to dashboard, see bookings/professionals, and their **Profile**, **Wallet (coming soon)** and referral tools.

- **Professional**
  - Service provider (electrician, plumber, etc.).
  - Created when:
    - They sign up from the public site, and
    - An admin approves them in the dashboard.
  - Has their own **professional referral code** to invite more professionals.
  - Can log in to dashboard, see **Profile**, **Wallet (coming soon)** and referral tools.

- **End User (Customer)**
  - Books services on the public site.
  - Can log into dashboard only to manage their own profile (no admin features).

### Journey: Staff invites a new professional

1. **Staff gets their referral code**
   - Staff logs into dashboard → **Users** page (for admins) or **Profile** (for staff).
   - On the **Referrals** page (admin) or their **Profile** page, they can see their **staff referral code** (e.g. `STFABC123`).

2. **Staff shares the code**
   - They send this code to someone who wants to become a professional (WhatsApp, SMS, etc.).

3. **Professional signs up**
   - The new person goes to the public “Become a Professional” form on the website.
   - They fill basic details (name, phone, city, etc.) and also enter the **referral code**.

4. **System links the new professional to the staff**
   - Behind the scenes, when the form is submitted:
     - The system checks which staff member owns that code.
     - It stores the link so we know “this professional was brought in by that staff”.

5. **Admin approves the professional**
   - In the **Professionals** page of the dashboard, a **Super Admin** or **Team Leader** sees the new professional as `pending`.
   - When they click **Approve**:
     - A new **Professional user account** is created so they can log in.
     - A unique **professional referral code** is assigned to them for future use.
   - Now the professional can log in to the dashboard and start their own referrals.

### Journey: Professional refers another professional

1. **Professional logs into dashboard**
   - They see the **My Profile** page.
   - The profile shows:
     - Their basic account info.
     - Their **service provider details** (city, services, etc.).
     - A **“Refer New Professionals”** section (once their referral code is available).

2. **Professional sees their referral tools**
   - In the **Refer New Professionals** card, they see:
     - Their **referral code** (e.g. `PROXYZ123`).
     - How many professionals they have referred so far.
   - They have two ways to refer:
     1. **Share their referral code** for use on the public signup form.
     2. **Fill the “Add New Professional (via your referral)” form** right inside the dashboard.

3. **When they use the dashboard form**
   - On the Profile page they fill:
     - Name, phone, optional email, city, state, pincode, language, services.
   - When they submit:
     - The system creates a new **pending** professional record.
     - It automatically links that record back to the **current professional’s account**.
   - Later, when an admin approves that professional, they get their own login and referral code.

4. **When someone uses their referral code on the public form**
   - The process is similar to the staff case:
     - The public form includes `referral_code`.
     - The backend recognises this as a professional’s code and attributes the new professional to that referrer.
   - The professional will see their **referred count increase** over time.

### Journey: Admin monitors referrals

1. **Referrals page (Super Admin / Team Leader)**
   - In the sidebar there is a **Referrals** menu item.
   - This page has two sections:
     - **Staff Referral Codes**
       - Shows each staff member, their code, and how many professionals they have referred.
     - **Professional Referral Codes**
       - Shows each professional with a referral code, their user ID, and how many professionals they have brought in.

2. **Use cases**
   - Incentivise staff or professionals who bring in more service providers.
   - Check who is actively referring new professionals.

### Wallet (Coming Soon) – planned journey

Current state:

- The **Wallet** link appears in the sidebar for:
  - Super Admin, Team Leader, Staff, Professional.
- The Wallet page currently displays a **full-page “Coming Soon”** message explaining that:
  - Earnings, referral bonuses, and payout history will be shown here in future.

Planned behaviour (future implementation, not yet coded):

- **Staff / Professional:**
  - See their current referral earnings balance.
  - See a breakdown of referral bonuses per professional they brought in.
  - See payout history (when and how they were paid).

- **Admin / Team Leader:**
  - See high-level stats: total referral payouts, top referrers, etc.

This separation—**technical details in `php-api/README.md`** and **step‑by‑step journeys here**—should help both developers and non‑technical stakeholders understand how roles, referrals, and the future wallet fit together.

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

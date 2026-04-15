# Village Connect SRP (PHP + MySQL)

Responsive rural service platform with secure authentication, profile media uploads, complaints workflow, jobs/schemes publishing, and role-based admin management.

## Features

- User registration and login with hashed passwords
- Secure session handling and CSRF protection
- Profile management with avatar upload
- Upload and view image, video, and audio files
- Complaint creation and status tracking
- Jobs and schemes publishing
- Role-based access: `user`, `manager`, `admin`
- Admin panel for user management and management teams
- Admin audit tracking with user profile watermark:
  - Edited by <admin name> and timestamp
  - Field-level edit logs in `admin_edits`
- Mobile-first responsive UI using your requested style direction

## Project Structure

- `index.php` Home page
- `register.php`, `login.php`, `logout.php` Auth
- `dashboard.php` User dashboard
- `profile.php` Profile and media view
- `upload.php` Media upload page
- `complaint_new.php`, `complaints.php` Complaint module
- `jobs.php`, `schemes.php` Public/manager content modules
- `admin/` Admin and team-management pages
- `config/` App and DB configuration
- `includes/` Shared PHP utilities, auth, CSRF, layout
- `database/schema.sql` MySQL schema + default admin seed
- `assets/css/style.css` Shared responsive styles

## Setup

1. Install dependencies:
	- PHP 8.1+
	- MySQL 8+
	- Apache/Nginx with PHP enabled
2. Create database and tables:
	- Import `database/schema.sql`
3. Configure DB credentials in `config/app.php`:
	- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
4. Ensure write permissions for uploads:
	- `uploads/media`
5. Serve project root as web root (or configure virtual host).

## Default Admin Account

- Email: `admin@village.local`
- Password: `Admin@12345`

Change this password immediately after first login.

## Security Notes

- Prepared statements via PDO
- `password_hash()` and `password_verify()`
- CSRF token validation on all form POSTs
- Role-based access control checks
- Upload validation:
  - MIME type allow-list
  - Size limit (50MB)
  - Randomized filenames
  - Upload directory execution blocked via `.htaccess`

## Optional Hardening (Recommended)

- Move DB credentials to environment variables
- Force HTTPS in production
- Add rate limiting on login endpoint
- Add antivirus scanning for uploaded files
- Add 2FA for admin users
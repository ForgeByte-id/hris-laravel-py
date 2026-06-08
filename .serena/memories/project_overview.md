# HRIS Project Overview

## Purpose
Human Resource Information System (HRIS) — a web application for managing employees, attendance, leave (cuti), work schedules (jadwal kerja), divisions, positions, and reporting. Attendance is fully admin-controlled using face recognition via an integrated camera system.

## Tech Stack
- **Backend**: Laravel 12 (PHP 8.2+), Spatie Permission (roles/permissions)
- **Frontend**: Blade templates, Bootstrap 5.2, Bootstrap Icons, Select2, Leaflet.js, SweetAlert2, DataTables
- **Face Recognition**: Python 3.10 microservice (`face_recognition` library) running on port 5000
- **Database**: MySQL 8.0
- **Auth**: Session-based (custom guard using `username` as identifier)
- **Docker**: nginx (port 8085) → Laravel (php-fpm) → MySQL; Python face service on port 5000
- **Build**: Vite for frontend assets

## Architecture
- Laravel serves the main app via nginx (Docker: port 8085, local: port 8000)
- Python face recognition service runs separately (Docker: `face-service:5000`, local: `localhost:5000`)
- PHP calls Python via HTTP (`FACE_SERVICE_URL` env var)
- Temp images shared via filesystem (`FACE_TEMP_DIR` / `ALLOWED_IMAGE_TMP_DIR`)

## Key Design Decisions
- All attendance actions are **admin-only** (enforced at route + controller level via `is_admin` middleware)
- Employee dashboard is read-only (server-side rendered, no admin-only API calls)
- Menu items are dynamic from DB (`menu_items` table + `role_menu_permissions` pivot)
- All times displayed/stored in **GMT+8** (Asia/Singapore)
- Face recognition temp files go to `storage/app/temp` (shared volume in Docker)

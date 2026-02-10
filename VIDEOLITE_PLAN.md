# VideoLite - Video Streaming Platform Plan

**Project:** VideoLite - A lightweight video streaming platform inspired by Udemy  
**Date Created:** February 9, 2026  
**Status:** In Development (Phase 3/4)

---

## Project Overview

VideoLite is a single-tenant video streaming platform with the following characteristics:

- **Pricing Model:** Pay-per-course (one-time purchase, not subscription)
- **Payment Gateway:** BillPlz
- **Content Protection:** Videos cannot be downloaded or shared
- **Infrastructure:** Shared hosting (small video files, scalable later)
- **Technology Stack:** Laravel + Filament Admin + MySQL

---

## Architecture

```
Frontend (React/Vue)
        ↓
API Layer (Laravel)
        ↓
Database (MySQL)
        ↓
File Storage (Local/S3)
```

### Tech Stack

| Component | Technology |
|-----------|-----------|
| Backend Framework | Laravel 10+ |
| Admin Panel | Filament 3 |
| Database | MySQL 8+ |
| Authentication | JWT (tymon/jwt-auth) |
| Payment Gateway | BillPlz |
| Video Storage | Local (later: S3) |
| Frontend | React/Vue (TBD) |

---

## Features Planned

### Core Features (MVP)

#### 1. User Management
- [x] User registration & login
- [ ] Email verification
- [ ] User profile management
- [ ] Password reset
- [ ] Avatar upload

#### 2. Course Management
- [x] Create/Edit/Delete courses (admin)
- [x] Upload videos (admin)
- [x] Course metadata (title, description, price, thumbnail)
- [x] Course status (draft/published)
- [x] Course visibility

#### 3. Video Streaming
- [x] Secure authenticated video playback
- [x] Video progress tracking
- [x] Resume video from last position
- [x] No download functionality
- [x] Range request support (seeking)
- [ ] Video quality/bitrate options
- [ ] Watermarking (future)

#### 4. Purchase System
- [x] Course catalog
- [x] Shopping cart (implied)
- [x] BillPlz payment integration
- [x] Purchase confirmation
- [ ] Invoice/Receipt
- [ ] Refund handling

#### 5. User Library
- [x] My Courses dashboard
- [x] Purchased courses list
- [x] Continue watching functionality
- [x] Course progress percentage
- [ ] Course completion certificates
- [ ] Favorites/Bookmarks

#### 6. Admin Panel (Filament)
- [x] User management (view, create, edit, delete, suspend)
- [x] Course management (CRUD)
- [x] Video management (upload, organize, delete)
- [x] Purchase tracking
- [ ] Revenue dashboard
- [ ] Analytics (users, courses, revenue)
- [ ] Email management

#### 7. Security
- [x] JWT authentication
- [x] Access control (purchased courses only)
- [x] BillPlz webhook signature verification
- [ ] Rate limiting
- [ ] IP-based access control
- [ ] DRM watermarking

### Future Enhancements
- Course categories/tags
- Full-text search
- Email notifications
- Student progress reports
- Certificate of completion
- Coupon/discount codes
- Multi-creator support
- Live streaming
- Course bundle pricing

---

## Development Checklist

### Phase 1: Authentication ✅
- [x] Setup Laravel project with MySQL
- [x] Create database migrations and models
- [x] Setup Filament admin resources
- [x] Implement user authentication (register/login)
- [x] Setup JWT token management
- [x] Create API routes for courses

### Phase 2: Payments ✅
- [x] Integrate BillPlz payment gateway
- [x] Create BillPlz webhook handler
- [x] Payment initiation endpoint
- [x] Purchase status tracking
- [x] Purchase history API

### Phase 3: Video Streaming ✅
- [x] Build secure video streaming endpoint
- [x] Implement video progress tracking
- [x] Range request support (seeking)
- [x] Course progress calculation
- [x] Video metadata API

### Phase 4: Frontend 🔄 (Next)
- [ ] Create user library/dashboard API
- [ ] Build frontend user portal
- [ ] Implement video player UI
- [ ] Create purchase checkout flow
- [ ] Build user dashboard

---

## Database Schema

### Tables

#### users
```
id, name, email, email_verified_at, password, avatar, is_active, remember_token, created_at, updated_at
```

#### courses
```
id, title, description, slug, price, thumbnail, status (draft/published), created_by, created_at, updated_at
```

#### videos
```
id, course_id, title, description, file_path, duration, order, created_at, updated_at
```

#### purchases
```
id, user_id, course_id, amount, billplz_bill_id, status (pending/paid/failed), created_at, updated_at
UNIQUE: (user_id, course_id)
```

#### video_progress
```
id, user_id, video_id, watched_seconds, created_at, updated_at
UNIQUE: (user_id, video_id)
```

---

## API Endpoints

### Authentication

```
POST   /api/auth/register              - Register new user
POST   /api/auth/login                 - Login user
POST   /api/auth/logout                - Logout (protected)
GET    /api/auth/me                    - Get current user (protected)
POST   /api/auth/refresh               - Refresh JWT token (protected)
```

### Courses

```
GET    /api/courses                    - List all published courses
GET    /api/courses/{id}               - Get single course details
GET    /api/my-courses                 - Get user's purchased courses (protected)
GET    /api/courses/{id}/access        - Check if user has access (protected)
POST   /api/courses                    - Create course (protected, admin)
PUT    /api/courses/{id}               - Update course (protected, admin)
DELETE /api/courses/{id}               - Delete course (protected, admin)
```

### Payments

```
POST   /api/payment/create             - Initiate course purchase (protected)
GET    /api/payment/{billId}/status    - Check payment status (protected)
GET    /api/purchases                  - Get purchase history (protected)
GET    /api/purchases/{id}             - Get purchase details (protected)
POST   /api/webhook/billplz            - BillPlz webhook callback (public)
```

### Videos

```
GET    /api/courses/{courseId}/videos  - Get course videos (protected)
GET    /api/videos/{videoId}/info      - Get video info (protected)
GET    /api/videos/{videoId}/stream    - Stream video file (protected)
GET    /api/videos/{videoId}/stream-range - Stream with range support (protected)
POST   /api/videos/{videoId}/progress  - Update watch progress (protected)
GET    /api/courses/{courseId}/progress - Get course progress (protected)
```

---

## Installation & Setup

### Prerequisites
- PHP 8.1+
- MySQL 8.0+
- Composer
- Node.js (optional, for frontend)

### Installation Steps

```bash
# Clone repository
git clone <repo-url>
cd videolite

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret

# Configure MySQL in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=videolite
DB_USERNAME=root
DB_PASSWORD=

# Run migrations
php artisan migrate

# Install Filament (if not already done)
composer require filament/filament:"^3.0" -W
php artisan filament:install --panels=admin

# Create storage symlink
php artisan storage:link

# Start server
php artisan serve
```

### Accessing the Application

- **API:** http://localhost:8000
- **Admin Panel:** http://localhost:8000/admin
- **Frontend:** http://localhost:3000 (separate project)

---

## Environment Variables

```env
APP_NAME=VideoLite
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_FRONTEND_URL=http://localhost:3000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=videolite
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=<generated-by-php-artisan-jwt:secret>

BILLPLZ_KEY=your_billplz_api_key
BILLPLZ_X_SIGNATURE_KEY=your_billplz_x_signature_key
BILLPLZ_COLLECTION_ID=your_billplz_collection_id
BILLPLZ_MODE=sandbox  # Use 'sandbox' for testing
```

---

## File Paths

### Important Directories

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php
│   │       ├── CourseController.php
│   │       ├── PaymentController.php
│   │       ├── VideoController.php
│   │       └── WebhookController.php
│   └── Middleware/
├── Models/
│   ├── User.php
│   ├── Course.php
│   ├── Video.php
│   ├── Purchase.php
│   └── VideoProgress.php
├── Filament/
│   └── Resources/
│       ├── CourseResource.php
│       ├── VideoResource.php
│       ├── UserResource.php
│       └── PurchaseResource.php
└── Policies/
    └── CoursePolicy.php

routes/
├── api.php       - All API endpoints
├── web.php       - Web routes (Filament)
└── console.php

database/
├── migrations/
└── seeders/

storage/
└── app/
    └── courses/
        ├── videos/      - Video files (private)
        └── thumbnails/  - Course thumbnails (public)
```

---

## Security Considerations

1. **Video Access:** Protected by JWT authentication + purchase verification
2. **Payment Webhook:** Verified using BillPlz signature
3. **CSRF Protection:** Disabled for webhook endpoint
4. **File Uploads:** Validated file types and sizes
5. **Database:** Foreign key constraints for data integrity

### To Implement
- Email verification for new accounts
- Rate limiting on authentication endpoints
- IP whitelisting for BillPlz webhooks
- Video watermarking
- HTTPS enforcement in production

---

## Deployment Checklist

### Pre-Deployment
- [ ] Set `APP_DEBUG=false` in production
- [ ] Update database credentials
- [ ] Configure real BillPlz credentials
- [ ] Set proper JWT secret
- [ ] Update `APP_URL` and `APP_FRONTEND_URL`
- [ ] Configure email service
- [ ] Setup S3 storage (recommended)
- [ ] Setup CDN for video delivery

### Post-Deployment
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Create admin user
- [ ] Test payment flow with sandbox
- [ ] Test video streaming
- [ ] Setup monitoring/logging
- [ ] Configure SSL certificate

---

## Next Steps

1. **Complete Phase 4 (Frontend):**
   - Create user library/dashboard API endpoints
   - Build React/Vue frontend
   - Implement video player
   - Create payment checkout flow

2. **Polish & Testing:**
   - Unit tests for API
   - Integration tests for payment flow
   - End-to-end testing
   - Security audit

3. **Deployment:**
   - Setup hosting environment
   - Configure production database
   - Setup backup strategy
   - Monitor performance

4. **Future Enhancements:**
   - Analytics dashboard
   - Email notifications
   - Advanced course features

---

## References

- [Laravel Documentation](https://laravel.com/docs)
- [Filament Documentation](https://filamentphp.com)
- [JWT Auth Documentation](https://jwt-auth.readthedocs.io)
- [BillPlz API Documentation](https://billplz.com/api)

---

**Last Updated:** February 9, 2026  
**Next Review:** Upon completion of Phase 4

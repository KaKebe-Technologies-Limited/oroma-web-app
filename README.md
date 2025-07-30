# Oroma TV - PHP Version

## Overview
Complete PHP conversion of the Oroma TV streaming platform, designed for shared hosting deployment. This version maintains all original functionality including live TV/radio streaming, real-time interactions, admin dashboard, and content management.

## Features
- **Live Streaming**: TV and Radio streaming with HLS.js support
- **Real-time Interactions**: Live reactions, comments, and viewer tracking
- **Content Management**: News articles, events, programs management
- **Admin Dashboard**: Complete admin interface with analytics
- **User Management**: Authentication and role-based access
- **Song Requests**: Interactive song request system
- **Newsletter**: Email subscription management
- **Analytics**: Comprehensive user engagement tracking

## Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- 128MB RAM minimum
- 5MB upload file size limit

## Installation

### 1. Database Setup
```sql
-- Create database and import schema
CREATE DATABASE oromatv;
mysql -u username -p oromatv < database/schema.sql
```

### 2. Configuration
```bash
# Copy and edit environment file
cp .env.example .env

# Update database credentials in .env
DB_HOST=localhost
DB_NAME=oromatv
DB_USER=your_username
DB_PASS=your_password
```

### 3. Permissions
```bash
# Set proper permissions
chmod 755 index.php
chmod 755 api/
chmod 755 admin/
chmod 777 uploads/
```

### 4. Apache Configuration
Ensure `.htaccess` is properly configured with rewrite rules for:
- API routing (`/api/*` → `api/index.php`)
- Admin routing (`/admin/*` → `admin/index.php`)
- Clean URLs for pages

## File Structure
```
php-version/
├── api/                    # API endpoints
│   ├── handlers/           # API request handlers
│   │   ├── auth.php       # Authentication
│   │   ├── news.php       # News management
│   │   ├── live_reactions.php # Live reactions
│   │   ├── live_comments.php  # Live comments
│   │   ├── active_users.php   # User tracking
│   │   └── song_requests.php  # Song requests
│   └── index.php          # API router
├── admin/                 # Admin dashboard
│   └── index.php          # Admin interface
├── assets/                # Static assets
│   ├── css/style.css      # Custom styles
│   ├── js/main.js         # JavaScript functionality
│   └── images/            # Images
├── config/                # Configuration
│   └── database.php       # Database connection
├── includes/              # Shared functions
│   └── functions.php      # Utility functions
├── uploads/               # File uploads
├── database/              # Database files
│   └── schema.sql         # Database schema
├── index.php              # Homepage
├── .htaccess             # Apache configuration
├── .env                  # Environment variables
└── README.md             # This file
```

## API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout  
- `GET /api/auth/check` - Check authentication

### News
- `GET /api/news` - Get news articles
- `GET /api/news/{id}` - Get specific article
- `POST /api/news` - Create article (admin)
- `PUT /api/news/{id}` - Update article (admin)
- `DELETE /api/news/{id}` - Delete article (admin)

### Live Features
- `GET /api/live-reactions/{stream}` - Get reactions
- `POST /api/live-reactions` - Send reaction
- `GET /api/live-comments/{stream}` - Get comments
- `POST /api/live-comments` - Send comment
- `GET /api/active-users/count/{stream}` - Get viewer count

### Song Requests
- `GET /api/song-requests` - Get requests
- `POST /api/song-requests` - Submit request
- `PUT /api/song-requests/{id}` - Update request (admin)

## Admin Dashboard
Access: `/admin`

Default credentials:
- Username: `admin`
- Password: `admin123`

Features:
- Real-time viewer statistics
- Content management (news, events, programs)
- Song request moderation
- User management
- Analytics dashboard
- System monitoring

## Streaming Configuration
Update stream URLs in `.env` file:
```
TV_STREAM_URL=https://your-tv-stream.m3u8
RADIO_STREAM_URL=https://your-radio-stream/stream
```

## Security Features
- Password hashing with PHP's password_hash()
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- CSRF protection for forms
- Rate limiting for API requests
- File upload validation
- Session security

## Performance Optimizations
- Database query optimization
- Browser caching headers
- Gzip compression
- Image optimization
- Lazy loading for content
- Efficient real-time updates

## Shared Hosting Deployment

### 1. Upload Files
- Upload all files to your hosting public_html directory
- Ensure proper file permissions (755 for directories, 644 for files)

### 2. Database Import
- Create MySQL database via cPanel
- Import `database/schema.sql` through phpMyAdmin

### 3. Configuration
- Update `.env` with your hosting database credentials
- Verify `.htaccess` is working (test clean URLs)

### 4. Testing
- Visit your domain
- Test live streaming functionality
- Verify admin access at `/admin`
- Check API endpoints are responding

## Troubleshooting

### Common Issues
1. **"Database connection failed"**
   - Check database credentials in `.env`
   - Verify database exists and is accessible

2. **"404 Not Found" for clean URLs**
   - Ensure `.htaccess` is uploaded
   - Verify mod_rewrite is enabled on server

3. **File upload errors**
   - Check `uploads/` directory permissions (777)
   - Verify PHP upload limits

4. **Streaming not working**
   - Verify stream URLs in `.env`
   - Check browser console for CORS errors

### Debug Mode
Enable debug mode in `.env`:
```
DEBUG=true
```

## Support
For technical support or customization:
- Check server error logs
- Enable debug mode for detailed errors
- Verify all requirements are met

## License
Proprietary software for Oroma TV Northern Uganda.

---

**Note**: This PHP version maintains full feature parity with the original Node.js/React version while being optimized for shared hosting environments.
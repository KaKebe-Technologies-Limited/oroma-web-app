# Oroma TV - MySQL Deployment Guide

## Overview

This is the complete deployment package for Oroma TV streaming platform configured for MySQL database. The platform features 100% PHP compatibility with AJAX polling for real-time features.

## Database Configuration

### MySQL Database Details
- **Host**: localhost
- **Database**: u850523537_oroma_web
- **Username**: u850523537_oroma_user
- **Password**: Oroma 101619

### Database Setup

1. **Create Database** (if not exists):
```sql
CREATE DATABASE u850523537_oroma_web;
```

2. **Import Schema**:
```bash
mysql -u u850523537_oroma_user -p u850523537_oroma_web < database_final.sql
```

## Files Structure

```
oroma-tv/
├── api/                    # API endpoints for AJAX polling
│   ├── chat.php           # Chat messages API
│   ├── reactions.php      # Reactions API
│   └── stream-status.php  # Stream status API
├── assets/
│   ├── css/style.css      # Main styles
│   ├── js/
│   │   ├── main.js        # Core functionality
│   │   ├── chat.js        # Chat system
│   │   ├── reactions.js   # Reactions system
│   │   └── video-controls.js # Video controls
│   └── images/logo.png    # Official Oroma TV logo
├── admin/                 # Admin panel (future)
├── uploads/               # File uploads
├── config.php            # Database configuration
├── database_final.sql    # MySQL schema
├── index.php             # Main page
├── contact.php           # Contact page
├── newsroom.php          # News page
├── post.php              # Blog post page
├── robots.txt            # SEO robots file
└── sitemap.xml           # SEO sitemap
```

## Key Features

### ✅ Implemented Features
- 100% PHP backend with MySQL database
- AJAX polling system for real-time features
- Live chat system with spam protection
- Reactions system (like, love, laugh, wow, angry)
- Stream status monitoring
- Professional responsive design
- Official Oroma TV branding
- QFM Radio 94.3 FM integration
- Complete SEO optimization
- Contact form functionality

### 🔄 Real-time System (AJAX Polling)
- **Chat Messages**: Polls every 2 seconds
- **Reactions**: Updates every 3 seconds  
- **Stream Status**: Checks every 10 seconds
- **Rate Limiting**: Prevents spam and abuse
- **Auto-cleanup**: Maintains database performance

### 📱 Responsive Design
- Mobile-first approach
- Bootstrap integration
- Touch-friendly controls
- Optimized for all screen sizes

## Deployment Steps

### 1. File Upload
Upload all files to your web server directory.

### 2. Database Setup
Run the `database_final.sql` file in your MySQL database.

### 3. Configuration
Verify `config.php` has correct database credentials.

### 4. Permissions
Set appropriate file permissions:
```bash
chmod 755 uploads/
chmod 644 *.php
chmod 644 assets/css/*.css
chmod 644 assets/js/*.js
```

### 5. Testing
- Test live chat functionality
- Test reaction buttons
- Verify stream status updates
- Check contact form submission

## Stream Configuration

### TV Stream Setup
Update the stream URL in your video player source:
```html
<source src="YOUR_HLS_STREAM_URL" type="application/x-mpegURL">
```

### Radio Stream Setup
Update the radio player source:
```html
<source src="YOUR_RADIO_STREAM_URL" type="audio/mpeg">
```

## Browser Compatibility

- ✅ Chrome 60+
- ✅ Firefox 55+
- ✅ Safari 11+
- ✅ Edge 79+
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

## Performance Optimization

### Database Optimization
- Automatic cleanup of old messages (200 chat messages max)
- Reaction data cleanup (24 hours retention)
- Indexed tables for fast queries
- Rate limiting to prevent abuse

### Frontend Optimization
- CDN-hosted libraries (Video.js, Bootstrap, Font Awesome)
- Compressed assets
- Efficient polling intervals
- Smart page visibility handling

## SEO Features

- Comprehensive meta tags
- Structured data markup
- XML sitemap
- Robots.txt configuration
- Social media sharing integration
- Open Graph tags

## Support & Maintenance

### Regular Maintenance
- Monitor database size
- Check error logs
- Update stream URLs as needed
- Review contact form submissions

### Troubleshooting
- Check `config.php` for database connection issues
- Verify file permissions
- Test API endpoints directly
- Monitor browser console for JavaScript errors

## Security Features

- Input validation and sanitization
- SQL injection prevention (prepared statements)
- Rate limiting on all user actions
- IP address logging
- CSRF protection on forms

## Contact Information

- **Website**: www.oromatv.com
- **Location**: Lira City, Northern Uganda
- **Radio**: QFM Radio 94.3 FM

---

**Note**: This platform is fully compatible with standard PHP hosting environments and requires no special server configurations beyond MySQL database access.
# Oroma TV Website Deployment Instructions

## Quick Setup Guide

### 1. Server Requirements
- PHP 8.0 or higher
- PostgreSQL database
- Node.js (for WebSocket server)
- Web server (Apache/Nginx)

### 2. Extract and Upload Files
1. Extract the `oromatv-website.tar.gz` file to your web server root directory
2. Ensure all files have proper permissions (755 for directories, 644 for files)

### 3. Database Setup
1. Create a PostgreSQL database
2. Import the database schema: `psql -U username -d database_name -f database.sql`
3. Update database credentials in `config.php`

### 4. Configuration
1. Edit `config.php` and update:
   - Database connection details
   - Site URLs and paths
   - Radio stream URLs

### 5. WebSocket Server
1. Install Node.js dependencies: `npm install`
2. Start the WebSocket server: `node websocket-server.js`
3. For production, use PM2 or similar process manager

### 6. Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([^?]*) index.php [NC,L,QSA]

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/css text/javascript application/javascript application/json
</IfModule>

# Set cache headers
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
</IfModule>
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}

# WebSocket proxy
location /ws {
    proxy_pass http://localhost:3001;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
}
```

### 7. SSL Certificate
1. Install SSL certificate for HTTPS
2. Update all URLs in config.php to use HTTPS
3. Redirect HTTP to HTTPS

### 8. Domain Configuration
1. Point your domain www.oromatv.com to your server IP
2. Update DNS records (A record for www and root domain)

### 9. Final Checks
- [ ] Website loads at https://www.oromatv.com
- [ ] Logo displays properly
- [ ] Video player works
- [ ] Radio stream plays
- [ ] Chat functionality works
- [ ] Contact form submits
- [ ] All pages have proper SEO meta tags

### 10. Monitoring
- Set up monitoring for the WebSocket server
- Monitor database performance
- Check stream uptime regularly

## Support
For technical support, contact: info@oromatv.com

## File Structure
```
/
├── index.php           # Main streaming page
├── contact.php         # Contact page
├── newsroom.php        # News and blog page
├── config.php          # Configuration file
├── database.sql        # Database schema
├── websocket-server.js # Real-time features
├── sitemap.xml         # SEO sitemap
├── robots.txt          # Search engine directives
├── assets/            # CSS, JS, images
├── api/              # API endpoints
├── admin/            # Admin panel
└── uploads/          # User uploads

```

## Features Included
✓ Live TV streaming with HLS support
✓ QFM Radio 94.3 FM streaming
✓ Real-time chat and reactions
✓ Contact form and information
✓ News/blog system
✓ SEO optimized pages
✓ Mobile responsive design
✓ Social media integration
✓ Admin panel for content management
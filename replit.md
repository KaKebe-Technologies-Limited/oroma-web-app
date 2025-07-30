# Oroma TV Live Streaming Platform

## Overview

This is a professional PHP-based live streaming platform featuring HLS video streaming, real-time chat, reactions system, and a complete newsroom with blog management. The platform uses MySQL database (u850523537_oroma_web) for data storage and PHP AJAX polling for real-time features (converted from WebSocket). It includes both TV and QFM Radio 94.3 FM streaming with a clean, responsive design and is 100% PHP compatible for easy deployment.

## User Preferences

- Preferred communication style: Simple, everyday language
- Color scheme: Red (#8B0000) and yellow (#FFD700) branding
- Radio station: QFM Radio 94.3 FM (not OFM)
- Navigation: Simplified to Home and Contact only (no About or News pages)
- Contact info: Lira City, Northern Uganda location
- Logo: Official Oroma TV PNG logo (no text branding)
- Share URL: www.oromatv.com for all sharing functions
- SEO: Comprehensive optimization for search engines

## System Architecture

The application follows a client-server architecture with real-time communication capabilities:

### Backend Architecture
- **PHP Server**: Pure PHP handles all backend operations including API endpoints
- **MySQL Database**: Stores chat messages, reactions, stream status, and user data (u850523537_oroma_web)
- **AJAX Polling System**: Replaces WebSocket with efficient HTTP polling for real-time features
- **RESTful API**: Clean API endpoints for chat, reactions, and stream status

### Frontend Architecture
- **Vanilla JavaScript**: No framework dependencies for maximum compatibility
- **Modular JS Structure**: Separate modules for chat, reactions, video controls, and main functionality
- **Video.js Integration**: Professional video player with custom controls
- **AJAX Polling**: Client-side polling for real-time data updates (2-3 second intervals)

## Recent Changes (July 30, 2025)

- ✓ Implemented official Oroma TV PNG logo and removed text branding
- ✓ Updated share functionality to use www.oromatv.com URL
- ✓ Enhanced SEO with comprehensive meta tags, structured data, and sitemap
- ✓ Created deployment package (oromatv-website.tar.gz) with instructions
- ✓ Added robots.txt and sitemap.xml for search engine optimization
- ✓ Updated all navigation to show only Home and Contact pages
- ✓ Fixed logo sizing and brand colors throughout the platform
- ✓ **MAJOR ARCHITECTURAL CHANGE**: Converted from WebSocket to 100% PHP with AJAX polling
- ✓ **DATABASE MIGRATION**: Converted from MySQL to PostgreSQL for Replit compatibility
- ✓ **API REWRITE**: Updated all real-time APIs to use PostgreSQL-compatible SQL syntax
- ✓ **POLLING SYSTEM**: Implemented efficient PHP polling for chat, reactions, and stream status

## Key Components

### 1. WebSocket Server (`websocket-server.js`)
- **Purpose**: Central communication hub for real-time features
- **Responsibilities**: 
  - Manage client connections
  - Broadcast messages to all connected clients
  - Handle chat messages, reactions, and stream status updates
  - Persist data to JSON files

### 2. Data Storage System
- **Location**: `/data/` directory with JSON files
- **Files**:
  - `chat.json`: Stores chat messages and metadata
  - `reactions.json`: Tracks reaction counts and active viewers
  - `stream-status.json`: Monitors TV and radio stream health
- **Rationale**: Simple file-based storage chosen for ease of deployment and maintenance

### 3. Client-side Modules
- **`main.js`**: Core application initialization and WebSocket management
- **`chat.js`**: Live chat functionality with spam prevention
- **`reactions.js`**: Real-time reaction system with animations
- **`video-controls.js`**: Custom video controls including brightness and picture-in-picture

### 4. Styling System
- **CSS Variables**: Centralized theming with dark mode design
- **Responsive Design**: Mobile-first approach with Bootstrap integration
- **Custom Components**: Branded styling with red/gold color scheme

## Data Flow

### Real-time Communication Flow
1. **Client Connection**: Browser connects to WebSocket server at `/ws`
2. **Message Broadcasting**: Server receives messages and broadcasts to all connected clients
3. **Data Persistence**: Critical data (chat, reactions) saved to JSON files
4. **State Synchronization**: All clients receive real-time updates

### Chat System Flow
1. User enters name and message
2. Client validates input locally
3. Message sent via WebSocket to server
4. Server broadcasts message to all clients
5. Server saves message to `chat.json`
6. All clients update their chat displays

### Reaction System Flow
1. User clicks reaction button
2. Client sends reaction via WebSocket
3. Server updates reaction counts
4. Server broadcasts updated counts to all clients
5. Clients update their reaction displays with animations

## External Dependencies

### Core Dependencies
- **ws (^8.18.3)**: WebSocket server implementation
  - Chosen for its simplicity and performance
  - Handles client connections and message broadcasting

### Frontend Libraries (via CDN)
- **Video.js**: Professional video player with extensive plugin support
- **Bootstrap**: Responsive design framework
- **Font Awesome**: Icon library for UI elements

### Stream Sources
- **TV Stream**: External HLS/RTMP stream URL
- **Radio Stream**: External audio stream URL
- **Integration**: Streams are embedded but not hosted by the application

## Deployment Strategy

### Simple Deployment Model
- **Single Node.js Process**: All functionality in one server process
- **Static Asset Serving**: CSS, JS, and HTML served from the same server
- **Port Configuration**: Single port for both HTTP and WebSocket traffic
- **Data Persistence**: JSON files stored in local `/data/` directory

### Scalability Considerations
- **Current Limitation**: Single server instance with file-based storage
- **Future Enhancement**: Could migrate to database for multi-instance deployment
- **Session Management**: No user authentication - clients identified by connection

### Monitoring and Health
- **Stream Status Tracking**: Real-time monitoring of TV and radio streams
- **Connection Management**: Automatic client cleanup on disconnect
- **Error Handling**: Graceful degradation when streams are unavailable

## Development Considerations

### Code Organization
- **Modular Frontend**: Separate JS files for different features
- **Clean Separation**: Server logic separate from client-side code
- **Configuration**: Stream URLs and settings easily configurable

### Performance Optimizations
- **Message Throttling**: Built-in spam prevention for chat and reactions
- **Efficient Broadcasting**: Single message sent to all connected clients
- **Local Storage**: User preferences saved in browser

### Security Features
- **Input Validation**: Message length limits and content sanitization
- **Rate Limiting**: Temporary button disabling to prevent spam
- **No Authentication**: Open platform design for anonymous access
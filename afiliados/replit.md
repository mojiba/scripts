# hardMOB Afiliados - XenForo Add-On

## Overview

This is a comprehensive affiliate link management system designed as a XenForo 2.2.17+ add-on. The system provides centralized management of affiliate links with intelligent caching, detailed click statistics, and automated connectors for multiple marketplaces. It enables automatic URL rewriting to insert affiliate codes, scheduled link generation, and comprehensive analytics integration.

## Recent Changes

**July 29, 2025:**
- Fixed critical database table conflict errors during installation
- Added table existence checks in Setup.php before creating tables
- Implemented automatic cleanup of conflicting tables
- Added preInstall() method to remove conflict tables
- Updated to version 1.0.2 with improved installation reliability

**July 28, 2025:**
- Created complete XenForo 2.2.17 addon structure
- Fixed XML route configuration issues for proper installation
- Updated addon.json to require XenForo 2.2.17+ and PHP 8.1+
- Added proper route_type attributes and XF:PrefixBasic route classes
- Created admin permissions and entity definitions
- All core PHP classes implemented (controllers, entities, services, connectors)

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Core Framework
- **Platform**: XenForo 2.2.17 add-on
- **PHP Version**: 8.1.0+
- **Architecture Pattern**: MVC (Model-View-Controller) following XenForo conventions
- **Namespace**: `hardMOB\Afiliados`

### Frontend Architecture
- **Template Engine**: XenForo's native template system
- **Admin Interface**: Integrated into XenForo's Admin Control Panel (ACP)
- **Public Interface**: Custom redirect pages and affiliate tracking
- **JavaScript**: Minimal client-side scripting for redirects and form interactions

### Backend Architecture
- **Controllers**: Separate admin and public controllers for different access levels
- **Entities**: XenForo entities for data modeling (stores, clicks, cache entries)
- **Services**: Business logic separation for affiliate processing, caching, and statistics
- **Jobs**: Cron-based background processing for link generation
- **Routes**: Custom routing for affiliate redirect URLs (`/affiliate/{store}/{slug}`)

## Key Components

### Store Management System
- **Purpose**: Manage affiliate partner stores and their configurations
- **Features**: CRUD operations for stores with domain, affiliate codes, and status management
- **Auto-scaffolding**: Automatic creation of connector stubs when adding new stores

### Link Processing Engine
- **URL Rewriting**: Automatic detection and replacement of URLs with affiliate codes
- **Placeholder System**: Support for `{{slug:/produtos/123}}` syntax for dynamic link generation
- **Redirect Handling**: Public route system for tracking clicks and performing 302 redirects

### Caching System
- **Multi-driver Support**: Configurable cache drivers (file, Redis, etc.)
- **Intelligent Expiration**: Configurable cache expiration (0 = permanent)
- **Management Tools**: Admin interface for cache clearing and maintenance

### Statistics and Analytics
- **Click Tracking**: Detailed logging of store, path, creator, clicker, and timestamps
- **Dashboard**: Visual representation of affiliate performance
- **Filtering**: Period-based, store-based, and user-based filtering options
- **Integration Ready**: Prepared for Google Analytics (GA4/UA) integration

### Scheduled Processing
- **Cron Jobs**: Background generation of affiliate links via `GenerateLinks.php`
- **Configurable Intervals**: Admin-configurable scheduling for link generation
- **Bulk Processing**: Pre-generation of all store/slug combinations

## Data Flow

1. **Link Creation**: Users create content with placeholder affiliate links
2. **Processing**: System detects and processes affiliate placeholders
3. **Caching**: Generated affiliate URLs are cached for performance
4. **User Interaction**: Users click on affiliate links in content
5. **Tracking**: System logs click data (user, store, product, timestamp)
6. **Redirect**: 302 redirect to actual affiliate URL with tracking codes
7. **Analytics**: Click data is aggregated for reporting and statistics

## External Dependencies

### Required Dependencies
- **XenForo 2.2.0+**: Core framework dependency
- **PHP 8.1.0+**: Minimum PHP version requirement

### Optional Integrations
- **Redis**: For advanced caching (alternative to file-based caching)
- **Google Analytics**: For enhanced tracking and analytics (GA4/UA)
- **External APIs**: Marketplace-specific APIs for automated product data retrieval

### Database Requirements
- Uses XenForo's existing database infrastructure
- Custom tables for stores, clicks, and cache entries
- Leverages XenForo's phrase system for internationalization

## Deployment Strategy

### Installation Process
- Standard XenForo add-on installation via Admin CP
- Automatic database schema creation through XenForo's setup system
- Phrase installation for UI localization
- Route registration for public affiliate URLs

### Configuration Requirements
- Admin configuration for cache drivers and expiration
- Store setup and affiliate code configuration
- Cron job scheduling for automated link generation
- Optional analytics integration setup

### Maintenance Tools
- **Cache Management**: Admin tools for clearing and managing cache
- **System Reset**: Complete system reset functionality (clears tables, phrases, routes, jobs, templates)
- **Connector Scaffolding**: Automated generation of store-specific connector modules
- **Statistics Dashboard**: Performance monitoring and click analytics

### Scalability Considerations
- Configurable caching system for high-traffic scenarios
- Background job processing to avoid blocking user requests
- Efficient database indexing for click tracking and statistics
- Support for multiple cache backends (file, Redis) based on infrastructure needs
# ITDB to SlimPHP Refactoring Plan

## Executive Summary

This document outlines a comprehensive plan to refactor the ITDB (IT Items Database) application from its current monolithic architecture to a modern SlimPHP-based framework structure. The current codebase consists of ~93 PHP files with a procedural approach using direct file includes and global state management.

## Current Architecture Analysis

### Current Structure
```
src/
├── index.php (main entry point - 430 lines)
├── init.php (initialization, auth, DB setup - 287 lines)
├── functions.php (utility functions)
├── model.php (database queries - 654 lines)
├── conf.php (configuration)
├── php/ (57 individual page modules)
├── tcpdf/ (third-party PDF library)
├── js/ (frontend assets)
└── data/ (SQLite database and uploads)
```

### Current Issues
- **Monolithic front controller**: Single `index.php` handles all routing via `$_GET['action']`
- **Global state**: Heavy use of global variables (`$dbh`, `$settings`, etc.)
- **Mixed concerns**: Business logic mixed with presentation in page modules
- **No dependency injection**: Direct database access throughout
- **Security concerns**: SQL injection vulnerabilities, unescaped user input
- **No PSR compliance**: No autoloading, inconsistent coding standards
- **Legacy code**: PHP 5.2+ compatibility, deprecated functions

### Current Features to Preserve
- IT asset management (items, software, contracts, invoices)
- File attachment system
- User authentication and authorization
- Multi-language support
- Reporting and labels
- SQLite database with 20+ tables

## Target SlimPHP 4 Architecture

### New Structure
```
/
├── composer.json
├── public/
│   ├── index.php (Slim app bootstrap)
│   └── assets/ (CSS, JS, images)
├── src/
│   ├── Controllers/ (MVC controllers)
│   ├── Models/ (Eloquent models or repositories)
│   ├── Services/ (business logic)
│   ├── Middleware/ (auth, CSRF, etc.)
│   ├── Views/ (Twig templates)
│   └── Migrations/ (database migrations)
├── config/
│   ├── bootstrap.php
│   ├── container.php (DI definitions)
│   ├── routes.php
│   └── settings.php
└── templates/ (Twig template files)
```

## Phase 1: Foundation Setup (Estimated: 2-3 weeks)

### 1.1 Initialize Modern PHP Environment
- [ ] Create `composer.json` with PHP 8.1+ requirement
- [ ] Install SlimPHP 4 + essential packages:
  ```json
  {
    "require": {
      "slim/slim": "^4.12",
      "slim/psr7": "^1.6",
      "php-di/slim-bridge": "^3.4",
      "twig/twig": "^3.8",
      "illuminate/database": "^10.0",
      "monolog/monolog": "^3.5"
    }
  }
  ```
- [ ] Set up PSR-4 autoloading for `App\` namespace
- [ ] Configure development tools (PHPStan, PHP-CS-Fixer)

### 1.2 Create Bootstrap Infrastructure
- [ ] Create `public/index.php` with Slim app initialization
- [ ] Set up PHP-DI container configuration
- [ ] Implement basic middleware stack (error handling, logging)
- [ ] Create base controller class with common dependencies

### 1.3 Database Layer Modernization
- [ ] Analyze current SQLite schema (20+ tables)
- [ ] Create Eloquent models for core entities:
  - Item, Software, Contract, Invoice, User, Agent, Location
- [ ] Implement repository pattern for complex queries
- [ ] Set up database migrations system
- [ ] Create database seeder with current data

## Phase 2: Authentication & Security (Estimated: 1-2 weeks)

### 2.1 Modern Authentication System
- [ ] Replace cookie-based auth with JWT or session-based system
- [ ] Implement PSR-15 authentication middleware
- [ ] Create User model with proper password hashing
- [ ] Migrate LDAP authentication support
- [ ] Add CSRF protection middleware

### 2.2 Security Hardening
- [ ] Implement input validation using Symfony Validator
- [ ] Add XSS protection in templates (Twig auto-escaping)
- [ ] Replace direct SQL queries with parameterized queries/ORM
- [ ] Add rate limiting middleware
- [ ] Implement proper file upload security

## Phase 3: Core Module Migration (Estimated: 4-6 weeks)

### 3.1 Items Management Module
- [ ] Create `ItemController` with CRUD operations
- [ ] Implement `ItemService` for business logic
- [ ] Create Twig templates for item views
- [ ] Migrate item search functionality
- [ ] Handle item-to-item relationships
- [ ] Migrate file attachment system

### 3.2 Software Management Module
- [ ] Create `SoftwareController` and `SoftwareService`
- [ ] Implement software licensing logic
- [ ] Migrate software-to-item relationships
- [ ] Create installation tracking system

### 3.3 Contract & Invoice Modules
- [ ] Create `ContractController` with event tracking
- [ ] Implement `InvoiceController` with financial calculations
- [ ] Migrate contract-item relationships
- [ ] Create invoice-item associations

### 3.4 User & Agent Management
- [ ] Create `UserController` with role-based access
- [ ] Implement `AgentController` for vendors/manufacturers
- [ ] Migrate user permission system
- [ ] Create agent URL management

## Phase 4: Advanced Features (Estimated: 3-4 weeks)

### 4.1 Location & Rack Management
- [ ] Create `LocationController` with hierarchical locations
- [ ] Implement `RackController` with visual rack layouts
- [ ] Migrate rack positioning system
- [ ] Create location tree browsing

### 4.2 Reporting & Analytics
- [ ] Refactor reporting system to use modern charting
- [ ] Create `ReportController` with configurable reports
- [ ] Implement export functionality (PDF, CSV, Excel)
- [ ] Migrate label printing system

### 4.3 File Management System
- [ ] Create secure file upload service
- [ ] Implement file type validation and scanning
- [ ] Create file association management
- [ ] Add file versioning support

## Phase 5: UI/UX Modernization (Estimated: 2-3 weeks)

### 5.1 Frontend Framework Integration
- [ ] Evaluate current jQuery usage and dependencies
- [ ] Consider migration to modern framework (Vue.js/React) or vanilla JS
- [ ] Implement responsive design with CSS Grid/Flexbox
- [ ] Add progressive web app features

### 5.2 Template System
- [ ] Convert all PHP templates to Twig
- [ ] Implement template inheritance and components
- [ ] Add form helpers and validation display
- [ ] Create reusable UI components

## Phase 6: Migration & Testing (Estimated: 2-3 weeks)

### 6.1 Data Migration
- [ ] Create data migration scripts from current SQLite schema
- [ ] Implement backup/restore functionality
- [ ] Create data validation tools
- [ ] Test migration with production data

### 6.2 Testing & Quality Assurance
- [ ] Write unit tests for all services and repositories
- [ ] Create integration tests for API endpoints
- [ ] Implement functional tests for critical user flows
- [ ] Add performance testing for large datasets

### 6.3 Deployment & Documentation
- [ ] Create Docker containers for easy deployment
- [ ] Document API endpoints (OpenAPI/Swagger)
- [ ] Create user migration guide
- [ ] Set up CI/CD pipeline

## Risk Assessment & Mitigation

### High Risks
1. **Data Loss**: Implement comprehensive backup strategy
2. **Performance Regression**: Benchmark current vs. new system
3. **Feature Gaps**: Maintain feature parity matrix
4. **User Adoption**: Provide migration training and documentation

### Medium Risks
1. **Third-party Dependencies**: Evaluate and update TCPDF integration
2. **Browser Compatibility**: Test with target browsers
3. **Scalability**: Design for potential PostgreSQL migration

## Success Metrics

- [ ] 100% feature parity with current system
- [ ] < 20% performance regression on key operations
- [ ] All security vulnerabilities addressed
- [ ] PSR-12 coding standards compliance
- [ ] 80%+ test coverage
- [ ] Zero data loss during migration

## Technology Stack Summary

### Core Framework
- **SlimPHP 4**: Micro-framework for HTTP routing
- **PHP-DI**: Dependency injection container
- **Twig**: Template engine
- **Monolog**: Logging system

### Database & ORM
- **Illuminate/Database (Laravel's Eloquent)**: ORM for database operations
- **SQLite**: Continue using current database (with migration path to PostgreSQL)

### Security & Validation
- **Symfony Validator**: Input validation
- **Firebase JWT**: Authentication tokens
- **CSRF Middleware**: Cross-site request forgery protection

### Development Tools
- **PHPStan**: Static analysis
- **PHP-CS-Fixer**: Code formatting
- **PHPUnit**: Testing framework
- **Composer**: Dependency management

## Timeline Estimate

**Total Duration**: 12-17 weeks (3-4.25 months)

### Recommended Approach
1. **Parallel Development**: Run new Slim app alongside current system
2. **Gradual Migration**: Migrate modules one by one
3. **Feature Flags**: Allow switching between old/new implementations
4. **Progressive Rollout**: Start with non-critical modules

## Conclusion

This refactoring will transform ITDB from a legacy PHP application to a modern, secure, and maintainable system. The migration preserves all existing functionality while introducing modern development practices, better security, and improved maintainability.

The estimated effort is significant but will result in a system that is:
- More secure and compliant with modern security standards
- Easier to maintain and extend
- Better performance and scalability
- Compatible with modern PHP versions and hosting environments
- Suitable for long-term maintenance and feature development

**Recommendation**: Proceed with Phase 1 to establish the foundation, then evaluate progress and refine estimates for subsequent phases.
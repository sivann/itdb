# ITDB to SlimPHP - Detailed Work Items with State Tracking

## Overview
This document provides granular tracking for the ITDB refactoring project. Each work item can be marked as:
- `[ ]` Not Started
- `[P]` In Progress
- `[✓]` Completed
- `[X]` Blocked/Skipped
- `[R]` Requires Review

---

## PHASE 1: Foundation Setup

### 1.1 Project Infrastructure
- [ ] **P1.1.1** Create `composer.json` with dependencies
  - [ ] Add SlimPHP 4.12+ requirement
  - [ ] Add PHP-DI slim-bridge
  - [ ] Add Twig template engine
  - [ ] Add Eloquent ORM (illuminate/database)
  - [ ] Add Monolog for logging
  - [ ] Add development tools (PHPStan, PHP-CS-Fixer, PHPUnit)
  - [ ] Configure PSR-4 autoloading for `App\` namespace

- [ ] **P1.1.2** Create directory structure
  - [ ] Create `public/` directory
  - [ ] Create `src/Controllers/` directory
  - [ ] Create `src/Models/` directory
  - [ ] Create `src/Services/` directory
  - [ ] Create `src/Middleware/` directory
  - [ ] Create `src/Repositories/` directory
  - [ ] Create `config/` directory
  - [ ] Create `templates/` directory
  - [ ] Create `migrations/` directory

### 1.2 Bootstrap Configuration
- [ ] **P1.2.1** Create `public/index.php`
  - [ ] Import Composer autoloader
  - [ ] Initialize PHP-DI container
  - [ ] Create Slim app instance
  - [ ] Set up error handling middleware
  - [ ] Set up logging middleware
  - [ ] Load routes configuration
  - [ ] Run application

- [ ] **P1.2.2** Create `config/bootstrap.php`
  - [ ] Container builder setup
  - [ ] Environment-specific configurations
  - [ ] Database connection setup
  - [ ] Return configured app instance

- [ ] **P1.2.3** Create `config/container.php` (DI definitions)
  - [ ] Database connection definition
  - [ ] Logger definition
  - [ ] Twig renderer definition
  - [ ] Repository definitions
  - [ ] Service definitions
  - [ ] Controller definitions

- [ ] **P1.2.4** Create `config/settings.php`
  - [ ] Database configuration
  - [ ] Logging configuration
  - [ ] Template configuration
  - [ ] Application settings
  - [ ] Environment variables support

### 1.3 Database Foundation
- [ ] **P1.3.1** Analyze current schema from `src/data/itdb.db`
  - [ ] Document all 20+ tables
  - [ ] Map relationships between tables
  - [ ] Identify foreign key constraints
  - [ ] Document current indexes

- [ ] **P1.3.2** Create base migration system
  - [ ] Create `migrations/Migration.php` base class
  - [ ] Create migration runner script
  - [ ] Create `migrations/001_create_initial_tables.php`

### 1.4 Base Classes
- [ ] **P1.4.1** Create `src/Controllers/BaseController.php`
  - [ ] Protected logger property
  - [ ] Protected renderer property
  - [ ] Protected database property
  - [ ] Common response methods (json, render, redirect)
  - [ ] Error handling methods

- [ ] **P1.4.2** Create `src/Models/BaseModel.php` (Eloquent base)
  - [ ] Extend Illuminate\Database\Eloquent\Model
  - [ ] Common timestamp handling
  - [ ] Soft delete support
  - [ ] Custom attribute casting

- [ ] **P1.4.3** Create `src/Services/BaseService.php`
  - [ ] Common business logic patterns
  - [ ] Transaction handling
  - [ ] Validation support
  - [ ] Event dispatching

---

## PHASE 2: Current File Analysis & Migration Map

### 2.1 Core Files Analysis
- [ ] **P2.1.1** Analyze `src/index.php` (430 lines)
  - [ ] Map all route actions (50+ switch cases)
  - [ ] Document authentication checks
  - [ ] Extract menu generation logic
  - [ ] Identify title/header logic per action

- [ ] **P2.1.2** Analyze `src/init.php` (287 lines)
  - [ ] Extract database connection logic (lines 116-132)
  - [ ] Extract authentication system (lines 194-286)
  - [ ] Extract cookie handling (lines 194-281)
  - [ ] Extract LDAP integration (lines 206-227)
  - [ ] Extract settings loading (lines 146-151)
  - [ ] Extract translation system (lines 174-191)

- [ ] **P2.1.3** Analyze `src/functions.php`
  - [ ] **File Functions** (lines to be determined during analysis)
    - [ ] Extract `getmicrotime()` function
    - [ ] Extract `stripslashes_deep()` function
    - [ ] Extract `strenc()` function
    - [ ] Extract `db_execute()` function
    - [ ] Extract `db_exec()` function
    - [ ] Extract `connect_to_ldap_server()` function

- [ ] **P2.1.4** Analyze `src/model.php` (654 lines)
  - [ ] **Status Functions** (lines 5-29)
    - [ ] Migrate `getstatusidofitem()` to ItemService
    - [ ] Migrate `attrofstatus()` to StatusService
  - [ ] **File Association Functions** (lines 44-128)
    - [ ] Migrate `invid2files()` to InvoiceService
    - [ ] Migrate `softid2files()` to SoftwareService
    - [ ] Migrate `itemid2files()` to ItemService
    - [ ] Migrate `contractid2files()` to ContractService
  - [ ] **Count Functions** (lines 133-194)
    - [ ] Migrate `countloclinks()` to LocationService
    - [ ] Migrate `countlocarealinks()` to LocationService
    - [ ] Migrate `countfileidlinks()` to FileService
  - [ ] **Delete Functions** (lines 197-334)
    - [ ] Migrate `delfile()` to FileService
    - [ ] Migrate `delrack()` to RackService
    - [ ] Migrate `deluser()` to UserService
  - [ ] **Tag Functions** (lines 226-301)
    - [ ] Migrate `countitemtags()` to TagService
    - [ ] Migrate `countsoftwaretags()` to TagService
    - [ ] Migrate `tagid2name()` to TagService
    - [ ] Migrate `tagname2id()` to TagService
    - [ ] Migrate `showtags()` to TagService
  - [ ] **Utility Functions** (lines 304-654)
    - [ ] Migrate date calculation functions to DateService
    - [ ] Migrate translation functions to TranslationService
    - [ ] Migrate agent lookup functions to AgentService

### 2.2 PHP Module Files Analysis (57 files in `src/php/`)
- [ ] **P2.2.1** Authentication & User Management
  - [ ] Analyze `src/php/edituser.php`
    - [ ] Extract user CRUD operations
    - [ ] Map to UserController methods
    - [ ] Identify validation rules
  - [ ] Analyze `src/php/listusers.php`
    - [ ] Extract user listing logic
    - [ ] Map to UserController::index()
    - [ ] Extract search/filter functionality

- [ ] **P2.2.2** Item Management Module
  - [ ] Analyze `src/php/edititem.php`
    - [ ] Extract item creation logic → ItemController::create()
    - [ ] Extract item update logic → ItemController::update()
    - [ ] Extract item validation → ItemService::validate()
    - [ ] Extract file attachment logic → FileService::attach()
    - [ ] Map form fields to Item model properties
  - [ ] Analyze `src/php/listitems.php`
    - [ ] Extract search functionality → ItemController::search()
    - [ ] Extract pagination logic → ItemService::paginate()
    - [ ] Extract filtering logic → ItemService::filter()
    - [ ] Map DataTables integration
  - [ ] Analyze `src/php/listitems2.php`
    - [ ] Extract export functionality → ItemController::export()
    - [ ] Extract different view formats

- [ ] **P2.2.3** Software Management Module
  - [ ] Analyze `src/php/editsoftware.php`
    - [ ] Extract software CRUD → SoftwareController
    - [ ] Extract license management → LicenseService
    - [ ] Extract installation tracking → InstallationService
  - [ ] Analyze `src/php/listsoftware.php`
    - [ ] Extract software listing → SoftwareController::index()
    - [ ] Extract software search → SoftwareController::search()

- [ ] **P2.2.4** Contract Management Module
  - [ ] Analyze `src/php/editcontract.php`
    - [ ] Extract contract CRUD → ContractController
    - [ ] Extract event tracking → ContractEventService
    - [ ] Extract renewal calculations → ContractService
  - [ ] Analyze `src/php/listcontracts.php`
    - [ ] Extract contract listing → ContractController::index()
    - [ ] Extract expiration tracking

- [ ] **P2.2.5** Invoice Management Module
  - [ ] Analyze `src/php/editinvoice.php`
    - [ ] Extract invoice CRUD → InvoiceController
    - [ ] Extract financial calculations → InvoiceService
    - [ ] Extract item associations → InvoiceItemService
  - [ ] Analyze `src/php/listinvoices.php`
    - [ ] Extract invoice listing → InvoiceController::index()
    - [ ] Extract financial reporting

- [ ] **P2.2.6** File Management Module
  - [ ] Analyze `src/php/editfile.php`
    - [ ] Extract file upload → FileController::upload()
    - [ ] Extract file metadata → FileService
    - [ ] Extract security validation → FileValidationService
  - [ ] Analyze `src/php/listfiles.php`
    - [ ] Extract file listing → FileController::index()
    - [ ] Extract file download logic

- [ ] **P2.2.7** Agent Management Module
  - [ ] Analyze `src/php/editagent.php`
    - [ ] Extract agent CRUD → AgentController
    - [ ] Extract agent type handling → AgentTypeService
    - [ ] Extract URL management → AgentService
  - [ ] Analyze `src/php/listagents.php`
    - [ ] Extract agent listing → AgentController::index()

- [ ] **P2.2.8** Location & Rack Management
  - [ ] Analyze `src/php/editlocation.php`
    - [ ] Extract location CRUD → LocationController
    - [ ] Extract hierarchical location logic → LocationService
  - [ ] Analyze `src/php/listlocations.php`
    - [ ] Extract location tree → LocationController::tree()
  - [ ] Analyze `src/php/editrack.php`
    - [ ] Extract rack CRUD → RackController
    - [ ] Extract rack visualization → RackVisualizationService
  - [ ] Analyze `src/php/listracks.php`
    - [ ] Extract rack listing → RackController::index()

- [ ] **P2.2.9** Configuration Modules
  - [ ] Analyze `src/php/edititypes.php`
    - [ ] Extract item type management → ItemTypeController
  - [ ] Analyze `src/php/editstatustypes.php`
    - [ ] Extract status type management → StatusTypeController
  - [ ] Analyze `src/php/editfiletypes.php`
    - [ ] Extract file type management → FileTypeController
  - [ ] Analyze `src/php/editcontracttypes.php`
    - [ ] Extract contract type management → ContractTypeController
  - [ ] Analyze `src/php/edittags.php`
    - [ ] Extract tag management → TagController
  - [ ] Analyze `src/php/settings.php`
    - [ ] Extract application settings → SettingsController

- [ ] **P2.2.10** Reporting & Export Modules
  - [ ] Analyze `src/php/reports.php`
    - [ ] Extract report generation → ReportController
    - [ ] Extract chart generation → ChartService
    - [ ] Extract data aggregation → ReportService
  - [ ] Analyze `src/php/printlabels.php`
    - [ ] Extract label printing → LabelController
    - [ ] Extract PDF generation → PDFService
  - [ ] Analyze `src/php/import.php`
    - [ ] Extract data import → ImportController
    - [ ] Extract CSV processing → ImportService

- [ ] **P2.2.11** Utility & Support Modules
  - [ ] Analyze `src/php/browse.php`
    - [ ] Extract tree browsing → BrowseController
    - [ ] Extract hierarchical data display
  - [ ] Analyze `src/php/translations.php`
    - [ ] Extract translation management → TranslationController
  - [ ] Analyze `src/php/showhist.php`
    - [ ] Extract history tracking → HistoryController
  - [ ] Analyze `src/php/about.php`
    - [ ] Extract about page → AboutController
  - [ ] Analyze `src/php/home.php`
    - [ ] Extract dashboard → DashboardController
    - [ ] Extract statistics → StatisticsService

---

## PHASE 3: Model Creation (Database Layer)

### 3.1 Core Models
- [ ] **P3.1.1** Create `src/Models/User.php`
  - [ ] Define table name: 'users'
  - [ ] Define fillable fields
  - [ ] Add password hashing mutator
  - [ ] Add authentication methods
  - [ ] Define relationships (items, contracts, etc.)

- [ ] **P3.1.2** Create `src/Models/Item.php`
  - [ ] Define table name: 'items'
  - [ ] Define fillable fields
  - [ ] Add custom attributes (warranty calculations)
  - [ ] Define relationships:
    - [ ] belongsTo User (userid)
    - [ ] belongsTo ItemType (type)
    - [ ] belongsTo Status (status)
    - [ ] belongsTo Location (locationid)
    - [ ] belongsTo Rack (rackid)
    - [ ] belongsToMany Tags (tag2item)
    - [ ] hasMany Files (item2file)
    - [ ] belongsToMany Invoices (item2inv)
    - [ ] belongsToMany Contracts (contract2item)

- [ ] **P3.1.3** Create `src/Models/Software.php`
  - [ ] Define table name: 'software'
  - [ ] Define fillable fields
  - [ ] Define relationships:
    - [ ] belongsToMany Items (soft2item)
    - [ ] belongsToMany Tags (tag2software)
    - [ ] hasMany Files (software2file)
    - [ ] belongsToMany Invoices (soft2inv)
    - [ ] belongsToMany Contracts (contract2soft)

- [ ] **P3.1.4** Create `src/Models/Invoice.php`
  - [ ] Define table name: 'invoices'
  - [ ] Define fillable fields
  - [ ] Add date mutators/accessors
  - [ ] Define relationships:
    - [ ] belongsToMany Items (item2inv)
    - [ ] belongsToMany Software (soft2inv)
    - [ ] hasMany Files (invoice2file)
    - [ ] belongsTo Agent (vendorid)

- [ ] **P3.1.5** Create `src/Models/Contract.php`
  - [ ] Define table name: 'contracts'
  - [ ] Define fillable fields
  - [ ] Add date calculations
  - [ ] Define relationships:
    - [ ] belongsToMany Items (contract2item)
    - [ ] belongsToMany Software (contract2soft)
    - [ ] hasMany Files (contract2file)
    - [ ] hasMany ContractEvents
    - [ ] belongsTo ContractType

### 3.2 Supporting Models
- [ ] **P3.2.1** Create lookup table models
  - [ ] `src/Models/ItemType.php` (itemtypes table)
  - [ ] `src/Models/StatusType.php` (statustypes table)
  - [ ] `src/Models/FileType.php` (filetypes table)
  - [ ] `src/Models/ContractType.php` (contracttypes table)
  - [ ] `src/Models/Tag.php` (tags table)

- [ ] **P3.2.2** Create association models
  - [ ] `src/Models/Item2File.php` (item2file table)
  - [ ] `src/Models/Item2Invoice.php` (item2inv table)
  - [ ] `src/Models/Tag2Item.php` (tag2item table)
  - [ ] `src/Models/Tag2Software.php` (tag2software table)
  - [ ] `src/Models/Contract2Item.php` (contract2item table)
  - [ ] `src/Models/Contract2Software.php` (contract2soft table)

- [ ] **P3.2.3** Create location models
  - [ ] `src/Models/Location.php` (locations table)
  - [ ] `src/Models/LocationArea.php` (locareas table)
  - [ ] `src/Models/Rack.php` (racks table)

### 3.3 Model Validation & Relationships Testing
- [ ] **P3.3.1** Test all model relationships
  - [ ] Create unit tests for each relationship
  - [ ] Test eager loading performance
  - [ ] Validate foreign key constraints

---

## PHASE 4: Service Layer Creation

### 4.1 Core Services
- [ ] **P4.1.1** Create `src/Services/AuthService.php`
  - [ ] Extract from `init.php` lines 194-286
  - [ ] Method: `authenticate($username, $password)`
  - [ ] Method: `validateSession($cookie)`
  - [ ] Method: `logout($user)`
  - [ ] Method: `createSession($user)`
  - [ ] LDAP integration methods

- [ ] **P4.1.2** Create `src/Services/ItemService.php`
  - [ ] Extract from `model.php` item-related functions
  - [ ] Method: `create($data)` - from edititem.php
  - [ ] Method: `update($id, $data)` - from edititem.php
  - [ ] Method: `search($criteria)` - from listitems.php
  - [ ] Method: `getFiles($itemId)` - from itemid2files()
  - [ ] Method: `attachFile($itemId, $fileId)`
  - [ ] Method: `calculateWarranty($item)`

- [ ] **P4.1.3** Create `src/Services/FileService.php`
  - [ ] Method: `upload($file, $metadata)`
  - [ ] Method: `validateFile($file)`
  - [ ] Method: `getFileAssociations($fileId)`
  - [ ] Method: `deleteFile($fileId)` - from delfile()
  - [ ] Method: `countFileLinks($fileId)` - from countfileidlinks()

### 4.2 Business Logic Services
- [ ] **P4.2.1** Create `src/Services/ContractService.php`
  - [ ] Method: `calculateExpiration($contract)`
  - [ ] Method: `getExpiringContracts($days)`
  - [ ] Method: `createRenewal($contractId)`
  - [ ] Method: `trackEvent($contractId, $event)`

- [ ] **P4.2.2** Create `src/Services/ReportService.php`
  - [ ] Method: `generateItemReport($criteria)`
  - [ ] Method: `generateFinancialReport($period)`
  - [ ] Method: `generateUtilizationReport()`
  - [ ] Method: `exportToPdf($report)`
  - [ ] Method: `exportToCsv($report)`

### 4.3 Utility Services
- [ ] **P4.3.1** Create `src/Services/TranslationService.php`
  - [ ] Extract from functions.php translation functions
  - [ ] Method: `translate($key, $lang)`
  - [ ] Method: `loadTranslations($lang)`
  - [ ] Method: `getMissingTranslations($lang)`

---

## PHASE 5: Controller Creation

### 5.1 Core Controllers
- [ ] **P5.1.1** Create `src/Controllers/ItemController.php`
  - [ ] Extract from `src/php/edititem.php`
  - [ ] Method: `index($request, $response)` - list items
  - [ ] Method: `show($request, $response, $args)` - show single item
  - [ ] Method: `create($request, $response)` - show create form
  - [ ] Method: `store($request, $response)` - handle create
  - [ ] Method: `edit($request, $response, $args)` - show edit form
  - [ ] Method: `update($request, $response, $args)` - handle update
  - [ ] Method: `destroy($request, $response, $args)` - delete item
  - [ ] Method: `search($request, $response)` - search items
  - [ ] Method: `export($request, $response)` - export functionality

- [ ] **P5.1.2** Create `src/Controllers/SoftwareController.php`
  - [ ] Extract from `src/php/editsoftware.php`
  - [ ] Follow same pattern as ItemController
  - [ ] Add license-specific methods

- [ ] **P5.1.3** Create `src/Controllers/ContractController.php`
  - [ ] Extract from `src/php/editcontract.php`
  - [ ] Add contract event handling methods
  - [ ] Add expiration tracking methods

### 5.2 Administrative Controllers
- [ ] **P5.2.1** Create `src/Controllers/UserController.php`
  - [ ] Extract from `src/php/edituser.php`
  - [ ] Add role management methods
  - [ ] Add LDAP user import methods

- [ ] **P5.2.2** Create `src/Controllers/SettingsController.php`
  - [ ] Extract from `src/php/settings.php`
  - [ ] Method: `index()` - show settings form
  - [ ] Method: `update()` - save settings
  - [ ] Method: `backup()` - database backup
  - [ ] Method: `restore()` - database restore

### 5.3 API Controllers (Optional Enhancement)
- [ ] **P5.3.1** Create `src/Controllers/Api/ItemApiController.php`
  - [ ] RESTful API endpoints for items
  - [ ] JSON response formatting
  - [ ] API authentication

---

## PHASE 6: Template Migration

### 6.1 Template Analysis & Extraction
- [ ] **P6.1.1** Analyze current template structure in PHP files
  - [ ] Extract HTML from `src/php/edititem.php`
  - [ ] Extract HTML from `src/php/listitems.php`
  - [ ] Identify common template patterns
  - [ ] Document JavaScript dependencies

### 6.2 Twig Template Creation
- [ ] **P6.2.1** Create base templates
  - [ ] `templates/base.twig` - main layout
  - [ ] `templates/partials/navigation.twig`
  - [ ] `templates/partials/footer.twig`
  - [ ] `templates/partials/flash-messages.twig`

- [ ] **P6.2.2** Create item templates
  - [ ] `templates/items/index.twig` - item listing
  - [ ] `templates/items/show.twig` - item details
  - [ ] `templates/items/form.twig` - create/edit form
  - [ ] `templates/items/search.twig` - search form

---

## PHASE 7: Migration & Testing

### 7.1 Data Migration Scripts
- [ ] **P7.1.1** Create migration scripts
  - [ ] Script to export current SQLite data
  - [ ] Script to validate data integrity
  - [ ] Script to import into new structure
  - [ ] Rollback procedures

### 7.2 Testing Implementation
- [ ] **P7.2.1** Unit tests for services
  - [ ] Test all ItemService methods
  - [ ] Test all AuthService methods
  - [ ] Test all business logic functions

- [ ] **P7.2.2** Integration tests
  - [ ] Test controller endpoints
  - [ ] Test database operations
  - [ ] Test file upload functionality

### 7.3 Performance Testing
- [ ] **P7.3.1** Load testing
  - [ ] Test with current data volume
  - [ ] Compare performance with legacy system
  - [ ] Optimize slow queries

---

## State Tracking Template

For each work item, maintain:

```markdown
## Work Item: [ID] - [Description]
- **Status**: [Not Started/In Progress/Completed/Blocked]
- **Assigned To**: [Developer Name]
- **Start Date**: [YYYY-MM-DD]
- **Target Completion**: [YYYY-MM-DD]
- **Actual Completion**: [YYYY-MM-DD]
- **Dependencies**: [List of prerequisite items]
- **Files Modified**: [List of files created/modified]
- **Lines of Code**: [Approximate LOC]
- **Testing Status**: [Not Tested/Unit Tests/Integration Tests/Fully Tested]
- **Notes**: [Any special considerations or issues encountered]
```

---

## Progress Tracking Summary

### Overall Progress
- **Phase 1**: [ ] 0% (0/X items completed)
- **Phase 2**: [ ] 0% (0/X items completed)
- **Phase 3**: [ ] 0% (0/X items completed)
- **Phase 4**: [ ] 0% (0/X items completed)
- **Phase 5**: [ ] 0% (0/X items completed)
- **Phase 6**: [ ] 0% (0/X items completed)
- **Phase 7**: [ ] 0% (0/X items completed)

### Key Milestones
- [ ] Foundation complete and tested
- [ ] First controller/service pair working
- [ ] Authentication system migrated
- [ ] Core CRUD operations migrated
- [ ] All legacy functions migrated
- [ ] Templates fully converted
- [ ] Data migration successful
- [ ] Performance targets met

This granular tracking allows the project to be resumed at any point with full context of what has been completed, what's in progress, and what remains to be done.
#!/bin/bash

# EventPro Issue Creation Script
# This script creates all GitHub issues from the ISSUES.md roadmap using GitHub CLI
#
# Prerequisites:
#   - GitHub CLI (gh) must be installed and authenticated
#   - Run from the repository root directory
#
# Usage:
#   chmod +x .github/scripts/create-issues.sh
#   ./.github/scripts/create-issues.sh [--labels-only] [--milestones-only] [--issues-only]
#
# Options:
#   --labels-only      Only create labels
#   --milestones-only  Only create milestones
#   --issues-only      Only create issues (assumes labels and milestones exist)
#   --dry-run          Show what would be created without actually creating

set -e

DRY_RUN=false
CREATE_LABELS=true
CREATE_MILESTONES=true
CREATE_ISSUES=true

# Parse arguments
for arg in "$@"; do
    case $arg in
        --labels-only)
            CREATE_MILESTONES=false
            CREATE_ISSUES=false
            ;;
        --milestones-only)
            CREATE_LABELS=false
            CREATE_ISSUES=false
            ;;
        --issues-only)
            CREATE_LABELS=false
            CREATE_MILESTONES=false
            ;;
        --dry-run)
            DRY_RUN=true
            ;;
    esac
done

# Check if gh CLI is installed
if ! command -v gh &> /dev/null; then
    echo "Error: GitHub CLI (gh) is not installed."
    echo "Install it from: https://cli.github.com/"
    exit 1
fi

# Check if authenticated
if ! gh auth status &> /dev/null; then
    echo "Error: Not authenticated with GitHub CLI."
    echo "Run: gh auth login"
    exit 1
fi

# Get repository name
REPO=$(gh repo view --json nameWithOwner -q .nameWithOwner)
echo "Repository: $REPO"
echo "Dry run: $DRY_RUN"
echo ""

# Function to create labels
create_labels() {
    echo "========================================="
    echo "Creating Labels"
    echo "========================================="
    
    declare -a LABELS=(
        # Priority labels
        "priority:critical|d73a4a|Must be completed, blocks other work"
        "priority:high|f9c513|Important feature, should be completed"
        "priority:medium|0e8a16|Nice to have, can be deferred if needed"
        "priority:low|c5def5|Optional enhancement"
        # Type labels
        "type:feature|a2eeef|New functionality"
        "type:enhancement|84b6eb|Improvement to existing feature"
        "type:bug|d73a4a|Bug fix"
        "type:docs|0075ca|Documentation"
        "type:test|5319e7|Testing"
        "type:devops|fbca04|Infrastructure/deployment"
        # Module labels
        "module:core|bfd4f2|Core architecture"
        "module:venue|b4a7d6|Venue management"
        "module:booking|d5a6bd|Booking system"
        "module:payment|ffe599|Payment tracking"
        "module:client-portal|9fc5e8|Client portal"
        "module:admin|f4cccc|Admin interface"
        "module:api|b6d7a8|API endpoints"
        "module:vendor|c9daf8|Vendor management"
        "module:staff|d9ead3|Staff scheduling"
        "module:reports|fff2cc|Reporting and analytics"
        "module:settings|e6b8af|Settings and customization"
        "module:theme|d0e0e3|Theme and plugin system"
        # Phase labels
        "phase:1-foundation|e99695|Phase 1: Foundation (Weeks 1-6)"
        "phase:2-core-modules|f9cb9c|Phase 2: Core Modules (Weeks 7-16)"
        "phase:3-extended|ffe599|Phase 3: Extended Modules (Weeks 17-24)"
        "phase:4-customization|b6d7a8|Phase 4: Customization (Weeks 25-30)"
        "phase:5-launch|a2c4c9|Phase 5: Launch (Weeks 31-36)"
    )
    
    for label_info in "${LABELS[@]}"; do
        IFS='|' read -r name color description <<< "$label_info"
        echo "Creating label: $name"
        if [ "$DRY_RUN" = false ]; then
            gh label create "$name" --color "$color" --description "$description" --force 2>/dev/null || true
        fi
    done
    
    echo "Labels created successfully!"
    echo ""
}

# Function to create milestones
create_milestones() {
    echo "========================================="
    echo "Creating Milestones"
    echo "========================================="
    
    declare -a MILESTONES=(
        "M1: Foundation Complete|Phase 1 complete - Core infrastructure and architecture (EP-001 to EP-017)|6"
        "M2: Core Booking Flow|Phase 2 complete - Core booking and payment modules (EP-018 to EP-039)|16"
        "M3: Extended Features|Phase 3 complete - Vendor, staff, reporting, and client portal (EP-040 to EP-056)|24"
        "M4: Customization Ready|Phase 4 complete - Settings, custom fields, themes, and plugins (EP-057 to EP-069)|30"
        "M5: Production Launch|Phase 5 complete - Testing, documentation, and deployment (EP-070 to EP-083)|36"
    )
    
    START_DATE=$(date +%Y-%m-%d)
    
    for milestone_info in "${MILESTONES[@]}"; do
        IFS='|' read -r title description weeks <<< "$milestone_info"
        # Calculate due date
        if date --version >/dev/null 2>&1; then
            # GNU date
            due_date=$(date -d "$START_DATE + $weeks weeks" +%Y-%m-%dT23:59:59Z)
        else
            # BSD date (macOS)
            due_date=$(date -v+${weeks}w +%Y-%m-%dT23:59:59Z)
        fi
        
        echo "Creating milestone: $title (due: $due_date)"
        if [ "$DRY_RUN" = false ]; then
            gh api repos/$REPO/milestones -f title="$title" -f description="$description" -f due_on="$due_date" 2>/dev/null || echo "  (may already exist)"
        fi
    done
    
    echo "Milestones created successfully!"
    echo ""
}

# Function to create an issue
create_issue() {
    local id="$1"
    local title="$2"
    local body="$3"
    local labels="$4"
    local milestone="$5"
    
    echo "Creating: [$id] $title"
    
    if [ "$DRY_RUN" = false ]; then
        local cmd="gh issue create --title \"[$id] $title\" --body \"$body\""
        
        # Add labels
        IFS=',' read -ra LABEL_ARRAY <<< "$labels"
        for label in "${LABEL_ARRAY[@]}"; do
            cmd="$cmd --label \"$label\""
        done
        
        # Add milestone
        if [ -n "$milestone" ]; then
            cmd="$cmd --milestone \"$milestone\""
        fi
        
        eval $cmd
        sleep 1  # Rate limiting
    fi
}

# Function to create all issues
create_issues() {
    echo "========================================="
    echo "Creating Issues"
    echo "========================================="
    echo ""
    
    # =========================================================================
    # PHASE 1: FOUNDATION (Weeks 1-6) - EP-001 to EP-017
    # =========================================================================
    echo "--- Phase 1: Foundation (Weeks 1-6) ---"
    echo ""
    
    create_issue "EP-001" "Initialize Laravel 11 project with required dependencies" \
"## Description
Set up Laravel 11 with PHP 8.2+ and configure all required dependencies.

## Tasks
- [ ] Set up Laravel 11 with PHP 8.2+
- [ ] Configure composer.json with all required packages (Spatie packages, DomPDF, etc.)
- [ ] Set up package.json with Vue 3, Inertia.js, and Tailwind CSS
- [ ] Verify all dependencies install correctly
- [ ] Set up basic project structure

## Technical Notes
- Use Laravel 11.x
- PHP 8.2+ required
- Key packages: spatie/laravel-multitenancy, spatie/laravel-permission, barryvdh/laravel-dompdf

## Acceptance Criteria
- [ ] Laravel 11 project runs successfully
- [ ] All composer dependencies install without errors
- [ ] All npm dependencies install without errors
- [ ] Basic routing works

**Priority:** Critical | **Estimate:** 2 days | **Week:** 1-2" \
"priority:critical,type:feature,module:core,phase:1-foundation" \
"M1: Foundation Complete"

    create_issue "EP-002" "Configure Docker development environment" \
"## Description
Create Docker configuration for local development environment.

## Tasks
- [ ] Create Dockerfile for PHP 8.2 with required extensions
- [ ] Set up docker-compose.yml with MySQL, Redis, Meilisearch, and Mailhog
- [ ] Document Docker setup instructions
- [ ] Test container networking
- [ ] Create development scripts

## Technical Notes
- PHP extensions needed: pdo_mysql, redis, gd, zip, bcmath
- MySQL 8.0
- Redis for caching and queues
- Meilisearch for full-text search
- Mailhog for email testing

## Acceptance Criteria
- [ ] All containers start successfully
- [ ] Laravel connects to MySQL
- [ ] Redis caching works
- [ ] Mail testing works with Mailhog

**Priority:** High | **Estimate:** 1 day | **Week:** 1-2" \
"priority:high,type:devops,module:core,phase:1-foundation" \
"M1: Foundation Complete"

    create_issue "EP-003" "Set up CI/CD pipeline" \
"## Description
Configure GitHub Actions for automated testing and deployment.

## Tasks
- [ ] Configure GitHub Actions for automated testing
- [ ] Set up code quality checks (PHPStan, Laravel Pint)
- [ ] Configure deployment workflows
- [ ] Add test coverage reporting
- [ ] Set up branch protection rules

## Technical Notes
- Use GitHub Actions
- PHPStan level 5 minimum
- Laravel Pint for code style
- Separate workflows for testing and deployment

## Acceptance Criteria
- [ ] Tests run on every PR
- [ ] Code style checks pass
- [ ] Static analysis passes
- [ ] Deployment triggers on main branch merge

**Priority:** High | **Estimate:** 2 days | **Week:** 1-2" \
"priority:high,type:devops,module:core,phase:1-foundation" \
"M1: Foundation Complete"

    create_issue "EP-004" "Configure multi-tenancy foundation" \
"## Description
Set up multi-tenant architecture using Spatie's laravel-multitenancy package.

## Tasks
- [ ] Install and configure spatie/laravel-multitenancy
- [ ] Implement tenant identification (domain/subdomain)
- [ ] Create BelongsToTenant trait with automatic scoping
- [ ] Set up tenant-aware middleware
- [ ] Configure tenant database connections

## Technical Notes
- Use single database with tenant_id column approach
- Automatic scoping via global scopes
- Tenant resolution via subdomain

## Acceptance Criteria
- [ ] Tenants can be created and identified
- [ ] Data is automatically scoped to tenant
- [ ] Tenant context is available throughout request
- [ ] Cross-tenant data access is prevented

**Priority:** Critical | **Estimate:** 3 days | **Week:** 1-2" \
"priority:critical,type:feature,module:core,phase:1-foundation" \
"M1: Foundation Complete"

    create_issue "EP-005" "Set up authentication system" \
"## Description
Configure authentication for both API and web interfaces.

## Tasks
- [ ] Configure Laravel Sanctum for API authentication
- [ ] Implement session-based auth for web
- [ ] Set up password reset functionality
- [ ] Implement email verification
- [ ] Create login/register views

## Technical Notes
- Sanctum for SPA authentication
- Session auth for traditional web pages
- Email verification required for new accounts

## Acceptance Criteria
- [ ] Users can register and login
- [ ] API authentication works with tokens
- [ ] Password reset emails are sent
- [ ] Email verification works

**Priority:** Critical | **Estimate:** 2 days | **Week:** 1-2" \
"priority:critical,type:feature,module:core,phase:1-foundation" \
"M1: Foundation Complete"

    create_issue "EP-006" "Create core database migrations" \
"## Description
Create database migrations for core system tables.

## Tasks
- [ ] Plans table for subscription tiers
- [ ] Tenants table with settings JSON
- [ ] Users table with tenant relationship
- [ ] Add necessary indexes
- [ ] Create foreign key constraints

## Technical Notes
- Use JSON columns for flexible settings
- Soft deletes on key tables
- UUID primary keys consideration

## Acceptance Criteria
- [ ] All migrations run successfully
- [ ] Rollback works correctly
- [ ] Foreign keys are properly defined
- [ ] Indexes are optimized for common queries

**Priority:** Critical | **Estimate:** 1 day | **Week:** 3-4" \
"priority:critical,type:feature,module:core,phase:1-foundation" \
"M1: Foundation Complete"

    create_issue "EP-007" "Create venue and package migrations" \
"## Description
Create database migrations for venues and packages.

## Tasks
- [ ] Venues table with capacity, pricing, amenities
- [ ] Packages table with guest pricing tiers
- [ ] Venue-Package pivot table
- [ ] Add image storage columns
- [ ] Configure JSON columns for flexible data

## Technical Notes
- Amenities stored as JSON array
- Pricing tiers as JSON structure
- Support for multiple images per venue

## Acceptance Criteria
- [ ] Venues can be stored with all attributes
- [ ] Packages support tiered pricing
- [ ] Many-to-many relationship works
- [ ] JSON queries are efficient

**Priority:** Critical | **Estimate:** 1 day | **Week:** 3-4" \
"priority:critical,type:feature,module:venue,phase:1-foundation" \
"M1: Foundation Complete"

    create_issue "EP-008" "Create booking workflow migrations" \
"## Description
Create database migrations for the complete booking workflow.

## Tasks
- [ ] Clients table
- [ ] Inquiries table with status workflow
- [ ] Bookings table with comprehensive fields
- [ ] Quotations table with versioning support
- [ ] Payments table for installment tracking

## Technical Notes
- Status fields use enums
- Version tracking for quotations
- Support for partial payments

## Acceptance Criteria
- [ ] Full booking workflow can be stored
- [ ] Status transitions are trackable
- [ ] Payment installments are supported
- [ ] Quotation versions are maintained

**Priority:** Critical | **Estimate:** 2 days | **Week:** 3-4" \
"priority:critical,type:feature,module:booking,phase:1-foundation" \
"M1: Foundation Complete"

    create_issue "EP-009" "Create custom fields system migrations" \
"## Description
Create database migrations for the custom fields system.

## Tasks
- [ ] Custom fields definition table
- [ ] Custom field values table with polymorphic relationships
- [ ] Field validation rules storage
- [ ] Field ordering support

## Technical Notes
- Polymorphic relationships for flexibility
- Support multiple field types (text, number, select, etc.)
- Validation rules stored as JSON

## Acceptance Criteria
- [ ] Custom fields can be defined
- [ ] Values can be attached to any entity
- [ ] Field ordering is preserved
- [ ] Validation rules are stored

**Priority:** High | **Estimate:** 1 day | **Week:** 3-4" \
"priority:high,type:feature,module:core,phase:1-foundation" \
"M1: Foundation Complete"

    create_issue "EP-010" "Implement core Eloquent models" \
"## Description
Create Eloquent models for all core entities.

## Tasks
- [ ] Tenant model with settings methods
- [ ] Venue, Package, Client models
- [ ] Booking, Inquiry, Quotation, Payment models
- [ ] Configure relationships and accessors
- [ ] Add model events and observers

## Technical Notes
- Use traits for common functionality
- Implement proper type casting
- Add scopes for common queries

## Acceptance Criteria
- [ ] All models are created with relationships
- [ ] Accessors and mutators work correctly
- [ ] Model events fire appropriately
- [ ] Queries are efficient

**Priority:** Critical | **Estimate:** 3 days | **Week:** 3-4" \
"priority:critical,type:feature,module:core,phase:1-foundation" \
"M1: Foundation Complete"

    create_issue "EP-011" "Create model factories and seeders" \
"## Description
Create factories and seeders for development and testing.

## Tasks
- [ ] Factory classes for all models
- [ ] DatabaseSeeder with sample data
- [ ] TenantSeeder for demo tenant
- [ ] Realistic fake data generation
- [ ] Seeder for different scenarios

## Technical Notes
- Use Faker for realistic data
- Create relationships in factories
- Support for multiple tenant demo data

## Acceptance Criteria
- [ ] All models have factories
- [ ] Seeders create valid test data
- [ ] Demo tenant has complete data
- [ ] Factories support states

**Priority:** Medium | **Estimate:** 2 days | **Week:** 3-4" \
"priority:medium,type:feature,module:core,phase:1-foundation" \
"M1: Foundation Complete"

    create_issue "EP-012" "Implement SettingsService" \
"## Description
Create a service for managing hierarchical settings.

## Tasks
- [ ] Hierarchical settings resolution (tenant > env > defaults)
- [ ] Settings schema validation
- [ ] Currency, date, and time formatting helpers
- [ ] Settings caching
- [ ] Settings update API

## Technical Notes
- Cache invalidation on update
- Support for nested settings
- Type-safe accessors

## Acceptance Criteria
- [ ] Settings resolve in correct order
- [ ] Validation prevents invalid settings
- [ ] Formatting helpers work correctly
- [ ] Caching improves performance

**Priority:** Critical | **Estimate:** 2 days | **Week:** 5-6" \
"priority:critical,type:feature,module:core,phase:1-foundation" \
"M1: Foundation Complete"

    create_issue "EP-013" "Implement AvailabilityService" \
"## Description
Create a service for managing venue availability.

## Tasks
- [ ] Venue availability checking with date locking
- [ ] Calendar availability generation
- [ ] Multi-venue availability matrix
- [ ] Conflict detection
- [ ] Buffer time handling

## Technical Notes
- Consider time zones
- Lock mechanism to prevent double booking
- Efficient calendar queries

## Acceptance Criteria
- [ ] Availability is accurately checked
- [ ] Calendar data is generated correctly
- [ ] Double bookings are prevented
- [ ] Buffer times are respected

**Priority:** Critical | **Estimate:** 2 days | **Week:** 5-6" \
"priority:critical,type:feature,module:venue,phase:1-foundation" \
"M1: Foundation Complete"

    create_issue "EP-014" "Implement PricingService" \
"## Description
Create a comprehensive pricing calculation engine.

## Tasks
- [ ] Event pricing calculation engine
- [ ] Venue and package pricing
- [ ] Surcharges (weekend, peak season)
- [ ] Discount application
- [ ] Tax calculation

## Technical Notes
- Support multiple tax rates
- Percentage and fixed discounts
- Configurable surcharge rules

## Acceptance Criteria
- [ ] Prices calculate correctly
- [ ] Surcharges apply appropriately
- [ ] Discounts work as expected
- [ ] Taxes are calculated correctly

**Priority:** Critical | **Estimate:** 3 days | **Week:** 5-6" \
"priority:critical,type:feature,module:booking,phase:1-foundation" \
"M1: Foundation Complete"

    create_issue "EP-015" "Implement BrandingService" \
"## Description
Create a service for managing tenant branding.

## Tasks
- [ ] Logo and favicon URL resolution
- [ ] Dynamic CSS variables generation
- [ ] Contact and social media configuration
- [ ] Brand color management
- [ ] Email header/footer branding

## Technical Notes
- Support for light/dark logos
- CSS custom properties for theming
- Fallback to defaults

## Acceptance Criteria
- [ ] Logos display correctly
- [ ] CSS variables are generated
- [ ] Brand colors apply throughout
- [ ] Fallbacks work properly

**Priority:** High | **Estimate:** 1 day | **Week:** 5-6" \
"priority:high,type:feature,module:core,phase:1-foundation" \
"M1: Foundation Complete"

    create_issue "EP-016" "Create API response formatting" \
"## Description
Standardize API response structure across the application.

## Tasks
- [ ] Standardized JSON response structure
- [ ] API resources for all models
- [ ] Error handling and validation responses
- [ ] Pagination formatting
- [ ] Response macros

## Technical Notes
- Consistent error format
- Include metadata in responses
- Support for includes/relationships

## Acceptance Criteria
- [ ] All API responses follow standard format
- [ ] Errors are consistently formatted
- [ ] Pagination works correctly
- [ ] Resources transform data properly

**Priority:** High | **Estimate:** 2 days | **Week:** 5-6" \
"priority:high,type:feature,module:api,phase:1-foundation" \
"M1: Foundation Complete"

    create_issue "EP-017" "Set up authorization with Spatie Permissions" \
"## Description
Implement role-based access control using Spatie Permissions.

## Tasks
- [ ] Define roles (super_admin, admin, manager, staff, client)
- [ ] Create permission sets for each role
- [ ] Implement role-based middleware
- [ ] Add authorization gates and policies
- [ ] Create permission seeder

## Technical Notes
- Tenant-aware permissions
- Cache permissions for performance
- Support for direct permissions

## Acceptance Criteria
- [ ] Roles are created correctly
- [ ] Permissions are assigned appropriately
- [ ] Middleware blocks unauthorized access
- [ ] Policies work with models

**Priority:** Critical | **Estimate:** 2 days | **Week:** 5-6" \
"priority:critical,type:feature,module:core,phase:1-foundation" \
"M1: Foundation Complete"

    echo ""
    echo "Phase 1 issues created!"
    echo ""

    # =========================================================================
    # PHASE 2: CORE MODULES (Weeks 7-16) - EP-018 to EP-039
    # =========================================================================
    echo "--- Phase 2: Core Modules (Weeks 7-16) ---"
    echo ""

    create_issue "EP-018" "Create Venue CRUD API endpoints" \
"## Description
Build REST API endpoints for venue management.

## Tasks
- [ ] List venues with filtering and pagination
- [ ] Create, update, delete venue operations
- [ ] Image upload and management
- [ ] Validation rules
- [ ] API documentation

## Acceptance Criteria
- [ ] All CRUD operations work
- [ ] Filtering and pagination function correctly
- [ ] Images upload and display properly
- [ ] Validation prevents invalid data

**Priority:** Critical | **Estimate:** 3 days | **Week:** 7-8" \
"priority:critical,type:feature,module:venue,module:api,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-019" "Build Venue admin interface (Vue/Inertia)" \
"## Description
Create the admin interface for venue management using Vue 3 and Inertia.js.

## Tasks
- [ ] Venues list page with search and filters
- [ ] Create/Edit venue form with image gallery
- [ ] Amenities and pricing configuration
- [ ] Delete confirmation
- [ ] Responsive design

## Acceptance Criteria
- [ ] All venue CRUD operations work from UI
- [ ] Image gallery functions properly
- [ ] Forms validate correctly
- [ ] UI is responsive

**Priority:** Critical | **Estimate:** 4 days | **Week:** 7-8" \
"priority:critical,type:feature,module:venue,module:admin,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-020" "Implement availability calendar" \
"## Description
Create a visual calendar for venue availability.

## Tasks
- [ ] FullCalendar integration
- [ ] Color-coded booking status display
- [ ] Date selection for availability check
- [ ] Month/week/day views
- [ ] Event tooltips

## Acceptance Criteria
- [ ] Calendar displays bookings correctly
- [ ] Colors indicate status clearly
- [ ] Navigation between views works
- [ ] Tooltips show booking details

**Priority:** High | **Estimate:** 3 days | **Week:** 7-8" \
"priority:high,type:feature,module:venue,module:admin,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-021" "Create public venue pages" \
"## Description
Build public-facing venue listing and detail pages.

## Tasks
- [ ] Venue listing with filters (capacity, price)
- [ ] Individual venue detail pages
- [ ] Availability calendar widget
- [ ] Image gallery
- [ ] Inquiry form integration

## Acceptance Criteria
- [ ] Venues display with correct information
- [ ] Filtering works correctly
- [ ] Availability is visible
- [ ] Inquiry form submits

**Priority:** High | **Estimate:** 2 days | **Week:** 7-8" \
"priority:high,type:feature,module:venue,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-022" "Create Package CRUD API endpoints" \
"## Description
Build REST API endpoints for package management.

## Tasks
- [ ] Package listing with venue associations
- [ ] Create, update, delete operations
- [ ] Tiered guest pricing management
- [ ] Validation rules

## Acceptance Criteria
- [ ] All CRUD operations work
- [ ] Venue associations are correct
- [ ] Pricing tiers save correctly
- [ ] API responses are properly formatted

**Priority:** Critical | **Estimate:** 2 days | **Week:** 9-10" \
"priority:critical,type:feature,module:booking,module:api,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-023" "Build Package admin interface" \
"## Description
Create the admin interface for package management.

## Tasks
- [ ] Packages list page
- [ ] Create/Edit package form
- [ ] Guest pricing tier builder
- [ ] Included services configuration
- [ ] Venue assignment

## Acceptance Criteria
- [ ] All package CRUD operations work
- [ ] Pricing tiers can be configured
- [ ] Services can be added/removed
- [ ] Venue assignments work

**Priority:** Critical | **Estimate:** 3 days | **Week:** 9-10" \
"priority:critical,type:feature,module:booking,module:admin,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-024" "Implement dynamic pricing calculator" \
"## Description
Build a real-time pricing calculation system.

## Tasks
- [ ] Real-time price calculation API
- [ ] Frontend pricing widget
- [ ] Service add-on selection
- [ ] Discount application
- [ ] Price breakdown display

## Acceptance Criteria
- [ ] Prices update in real-time
- [ ] Add-ons affect pricing correctly
- [ ] Discounts apply properly
- [ ] Breakdown is clear and accurate

**Priority:** High | **Estimate:** 3 days | **Week:** 9-10" \
"priority:high,type:feature,module:booking,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-025" "Create public packages page" \
"## Description
Build public-facing package listing and comparison.

## Tasks
- [ ] Package cards with key features
- [ ] Comparison view
- [ ] Direct inquiry from package
- [ ] Pricing display
- [ ] Mobile responsive design

## Acceptance Criteria
- [ ] Packages display correctly
- [ ] Comparison feature works
- [ ] Inquiry submission works
- [ ] Mobile display is correct

**Priority:** Medium | **Estimate:** 2 days | **Week:** 9-10" \
"priority:medium,type:feature,module:booking,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-026" "Create Inquiry management API" \
"## Description
Build REST API endpoints for inquiry management.

## Tasks
- [ ] Public inquiry submission endpoint
- [ ] Admin inquiry CRUD operations
- [ ] Status workflow management
- [ ] Assignment to staff
- [ ] Filtering and search

## Acceptance Criteria
- [ ] Inquiries can be submitted publicly
- [ ] Admin can manage inquiries
- [ ] Status changes are tracked
- [ ] Assignment works correctly

**Priority:** Critical | **Estimate:** 3 days | **Week:** 11-12" \
"priority:critical,type:feature,module:booking,module:api,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-027" "Build Inquiry admin interface" \
"## Description
Create the admin interface for inquiry management.

## Tasks
- [ ] Inquiry kanban board by status
- [ ] Inquiry detail view
- [ ] Communication log
- [ ] Convert to quotation action
- [ ] Quick reply functionality

## Acceptance Criteria
- [ ] Kanban board displays correctly
- [ ] Status changes via drag-drop
- [ ] Communication is logged
- [ ] Conversion to quotation works

**Priority:** Critical | **Estimate:** 4 days | **Week:** 11-12" \
"priority:critical,type:feature,module:booking,module:admin,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-028" "Implement QuotationService" \
"## Description
Create a service for quotation generation and management.

## Tasks
- [ ] Generate quotation from inquiry
- [ ] Line item management
- [ ] Terms and conditions
- [ ] Version tracking
- [ ] Price calculations

## Acceptance Criteria
- [ ] Quotations generate correctly
- [ ] Line items can be added/edited
- [ ] Versions are tracked
- [ ] Calculations are accurate

**Priority:** Critical | **Estimate:** 3 days | **Week:** 11-12" \
"priority:critical,type:feature,module:booking,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-029" "Create Quotation admin interface" \
"## Description
Build the admin interface for quotation management.

## Tasks
- [ ] Quotation builder with drag-drop items
- [ ] Preview mode
- [ ] Send via email functionality
- [ ] Track views and responses
- [ ] Version comparison

## Acceptance Criteria
- [ ] Quotation builder works intuitively
- [ ] Preview matches final output
- [ ] Email sending works
- [ ] Tracking shows accurate data

**Priority:** Critical | **Estimate:** 4 days | **Week:** 11-12" \
"priority:critical,type:feature,module:booking,module:admin,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-030" "Implement PDF generation for quotations" \
"## Description
Create branded PDF generation for quotations.

## Tasks
- [ ] Branded PDF template using DomPDF
- [ ] Dynamic content rendering
- [ ] Download and email attachment
- [ ] Multiple template support
- [ ] Logo and branding integration

## Acceptance Criteria
- [ ] PDFs generate correctly
- [ ] Branding displays properly
- [ ] Download and email work
- [ ] Content is accurately rendered

**Priority:** High | **Estimate:** 2 days | **Week:** 11-12" \
"priority:high,type:feature,module:booking,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-031" "Create Booking CRUD API" \
"## Description
Build REST API endpoints for booking management.

## Tasks
- [ ] Create booking from accepted quotation
- [ ] Manual booking creation
- [ ] Status workflow (pending → confirmed → completed)
- [ ] Update and cancel operations
- [ ] Filtering and search

## Acceptance Criteria
- [ ] Bookings can be created from quotations
- [ ] Manual creation works
- [ ] Status workflow is enforced
- [ ] All operations are audited

**Priority:** Critical | **Estimate:** 3 days | **Week:** 13-14" \
"priority:critical,type:feature,module:booking,module:api,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-032" "Implement booking validation rules" \
"## Description
Create comprehensive validation for booking operations.

## Tasks
- [ ] Venue availability check with locking
- [ ] Advance booking limits
- [ ] Guest count validation
- [ ] Date range validation
- [ ] Business rule enforcement

## Acceptance Criteria
- [ ] Double bookings are prevented
- [ ] Advance booking limits work
- [ ] Guest counts are validated
- [ ] Invalid dates are rejected

**Priority:** Critical | **Estimate:** 2 days | **Week:** 13-14" \
"priority:critical,type:feature,module:booking,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-033" "Build Booking admin interface" \
"## Description
Create the admin interface for booking management.

## Tasks
- [ ] Bookings calendar view
- [ ] Bookings list with filters
- [ ] Booking detail page
- [ ] Quick actions (confirm, cancel)
- [ ] Status management

## Acceptance Criteria
- [ ] Calendar shows all bookings
- [ ] List filtering works
- [ ] Details display correctly
- [ ] Quick actions function properly

**Priority:** Critical | **Estimate:** 4 days | **Week:** 13-14" \
"priority:critical,type:feature,module:booking,module:admin,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-034" "Create booking confirmation workflow" \
"## Description
Implement the booking confirmation process.

## Tasks
- [ ] Confirmation email templates
- [ ] Contract generation
- [ ] Deposit requirements
- [ ] Confirmation tracking
- [ ] Automated reminders

## Acceptance Criteria
- [ ] Confirmation emails send correctly
- [ ] Contracts generate properly
- [ ] Deposit tracking works
- [ ] Reminders are scheduled

**Priority:** High | **Estimate:** 2 days | **Week:** 13-14" \
"priority:high,type:feature,module:booking,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-035" "Implement booking cancellation" \
"## Description
Create booking cancellation functionality.

## Tasks
- [ ] Cancellation policy enforcement
- [ ] Refund calculation
- [ ] Cancellation notifications
- [ ] Release venue availability
- [ ] Cancellation reason tracking

## Acceptance Criteria
- [ ] Cancellation policy is applied
- [ ] Refunds calculate correctly
- [ ] Notifications are sent
- [ ] Venue becomes available

**Priority:** High | **Estimate:** 2 days | **Week:** 13-14" \
"priority:high,type:feature,module:booking,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-036" "Create Payment management API" \
"## Description
Build REST API endpoints for payment tracking.

## Tasks
- [ ] Record payment entries
- [ ] Installment schedule generation
- [ ] Balance calculation
- [ ] Payment method tracking
- [ ] Receipt generation

## Acceptance Criteria
- [ ] Payments can be recorded
- [ ] Installments are generated correctly
- [ ] Balances are accurate
- [ ] Receipts generate properly

**Priority:** Critical | **Estimate:** 2 days | **Week:** 15-16" \
"priority:critical,type:feature,module:payment,module:api,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-037" "Build Payment admin interface" \
"## Description
Create the admin interface for payment management.

## Tasks
- [ ] Payment recording form
- [ ] Payment history per booking
- [ ] Payment schedule display
- [ ] Receipt generation
- [ ] Outstanding balance alerts

## Acceptance Criteria
- [ ] Payments can be recorded easily
- [ ] History is accurate
- [ ] Schedule displays correctly
- [ ] Receipts can be generated

**Priority:** Critical | **Estimate:** 3 days | **Week:** 15-16" \
"priority:critical,type:feature,module:payment,module:admin,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-038" "Implement payment reminders" \
"## Description
Create automated payment reminder system.

## Tasks
- [ ] Automated reminder scheduling
- [ ] Email notification for due payments
- [ ] Overdue payment alerts
- [ ] Reminder configuration
- [ ] Stop reminders on payment

## Acceptance Criteria
- [ ] Reminders schedule correctly
- [ ] Emails are sent on time
- [ ] Overdue alerts work
- [ ] Configuration is flexible

**Priority:** High | **Estimate:** 2 days | **Week:** 15-16" \
"priority:high,type:feature,module:payment,phase:2-core-modules" \
"M2: Core Booking Flow"

    create_issue "EP-039" "Create financial dashboard" \
"## Description
Build a financial overview dashboard.

## Tasks
- [ ] Revenue overview charts
- [ ] Outstanding payments
- [ ] Payment method breakdown
- [ ] Date range selection
- [ ] Export functionality

## Acceptance Criteria
- [ ] Charts display accurate data
- [ ] Outstanding payments are tracked
- [ ] Breakdown is correct
- [ ] Export works properly

**Priority:** Medium | **Estimate:** 3 days | **Week:** 15-16" \
"priority:medium,type:feature,module:payment,module:admin,phase:2-core-modules" \
"M2: Core Booking Flow"

    echo ""
    echo "Phase 2 issues created!"
    echo ""

    # =========================================================================
    # PHASE 3: EXTENDED MODULES (Weeks 17-24) - EP-040 to EP-056
    # =========================================================================
    echo "--- Phase 3: Extended Modules (Weeks 17-24) ---"
    echo ""

    create_issue "EP-040" "Create Vendor model and migrations" \
"## Description
Set up database structure for vendor management.

## Tasks
- [ ] Vendor categories (caterer, florist, photographer, etc.)
- [ ] Contact information and services
- [ ] Pricing and availability
- [ ] Rating and reviews structure
- [ ] Document storage

## Acceptance Criteria
- [ ] Vendors can be stored with all attributes
- [ ] Categories are configurable
- [ ] Services can be defined
- [ ] Relationships work correctly

**Priority:** High | **Estimate:** 2 days | **Week:** 17-18" \
"priority:high,type:feature,module:vendor,phase:3-extended" \
"M3: Extended Features"

    create_issue "EP-041" "Implement Vendor CRUD API" \
"## Description
Build REST API endpoints for vendor management.

## Tasks
- [ ] Vendor listing and filtering
- [ ] Create, update, delete operations
- [ ] Service assignment to bookings
- [ ] Availability checking
- [ ] Search functionality

## Acceptance Criteria
- [ ] All CRUD operations work
- [ ] Filtering is comprehensive
- [ ] Assignment to bookings works
- [ ] Search returns relevant results

**Priority:** High | **Estimate:** 2 days | **Week:** 17-18" \
"priority:high,type:feature,module:vendor,module:api,phase:3-extended" \
"M3: Extended Features"

    create_issue "EP-042" "Build Vendor admin interface" \
"## Description
Create the admin interface for vendor management.

## Tasks
- [ ] Vendor directory
- [ ] Vendor profile pages
- [ ] Assignment workflow for bookings
- [ ] Contact management
- [ ] Document uploads

## Acceptance Criteria
- [ ] Directory displays correctly
- [ ] Profiles show all information
- [ ] Assignment workflow is intuitive
- [ ] Documents can be uploaded

**Priority:** High | **Estimate:** 3 days | **Week:** 17-18" \
"priority:high,type:feature,module:vendor,module:admin,phase:3-extended" \
"M3: Extended Features"

    create_issue "EP-043" "Create vendor booking integration" \
"## Description
Integrate vendors with the booking system.

## Tasks
- [ ] Assign vendors to events
- [ ] Track vendor confirmations
- [ ] Vendor payment tracking
- [ ] Vendor notifications
- [ ] Vendor calendar integration

## Acceptance Criteria
- [ ] Vendors can be assigned to bookings
- [ ] Confirmations are tracked
- [ ] Payments are recorded
- [ ] Notifications are sent

**Priority:** Medium | **Estimate:** 3 days | **Week:** 17-18" \
"priority:medium,type:feature,module:vendor,module:booking,phase:3-extended" \
"M3: Extended Features"

    create_issue "EP-044" "Create Staff and Task models" \
"## Description
Set up database structure for staff management.

## Tasks
- [ ] Staff profiles with roles and skills
- [ ] Task templates
- [ ] Shift management
- [ ] Availability tracking
- [ ] Performance metrics

## Acceptance Criteria
- [ ] Staff profiles are comprehensive
- [ ] Tasks can be templated
- [ ] Shifts can be managed
- [ ] Availability is trackable

**Priority:** High | **Estimate:** 2 days | **Week:** 19-20" \
"priority:high,type:feature,module:staff,phase:3-extended" \
"M3: Extended Features"

    create_issue "EP-045" "Implement staff scheduling API" \
"## Description
Build REST API endpoints for staff scheduling.

## Tasks
- [ ] Staff availability management
- [ ] Task assignment to events
- [ ] Shift scheduling
- [ ] Conflict detection
- [ ] Schedule optimization

## Acceptance Criteria
- [ ] Availability is manageable
- [ ] Tasks can be assigned
- [ ] Conflicts are detected
- [ ] Schedules are accurate

**Priority:** High | **Estimate:** 3 days | **Week:** 19-20" \
"priority:high,type:feature,module:staff,module:api,phase:3-extended" \
"M3: Extended Features"

    create_issue "EP-046" "Build Staff scheduling interface" \
"## Description
Create the admin interface for staff scheduling.

## Tasks
- [ ] Staff calendar view
- [ ] Event staffing requirements
- [ ] Task checklist per event
- [ ] Drag-drop scheduling
- [ ] Conflict visualization

## Acceptance Criteria
- [ ] Calendar displays schedules
- [ ] Staffing requirements are clear
- [ ] Checklists are functional
- [ ] Drag-drop works smoothly

**Priority:** High | **Estimate:** 4 days | **Week:** 19-20" \
"priority:high,type:feature,module:staff,module:admin,phase:3-extended" \
"M3: Extended Features"

    create_issue "EP-047" "Create mobile-friendly task view" \
"## Description
Build a mobile interface for staff task management.

## Tasks
- [ ] Staff mobile task list
- [ ] Task completion tracking
- [ ] Time logging
- [ ] Photo uploads
- [ ] Push notifications

## Acceptance Criteria
- [ ] Mobile view is responsive
- [ ] Tasks can be completed
- [ ] Time is accurately logged
- [ ] Photos can be uploaded

**Priority:** Medium | **Estimate:** 2 days | **Week:** 19-20" \
"priority:medium,type:feature,module:staff,phase:3-extended" \
"M3: Extended Features"

    create_issue "EP-048" "Implement revenue reports" \
"## Description
Create comprehensive revenue reporting.

## Tasks
- [ ] Daily/weekly/monthly revenue
- [ ] Revenue by venue
- [ ] Revenue by event type
- [ ] Export to Excel
- [ ] Trend analysis

## Acceptance Criteria
- [ ] Reports are accurate
- [ ] Filtering works correctly
- [ ] Export functions properly
- [ ] Trends are visualized

**Priority:** High | **Estimate:** 3 days | **Week:** 21-22" \
"priority:high,type:feature,module:reports,phase:3-extended" \
"M3: Extended Features"

    create_issue "EP-049" "Create booking reports" \
"## Description
Build booking analytics and reporting.

## Tasks
- [ ] Booking volume trends
- [ ] Conversion rates (inquiry → booking)
- [ ] Cancellation analysis
- [ ] Lead time analysis
- [ ] Source tracking

## Acceptance Criteria
- [ ] Trends are accurate
- [ ] Conversion rates calculate correctly
- [ ] Cancellation data is insightful
- [ ] Reports export properly

**Priority:** High | **Estimate:** 2 days | **Week:** 21-22" \
"priority:high,type:feature,module:reports,phase:3-extended" \
"M3: Extended Features"

    create_issue "EP-050" "Build analytics dashboard" \
"## Description
Create a central analytics dashboard.

## Tasks
- [ ] Key metrics overview
- [ ] ApexCharts visualizations
- [ ] Date range selection
- [ ] Comparison periods
- [ ] Dashboard customization

## Acceptance Criteria
- [ ] Metrics are accurate
- [ ] Charts render correctly
- [ ] Date selection works
- [ ] Comparisons are meaningful

**Priority:** High | **Estimate:** 3 days | **Week:** 21-22" \
"priority:high,type:feature,module:reports,module:admin,phase:3-extended" \
"M3: Extended Features"

    create_issue "EP-051" "Implement occupancy reports" \
"## Description
Create venue occupancy reporting.

## Tasks
- [ ] Venue utilization rates
- [ ] Peak booking times
- [ ] Seasonal trends
- [ ] Capacity analysis
- [ ] Forecasting

## Acceptance Criteria
- [ ] Utilization is calculated correctly
- [ ] Peak times are identified
- [ ] Trends are visible
- [ ] Forecasts are reasonable

**Priority:** Medium | **Estimate:** 2 days | **Week:** 21-22" \
"priority:medium,type:feature,module:reports,phase:3-extended" \
"M3: Extended Features"

    create_issue "EP-052" "Create Client Portal authentication" \
"## Description
Set up authentication for the client portal.

## Tasks
- [ ] Client registration and login
- [ ] Profile management
- [ ] Password reset
- [ ] Email verification
- [ ] Session management

## Acceptance Criteria
- [ ] Clients can register and login
- [ ] Profiles can be updated
- [ ] Password reset works
- [ ] Sessions are secure

**Priority:** High | **Estimate:** 2 days | **Week:** 23-24" \
"priority:high,type:feature,module:client-portal,phase:3-extended" \
"M3: Extended Features"

    create_issue "EP-053" "Build Client Portal dashboard" \
"## Description
Create the main client portal dashboard.

## Tasks
- [ ] Upcoming events display
- [ ] Recent activity feed
- [ ] Quick actions
- [ ] Notifications
- [ ] Profile summary

## Acceptance Criteria
- [ ] Events display correctly
- [ ] Activity is accurate
- [ ] Actions work properly
- [ ] Notifications are timely

**Priority:** High | **Estimate:** 2 days | **Week:** 23-24" \
"priority:high,type:feature,module:client-portal,phase:3-extended" \
"M3: Extended Features"

    create_issue "EP-054" "Implement Client booking view" \
"## Description
Create booking detail views for clients.

## Tasks
- [ ] View booking details
- [ ] Download documents
- [ ] Event countdown
- [ ] Timeline view
- [ ] Service details

## Acceptance Criteria
- [ ] Details are comprehensive
- [ ] Documents download correctly
- [ ] Countdown is accurate
- [ ] Timeline is clear

**Priority:** High | **Estimate:** 2 days | **Week:** 23-24" \
"priority:high,type:feature,module:client-portal,phase:3-extended" \
"M3: Extended Features"

    create_issue "EP-055" "Create Client payment features" \
"## Description
Build payment features for the client portal.

## Tasks
- [ ] View payment schedule
- [ ] Payment history
- [ ] Make online payments (preparation for plugin)
- [ ] Download receipts
- [ ] Payment notifications

## Acceptance Criteria
- [ ] Schedule displays correctly
- [ ] History is accurate
- [ ] Payment interface is ready
- [ ] Receipts download properly

**Priority:** High | **Estimate:** 2 days | **Week:** 23-24" \
"priority:high,type:feature,module:client-portal,module:payment,phase:3-extended" \
"M3: Extended Features"

    create_issue "EP-056" "Add Client communication features" \
"## Description
Implement communication tools for clients.

## Tasks
- [ ] Message thread with venue
- [ ] Document sharing
- [ ] Notification preferences
- [ ] Email integration
- [ ] Read receipts

## Acceptance Criteria
- [ ] Messages send and receive
- [ ] Documents can be shared
- [ ] Preferences are respected
- [ ] Notifications work correctly

**Priority:** Medium | **Estimate:** 2 days | **Week:** 23-24" \
"priority:medium,type:feature,module:client-portal,phase:3-extended" \
"M3: Extended Features"

    echo ""
    echo "Phase 3 issues created!"
    echo ""

    # =========================================================================
    # PHASE 4: CUSTOMIZATION (Weeks 25-30) - EP-057 to EP-069
    # =========================================================================
    echo "--- Phase 4: Customization (Weeks 25-30) ---"
    echo ""

    create_issue "EP-057" "Build Settings admin interface" \
"## Description
Create the main settings administration interface.

## Tasks
- [ ] General settings page
- [ ] Currency and localization
- [ ] Booking rules configuration
- [ ] Notification settings
- [ ] System preferences

## Acceptance Criteria
- [ ] Settings can be updated
- [ ] Localization works correctly
- [ ] Booking rules apply
- [ ] Changes persist properly

**Priority:** High | **Estimate:** 3 days | **Week:** 25-26" \
"priority:high,type:feature,module:settings,module:admin,phase:4-customization" \
"M4: Customization Ready"

    create_issue "EP-058" "Implement branding settings" \
"## Description
Create branding customization features.

## Tasks
- [ ] Logo and favicon upload
- [ ] Color scheme customization
- [ ] Business information
- [ ] Social media links
- [ ] Custom footer content

## Acceptance Criteria
- [ ] Logos upload and display
- [ ] Colors apply throughout
- [ ] Information is editable
- [ ] Social links work

**Priority:** High | **Estimate:** 2 days | **Week:** 25-26" \
"priority:high,type:feature,module:settings,phase:4-customization" \
"M4: Customization Ready"

    create_issue "EP-059" "Create email template settings" \
"## Description
Build customizable email templates.

## Tasks
- [ ] Editable email templates
- [ ] Template variables documentation
- [ ] Preview functionality
- [ ] Test email sending
- [ ] Template versioning

## Acceptance Criteria
- [ ] Templates can be edited
- [ ] Variables are documented
- [ ] Preview is accurate
- [ ] Test emails send correctly

**Priority:** Medium | **Estimate:** 3 days | **Week:** 25-26" \
"priority:medium,type:feature,module:settings,phase:4-customization" \
"M4: Customization Ready"

    create_issue "EP-060" "Implement document template settings" \
"## Description
Create customizable document templates.

## Tasks
- [ ] Contract template customization
- [ ] Terms and conditions editor
- [ ] PDF header/footer configuration
- [ ] Template preview
- [ ] Multiple template support

## Acceptance Criteria
- [ ] Templates can be customized
- [ ] Terms are editable
- [ ] PDF renders correctly
- [ ] Preview matches output

**Priority:** Medium | **Estimate:** 2 days | **Week:** 25-26" \
"priority:medium,type:feature,module:settings,phase:4-customization" \
"M4: Customization Ready"

    create_issue "EP-061" "Build Custom Fields admin interface" \
"## Description
Create the admin interface for custom field management.

## Tasks
- [ ] Field type selection (text, number, select, etc.)
- [ ] Validation rules configuration
- [ ] Field ordering
- [ ] Required/optional toggle
- [ ] Conditional visibility

## Acceptance Criteria
- [ ] All field types work
- [ ] Validation is enforced
- [ ] Ordering is preserved
- [ ] Conditionals function correctly

**Priority:** High | **Estimate:** 3 days | **Week:** 27-28" \
"priority:high,type:feature,module:settings,module:admin,phase:4-customization" \
"M4: Customization Ready"

    create_issue "EP-062" "Implement CustomFieldService" \
"## Description
Create the service for managing custom fields.

## Tasks
- [ ] Field retrieval by entity type
- [ ] Value storage and retrieval
- [ ] Validation integration
- [ ] Default value handling
- [ ] Field caching

## Acceptance Criteria
- [ ] Fields retrieve correctly
- [ ] Values store and retrieve
- [ ] Validation works
- [ ] Defaults apply properly

**Priority:** High | **Estimate:** 2 days | **Week:** 27-28" \
"priority:high,type:feature,module:core,phase:4-customization" \
"M4: Customization Ready"

    create_issue "EP-063" "Integrate custom fields with forms" \
"## Description
Add custom fields to existing forms.

## Tasks
- [ ] Dynamic form field rendering
- [ ] Custom field display in views
- [ ] Search integration
- [ ] Export integration
- [ ] API support

## Acceptance Criteria
- [ ] Fields render correctly
- [ ] Display is consistent
- [ ] Search includes custom fields
- [ ] Export includes values

**Priority:** High | **Estimate:** 3 days | **Week:** 27-28" \
"priority:high,type:feature,module:core,phase:4-customization" \
"M4: Customization Ready"

    create_issue "EP-064" "Implement ThemeService" \
"## Description
Create a service for theme management.

## Tasks
- [ ] Theme discovery and loading
- [ ] Active theme switching
- [ ] Theme asset management
- [ ] Theme configuration
- [ ] Theme inheritance

## Acceptance Criteria
- [ ] Themes are discovered
- [ ] Switching works correctly
- [ ] Assets load properly
- [ ] Configuration applies

**Priority:** Medium | **Estimate:** 2 days | **Week:** 29-30" \
"priority:medium,type:feature,module:theme,phase:4-customization" \
"M4: Customization Ready"

    create_issue "EP-065" "Create default and elegant themes" \
"## Description
Build the bundled theme options.

## Tasks
- [ ] Default theme with Tailwind styles
- [ ] Elegant theme for luxury venues
- [ ] Theme configuration files
- [ ] Theme screenshots
- [ ] Theme documentation

## Acceptance Criteria
- [ ] Default theme is complete
- [ ] Elegant theme is polished
- [ ] Configuration is documented
- [ ] Both themes work correctly

**Priority:** Medium | **Estimate:** 3 days | **Week:** 29-30" \
"priority:medium,type:feature,module:theme,phase:4-customization" \
"M4: Customization Ready"

    create_issue "EP-066" "Build theme management interface" \
"## Description
Create the admin interface for theme management.

## Tasks
- [ ] Theme selection
- [ ] Theme preview
- [ ] Color customization override
- [ ] Reset to defaults
- [ ] Theme installation

## Acceptance Criteria
- [ ] Themes can be selected
- [ ] Preview is accurate
- [ ] Overrides work correctly
- [ ] Reset functions properly

**Priority:** Low | **Estimate:** 2 days | **Week:** 29-30" \
"priority:low,type:feature,module:theme,module:admin,phase:4-customization" \
"M4: Customization Ready"

    create_issue "EP-067" "Implement PluginService" \
"## Description
Create the plugin system infrastructure.

## Tasks
- [ ] Plugin discovery and loading
- [ ] Hook system for extensibility
- [ ] Plugin settings storage
- [ ] Plugin activation/deactivation
- [ ] Dependency management

## Acceptance Criteria
- [ ] Plugins are discovered
- [ ] Hooks fire correctly
- [ ] Settings persist
- [ ] Activation works properly

**Priority:** Medium | **Estimate:** 3 days | **Week:** 29-30" \
"priority:medium,type:feature,module:core,phase:4-customization" \
"M4: Customization Ready"

    create_issue "EP-068" "Create SMS Gateway plugin" \
"## Description
Build an SMS notification plugin.

## Tasks
- [ ] Twilio/Nexmo integration
- [ ] Booking notification SMS
- [ ] Payment reminder SMS
- [ ] Template management
- [ ] Delivery tracking

## Acceptance Criteria
- [ ] SMS sends successfully
- [ ] Templates are customizable
- [ ] Delivery is tracked
- [ ] Multiple providers work

**Priority:** Medium | **Estimate:** 3 days | **Week:** 29-30" \
"priority:medium,type:feature,module:core,phase:4-customization" \
"M4: Customization Ready"

    create_issue "EP-069" "Create Payment Gateway plugin" \
"## Description
Build online payment processing plugin.

## Tasks
- [ ] Stripe integration
- [ ] PayPal integration
- [ ] Online payment processing
- [ ] Payment webhooks
- [ ] Refund handling

## Acceptance Criteria
- [ ] Stripe payments work
- [ ] PayPal payments work
- [ ] Webhooks process correctly
- [ ] Refunds are handled

**Priority:** High | **Estimate:** 4 days | **Week:** 29-30" \
"priority:high,type:feature,module:payment,phase:4-customization" \
"M4: Customization Ready"

    echo ""
    echo "Phase 4 issues created!"
    echo ""

    # =========================================================================
    # PHASE 5: LAUNCH (Weeks 31-36) - EP-070 to EP-083
    # =========================================================================
    echo "--- Phase 5: Launch (Weeks 31-36) ---"
    echo ""

    create_issue "EP-070" "Write unit tests for services" \
"## Description
Create comprehensive unit tests for core services.

## Tasks
- [ ] SettingsService tests
- [ ] PricingService tests
- [ ] AvailabilityService tests
- [ ] QuotationService tests
- [ ] Test coverage reporting

## Acceptance Criteria
- [ ] All services have tests
- [ ] Edge cases are covered
- [ ] Coverage is above 80%
- [ ] Tests run in CI

**Priority:** Critical | **Estimate:** 4 days | **Week:** 31-32" \
"priority:critical,type:test,module:core,phase:5-launch" \
"M5: Production Launch"

    create_issue "EP-071" "Write feature tests for API" \
"## Description
Create feature tests for all API endpoints.

## Tasks
- [ ] Authentication tests
- [ ] Venue API tests
- [ ] Booking workflow tests
- [ ] Payment tests
- [ ] Error handling tests

## Acceptance Criteria
- [ ] All endpoints are tested
- [ ] Validation is tested
- [ ] Auth is verified
- [ ] Errors return correctly

**Priority:** Critical | **Estimate:** 4 days | **Week:** 31-32" \
"priority:critical,type:test,module:api,phase:5-launch" \
"M5: Production Launch"

    create_issue "EP-072" "Write feature tests for admin interface" \
"## Description
Create browser tests for the admin interface.

## Tasks
- [ ] Browser tests with Laravel Dusk
- [ ] Form submission tests
- [ ] Workflow tests
- [ ] Navigation tests
- [ ] Responsive tests

## Acceptance Criteria
- [ ] All major flows are tested
- [ ] Forms submit correctly
- [ ] Workflows complete
- [ ] Tests pass consistently

**Priority:** High | **Estimate:** 3 days | **Week:** 31-32" \
"priority:high,type:test,module:admin,phase:5-launch" \
"M5: Production Launch"

    create_issue "EP-073" "Perform security audit" \
"## Description
Conduct a comprehensive security review.

## Tasks
- [ ] OWASP vulnerability check
- [ ] SQL injection prevention
- [ ] XSS prevention
- [ ] CSRF protection verification
- [ ] Authentication security review

## Acceptance Criteria
- [ ] No critical vulnerabilities
- [ ] All inputs are sanitized
- [ ] CSRF tokens are validated
- [ ] Auth is secure

**Priority:** Critical | **Estimate:** 2 days | **Week:** 31-32" \
"priority:critical,type:test,module:core,phase:5-launch" \
"M5: Production Launch"

    create_issue "EP-074" "Create API documentation" \
"## Description
Build comprehensive API documentation.

## Tasks
- [ ] OpenAPI/Swagger specification
- [ ] Endpoint descriptions
- [ ] Request/response examples
- [ ] Authentication guide
- [ ] Error code reference

## Acceptance Criteria
- [ ] All endpoints documented
- [ ] Examples are accurate
- [ ] Auth is explained
- [ ] Docs are accessible

**Priority:** High | **Estimate:** 3 days | **Week:** 33-34" \
"priority:high,type:docs,module:api,phase:5-launch" \
"M5: Production Launch"

    create_issue "EP-075" "Write admin user guide" \
"## Description
Create documentation for admin users.

## Tasks
- [ ] Feature documentation
- [ ] Step-by-step tutorials
- [ ] FAQ section
- [ ] Troubleshooting guide
- [ ] Best practices

## Acceptance Criteria
- [ ] All features documented
- [ ] Tutorials are clear
- [ ] FAQs are comprehensive
- [ ] Troubleshooting helps

**Priority:** High | **Estimate:** 3 days | **Week:** 33-34" \
"priority:high,type:docs,module:admin,phase:5-launch" \
"M5: Production Launch"

    create_issue "EP-076" "Create developer documentation" \
"## Description
Build documentation for developers.

## Tasks
- [ ] Architecture overview
- [ ] Custom field development
- [ ] Plugin development guide
- [ ] Theme development guide
- [ ] Contributing guidelines

## Acceptance Criteria
- [ ] Architecture is explained
- [ ] Extension guides are complete
- [ ] Examples are provided
- [ ] Contributing is clear

**Priority:** Medium | **Estimate:** 3 days | **Week:** 33-34" \
"priority:medium,type:docs,module:core,phase:5-launch" \
"M5: Production Launch"

    create_issue "EP-077" "Prepare installation guide" \
"## Description
Create comprehensive installation documentation.

## Tasks
- [ ] Requirements documentation
- [ ] Step-by-step installation
- [ ] Environment configuration
- [ ] Troubleshooting guide
- [ ] Upgrade instructions

## Acceptance Criteria
- [ ] Requirements are clear
- [ ] Steps are accurate
- [ ] Configuration is documented
- [ ] Troubleshooting helps

**Priority:** High | **Estimate:** 1 day | **Week:** 33-34" \
"priority:high,type:docs,module:core,phase:5-launch" \
"M5: Production Launch"

    create_issue "EP-078" "Set up production infrastructure" \
"## Description
Provision and configure production servers.

## Tasks
- [ ] Server provisioning
- [ ] Database setup
- [ ] Redis configuration
- [ ] SSL certificates
- [ ] Firewall configuration

## Acceptance Criteria
- [ ] Servers are provisioned
- [ ] Database is configured
- [ ] SSL is active
- [ ] Security is in place

**Priority:** Critical | **Estimate:** 2 days | **Week:** 35-36" \
"priority:critical,type:devops,module:core,phase:5-launch" \
"M5: Production Launch"

    create_issue "EP-079" "Configure production deployment" \
"## Description
Set up automated production deployment.

## Tasks
- [ ] Environment variables
- [ ] Queue workers (Supervisor)
- [ ] Scheduled tasks (Cron)
- [ ] Backup configuration
- [ ] Deployment scripts

## Acceptance Criteria
- [ ] Environment is configured
- [ ] Queues process correctly
- [ ] Schedules run on time
- [ ] Backups are automated

**Priority:** Critical | **Estimate:** 2 days | **Week:** 35-36" \
"priority:critical,type:devops,module:core,phase:5-launch" \
"M5: Production Launch"

    create_issue "EP-080" "Perform performance optimization" \
"## Description
Optimize application performance.

## Tasks
- [ ] Query optimization
- [ ] Cache configuration
- [ ] Asset optimization
- [ ] CDN setup
- [ ] Database indexing

## Acceptance Criteria
- [ ] Queries are efficient
- [ ] Caching improves speed
- [ ] Assets load quickly
- [ ] CDN delivers content

**Priority:** High | **Estimate:** 2 days | **Week:** 35-36" \
"priority:high,type:enhancement,module:core,phase:5-launch" \
"M5: Production Launch"

    create_issue "EP-081" "Create demo tenant" \
"## Description
Set up a demonstration tenant with sample data.

## Tasks
- [ ] Sample data population
- [ ] Demo credentials
- [ ] Reset mechanism
- [ ] Tour/onboarding
- [ ] Demo limitations

## Acceptance Criteria
- [ ] Demo has realistic data
- [ ] Credentials work
- [ ] Reset is automated
- [ ] Tour guides users

**Priority:** Medium | **Estimate:** 1 day | **Week:** 35-36" \
"priority:medium,type:feature,module:core,phase:5-launch" \
"M5: Production Launch"

    create_issue "EP-082" "Conduct UAT" \
"## Description
Perform comprehensive user acceptance testing.

## Tasks
- [ ] End-to-end workflow testing
- [ ] Multi-tenant testing
- [ ] Mobile responsiveness
- [ ] Cross-browser testing
- [ ] Performance testing

## Acceptance Criteria
- [ ] All workflows pass
- [ ] Tenants are isolated
- [ ] Mobile works correctly
- [ ] All browsers supported

**Priority:** Critical | **Estimate:** 3 days | **Week:** 35-36" \
"priority:critical,type:test,module:core,phase:5-launch" \
"M5: Production Launch"

    create_issue "EP-083" "Launch preparation" \
"## Description
Complete final launch preparations.

## Tasks
- [ ] Final security review
- [ ] Monitoring setup (logs, alerts)
- [ ] Support procedures
- [ ] Go-live checklist
- [ ] Rollback plan

## Acceptance Criteria
- [ ] Security is verified
- [ ] Monitoring is active
- [ ] Support is ready
- [ ] Checklist is complete

**Priority:** Critical | **Estimate:** 2 days | **Week:** 35-36" \
"priority:critical,type:devops,module:core,phase:5-launch" \
"M5: Production Launch"

    echo ""
    echo "Phase 5 issues created!"
    echo ""
}

# Main execution
echo "========================================"
echo "EventPro GitHub Issues Creation Script"
echo "========================================"
echo ""

if [ "$CREATE_LABELS" = true ]; then
    create_labels
fi

if [ "$CREATE_MILESTONES" = true ]; then
    create_milestones
fi

if [ "$CREATE_ISSUES" = true ]; then
    create_issues
fi

echo "========================================"
echo "Script completed!"
echo "========================================"
echo ""
echo "Summary:"
echo "  - Labels created: $([ "$CREATE_LABELS" = true ] && echo "Yes" || echo "No")"
echo "  - Milestones created: $([ "$CREATE_MILESTONES" = true ] && echo "Yes" || echo "No")"
echo "  - Issues created: $([ "$CREATE_ISSUES" = true ] && echo "Yes" || echo "No")"
echo ""
echo "Total issues: 83 (EP-001 to EP-083)"

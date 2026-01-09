# GitHub Configuration

This directory contains GitHub-specific configuration for the EventPro project.

## Issue Templates

Located in `.github/ISSUE_TEMPLATE/`:

| Template | Purpose |
|----------|---------|
| `task.yml` | Development roadmap tasks (EP-001 to EP-083) |
| `feature_request.yml` | New feature requests |
| `bug_report.yml` | Bug reports |

### Using Templates

When creating a new issue on GitHub, you'll be presented with template options. Select the appropriate template for your issue type.

## Issue Creation Script

The script `.github/scripts/create-issues.sh` can bulk-create all 83 roadmap issues using the GitHub CLI.

### Prerequisites

1. Install [GitHub CLI](https://cli.github.com/)
2. Authenticate: `gh auth login`

### Usage

```bash
# Make the script executable
chmod +x .github/scripts/create-issues.sh

# Create all labels, milestones, and issues
./.github/scripts/create-issues.sh

# Create only labels
./.github/scripts/create-issues.sh --labels-only

# Create only milestones
./.github/scripts/create-issues.sh --milestones-only

# Create only issues (assumes labels and milestones exist)
./.github/scripts/create-issues.sh --issues-only

# Dry run (shows what would be created without actually creating)
./.github/scripts/create-issues.sh --dry-run
```

### What Gets Created

**Labels:**
- Priority: `priority:critical`, `priority:high`, `priority:medium`, `priority:low`
- Type: `type:feature`, `type:enhancement`, `type:bug`, `type:docs`, `type:test`, `type:devops`
- Module: `module:core`, `module:venue`, `module:booking`, `module:payment`, `module:client-portal`, `module:admin`, `module:api`, `module:vendor`, `module:staff`, `module:reports`, `module:settings`, `module:theme`
- Phase: `phase:1-foundation`, `phase:2-core-modules`, `phase:3-extended`, `phase:4-customization`, `phase:5-launch`

**Milestones:**
- M1: Foundation Complete (Week 6)
- M2: Core Booking Flow (Week 16)
- M3: Extended Features (Week 24)
- M4: Customization Ready (Week 30)
- M5: Production Launch (Week 36)

**Issues:**
- EP-001 to EP-083 (83 total issues)
- Organized across 5 development phases
- Each with detailed descriptions, tasks, and acceptance criteria

## Issues Data File

The file `.github/ISSUES_DATA.json` contains all issue data in a structured JSON format for:
- Programmatic access
- Import into project management tools
- Manual issue creation reference

### JSON Structure

```json
{
  "metadata": { ... },
  "labels": [ ... ],
  "milestones": [ ... ],
  "issues": [
    {
      "id": "EP-001",
      "title": "...",
      "phase": 1,
      "week": "1-2",
      "priority": "critical",
      "type": "feature",
      "modules": ["core"],
      "milestone": "M1: Foundation Complete",
      "estimate": "2 days",
      "tasks": [ ... ]
    },
    ...
  ]
}
```

## Development Phases

| Phase | Weeks | Issues | Focus |
|-------|-------|--------|-------|
| 1 | 1-6 | EP-001 to EP-017 | Foundation (infrastructure, database, core services) |
| 2 | 7-16 | EP-018 to EP-039 | Core Modules (venue, booking, payment) |
| 3 | 17-24 | EP-040 to EP-056 | Extended Modules (vendor, staff, reports, client portal) |
| 4 | 25-30 | EP-057 to EP-069 | Customization (settings, themes, plugins) |
| 5 | 31-36 | EP-070 to EP-083 | Launch (testing, docs, deployment) |

## Manual Issue Creation

If you prefer to create issues manually:

1. Go to the repository's Issues tab
2. Click "New Issue"
3. Select the appropriate template
4. Fill in the required fields
5. Reference the `ISSUES.md` file or `ISSUES_DATA.json` for detailed task information

## Related Files

- `/ISSUES.md` - Complete development roadmap with all 83 issues detailed
- `/.github/ISSUES_DATA.json` - Structured JSON data for all issues
- `/.github/scripts/create-issues.sh` - Bulk issue creation script

# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview
This is the official documentation website for Statamic 5, a Laravel-based flat-file CMS. The docs are built using Statamic itself, creating a self-documenting system.

## Common Development Commands

### Initial Setup
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
npm run dev
```

### Frontend Development
- `npm run dev` - Start Vite development server with hot reloading
- `npm run build` - Build production assets

### Backend Development
- `php artisan serve` - Run local development server
- `php please stache:clear` - Clear Statamic's content cache (required after content changes)
- `php please stache:refresh` - Clear and rebuild the entire cache
- `php please ssg:generate` - Generate static site

### Testing
- `php artisan test` - Run PHPUnit tests

## Architecture Overview

### Technology Stack
- **Backend**: Laravel 10.x with Statamic 5.x
- **Frontend**: Vite, Alpine.js, Vue 2 (for specific components)
- **CSS**: PostCSS with custom styles
- **Search**: Meilisearch for site search, DocSearch for documentation
- **Syntax Highlighting**: Torchlight

### Key Directories
- `content/collections/` - Documentation markdown files organized by section
- `app/Markdown/` - Custom markdown extensions (Hints.php, Tabs.php)
- `app/Tags/` - Custom Statamic tags for documentation features
- `resources/views/` - Antlers templates for rendering pages
- `resources/css/` - Source CSS files processed by PostCSS
- `resources/js/` - JavaScript source files

### Content Structure
Documentation is organized into collections:
- `docs/` - Main documentation articles
- `extending-docs/` - Extension and addon development
- `fieldtypes/`, `modifiers/`, `tags/`, `variables/` - Reference documentation

### Custom Markdown Features
The documentation uses custom markdown extensions:
- **Hints**: `:::tip`, `:::warning`, `:::best-practice`, `:::watch` blocks
- **Tabs**: Code blocks with multiple language/framework examples

### Development Workflow
1. Content changes are made in `content/collections/`
2. Clear Stache cache after content changes: `php please stache:clear`
3. Frontend changes require `npm run dev` for hot reloading
4. Custom features are implemented via Statamic's extension points (Tags, Modifiers, Markdown)

## Important Conventions
- Documentation files use markdown with Statamic's frontmatter
- All code examples use Torchlight for syntax highlighting
- Navigation structure is managed in `content/navigation/`
- Search indexes must be updated after significant content changes
- The site can be deployed as static HTML using SSG

# Statamic Documentation CLI Search Commands

This documentation site includes several CLI commands to search the documentation from your terminal.

## Available Commands

### 1. Basic Search Command
```bash
php artisan docs:search "your search query"
```

**Options:**
- `--limit=N` - Number of results to display (default: 10)
- `--collection=name` - Filter by collection (docs, tags, fieldtypes, modifiers, variables)
- `--json` - Output results as JSON

**Examples:**
```bash
# Search for cache-related documentation
php artisan docs:search "cache" --limit=5

# Search only in tags collection
php artisan docs:search "form" --collection=tags

# Get JSON output for programmatic use
php artisan docs:search "collection" --json
```

### 2. Simple Search Command
```bash
php artisan docs:search:simple "your search query"
```

This command uses a simple content-based search that doesn't require Meilisearch. It searches through:
- Page titles
- Page content
- Introduction text

**Options:** Same as the basic search command

**Example:**
```bash
php artisan docs:search:simple "cache" --limit=5
```

### 3. Interactive Search Command
```bash
php artisan docs:search:interactive
```

This command provides an interactive search experience where you can:
- Enter search queries interactively
- Select from search results
- Automatically open selected results in your browser

**Options:**
- `--limit=N` - Number of results to display (default: 20)
- `--open` - Automatically open the first result

**Examples:**
```bash
# Interactive search
php artisan docs:search:interactive

# Search with automatic opening of first result
php artisan docs:search:interactive "collections" --open
```

## Setup Requirements

### For Meilisearch-based Search (docs:search)
1. Install and run Meilisearch:
   ```bash
   # Install Meilisearch (macOS)
   brew install meilisearch
   
   # Run Meilisearch
   meilisearch --http-addr 127.0.0.1:7700
   ```

2. Update your `.env` file:
   ```
   SEARCH_DRIVER=meilisearch
   MEILISEARCH_HOST=http://127.0.0.1:7700
   MEILISEARCH_KEY=your-master-key
   ```

3. Build the search index:
   ```bash
   php please search:update --all
   ```

### For Local Search (docs:search:simple)
The simple search command works out of the box without any additional setup. It directly queries the Statamic entries.

## Search Tips

1. **Use specific keywords**: Instead of generic terms, use specific feature names
2. **Try different variations**: If "delete cache" doesn't work, try "clear cache" or "stache"
3. **Filter by collection**: Use `--collection` to narrow down results to specific documentation types
4. **Use JSON output**: Great for integrating with other tools or scripts

## Troubleshooting

### "Meilisearch is not available" Error
- Make sure Meilisearch is running: `meilisearch --http-addr 127.0.0.1:7700`
- Or use the simple search command: `php artisan docs:search:simple "query"`
- Or set `SEARCH_DRIVER=local` in your `.env` file

### "No results found"
- Try using broader search terms
- Check if the search index is built: `php please search:update --all`
- Use the simple search command as a fallback

### Browser doesn't open (Interactive search)
- The command will display the URL if it can't open your browser automatically
- Copy and paste the URL manually into your browser
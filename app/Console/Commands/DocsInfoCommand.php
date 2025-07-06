<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DocsInfoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Information about Statamic documentation CLI commands (for Claude Code)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->line('');
        $this->info('Statamic Documentation CLI Commands');
        $this->line('====================================');
        $this->line('');
        $this->line('This is a guide for Claude Code to understand how to search and retrieve');
        $this->line('documentation from the Statamic docs. These commands are designed to help');
        $this->line('AI assistants quickly find and read documentation content.');
        $this->line('');

        $this->info('Available Commands:');
        $this->line('');

        // docs:search command
        $this->comment('1. docs:search - Search through documentation using Meilisearch');
        $this->line('   Usage: php artisan docs:search {query} [options]');
        $this->line('   ');
        $this->line('   Options:');
        $this->line('   --limit=10              Number of results to display (default: 10)');
        $this->line('   --collection=           Filter by collection (docs, tags, fieldtypes, modifiers, variables, extending_docs)');
        $this->line('   --json                  Output results as JSON');
        $this->line('   ');
        $this->line('   Examples:');
        $this->line('   php artisan docs:search "collection tag"');
        $this->line('   php artisan docs:search "assets" --collection=tags --limit=5');
        $this->line('   php artisan docs:search "routing" --json');
        $this->line('');

        // docs:get command
        $this->comment('2. docs:get - Retrieve complete documentation entry by ID, title, or slug');
        $this->line('   Usage: php artisan docs:get {identifier} [options]');
        $this->line('   ');
        $this->line('   Options:');
        $this->line('   --collection=           Filter by collection');
        $this->line('   --yaml                  Output full entry data as YAML (recommended for Claude Code)');
        $this->line('   --json                  Output as JSON');
        $this->line('   --full                  Show full content (default shows truncated)');
        $this->line('   ');
        $this->line('   Examples:');
        $this->line('   php artisan docs:get 045a6e54-c792-483a-a109-f07251a79e47 --yaml');
        $this->line('   php artisan docs:get "collection" --json');
        $this->line('   php artisan docs:get collections --full');
        $this->line('');

        $this->info('Workflow for Claude Code:');
        $this->line('');
        $this->line('1. First, search for relevant documentation:');
        $this->line('   php artisan docs:search "your search term" --json');
        $this->line('   ');
        $this->line('2. From the search results, note the ID of the entry you want to read');
        $this->line('   ');
        $this->line('3. Retrieve the full content using the ID:');
        $this->line('   php artisan docs:get {id} --yaml');
        $this->line('   ');
        $this->line('   The --yaml flag provides the complete entry data including:');
        $this->line('   - Full markdown content');
        $this->line('   - Metadata (title, intro, description)');
        $this->line('   - Parameters and variables (for tags/modifiers)');
        $this->line('   - Related entries');
        $this->line('   - Collection and blueprint information');
        $this->line('');

        $this->info('Collections Available:');
        $this->line('');
        $this->line('- docs           Main documentation articles');
        $this->line('- tags           Tag reference documentation');
        $this->line('- fieldtypes     Fieldtype reference documentation');
        $this->line('- modifiers      Modifier reference documentation');
        $this->line('- variables      Variable reference documentation');
        $this->line('- extending_docs Extension and addon development documentation');
        $this->line('');

        $this->info('Tips for Claude Code:');
        $this->line('');
        $this->line('- Use --json flag with docs:search for easier parsing');
        $this->line('- Use --yaml flag with docs:get to retrieve complete documentation');
        $this->line('- Entry IDs are UUIDs and provide the most reliable way to retrieve specific docs');
        $this->line('- You can also search by slug (URL-friendly name) or exact title');
        $this->line('- The search supports partial matches and will return relevant results');
        $this->line('');

        return Command::SUCCESS;
    }
}

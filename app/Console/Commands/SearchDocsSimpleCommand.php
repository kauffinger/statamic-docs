<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Statamic\Facades\Entry;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\Table;

class SearchDocsSimpleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:search:simple
                            {query : The search query}
                            {--limit=10 : Number of results to display}
                            {--collection= : Filter by collection (docs, tags, fieldtypes, etc.)}
                            {--json : Output results as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search Statamic documentation using simple content search';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = $this->argument('query');
        $limit = (int) $this->option('limit');
        $collectionFilter = $this->option('collection');

        $this->info("Searching for: \"{$query}\"");
        $this->newLine();

        // Get all documentation collections
        $collections = ['docs', 'tags', 'fieldtypes', 'modifiers', 'variables', 'extending_docs'];

        if ($collectionFilter && in_array($collectionFilter, $collections)) {
            $collections = [$collectionFilter];
        }

        $results = collect();

        // Search through each collection
        foreach ($collections as $collection) {
            $entries = Entry::query()
                ->where('collection', $collection)
                ->get()
                ->filter(function ($entry) use ($query) {
                    // Search in title
                    if (Str::contains(strtolower($entry->get('title', '')), strtolower($query))) {
                        return true;
                    }

                    // Search in content
                    if (Str::contains(strtolower($entry->get('content', '')), strtolower($query))) {
                        return true;
                    }

                    // Search in intro
                    if (Str::contains(strtolower($entry->get('intro', '')), strtolower($query))) {
                        return true;
                    }

                    return false;
                })
                ->map(function ($entry) use ($query) {
                    // Calculate a simple relevance score
                    $title = $entry->get('title', '');
                    $content = $entry->get('content', '');
                    $intro = $entry->get('intro', '');

                    $score = 0;

                    // Title matches are worth more
                    if (Str::contains(strtolower($title), strtolower($query))) {
                        $score += 10;
                        if (strtolower($title) === strtolower($query)) {
                            $score += 20; // Exact match bonus
                        }
                    }

                    // Intro matches
                    if (Str::contains(strtolower($intro), strtolower($query))) {
                        $score += 5;
                    }

                    // Content matches
                    $contentMatches = substr_count(strtolower($content), strtolower($query));
                    $score += min($contentMatches, 5); // Cap at 5 to prevent spam

                    $entry->set('search_score', $score);

                    return $entry;
                });

            $results = $results->merge($entries);
        }

        // Sort by relevance and limit
        $results = $results
            ->sortByDesc('search_score')
            ->take($limit);

        if ($results->isEmpty()) {
            $this->warn('No results found.');
            return Command::SUCCESS;
        }

        if ($this->option('json')) {
            $this->outputJson($results);
        } else {
            $this->outputTable($results);
        }

        if ($results->count() >= $limit) {
            $this->newLine();
            $this->comment("Showing top {$limit} results. Use --limit to see more.");
        }

        return Command::SUCCESS;
    }

    /**
     * Output results as a formatted table.
     */
    protected function outputTable($results)
    {
        $rows = [];

        foreach ($results as $entry) {
            $title = $entry->get('title', 'Untitled');
            $collection = $entry->collection()->title();
            $url = $entry->absoluteUrl();

            // Get a snippet of content or intro
            $snippet = $entry->get('intro', '');
            if (!$snippet) {
                $content = strip_tags($entry->get('content', ''));
                $snippet = $this->getSnippet($content, 240);
            } else {
                $snippet = $this->getSnippet($snippet, 240);
            }

            $rows[] = [
                $title,
                $collection,
                $snippet,
                $url
            ];
        }

        $table = new Table($this->output);
        $table->setHeaders(['Title', 'Collection', 'Snippet', 'URL']);
        $table->setRows($rows);
        $table->setStyle('box');
        $table->render();
    }

    /**
     * Output results as JSON.
     */
    protected function outputJson($results)
    {
        $data = [];

        foreach ($results as $entry) {
            $data[] = [
                'title' => $entry->get('title', 'Untitled'),
                'collection' => $entry->collection()->handle(),
                'url' => $entry->absoluteUrl(),
                'uri' => $entry->uri(),
                'intro' => $entry->get('intro'),
                'score' => $entry->get('search_score'),
                'id' => $entry->id(),
            ];
        }

        $this->line(json_encode([
            'query' => $this->argument('query'),
            'count' => count($data),
            'results' => $data
        ], JSON_PRETTY_PRINT));
    }

    /**
     * Get a snippet of text.
     */
    protected function getSnippet($text, $length = 100)
    {
        $text = preg_replace('/\s+/', ' ', trim($text));

        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length) . '...';
    }
}

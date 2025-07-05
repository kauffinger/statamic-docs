<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Statamic\Facades\Search;
use Statamic\Search\Result;
use Symfony\Component\Console\Helper\Table;
use Meilisearch\Exceptions\CommunicationException;

class SearchDocsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:search
                            {query : The search query}
                            {--limit=10 : Number of results to display}
                            {--collection= : Filter by collection (docs, tags, fieldtypes, etc.)}
                            {--json : Output results as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search the Statamic documentation from the command line';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = $this->argument('query');
        $limit = (int) $this->option('limit');
        $collection = $this->option('collection');

        $this->info("Searching for: \"{$query}\"");
        $this->newLine();

        try {
            // Perform the search
            $searchQuery = Search::index('default')->search($query);

            if ($collection) {
                $searchQuery->where('collection', $collection);
            }

            $results = $searchQuery->get();

            if ($results->isEmpty()) {
                $this->warn('No results found.');
                return Command::SUCCESS;
            }

            // Limit results
            $results = $results->take($limit);

            if ($this->option('json')) {
                $this->outputJson($results);
            } else {
                $this->outputTable($results);
            }

            if ($results->count() >= $limit) {
                $this->newLine();
                $this->comment("Showing top {$limit} results. Use --limit to see more.");
            }

        } catch (\Meilisearch\Exceptions\CommunicationException $e) {
            $this->error('Meilisearch is not available. Make sure Meilisearch is running or set SEARCH_DRIVER=local in your .env file.');
            $this->comment('You can start Meilisearch with: meilisearch --http-addr 127.0.0.1:7700');
            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error('Search failed: ' . $e->getMessage());

            // Check if we're using the local driver
            if (config('statamic.search.driver') === 'local') {
                $this->comment('Using local search driver. Make sure the search index is built:');
                $this->comment('php please search:update --all');
            }

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Output results as a formatted table.
     */
    protected function outputTable($results)
    {
        $rows = [];

        foreach ($results as $result) {
            $entry = $result->getSearchable();

            $title = $entry->get('title', 'Untitled');
            $collection = $entry->collection()->title();
            $url = $entry->absoluteUrl();

            // Get a snippet of content
            $content = strip_tags($entry->augmentedValue('content')->value() ?? '');
            $snippet = $this->getSnippet($content, 80);

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

        foreach ($results as $result) {
            $entry = $result->getSearchable();

            $data[] = [
                'title' => $entry->get('title', 'Untitled'),
                'collection' => $entry->collection()->handle(),
                'url' => $entry->absoluteUrl(),
                'uri' => $entry->uri(),
                'content_snippet' => $this->getSnippet(
                    strip_tags($entry->augmentedValue('content')->value() ?? ''),
                    200
                ),
                'intro' => $entry->get('intro'),
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

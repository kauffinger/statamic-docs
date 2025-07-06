<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Statamic\Facades\Entry;
use Statamic\Facades\YAML;
use Symfony\Component\Console\Helper\Table;

class GetDocsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:get
                            {identifier : The entry ID, title, or slug}
                            {--collection= : Filter by collection (docs, tags, fieldtypes, etc.)}
                            {--yaml : Output full entry data as YAML}
                            {--json : Output result as JSON}
                            {--full : Show full content (by default, content is truncated)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get detailed information about a specific documentation entry';

    /**
     * Available collections
     *
     * @var array
     */
    protected $collections = ['docs', 'tags', 'fieldtypes', 'modifiers', 'variables', 'extending_docs', 'repositories'];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $identifier = $this->argument('identifier');
        $collectionFilter = $this->option('collection');

        $this->info("Searching for: \"{$identifier}\"");
        $this->newLine();

        // Determine which collections to search
        $searchCollections = $this->collections;
        if ($collectionFilter && in_array($collectionFilter, $this->collections)) {
            $searchCollections = [$collectionFilter];
        }

        // Try to find the entry
        $entry = $this->findEntry($identifier, $searchCollections);

        if (! $entry) {
            $this->error('Entry not found.');

            // Try fuzzy matching
            $suggestions = $this->findSimilarEntries($identifier, $searchCollections);
            if ($suggestions->isNotEmpty()) {
                $this->newLine();
                $this->comment('Did you mean one of these?');
                foreach ($suggestions->take(5) as $suggestion) {
                    $this->line(" - {$suggestion->get('title')} (ID: {$suggestion->id()})");
                }
            }

            return Command::FAILURE;
        }

        // Output the entry data
        if ($this->option('yaml')) {
            $this->outputYaml($entry);
        } elseif ($this->option('json')) {
            $this->outputJson($entry);
        } else {
            $this->outputTable($entry);
        }

        return Command::SUCCESS;
    }

    /**
     * Find an entry by ID, title, or slug
     */
    protected function findEntry($identifier, $collections)
    {
        // First try exact ID match
        $entry = Entry::find($identifier);
        if ($entry && in_array($entry->collection()->handle(), $collections)) {
            return $entry;
        }

        // Then try exact matches on title or slug
        foreach ($collections as $collection) {
            // Try by exact title
            $entry = Entry::query()
                ->where('collection', $collection)
                ->where('title', $identifier)
                ->first();

            if ($entry) {
                return $entry;
            }

            // Try by slug
            $entry = Entry::query()
                ->where('collection', $collection)
                ->where('slug', $identifier)
                ->first();

            if ($entry) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Find similar entries using fuzzy matching
     */
    protected function findSimilarEntries($identifier, $collections)
    {
        $allEntries = collect();

        foreach ($collections as $collection) {
            $entries = Entry::query()
                ->where('collection', $collection)
                ->get();
            $allEntries = $allEntries->merge($entries);
        }

        return $allEntries
            ->map(function ($entry) use ($identifier) {
                $titleDistance = levenshtein(strtolower($identifier), strtolower($entry->get('title', '')));
                $slugDistance = levenshtein(strtolower($identifier), strtolower($entry->slug()));
                $entry->set('distance', min($titleDistance, $slugDistance));

                return $entry;
            })
            ->sortBy('distance')
            ->filter(function ($entry) {
                return $entry->get('distance') <= 5;
            });
    }

    /**
     * Output entry as YAML
     */
    protected function outputYaml($entry)
    {
        // Get all entry data
        $data = $entry->toArray();

        // Include augmented values
        $data['id'] = $entry->id();
        $data['url'] = $entry->absoluteUrl();
        $data['uri'] = $entry->uri();
        $data['collection'] = $entry->collection()->handle();

        // Convert to YAML and output
        $yaml = YAML::dump($data);
        $this->line($yaml);
    }

    /**
     * Output entry as JSON
     */
    protected function outputJson($entry)
    {
        $data = [
            'id' => $entry->id(),
            'title' => $entry->get('title', 'Untitled'),
            'slug' => $entry->slug(),
            'collection' => $entry->collection()->handle(),
            'url' => $entry->absoluteUrl(),
            'uri' => $entry->uri(),
            'template' => $entry->get('template'),
            'blueprint' => $entry->blueprint()->handle(),
            'intro' => $entry->get('intro'),
            'description' => $entry->get('description'),
            'content' => $entry->get('content'),
            'parameters' => $entry->get('parameters', []),
            'variables' => $entry->get('variables', []),
            'related_entries' => $entry->get('related_entries', []),
            'updated_at' => $entry->lastModified()->format('Y-m-d H:i:s'),
            'data' => $entry->data(),
        ];

        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Output entry as formatted table
     */
    protected function outputTable($entry)
    {
        // Basic information
        $this->components->twoColumnDetail('<info>Title</info>', $entry->get('title', 'Untitled'));
        $this->components->twoColumnDetail('<info>ID</info>', $entry->id());
        $this->components->twoColumnDetail('<info>Collection</info>', $entry->collection()->title());
        $this->components->twoColumnDetail('<info>Slug</info>', $entry->slug());
        $this->components->twoColumnDetail('<info>URL</info>', $entry->absoluteUrl());
        $this->components->twoColumnDetail('<info>URI</info>', $entry->uri());
        $this->components->twoColumnDetail('<info>Template</info>', $entry->get('template', 'default'));
        $this->components->twoColumnDetail('<info>Blueprint</info>', $entry->blueprint()->handle());
        $this->components->twoColumnDetail('<info>Last Modified</info>', $entry->lastModified()->format('Y-m-d H:i:s'));

        // Intro/Description
        if ($intro = $entry->get('intro')) {
            $this->newLine();
            $this->line('<info>Intro:</info>');
            $this->line($intro);
        }

        if ($description = $entry->get('description')) {
            $this->newLine();
            $this->line('<info>Description:</info>');
            $this->line($description);
        }

        // Parameters (for tags/modifiers)
        if ($parameters = $entry->get('parameters')) {
            $this->newLine();
            $this->line('<info>Parameters:</info>');

            $table = new Table($this->output);
            $table->setHeaders(['Name', 'Type', 'Required', 'Description']);
            $rows = [];

            foreach ($parameters as $param) {
                $rows[] = [
                    $param['name'] ?? '',
                    $param['type'] ?? '',
                    isset($param['required']) && $param['required'] ? 'Yes' : 'No',
                    $param['description'] ?? '',
                ];
            }

            $table->setRows($rows);
            $table->render();
        }

        // Variables (for tags)
        if ($variables = $entry->get('variables')) {
            $this->newLine();
            $this->line('<info>Variables:</info>');

            $table = new Table($this->output);
            $table->setHeaders(['Name', 'Type', 'Description']);
            $rows = [];

            foreach ($variables as $var) {
                $rows[] = [
                    $var['name'] ?? '',
                    $var['type'] ?? '',
                    $var['description'] ?? '',
                ];
            }

            $table->setRows($rows);
            $table->render();
        }

        // Related entries
        if ($relatedEntries = $entry->get('related_entries')) {
            $this->newLine();
            $this->line('<info>Related Entries:</info>');
            foreach ($relatedEntries as $relatedId) {
                if ($related = Entry::find($relatedId)) {
                    $this->line(" - {$related->get('title')} ({$related->collection()->handle()})");
                }
            }
        }

        // Content
        if ($content = $entry->get('content')) {
            $this->newLine();
            $this->line('<info>Content:</info>');

            if ($this->option('full')) {
                $this->line($content);
            } else {
                $snippet = $this->getSnippet(strip_tags($content), 500);
                $this->line($snippet);

                if (strlen(strip_tags($content)) > 500) {
                    $this->newLine();
                    $this->comment('Content truncated. Use --full to see the complete content.');
                }
            }
        }
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

        return substr($text, 0, $length).'...';
    }
}

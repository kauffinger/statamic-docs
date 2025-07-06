<?php

namespace App\Search;

use Statamic\Search\Result;

class CustomSearchResult extends Result
{
    protected $documentReference;

    protected $entry;

    public function __construct($entry, $documentReference)
    {
        $this->entry = $entry;
        $this->documentReference = $documentReference;
    }

    public function getSearchable(): \Statamic\Contracts\Search\Searchable
    {
        return $this->entry;
    }

    public function getReference(): string
    {
        return $this->documentReference;
    }

    public function getCpUrl(): string
    {
        return $this->entry ? $this->entry->editUrl() : '';
    }

    public function getTitle(): string
    {
        return $this->entry ? $this->entry->get('title', 'Untitled') : 'Untitled';
    }

    public function is($item): bool
    {
        return $this->entry && $this->entry->is($item);
    }
}

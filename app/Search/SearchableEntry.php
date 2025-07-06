<?php

namespace App\Search;

use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Search\Result;

class SearchableEntry
{
    protected $entry;

    protected $documentReference;

    public function __construct(EntryContract $entry, string $documentReference)
    {
        $this->entry = $entry;
        $this->documentReference = $documentReference;
    }

    public function toSearchResult(): Result
    {
        $result = new CustomSearchResult($this->entry, $this->documentReference);

        return $result;
    }

    // Delegate all other method calls to the wrapped entry
    public function __call($method, $arguments)
    {
        return $this->entry->$method(...$arguments);
    }

    public function __get($property)
    {
        return $this->entry->$property;
    }

    public function __set($property, $value)
    {
        $this->entry->$property = $value;
    }

    public function __isset($property)
    {
        return isset($this->entry->$property);
    }
}

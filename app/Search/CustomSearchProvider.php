<?php

namespace App\Search;

use Illuminate\Support\Collection;
use Statamic\Facades\Entry;
use Stillat\DocumentationSearch\SearchProvider as BaseSearchProvider;

class CustomSearchProvider extends BaseSearchProvider
{
    /**
     * Find entries by their IDs.
     * 
     * This method properly retrieves Statamic entries based on the document IDs
     * returned from the search index.
     */
    public function find(array $ids): Collection
    {
        $entries = collect();
        $processedEntryIds = [];
        
        foreach ($ids as $id) {
            // The IDs passed here are in the format "section:entry-id"
            // We need to extract just the entry ID part
            if (str_contains($id, ':')) {
                $entryId = substr($id, strpos($id, ':') + 1);
            } else {
                $entryId = $id;
            }
            
            // Avoid duplicate entries (multiple document sections from same entry)
            if (!in_array($entryId, $processedEntryIds)) {
                // Try to find the entry by ID
                $entry = Entry::find($entryId);
                
                if ($entry) {
                    // Create a wrapper entry that has the correct reference for search results
                    $wrappedEntry = new SearchableEntry($entry, 'doc::' . $id);
                    $entries->push($wrappedEntry);
                    $processedEntryIds[] = $entryId;
                }
            }
        }
        
        return $entries;
    }
}
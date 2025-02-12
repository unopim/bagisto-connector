<?php

namespace Webkul\DataTransfer\Helpers\Iterators;

use Iterator;
use Illuminate\Support\Collection;

/**
 * DatabaseProductIterator handles paginated iteration of a database query.
 */
class DatabaseProductIterator implements Iterator
{
    /**
     * @var \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder Query builder instance.
     */
    protected $query;

    /**
     * @var int Number of items per page.
     */
    protected $pageSize;

    /**
     * @var int Current page number (0-based).
     */
    protected $currentPage;

    /**
     * @var Collection Current items fetched from the query.
     */
    protected $currentItems;

    /**
     * @var int Current position within the current page.
     */
    protected $currentKey;

    /**
     * Constructor.
     *
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     * @param int $pageSize
     */
    public function __construct($query, $pageSize = 10000)
    {
        $this->query = $query;
        $this->pageSize = $pageSize;
        $this->currentPage = 0;
        $this->currentItems = collect();
        $this->currentKey = 0;
    }

    /**
     * Rewind the iterator to the first page.
     */
    public function rewind()
    {
        $this->currentPage = 0;
        $this->currentKey = 0;
        $this->fetchPage();
    }

    /**
     * Get the current item.
     *
     * @return mixed|null
     */
    public function current()
    {
        return $this->currentItems->get($this->currentKey);
    }

    /**
     * Get the current key.
     *
     * @return int
     */
    public function key()
    {
        return $this->currentKey;
    }

    /**
     * Move to the next item.
     */
    public function next()
    {
        $this->currentKey++;

        if ($this->currentKey >= $this->currentItems->count()) {
            $this->currentPage++;
            $this->fetchPage();
            $this->currentKey = 0;
        }
    }

    /**
     * Check if the current position is valid.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->currentKey < $this->currentItems->count();
    }

    /**
     * Fetch the current page of results.
     */
    protected function fetchPage()
    {
        try {
            $this->currentItems = $this->query
                ->forPage($this->currentPage + 1, $this->pageSize)
                ->get();

        } catch (\Exception $e) {
            // Log the error or handle it as needed.
            $this->currentItems = collect();
        }
    }
}

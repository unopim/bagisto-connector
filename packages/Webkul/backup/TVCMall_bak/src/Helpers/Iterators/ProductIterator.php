<?php

namespace Webkul\TVCMall\Helpers\Iterators;

class ProductIterator implements \Iterator
{
    private $lastProductId = INF;

    private $data = [];

    private $openAPITVCMall;

    private $filters;

    private $key = 0;

    private $page = 0;

    private $totalPages;

    public function __construct($openAPITVCMall, $filters)
    {
        $this->openAPITVCMall = $openAPITVCMall;

        $this->filters = $filters;

        $this->totalPages = $filters['TotalPages'] ?: INF;

        $this->data[] = $this->getData();
    }

    /**
     * abstract method current
     * @return mixed
     * */
    public function current(): mixed
    {
        return $this->data[$this->key] ?? [];
    }

    /**
     * abstract method next
     * @return void
     * */
    public function next(): void
    {
        $this->data[$this->key] = $this->getData();
    }

    /**
     * get products from api
     * @return void
     * */
    private function getData()
    {
        if ($this->page >= $this->totalPages) {
            $this->lastProductId = 0;
            
            return [];
        }

        $productData = $this->openAPITVCMall->getProducts($this->getFilters());

        $this->lastProductId = $productData['lastProductId'];

        $this->page++;

        echo "lastProductId: " . $this->lastProductId . PHP_EOL;
        
        return $productData['products'];
    }

    /**
     * get filters
     * @return array
     * */
    private function getFilters(): array
    {
        if ($this->lastProductId != INF) {
            $this->filters['lastProductId'] = $this->lastProductId;
        }

        return $this->filters;
    }

    /**
     * abstract method key
     * @return mixed
     * */
    public function key(): mixed
    {
        return $this->key;
    }

    /**
     * abstract method rewind
     * @return void
     * */
    public function rewind(): void
    {
        // nothing to do here
    }
    
    /**
     * abstract method valid
     * @return bool
     * */
    public function valid(): bool
    {
        return $this->data[$this->key] ? true : false;
    }

    /**
     * get last product id
     * @return int
     * */
    public function getLastProductId(): int
    {
        return $this->lastProductId;
    }
}

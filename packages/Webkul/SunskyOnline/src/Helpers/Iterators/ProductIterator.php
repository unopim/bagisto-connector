<?php

namespace Webkul\SunskyOnline\Helpers\Iterators;

/**
 * ProductIterator
 *
 * This class is used to iterate over the products fetched from the Sunsky API.
 */
class ProductIterator implements \Iterator
{
    const DEFAULT_LANG = 'en';

    private $pageNo;

    private $currentPageData;

    private $currentKey;

    private $apiClient;

    private $totalPages = 0;

    private $totalRecords = 0;

    private $filters;

    private $identifiers;

    private $isIdentifiersFetched = false;

    private $supportLangs = ['it', 'vi', 'ko', 'ar', 'zh_CN', 'th', 'de', 'zh_TW', 'pt', 'fr', 'en', 'ru', 'es', 'ja', 'nl'];

    public function __construct($apiClient, $filters)
    {
        $this->apiClient = $apiClient;
        $this->filters = $filters;
        $this->pageNo = $this->filters['from_page_no'] ?? 1;
        $this->currentPageData = [];
        $this->currentKey = 0;
        $this->checkAndSetIdentifiers();

        $this->fetchPage();
    }

    /**
     * Check and set identifiers
     */
    public function checkAndSetIdentifiers()
    {
        if (! empty($this->filters['identifiers'])) {
            $this->identifiers = array_filter(array_map('trim', explode(',', $this->filters['identifiers'])));
        }
    }

    /**
     * Get current element
     */
    public function current()
    {
        return $this->currentPageData[$this->currentKey] ?? null;
    }

    /**
     * Get current key
     */
    public function key()
    {
        return $this->currentKey;
    }

    /**
     * Get page number
     */
    public function page()
    {
        return $this->pageNo;
    }

    /**
     * Set page number
     */
    public function setPage($pageNo)
    {
        $this->pageNo = $pageNo;
        $this->fetchPage();
    }

    /**
     * Get total pages
     */
    public function totalPages()
    {
        return $this->totalPages;
    }

    /**
     * Get total records
     */
    public function totalRecords()
    {
        return $this->totalRecords;
    }

    /**
     * Move the iterator to the next element
     */
    public function next()
    {
        $this->currentKey++;

        if ($this->currentKey >= count($this->currentPageData) && $this->pageNo < $this->totalPages) {
            $this->pageNo++;
            $this->fetchPage();
        }
    }

    /**
     * Move the iterator to the first element
     */
    public function rewind()
    {
        $this->pageNo = $this->filters['from_page_no'] ?? 1;
        $this->currentPageData = [];
        $this->currentKey = 0;
        $this->fetchPage();
    }

    /**
     * Check if the current key is valid
     */
    public function valid()
    {
        return ! empty($this->currentPageData) && $this->currentKey < count($this->currentPageData);
    }

    /**
     * Fetch page data
     */
    private function fetchPage()
    {
        $this->currentPageData = [];

        if ($this->identifiers) {
            $this->fetchIdentifiers();
        } else {
            $this->fetchProducts();
        }

    }

    /**
     * Fetch products by identifiers
     **/
    public function fetchIdentifiers()
    {
        if ($this->isIdentifiersFetched) {
            return;
        }

        try {
            foreach ($this->identifiers as $identifier) {
                $params = [
                    'itemNo'            => $identifier,
                    'lang'              => $this->getLang(),
                ];
                $response = $this->apiClient->getProductDetail(...$params);

                if (! empty($response['itemNo'])) {
                    $this->currentPageData[] = $response;
                } else {
                    error_log('Product not found for identifier: '.$identifier);
                }

            }

            $this->totalPages = 1;
            $this->totalRecords = count($this->currentPageData);
            $this->isIdentifiersFetched = true;
        } catch (\Exception $e) {
            error_log('Error fetching product data: '.$e->getMessage().'. Params: '.json_encode($params));

            throw new \RuntimeException('Failed to fetch product data: '.$e->getMessage(), 0, $e);
        }

        $this->currentKey = 0;

    }

    /**
     * Fetch products by filters
     **/
    public function fetchProducts()
    {
        if ($this->totalPages && $this->pageNo > $this->totalPages) {
            return;
        }

        $endPageNo = $this->filters['end_page_no'] ?? null;
        if ($endPageNo && $this->pageNo > $endPageNo) {
            return;
        }

        $params = [
            'page'              => $this->pageNo,
            'lang'              => $this->getLang(),
            'categoryId'        => ! empty($this->filters['categoryId']) ? $this->filters['categoryId'] : null,
            'gmtModifiedStart'  => ! empty($this->filters['gmtModifiedStart']) ? $this->filters['gmtModifiedStart'] : null,
            'status'            => ! empty($this->filters['status']) ? $this->filters['status'] : -1,
            'brandName'         => ! empty($this->filters['brandName']) ? $this->filters['brandName'] : null,
            'leadTimeLevel'     => ! empty($this->filters['leadTimeLevel']) ? $this->filters['leadTimeLevel'] : null,
            'pageSize'          => ! empty($this->filters['pageSize']) ? $this->filters['pageSize'] : 100,
        ];

        try {
            $response = $this->apiClient->searchProducts(...$params);

            $this->currentPageData = $response['result'] ?? [];
            $this->totalPages = $response['pageCount'] ?? 0;
            $this->totalRecords = $response['total'] ?? 0;

        } catch (\Exception $e) {
            error_log('Error fetching product data: '.$e->getMessage().'. Params: '.json_encode($params));

            throw new \RuntimeException('Failed to fetch product data: '.$e->getMessage(), 0, $e);
        }

        $this->currentKey = 0;
    }

    /**
     * Language according to supported with align filter
     **/
    public function getLang()
    {
        $filterLocale = $this->filters['locale'] ?? null;

        foreach ($this->supportLangs as $lang) {
            if (strpos($filterLocale, $lang) === 0) {
                return $lang;
            }
        }

        return self::DEFAULT_LANG;
    }
}

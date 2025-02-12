<?php

namespace Webkul\SunskyOnline\Helpers\Iterators;

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

    private $supportLangs = [
        'it',
        'vi',
        'ko',
        'ar',
        'zh_CN',
        'th',
        'de',
        'zh_TW',
        'pt',
        'fr',
        'en',
        'ru',
        'es',
        'ja',
        'nl',
    ];



    public function __construct($apiClient, $filters)
    {
        $this->apiClient = $apiClient;
        $this->filters = $filters;
        $this->pageNo = $this->filters['from_page_no'] ?? 1;
        $this->currentPageData = [];
        $this->currentKey = 0;
        $this->fetchPage();
    }

    public function current()
    {
        return $this->currentPageData[$this->currentKey] ?? null;
    }

    public function key()
    {
        return $this->currentKey;
    }

    public function page()
    {
        return $this->pageNo;
    }

    public function setPage($pageNo)
    {
        $this->pageNo = $pageNo;
        $this->fetchPage();
    }

    public function totalPages()
    {
        return $this->totalPages;
    }

    public function totalRecords()
    {
        return $this->totalRecords;
    }

    public function next()
    {
        $this->currentKey++;

        if ($this->currentKey >= count($this->currentPageData) && $this->pageNo < $this->totalPages) {
            $this->pageNo++;
            $this->fetchPage();
        }
    }

    public function rewind()
    {
        $this->pageNo = $this->filters['from_page_no'] ?? 1;
        $this->currentPageData = [];
        $this->currentKey = 0;
        $this->fetchPage();
    }

    public function valid()
    {
        return ! empty($this->currentPageData) && $this->currentKey < count($this->currentPageData);
    }

    private function fetchPage()
    {
        $this->currentPageData = [];


        if ($this->totalPages && $this->pageNo > $this->totalPages) {
            return ;
        }

        $endPageNo = $this->filters['end_page_no'] ?? null;
        if ($endPageNo && $this->pageNo > $endPageNo) {
            return ;
        }

        $params = [
            'page'              => $this->pageNo,
            'lang'              => $this->getLang(),
            'categoryId'        => !empty($this->filters['categoryId']) ? $this->filters['categoryId'] : null,
            'gmtModifiedStart'  => !empty($this->filters['gmtModifiedStart']) ? $this->filters['gmtModifiedStart'] : null,
            'status'            => !empty($this->filters['status']) ? $this->filters['status'] : -1,
            'brandName'         => !empty($this->filters['brandName']) ? $this->filters['brandName'] : null,
            'leadTimeLevel'     => !empty($this->filters['leadTimeLevel']) ? $this->filters['leadTimeLevel'] : null,
            'pageSize'          => !empty($this->filters['pageSize']) ? $this->filters['pageSize'] : 100,
        ];

        try {
            $response = $this->apiClient->searchProducts(...$params);

            $this->currentPageData = $response['result'] ?? [];
            $this->totalPages = $response['pageCount'] ?? 0;
            $this->totalRecords = $response['total'] ?? 0;

        } catch (\Exception $e) {
            error_log('Error fetching product data: ' . $e->getMessage() . '. Params: ' . json_encode($params));

            throw new \RuntimeException('Failed to fetch product data: ' . $e->getMessage(), 0, $e);
        }

        $this->currentKey = 0;
    }


    /** Language according to supported with align filter */
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

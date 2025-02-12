<?php

namespace Webkul\SunskyOnline\Services;

use Illuminate\Support\Facades\Http;

/**
 * OpenApiClient handles communication with the Sunsky Open API.
 *
 * This class is responsible for managing API credentials and making requests to
 * the Sunsky Open API for various operations such as retrieving product details,
 * categories, prices, images, and more. The class uses the provided API key and
 * secret to authenticate requests and interact with the API endpoints.
 */
class OpenApiClient
{
    /**
     * The API key used for authentication.
     *
     * @var string
     */
    protected $key;

    /**
     * The API secret associated with the API key.
     *
     * @var string
     */
    protected $secret;

    /**
     * The base URL for the API.
     *
     * @var string
     */
    protected $baseApiUrl;

    /**
     * Sets the credentials for the API connection.
     *
     * @param  string  $key  The API key to authenticate the connection.
     * @param  string  $secret  The API secret associated with the API key.
     * @param  string  $baseApiUrl  The base URL for the API (default: 'https://open.sunsky-online.com').
     * @return $this Returns the current instance for method chaining.
     */
    public function setCredentials($key, $secret, $baseApiUrl = 'https://open.sunsky-online.com')
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->baseApiUrl = $baseApiUrl;

        return $this;
    }

    /**
     * Retrieves categories based on the specified parameters.
     *
     * @param  int|null  $parentId  The ID of the parent category to filter child categories.
     *                              If null, retrieves all top-level categories.
     * @param  string|null  $gmtModifiedStart  The start date-time (GMT) for filtering categories modified after this date
     *                                         (format: 'YYYY-MM-DD HH:MM:SS').
     * @param  string|null  $lang  The language code for the categories (e.g., "en" or "fr").
     * @return mixed Returns the API response containing the list of categories.
     */
    public function getCategories($parentId = null, $gmtModifiedStart = null, $lang = null)
    {
        $params = [];

        if ($lang) {
            $params['lang'] = $lang;
        }

        if ($parentId) {
            $params['parentId'] = $parentId;
        }

        if ($gmtModifiedStart) {
            $params['gmtModifiedStart'] = $gmtModifiedStart;
        }

        return $this->callApi('category!getChildren.do', $params);
    }

    /**
     * Searches for products based on the specified parameters.
     *
     * @param  int  $page  The page number for pagination (default: 1).
     * @param  string|null  $lang  The language code (e.g., "en" or "fr").
     * @param  int|null  $categoryId  The ID of the category to filter products by.
     * @param  string|null  $gmtModifiedStart  The start date-time (GMT) for filtering modified products (format: 'YYYY-MM-DD HH:MM:SS').
     * @param  int  $status  The product status (-1 for all statuses, or a specific status code).
     * @param  string|null  $brandName  The brand name to filter products by.
     * @param  string|null  $leadTimeLevel  The lead time level to filter products by.
     * @param  int  $pageSize  The number of products per page (default: 100).
     * @return mixed Returns the API response for the product search.
     */
    public function searchProducts(
        $page = 1,
        $lang = null,
        $categoryId = null,
        $gmtModifiedStart = null,
        $status = -1,
        $brandName = null,
        $leadTimeLevel = null,
        $pageSize = 100
    ) {
        $params = [
            'page'     => $page,
            'status'   => $status,
            'pageSize' => $pageSize,
        ];

        if ($lang) {
            $params['lang'] = $lang;
        }

        if ($categoryId) {
            $params['categoryId'] = $categoryId;
        }

        if ($gmtModifiedStart) {
            $params['gmtModifiedStart'] = $gmtModifiedStart;
        }

        if ($brandName) {
            $params['brandName'] = $brandName;
        }

        if ($leadTimeLevel) {
            $params['leadTimeLevel'] = $leadTimeLevel;
        }

        return $this->callApi('product!search.do', $params);
    }

    /**
     * Retrieves the product details by item number.
     *
     * @param  string  $itemNo  The item number of the product to retrieve details for.
     * @return mixed Returns the product details.
     */
    public function getProductDetail($itemNo, $lang = null)
    {
        $params = [
            'itemNo'     => $itemNo,
        ];

        if ($lang) {
            $params['lang'] = $lang;
        }

        return $this->callApi('product!detail.do', $params);
    }

    /**
     * Retrieves images for a product.
     *
     * @param  string  $itemNo  The item number of the product.
     * @param  string  $size  The desired size of the images.
     * @param  bool  $watermark  Whether to include a watermark on the images.
     * @return mixed Returns the product images.
     */
    public function getProductMedia($itemNo, $size = null, $watermark = null)
    {
        $params = [
            'itemNo'    => $itemNo,
        ];

        if ($size) {
            $params['size'] = $size;
        }

        if ($watermark) {
            $params['watermark'] = $watermark;
        }

        $tempFilePath = sprintf(
            '%s%s%s%s%s.zip',
            sys_get_temp_dir(),
            DIRECTORY_SEPARATOR,
            config('app.name'),
            DIRECTORY_SEPARATOR,
            $itemNo.uniqid()
        );

        if (! file_exists(dirname($tempFilePath))) {
            mkdir(dirname($tempFilePath), 0777, true);
        }

        return $this->downloadWithRetry('product!getImages.do', $params, $tempFilePath);
    }

    /**
     * Retrieves the list of products that have changed images since a specific date.
     *
     * @param  string  $gmtModifiedStart  The start date-time (GMT) to filter image changes (format: 'YYYY-MM-DD HH:MM:SS').
     * @return mixed Returns the list of products with changed images.
     */
    public function getImageChangeList($gmtModifiedStart)
    {
        return $this->callApi('product!getImageChangeList.do', ['gmtModifiedStart' => $gmtModifiedStart]);
    }

    /**
     * Retrieves the list of countries supported by the API.
     *
     * @return mixed Returns the list of countries.
     */
    public function getCountries()
    {
        return $this->callApi('order!getCountries.do', []);
    }

    /**
     * Retrieves prices and freights for a list of items in a specific country.
     *
     * @param  int  $countryId  The ID of the country to retrieve prices and freights for.
     * @param  array  $items  The list of items, each containing 'itemNo' and 'qty'.
     * @return mixed Returns the prices and freights for the items.
     */
    public function getPricesAndFreights($countryId, $items)
    {
        $parameters = ['countryId' => $countryId];
        foreach ($items as $index => $item) {
            $parameters["items.{$index}.itemNo"] = $item['itemNo'];
            $parameters["items.{$index}.qty"] = $item['qty'];
        }

        return $this->callApi('order!getPricesAndFreights.do', $parameters);
    }

    /**
     * Validates the API credentials by retrieving the list of countries.
     *
     * @return mixed Returns the list of countries if credentials are valid.
     */
    public function validate()
    {
        return $this->getCountries();
    }

    /**
     * Makes an API request to the specified endpoint with the given parameters.
     *
     * @param  string  $endpoint  The API endpoint to call.
     * @param  array  $parameters  The parameters to send with the request.
     * @return mixed Returns the API response.
     *
     * @throws \Exception If the credentials are not set or if the API request fails.
     */
    protected function callApi(string $endpoint, array $parameters, $attempt = 1)
    {
        if (! isset($this->key, $this->secret, $this->baseApiUrl) || empty($this->key) || empty($this->secret) || empty($this->baseApiUrl)) {
            throw new \Exception('Please set credentials first using the setCredentials method.');
        }

        $parameters['key'] = $this->key;
        $parameters['signature'] = $this->generateSignature($parameters, $this->secret);

        $url = rtrim($this->baseApiUrl, '/').'/openapi/'.ltrim($endpoint, '/');

        $response = Http::retry(3, 100)->asForm()->post($url, $parameters);
        if ($response->successful()) {
            $responseData = $response->json();

            if ($responseData['result'] === 'success') {
                return $responseData['data'];
            }

            if ($responseData['result'] === 'error') {
                if ($attempt < 5) {  // Limit retries to 20 attempts
                    $delay = pow(2, $attempt) + rand(0, 1000) / 1000; // Exponential backoff with jitter
                    sleep($delay);

                    return $this->callApi($endpoint, $parameters, $attempt + 1);
                }

                throw new \Exception('API request failed after multiple attempts: '.json_encode($responseData));
            }
        }

        throw new \Exception(
            sprintf(
                'API request failed with status %d: %s',
                $response->status(),
                $response->body()
            )
        );
    }

    public function downloadWithRetry(string $endpoint, array $parameters, string $path, $maxRetries = 3)
    {
        $attempts = 0;
        do {
            try {
                return $this->downloadFile($endpoint, $parameters, $path);
            } catch (\RuntimeException $e) {
                $attempts++;
                if ($attempts >= $maxRetries) {
                    throw new \RuntimeException("Failed to download after $attempts attempts: ".$e->getMessage());
                }
            } catch (\Exception $e) {
                throw new \RuntimeException($e->getMessage());
            }
        } while ($attempts < $maxRetries);
    }

    /**
     * Downloads a file from the API.
     *
     * @param  string  $endpoint  The API endpoint to call.
     * @param  array  $parameters  The parameters to send with the request.
     * @param  string  $path  The path where the downloaded file should be saved.
     * @return void
     *
     * @throws \Exception If the credentials are not set or if the download fails.
     */
    public function downloadFile(string $endpoint, array $parameters, string $path)
    {
        if (! isset($this->key, $this->secret, $this->baseApiUrl) || empty($this->key) || empty($this->secret) || empty($this->baseApiUrl)) {
            throw new \Exception('Please set credentials first using the setCredentials method.');
        }

        $parameters['key'] = $this->key;

        $parameters['signature'] = $this->generateSignature($parameters, $this->secret);

        $url = rtrim($this->baseApiUrl, '/').'/openapi/'.ltrim($endpoint, '/');

        $response = Http::withOptions([
            'sink'            => $path,
            'timeout'         => 300,
            'connect_timeout' => 100,
        ])->asForm()->post($url, $parameters);

        if ($response->successful()) {
            $responseType = $response->header('Content-Type');

            if ($responseType === 'application/zip') {
                return $path;
            } else {
                $jsonResponse = $response->json();

                if (isset($jsonResponse['result']) && $jsonResponse['result'] === 'error') {
                    throw new \Exception('API error: '.json_encode($jsonResponse));
                } else {
                    throw new \RuntimeException('Response is not a valid JSON or ZIP file.');
                }
            }
        }

        throw new \Exception(
            sprintf(
                'API request failed with status %d: %s',
                $response->status(),
                $response->body()
            )
        );
    }

    /**
     * Generates a signature for the API request using the given parameters and secret.
     *
     * @param  array  $parameters  The parameters to include in the signature.
     * @param  string  $secret  The API secret to generate the signature.
     * @return string The generated signature.
     */
    protected function generateSignature($parameters, $secret)
    {
        ksort($parameters);
        $signatureString = implode('', $parameters).'@'.$secret;

        return md5($signatureString);
    }
}

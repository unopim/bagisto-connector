<?php

namespace Webkul\TVCMall\Services;

use Illuminate\Support\Facades\Http;
use Webkul\TVCMall\Repositories\ConfigurationRepository;

/**
 * OpenApiClient handles communication with the TVCMall Open API.
 *
 * This class is responsible for managing API credentials and making requests to
 * the TVCMall Open API for various operations such as retrieving product details,
 * categories, prices, images, and more. The class uses the provided API email and
 * password to authenticate requests and interact with the API endpoints.
 *
 * @package Webkul\TVCMall\Services
 */
class OpenAPITVCMall
{
    /**
     * The API email used for authentication.
     *
     * @var string
     */
    protected $email;

    /**
     * The API password associated with the API email.
     *
     * @var string
     */
    protected $password;

    /**
     * The base URL for the API.
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Auth Token
     *
     * @var string
     */
    protected $token;

    /**
     * api result
     *
     * @var array
     */
    protected $result = [];

    /**
     * Service instance.
     *
     **/
    public function __construct(protected ConfigurationRepository $configurationRepository)
    {
        $credentail = $configurationRepository->first();

        $this->baseUrl = $credentail?->baseUrl;
        $this->email = $credentail?->email;
        $this->password = $credentail?->password;
        $this->token = $credentail?->token;
    }

    /**
     * Sets the credentials for the API connection.
     *
     * @param string $email           The API email to authenticate the connection.
     * @param string $password        The API password associated with the API email.
     * @param string $baseUrl    The base URL for the API (default: 'https://openapi.tvc-mall.com').
     *
     * @return void
     */
    public function setCredentials($baseUrl, $email, $password)
    {
        $this->baseUrl = $baseUrl;
        $this->email = $email;
        $this->password = $password;
    }

    /**
     * Create Token
     *
     * @return array|null
     */
    public function generateAuthToken()
    {
        return $this->callApi('Authorization/GetAuthorization', 'get', [
            'baseUrl' => $this->baseUrl,
            'email' => $this->email,
            'password' => $this->password,
        ], false);
    }

    /**
     * Retrieves categories based on the specified parameters.
     *
     * @param array $filters An array of filters to apply to the API request.
     *
     * @return mixed Returns the API response containing the list of categories.
     */
    public function getCategories($filters = [])
    {
        $categories = $this->callApi('OpenApi/Category/GetChildren', 'get', $filters, true);

        if (isset($categories['CateoryList'])) {
            foreach ($categories['CateoryList'] as $category) {
                $this->result[] = $category;

                $this->getCategories(['ParentCode' => $category['Code']]);
            }
        }

        return $this->result;
    }

    /**
     * Retrieves products based on the specified parameters.
     *
     * @param array $filters An array of filters to apply to the API request.
     *
     * @return mixed Returns the API response containing the list of products.
     */
    public function getProducts($filters = [])
    {
        $filters = array_filter($filters, function ($filter) {
            return !empty($filter) || $filter === 0;
        });

        $productData = $this->callApi('OpenApi/Product/Search', 'get', $filters, true);

        $result = [
            'lastProductId' => $productData['lastProductId'] ?? 0,
            'products' => [],
        ];

        if (isset($productData['ProductItemNoList']) && $productData['ProductItemNoList']) {
            foreach ($productData['ProductItemNoList'] as $product) {
                $result['products'][] = [
                    'productId' => $product['ProductId'],
                    'itemNo' => $product['ItemNo'],
                ];
            }
        }

        return $result;
    }

    /**
     * get product details
     *
     * @param array $product
     *
     * @return array Returns the API response containing the product details.
     */
    public function getProductDetail($product = [])
    {
        $result = [];

        $productDetailNewVersion = $this->callApi('OpenApi/Product/Detail_NewVersion', 'get', ['ItemNo' => $product['itemNo']], true);

        $productDetail = $this->callApi('OpenApi/Product/Detail', 'get', ['ItemNo' => $product['itemNo']], true);

        if ($productDetailNewVersion && $productDetail && isset($productDetail['Detail'])) {
            $result = array_merge(
                ['productId' => $product['productId']],
                $productDetailNewVersion,
                $productDetail['Detail']
            );
        }

        return $result;
    }

    /**
     * Retrieves downloadable images urls based on the specified parameters.
     *
     * @param array $filters parameters to filter the images.
     *
     * @return string Returns downloaded image path.
     *
     **/
    public function getProductImages($filters): string
    {
        $images = $this->callApi('OpenApi/Product/Image', 'get', $filters, true);

        if ($images['Code'] ?? false) {
            return $images['ImageUrl'];
        }

        return '';
    }

    /**
     * Retrieves downloadable additional images urls based on the specified parameters.
     *
     * @param array $filters parameters to filter the images.
     *
     * @return string Returns downloaded image path.
     *
     **/
    public function getProductAdditionalImages($filters): string
    {
        $images = $this->callApi('OpenApi/Product/ScenesImage', 'get', $filters, true);

        if ($images['Code'] ?? false) {
            return $images['ImageUrl'];
        }

        return '';
    }

    /**
     * Makes an API request to the specified endpoint with the given parameters.
     *
     * @param string $endpoint   The API endpoint to call.
     * @param string $method   The API request method.
     * @param array  $parameters The parameters to send with the request.
     * @param bool   $isAuthRequired     Whether to include authentication headers in the request.
     *
     * @return mixed Returns the API response.
     * @throws \Exception If the credentials are not set or if the API request fails.
     */
    protected function callApi(string $endpoint, string $method, array $parameters, bool $isAuthRequired = true)
    {
        $url = ltrim($this->baseUrl, '/') . '/' . $endpoint;
        $headers = $isAuthRequired ? ['Authorization' => 'TVC ' . $this->token] : [];

        try {
            $response = Http::withHeaders($headers)->{$method}($url, $parameters);

            if ($response->status() === 401) {
                $tokenResponse = $this->generateAuthToken();

                if ($tokenResponse['Success']) {
                    $this->token = $tokenResponse['AuthorizationToken'];

                    $this->configurationRepository->where('email', $this->email)->update([
                        'token' => $this->token,
                    ]);

                    return $this->callApi($endpoint, $method, $parameters, $isAuthRequired);
                }
            }

            return $response->successful() ? $response->json() : null;

        } catch (\Exception $e) {
            return [
                'Success' => false,
                'Reason' => $e->getMessage(),
            ];
        }
    }
}

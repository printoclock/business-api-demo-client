<?php

namespace App\SDK;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Namshi\Cuzzle\Middleware\CurlFormatterMiddleware;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class PrintoclockBAPI
 */
Class PrintoclockBAPI
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    private $login;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var string
     */
    private $accessToken;

    /**
     * Printoclock Business API Constructor.
     *
     * @param string $host
     * @param string $login
     * @param string $password
     * @param string $version
     * @param Logger $logger
     */
    public function __construct($host, $login, $password, $version, Logger $logger)
    {
        $this->host = $host;
        $this->login = $login;
        $this->password = $password;
        $this->version = $version;

        $handler = HandlerStack::create();
        $handler->after('cookies', new CurlFormatterMiddleware($logger));
        $this->client = new Client(['handler' => $handler]);
    }

    public function getToken()
    {
        $url = $this->host . '/login_check';
        $data = ['username' => $this->login, 'password' => $this->password];
        $response = $this->client->post($url, array(RequestOptions::JSON => $data));
        $result = json_decode($response->getBody()->getContents());
        $this->accessToken = $result->token;

        return $this->accessToken;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getProducts()
    {
        $result = $this->request('get', '/products');

        return $result;
    }

    /**
     * @param string $code
     * @return string
     * @throws \Exception
     */
    public function getProduct($code)
    {
        $result = $this->request('get', '/products/' . $code);

        return $result;
    }

    /**
     * @param string $productCode
     * @param array $optionValueCodes
     * @param array $userInputs
     * @param string $countryCode
     * @param string|null $postCode
     * @return string
     * @throws \Exception
     */
    public function getProductVariants($productCode, $optionValueCodes, $userInputs = array(), $countryCode = 'FR', $postCode = null)
    {
        $params = [
            'optionValueCodes' => $optionValueCodes,
            'userInputs' => $userInputs,
            'countryCode' => $countryCode,
        ];
        if ($postCode) {
            $params['postCode'] = $postCode;
        }
        $result = $this->request('get', '/products/' . $productCode . '/variants', $params);

        return $result;
    }

    /**
     * @param File $file
     * @return string
     * @throws \Exception
     */
    public function uploadDocument(File $file)
    {
        $result = $this->request('post', '/documents', array(), array('file' => $file->getRealPath()));

        return $result;
    }

    /**
     * @param array $data
     * @return string
     * @throws \Exception
     */
    public function createOrder($data)
    {
        $result = $this->request('post', '/orders', $data);

        return $result;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getOrders()
    {
        $result = $this->request('get', '/orders');

        return $result;
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @param array $filesPath
     * @return string
     * @throws \Exception|RequestException
     */
    protected function request($method, $endpoint, $data = array(), $filesPath = array())
    {
        $url = $this->host . '/' . $this->version . $endpoint;

        if (!$this->accessToken) {
            $this->getToken();
        }
        if (!in_array($method, array('post', 'get'))) {
            throw new \Exception('bad request method');
        }
        $headers = array('Authorization' => 'Bearer ' . $this->accessToken);

        $params = array(
            'headers' => $headers,
            RequestOptions::QUERY => $method === 'get' ? $data : array(),
        );
        if (!$filesPath) {
            $params[RequestOptions::FORM_PARAMS] = $method !== 'get' ? $data : array();
        }
        foreach ($filesPath as $inputName => $filePath) {
            $params['multipart'][] = array(
                'name' => $inputName,
                'contents' => fopen($filePath, 'r'),
            );
        }
        /** @var Response $response */
        try {
            $response = $this->client->$method($url, $params);
            $result = json_decode($response->getBody()->getContents(), true);

            return $result;
        } catch (RequestException $e) {
            if ($e->getCode() === 400) {
                throw new BadRequestHttpException($e->getMessage());
            }

            throw $e;
        }
    }
}

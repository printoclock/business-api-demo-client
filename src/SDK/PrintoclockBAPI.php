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
    const DEFAULT_PAGE_LIMIT = 25;

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
     * @param int $page
     * @param int $limit
     * @return Response
     * @throws \Exception
     */
    public function getProducts($page = 1, $limit = self::DEFAULT_PAGE_LIMIT)
    {
        $result = $this->request('get', '/products', array('page' => $page, 'limit' => $limit));

        return $result;
    }

    /**
     * @param string $code
     * @return string
     * @throws \Exception
     */
    public function getProduct($code)
    {
        $response = $this->request('get', '/products/' . $code);
        $result = json_decode($response->getBody()->getContents(), true);

        return $result;
    }

    /**
     * @param string $code
     * @return string
     * @throws \Exception
     */
    public function getVariant($code)
    {
        $response = $this->request('get', '/variants/' . $code);
        $result = json_decode($response->getBody()->getContents(), true);

        return $result;
    }

    /**
     * @param string $productCode
     * @param array $optionValueCodes
     * @param array $userInputs
     * @param string $countryCode
     * @param string|null $postCode
     * @param int $page
     * @param int $limit
     * @return Response
     * @throws \Exception
     */
    public function getProductVariants($productCode, $optionValueCodes, $userInputs = array(), $countryCode = 'FR', $postCode = null, $page = 1, $limit = self::DEFAULT_PAGE_LIMIT)
    {
        $params = [
            'optionValueCodes' => $optionValueCodes,
            'userInputs' => $userInputs,
            'countryCode' => $countryCode,
            'page' => $page,
            'limit' => $limit,
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
        $response = $this->request('post', '/documents', array(), array('file' => $file->getRealPath()));
        $result = json_decode($response->getBody()->getContents(), true);

        return $result;
    }

    /**
     * @param array $data
     * @return string
     * @throws \Exception
     */
    public function createOrder($data)
    {
        $response = $this->request('post', '/orders', $data);
        $result = json_decode($response->getBody()->getContents(), true);

        return $result;
    }

    /**
     * @param int $page
     * @param int $limit
     * @return Response
     * @throws \Exception
     */
    public function getOrders($page = 1, $limit = self::DEFAULT_PAGE_LIMIT)
    {
        $result = $this->request('get', '/orders', array('page' => $page, 'limit' => $limit));

        return $result;
    }

    /**
     * @param Response $response
     * @return array
     */
    public function getLinksFromResponse($response)
    {
        $headersLink = $response->getHeader('Link');
        $headerLink = current($headersLink);
        $availableLinks = array();
        if ($headerLink) {
            $links = explode(',', $headerLink);
            foreach ($links as $link) {
                if (preg_match('/<(.*)>;\srel=\\"(.*)\\"/', $link, $matches)) {
                    $queryStr = parse_url($matches[1], PHP_URL_QUERY);
                    parse_str($queryStr, $queryParams);
                    $availableLinks[$matches[2]] = $queryParams;
                }
            }
        }

        return $availableLinks;
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @param array $filesPath
     * @return Response
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

            return $response;
        } catch (RequestException $e) {
            if ($e->getCode() === 400) {
                throw new BadRequestHttpException($e->getMessage() . ' ' . $e->getResponse()->getBody()->getContents());
            }

            throw $e;
        }
    }
}

<?php

namespace App\SDK;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Post\PostFile;
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

        $this->client = new Client();
    }

    public function getToken()
    {
        $url = $this->host . '/login_check';
        $data = array('username' => $this->login, 'password' => $this->password);
        $response = $this->client->post($url, array('json' => $data));
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
     * @param string $code
     * @return string
     * @throws \Exception
     */
    public function getVariant($code)
    {
        $result = $this->request('get', '/variants/' . $code);

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
            'query' => $method === 'get' ? $data : array(),
            'body' => $method === 'post' ? $data : array(),
        );

        $request = $this->client->createRequest($method, $url, $params);

        $body = $request->getBody();
        foreach ($filesPath as $inputName => $filePath) {
            $body->addFile(new PostFile($inputName, fopen($filePath, 'r')));
        }

        try {
            $response = $this->client->send($request);
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

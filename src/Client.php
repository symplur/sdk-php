<?php
namespace Symplur\Api;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Response;
use Symplur\Api\Exceptions\BadConfigException;
use Symplur\Api\Exceptions\BadJsonException;
use Symplur\Api\Exceptions\InvalidCredentialsException;

class Client
{
    protected $options = [
        'base_uri' => 'https://api.symplur.com/v1',
        'timeout' => 600,
        'headers' => [
            'User-Agent' => 'SymplurPhpSdk/1.0'
        ]
    ];

    protected $clientId;
    protected $clientSecret;
    protected $accessToken;

    /**
     * @var GuzzleClient
     */
    protected $guzzle;

    /**
     * @param string $clientId Symplur Client ID
     * @param string $clientSecret Symplur Client Secret
     * @param array $options Extra options to be passed straight into Guzzle HTTP Client (not usually needed)
     */
    public function __construct(string $clientId, string $clientSecret, array $options = [])
    {
        if (!$clientId) {
            throw new BadConfigException('Client ID is empty');
        } elseif (!$clientSecret) {
            throw new BadConfigException('Client Secret is empty');
        }

        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;

        $this->options = array_replace_recursive($this->options, $options);
    }

    /**
     * Perform a GET request
     *
     * @param string $relativePath Endpoint path, relative to the API's base URI
     * @param array $query Query parameters
     * @return \stdClass|null JSON data structure on success, or NULL if API gives a 404 Not Found response
     */
    public function get(string $relativePath, array $query = [])
    {
        return $this->requestJson('GET', $relativePath, $this->makeOptions([
            'query' => $query
        ]));
    }

    public function post(string $relativePath, array $formParams = [])
    {
        return $this->requestJson('POST', $relativePath, $this->makeOptions([
            'form_params' => $formParams
        ]));
    }

    public function put(string $relativePath, array $formParams = [])
    {
        return $this->requestJson('PUT', $relativePath, $this->makeOptions([
            'form_params' => $formParams
        ]));
    }

    public function patch(string $relativePath, array $formParams = [])
    {
        return $this->requestJson('PATCH', $relativePath, $this->makeOptions([
            'form_params' => $formParams
        ]));
    }

    public function delete(string $relativePath, array $formParams = [])
    {
        return $this->requestJson('DELETE', $relativePath, $this->makeOptions([
            'form_params' => $formParams
        ]));
    }

    public function getAsync(string $relativePath, array $query = [], callable $successFunc = null) : Promise
    {
        return $this->asyncRequestJson('GET', $relativePath, $this->makeOptions([
            'query' => $query
        ]), $successFunc);
    }

    public function postAsync(string $relativePath, array $formParams = [], callable $successFunc = null) : Promise
    {
        return $this->asyncRequestJson('POST', $relativePath, $this->makeOptions([
            'form_params' => $formParams
        ]), $successFunc);
    }

    public function putAsync(string $relativePath, array $formParams = [], callable $successFunc = null) : Promise
    {
        return $this->asyncRequestJson('PUT', $relativePath, $this->makeOptions([
            'form_params' => $formParams
        ]), $successFunc);
    }

    public function patchAsync(string $relativePath, array $formParams = [], callable $successFunc = null) : Promise
    {
        return $this->asyncRequestJson('PATCH', $relativePath, $this->makeOptions([
            'form_params' => $formParams
        ]), $successFunc);
    }

    public function deleteAsync(string $relativePath, array $formParams = [], callable $successFunc = null) : Promise
    {
        return $this->asyncRequestJson('DELETE', $relativePath, $this->makeOptions([
            'form_params' => $formParams
        ]), $successFunc);
    }

    protected function requestJson(string $method, string $relativePath, array $options = [])
    {
        try {
            $response = $this->getGuzzle()->request($method, ltrim($relativePath, '/'), $options);

        } catch (ClientException $e) {
            $response = $e->getResponse();

            if (substr($response->getHeaderLine('WWW-Authenticate'), 0, 7) == 'Bearer ') {
                $this->accessToken = null;
                $options = $this->makeOptions($options);

                return $this->requestJson($method, $relativePath, $options);

            } elseif ($response->getStatusCode() == 404) {
                return null;
            }

            throw $e;
        }

        $data = json_decode($response->getBody());
        if (json_last_error()) {
            throw new BadJsonException(sprintf(
                'JSON error %s: "%s" while trying to parse API response: %s',
                json_last_error(),
                json_last_error_msg(),
                $response->getBody()
            ));
        }

        return $data;
    }

    protected function asyncRequestJson(
        string $method, string $relativePath, array $options = [], callable $successFunc = null
    ) : Promise
    {
        $promise = $this->getGuzzle()
            ->requestAsync($method, ltrim($relativePath, '/'), $options);

        $promise->then(function(Response $response) use ($successFunc) {

            $data = json_decode($response->getBody());
            if (json_last_error()) {
                throw new BadJsonException(sprintf(
                    'JSON error %s: "%s" while trying to parse API response: %s',
                    json_last_error(),
                    json_last_error_msg(),
                    $response->getBody()
                ));
            }

            if ($successFunc) {
                $successFunc($data);
            }

        }, function(RequestException $e) use ($method, $relativePath, $options, $successFunc) {
            if ($e instanceof ClientException) {
                $response = $e->getResponse();
                if (substr($response->getHeaderLine('WWW-Authenticate'), 0, 7) == 'Bearer ') {
                    $this->accessToken = null;
                    $options = $this->makeOptions($options);

                    $this->asyncRequestJson($method, $relativePath, $options);

                } elseif ($response->getStatusCode() == 404) {
                    if ($successFunc) {
                        $successFunc(null);
                    }
                }
            }
            throw $e;
        });

        return $promise;
    }

    protected function makeOptions(array $extraOptions = []) : array
    {
        return array_replace_recursive($extraOptions, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getAccessToken()
            ]
        ]);
    }

    protected function getAccessToken() : string
    {
        if (!$this->accessToken) {
            try {
                $data = $this->requestJson('POST', 'oauth/token', [
                    'auth' => [
                        $this->clientId,
                        $this->clientSecret
                    ],
                    'form_params' => [
                        'grant_type' => 'client_credentials'
                    ]
                ]);

            } catch (ClientException $e) {
                $data = json_decode($e->getResponse()->getBody());
                if ($data && $data->error == 'invalid_client'
                    && substr($e->getResponse()->getHeaderLine('WWW-Authenticate'), 0, 6) == 'Basic '
                ) {
                    throw new InvalidCredentialsException('Invalid or missing client credentials');
                }
                throw $e;
            }

            $this->accessToken = $data->access_token;
        }

        return $this->accessToken;
    }

    protected function getGuzzle() : GuzzleClient
    {
        if (!$this->guzzle) {
            $config = $this->makeGuzzleConfig();
            $this->guzzle = new GuzzleClient($config);
        }

        return $this->guzzle;
    }

    protected function makeGuzzleConfig() : array
    {
        $config = $this->options;
        $config['base_uri'] = rtrim($config['base_uri'], '/') . '/';

        return $config;
    }
}

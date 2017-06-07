<?php

namespace Symplur\Api;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Symplur\Api\Exceptions\BadConfigException;
use Symplur\Api\Exceptions\BadJsonException;
use Symplur\Api\Exceptions\InvalidCredentialsException;

class Client
{
    private $userAgentBase = 'SymplurApiSdk/1.0';
    private $baseUri = 'https://api.symplur.com/v1';
    private $tokenPath = 'oauth/token';
    private $timeout = 600;

    private $clientId;
    private $clientSecret;
    private $accessToken;

    /**
     * @var Guzzle
     */
    private $guzzle;

    private $options = [];

    private $mockResponses = [];
    private $transactionLog = [];

    public function __construct($clientId, $clientSecret, array $options = [])
    {
        if (!$clientId) {
            throw new BadConfigException('Client ID is empty');
        } elseif (!$clientSecret) {
            throw new BadConfigException('Client Secret is empty');
        }

        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->options = $options;
    }

    public function setOptions(array $options)
    {
        $this->options += $options;
    }

    public function get($relativePath, array $query = [])
    {
        return $this->requestJson('GET', $relativePath, $this->makeRequestOptions(['query' => $query]));
    }

    public function post($relativePath, array $formParams = [])
    {
        return $this->requestJson('POST', $relativePath, $this->makeRequestOptions(['form_params' => $formParams]));
    }

    public function put($relativePath, array $formParams = [])
    {
        return $this->requestJson('PUT', $relativePath, $this->makeRequestOptions(['form_params' => $formParams]));
    }

    public function patch($relativePath, array $formParams = [])
    {
        return $this->requestJson('PATCH', $relativePath, $this->makeRequestOptions(['form_params' => $formParams]));
    }

    public function delete($relativePath, array $formParams = [])
    {
        return $this->requestJson('DELETE', $relativePath, $this->makeRequestOptions(['form_params' => $formParams]));
    }

    private function makeRequestOptions(array $extraOptions = [])
    {
        if (!isset($extraOptions['headers'])) {
            $extraOptions['headers'] = [];
        }
        $extraOptions['headers']['Authorization'] = 'Bearer ' . $this->getAccessToken();
        $extraOptions['headers']['Prefer'] = 'representation=minimal';

        return $extraOptions;
    }

    public function getAccessToken()
    {
        $token = $this->accessToken;
        if (!$token) {
            $token = $this->getCachedParam('access_token');
            if (!$token) {
                try {
                    $data = $this->requestJson('POST', $this->tokenPath, [
                        'auth' => [$this->clientId, $this->clientSecret],
                        'form_params' => ['grant_type' => 'client_credentials']
                    ]);
                } catch (ClientException $e) {
                    $data = @json_decode($e->getResponse()->getBody());
                    if ($data && $data->error == 'invalid_client'
                        && substr($e->getResponse()->getHeaderLine('WWW-Authenticate'), 0, 6) == 'Basic '
                    ) {
                        $msg = 'Invalid or missing client credentials for %s';
                        throw new InvalidCredentialsException(sprintf($msg, $this->getBaseUri()));
                    }
                    throw $e;
                }

                $token = $data->access_token;
            }
            $this->setAccessToken($token);
        }

        return $token;
    }

    private function requestJson($method, $relativePath, $options = [])
    {
        try {
            $response = $this->getGuzzleClient()->request($method, ltrim($relativePath, '/'), $options);

        } catch (ClientException $e) {
            $response = $e->getResponse();
            if (substr($response->getHeaderLine('WWW-Authenticate'), 0, 7) == 'Bearer ') {
                $this->setAccessToken('');
                $options = $this->makeRequestOptions($options);

                return $this->requestJson($method, $relativePath, $options);

            } elseif ($response->getStatusCode() == 404) {
                return null;
            }

            throw $e;
        }

        $data = @json_decode($response->getBody());
        if ($data === null) {
            $msg = 'JSON error %s: "%s" while trying to parse API response: %s';
            throw new BadJsonException(sprintf($msg, json_last_error(), json_last_error_msg(), $response->getBody()));
        }

        return $data;
    }

    public function setAccessToken($token)
    {
        $this->accessToken = $token;
        $this->setCachedParam('access_token', $token);
    }

    private function getGuzzleClient()
    {
        if (!$this->guzzle) {
            $config = [
                'base_uri' => $this->getBaseUri() . '/',
                'timeout' => (@$this->options['timeout'] ?: $this->timeout),
                'headers' => [
                    'User-Agent' => $this->userAgentBase . ' ' . \GuzzleHttp\default_user_agent()
                ]
            ];

            if ($this->mockResponses) {
                $stack = HandlerStack::create();
                $stack->setHandler(new MockHandler($this->mockResponses));
                $stack->push(Middleware::history($this->transactionLog));
                $config['handler'] = $stack;
            }

            $this->guzzle = new Guzzle($config);
        }

        return $this->guzzle;
    }

    private function getBaseUri()
    {
        return rtrim(@$this->options['base_uri'] ?: $this->baseUri, '/');
    }

    private function getCachedParam($name)
    {
        $getter = @$this->options['cache_getter'];
        if ($getter) {
            if (!is_callable($getter)) {
                throw new BadConfigException('Cache getter is not callable');
            }
            return call_user_func($getter, $name);
        }
    }

    private function setCachedParam($name, $value)
    {
        $setter = @$this->options['cache_setter'];
        if ($setter) {
            if (!is_callable($setter)) {
                throw new BadConfigException('Cache setter is not callable');
            }
            call_user_func($setter, $name, $value);
        }
    }

    public function setMockResponses(array $mockResponses = [])
    {
        $this->mockResponses = $mockResponses;
        $this->transactionLog = [];
        $this->guzzle = null;
    }

    public function getTransactionLog()
    {
        return $this->transactionLog;
    }
}

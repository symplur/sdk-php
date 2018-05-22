<?php
namespace Symplur\Api;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

class ClientHarness extends Client
{
    private $mockResponses = [];
    private $transactionLog = [];

    public function setAccessToken(string $token)
    {
        $this->accessToken = $token;
    }

    public function getAccessToken(): string
    {
        return parent::getAccessToken();
    }

    public function setMockResponses(array $mockResponses = [])
    {
        $this->mockResponses = $mockResponses;
        $this->transactionLog = [];
        $this->guzzle = null;
    }

    protected function makeGuzzleConfig() : array
    {
        $config = parent::makeGuzzleConfig();

        if ($this->mockResponses) {
            $handler = HandlerStack::create();
            $handler->setHandler(new MockHandler($this->mockResponses));
            $handler->push(Middleware::history($this->transactionLog));
            $config['handler'] = $handler;
        }

        return $config;
    }

    public function getTransactionLog()
    {
        return $this->transactionLog;
    }
}

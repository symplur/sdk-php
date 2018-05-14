<?php
namespace Symplur\Api\Tests;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symplur\Api\Client;

class ClientTest extends TestCase
{
    /**
     * @var Client
     */
    private $client;

    public function setUp()
    {
        parent::setUp();

        $this->client = new Client('myid', 'mysecret', ['base_uri' => 'http://example.com']);
        $this->client->setMockResponses();
    }

    public function testCanGet()
    {
        $this->client->setMockResponses([
            new Response(200, [], json_encode(['access_token' => 'abcdefg'])),
            new Response(200, [], json_encode(['title' => 'Da page'])),
        ]);

        $data = $this->client->get('/foo/yak');

        self::assertEquals((object)['title' => 'Da page'], $data);
        self::assertEquals(2, count($this->client->getTransactionLog()));
    }

    public function testCanPost()
    {
        $this->client->setMockResponses([
            new Response(200, [], json_encode(['access_token' => 'abcdefg'])),
            new Response(200, [], json_encode(['title' => 'Da POSTed result'])),
        ]);

        $data = $this->client->post('/foo/yak', ['one' => 'fish', 'two' => 'fish']);

        self::assertEquals((object)['title' => 'Da POSTed result'], $data);
        self::assertEquals(2, count($this->client->getTransactionLog()));
    }

    public function testCanPut()
    {
        $this->client->setMockResponses([
            new Response(200, [], json_encode(['access_token' => 'abcdefg'])),
            new Response(200, [], json_encode(['title' => 'Da PUTted result'])),
        ]);

        $data = $this->client->put('/foo/yak', ['one' => 'fish', 'two' => 'fish']);

        self::assertEquals((object)['title' => 'Da PUTted result'], $data);
        self::assertEquals(2, count($this->client->getTransactionLog()));
    }

    public function testCanPatch()
    {
        $this->client->setMockResponses([
            new Response(200, [], json_encode(['access_token' => 'abcdefg'])),
            new Response(200, [], json_encode(['title' => 'Da PATCHed result'])),
        ]);

        $data = $this->client->patch('/foo/yak', ['one' => 'fish', 'two' => 'fish']);

        self::assertEquals((object)['title' => 'Da PATCHed result'], $data);
        self::assertEquals(2, count($this->client->getTransactionLog()));
    }

    public function testCanDelete()
    {
        $this->client->setMockResponses([
            new Response(200, [], json_encode(['access_token' => 'abcdefg'])),
            new Response(200, [], json_encode(['title' => 'Da DELETEd result'])),
        ]);

        $data = $this->client->delete('/foo/yak', ['one' => 'fish', 'two' => 'fish']);

        self::assertEquals((object)['title' => 'Da DELETEd result'], $data);
        self::assertEquals(2, count($this->client->getTransactionLog()));
    }

    public function testCanUseCache()
    {
        $cache = [];
        $this->client->setOptions([
            'cache_getter' => function($name) use (&$cache) { return @$cache[$name]; },
            'cache_setter' => function($name, $value) use (&$cache) { $cache[$name] = $value; },
        ]);

        $this->client->setMockResponses([
            new Response(200, [], json_encode(['access_token' => 'abcdefg'])),
            new Response(200, [], json_encode(['title' => 'Wun page'])),
            new Response(200, [], json_encode(['title' => 'Nuther page'])),
        ]);

        $this->client->get('/foo/yak');
        $this->client->get('/foo/zat');

        self::assertEquals('abcdefg', $cache['access_token']);
        self::assertEquals(3, count($this->client->getTransactionLog()));
    }

    public function testCanGetAccessTokenFromServer()
    {
        $this->client->setMockResponses([
            new Response(200, [], json_encode(['access_token' => 'abcdefg'])),
        ]);

        $token = $this->client->getAccessToken();

        self::assertEquals('abcdefg', $token);
    }

    public function testCanUseAccessTokenFromCache()
    {
        $cache = ['access_token' => 'v83yb45voqc'];

        $this->client->setOptions([
            'cache_getter' => function($name) use (&$cache) { return @$cache[$name]; },
            'cache_setter' => function($name, $value) use (&$cache) { $cache[$name] = $value; },
        ]);

        $token = $this->client->getAccessToken();

        self::assertEquals($cache['access_token'], $token);
    }

    public function testCanGracefullyRegenerateTokenIfExpired()
    {
        $this->client->setAccessToken('bq8yvbq3vc');

        $this->client->setMockResponses([
            new Response(401, ['WWW-Authenticate' => 'Bearer realm="Foo", error="invalid_token"'], ''),
            new Response(200, [], json_encode(['access_token' => 'abcdefg'])),
            new Response(200, [], json_encode(['title' => 'Wun page'])),
        ]);

        $this->client->get('/foo/zat');

        self::assertEquals(3, count($this->client->getTransactionLog()));
        self::assertEquals('abcdefg', $this->client->getAccessToken());
    }

    /**
     * @expectedException \Symplur\Api\Exceptions\InvalidCredentialsException
     */
    public function testCanDetectInvalidCredentials()
    {
        $this->client->setMockResponses([
            new Response(401, ['WWW-Authenticate' => 'Basic realm="Yak"'], json_encode(['error' => 'invalid_client'])),
        ]);

        $this->client->get('/foo/zat');
    }

    /**
     * @expectedException \Symplur\Api\Exceptions\BadJsonException
     */
    public function testCanDetectUnparsableJson()
    {
        $this->client->setMockResponses([
            new Response(200, [], json_encode(['access_token' => 'abcdefg'])),
            new Response(200, [], '<html><body>Foo!</body></html>')
        ]);

        $this->client->get('/foo/zat');
    }
}

<?php

declare(strict_types=1);

namespace NicolasGuilloux\WiremockBundle\Tests\DependencyInjection;

use NicolasGuilloux\WiremockBundle\HttpClient\WiremockHttpClient;
use NicolasGuilloux\WiremockBundle\Tests\Resources\Stub\LoggerStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class WiremockHttpClientTest extends TestCase
{
    private MockHttpClient $innerHttpClient;
    private MockHttpClient $wiremockHttpClient;
    private LoggerStub $logger;
    private WiremockHttpClient $httpClient;

    protected function setUp(): void
    {
        $this->innerHttpClient = new MockHttpClient();
        $this->wiremockHttpClient = new MockHttpClient();
        $this->logger = new LoggerStub();

        $this->httpClient = new WiremockHttpClient(
            originalHttpClientId: 'test.client',
            inner: $this->innerHttpClient,
            wiremockHttpClient: $this->wiremockHttpClient,
            logger: $this->logger,
            dataCollector: null,
        );
    }

    public function testMockedFound(): void
    {
        $wiremockResponse = new MockResponse(
            body: 'Hello, world!',
            info: ['http_code' => 200],
        );

        $this->wiremockHttpClient->setResponseFactory($wiremockResponse);

        $result = $this->httpClient->request('GET', '/');
        $this->assertSame(1, $this->wiremockHttpClient->getRequestsCount());
        $this->assertSame(0, $this->innerHttpClient->getRequestsCount());
        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('Hello, world!', $result->getContent());
    }

    public function testMockedFoundWith404(): void
    {
        $wiremockResponse = new MockResponse(
            body: 'Hello, world!',
            info: ['http_code' => 404],
        );

        $this->wiremockHttpClient->setResponseFactory($wiremockResponse);

        $result = $this->httpClient->request('GET', '/');
        $this->assertSame(1, $this->wiremockHttpClient->getRequestsCount());
        $this->assertSame(0, $this->innerHttpClient->getRequestsCount());
        $this->assertSame(404, $result->getStatusCode());
        $this->assertSame('Hello, world!', $result->getContent(false));
    }

    public function testPassingThrough(): void
    {
        $wiremockResponse = new MockResponse(
            body: 'No response could be served as there are no stub mappings in this WireMock instance.',
            info: ['http_code' => 404],
        );

        $this->wiremockHttpClient->setResponseFactory($wiremockResponse);

        $innerResponse = new MockResponse(
            body: 'Hello, world!',
            info: ['http_code' => 200],
        );

        $this->innerHttpClient->setResponseFactory($innerResponse);

        $result = $this->httpClient->request('GET', '/');
        $this->assertSame(1, $this->wiremockHttpClient->getRequestsCount());
        $this->assertSame(1, $this->innerHttpClient->getRequestsCount());
        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('Hello, world!', $result->getContent());
    }

    public function testPassingThroughWithWiremockFailure(): void
    {
        $exception = new \RuntimeException('ERROR');
        $this->wiremockHttpClient->setResponseFactory(static fn () => throw $exception);

        $innerResponse = new MockResponse(
            body: 'Hello, world!',
            info: ['http_code' => 200],
        );

        $this->innerHttpClient->setResponseFactory($innerResponse);

        $result = $this->httpClient->request('GET', '/');
        $this->assertSame(0, $this->wiremockHttpClient->getRequestsCount());
        $this->assertSame(1, $this->innerHttpClient->getRequestsCount());
        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('Hello, world!', $result->getContent());

        $logs = $this->logger->getLogs();
        $this->assertCount(1, $logs);
        $this->assertSame('error', $logs[0]->level);
        $this->assertSame('Failed to retrieve the mock gracefully.', $logs[0]->message);
        $this->assertSame(
            [
                'exception' => $exception,
                'method' => 'GET',
                'url' => '/',
                'options' => [
                    'headers' => [
                        'X-Original-Http-Client' => 'test.client',
                    ],
                ],
            ],
            $logs[0]->context,
        );
    }

    public function testPassingThroughStream(): void
    {
        $result = iterator_to_array($this->httpClient->stream([]));
        $this->assertEmpty($result);
    }

    public function testWithOptions(): void
    {
        $result = $this->httpClient->withOptions(['test' => 'test']);
        $this->assertInstanceOf(WiremockHttpClient::class, $result);
        $this->assertNotSame($this->httpClient, $result);

        $wiremockResponse = new MockResponse(
            body: 'No response could be served as there are no stub mappings in this WireMock instance.',
            info: ['http_code' => 404],
        );

        $this->wiremockHttpClient->setResponseFactory($wiremockResponse);

        $result->request('GET', '/');
        $this->assertSame(1, $this->wiremockHttpClient->getRequestsCount());
        $this->assertSame(0, $this->innerHttpClient->getRequestsCount());
    }
}

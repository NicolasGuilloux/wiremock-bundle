<?php

declare(strict_types=1);

namespace NicolasGuilloux\WiremockBundle\Tests\DataCollector;

use NicolasGuilloux\WiremockBundle\DataCollector\WiremockDataCollector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class WiremockDataCollectorTest extends TestCase
{
    private WiremockDataCollector $dataCollector;

    protected function setUp(): void
    {
        $this->dataCollector = new WiremockDataCollector();
    }

    public function testName(): void
    {
        $this->assertEquals('wiremock', $this->dataCollector->getName());
    }

    public function testGetters(): void
    {
        $mockHttpClient = new MockHttpClient();

        $this->dataCollector->registerResponse(
            originalHttpClientId: 'inner.client',
            method: 'GET',
            url: '/passed_through',
            options: [],
            isMockedResponse: false,
            response: $mockHttpClient->request('GET', '/passed_through'),
        );

        $this->dataCollector->registerResponse(
            originalHttpClientId: 'inner.client',
            method: 'GET',
            url: '/mocked',
            options: [],
            isMockedResponse: true,
            response: $mockHttpClient->request('GET', '/mocked'),
        );

        $this->dataCollector->collect(new Request(), new Response());
        $this->assertCount(1, $this->dataCollector->getClients());
        $this->assertSame(1, $this->dataCollector->getMockedCount());
        $this->assertSame(1, $this->dataCollector->getPassedThroughCount());

        $apiCalls = $this->dataCollector->getMockedResponsesForClient('inner.client');
        $this->assertCount(1, $apiCalls);
        $this->assertSame('GET', $apiCalls[0]['method']);
        $this->assertSame('/mocked', $apiCalls[0]['url']);
        $this->assertTrue($apiCalls[0]['isMockedResponse']);
        $this->assertSame(200, $apiCalls[0]['response']['http_code']);

        $apiCalls = $this->dataCollector->getPassedThroughResponsesForClient('inner.client');
        $this->assertCount(1, $apiCalls);
        $this->assertSame('GET', $apiCalls[0]['method']);
        $this->assertSame('/passed_through', $apiCalls[0]['url']);
        $this->assertFalse($apiCalls[0]['isMockedResponse']);
        $this->assertSame(200, $apiCalls[0]['response']['http_code']);
    }
}

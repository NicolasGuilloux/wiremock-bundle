<?php

declare(strict_types=1);

namespace NicolasGuilloux\WiremockBundle\Tests\HttpClient;

use NicolasGuilloux\WiremockBundle\Tests\Resources\TestCase\KernelTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Service\Attribute\Required;

/** @group end_to_end */
final class EndToEndWiremockHttpClientTest extends KernelTestCase
{
    private HttpClientInterface $testHttpClient;
    private HttpClientInterface $wiremockHttpClient;

    #[Required]
    public function initTestCase(
        HttpClientInterface $testHttpClient,
        HttpClientInterface $wiremockHttpClient,
    ): void {
        $this->testHttpClient = $testHttpClient;
        $this->wiremockHttpClient = $wiremockHttpClient;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetWiremock();
    }

    protected function tearDown(): void
    {
        $this->resetWiremock();
        parent::tearDown();
    }

    public function testMockHttpClientPassedThrough(): void
    {
        $response = $this->testHttpClient->request('GET', '/test');
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testMockHttpClientMocked(): void
    {
        $this->wiremockHttpClient->request(
            'POST',
            '/__admin/mappings',
            [
                'json' => [
                    'request' => [
                        'method' => 'GET',
                        'url' => '/test',
                    ],
                    'response' => [
                        'status' => 204,
                    ],
                ],
            ]
        );

        $response = $this->testHttpClient->request('GET', '/test');
        $this->assertSame(204, $response->getStatusCode());
    }

    private function resetWiremock(): void
    {
        $this->wiremockHttpClient->request('DELETE', '/__admin/mappings');
    }
}

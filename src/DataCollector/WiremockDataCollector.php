<?php

declare(strict_types=1);

namespace NicolasGuilloux\WiremockBundle\DataCollector;

use NicolasGuilloux\WiremockBundle\DataCollector\Model\WiremockApiCall;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class WiremockDataCollector extends DataCollector implements LateDataCollectorInterface
{
    /** @var WiremockApiCall[] */
    private array $apiCalls = [];

    /** @param array<string, mixed> $options */
    public function registerResponse(
        string $originalHttpClientId,
        string $method,
        string $url,
        array $options,
        bool $isMockedResponse,
        ResponseInterface $response,
    ): void {
        $this->apiCalls[] = new WiremockApiCall(
            originalHttpClientId: $originalHttpClientId,
            method: $method,
            url: $url,
            options: $options,
            isMockedResponse: $isMockedResponse,
            response: $response,
        );
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->lateCollect();
    }

    public function getName(): string
    {
        return 'wiremock';
    }

    public function lateCollect(): void
    {
        $this->reset();

        foreach ($this->apiCalls as $apiCall) {
            if ($apiCall->isMockedResponse) {
                ++$this->data['mocked_count'];
            } else {
                ++$this->data['passed_though_count'];
            }

            $this->data['clients'][$apiCall->originalHttpClientId] ??= [];
            $this->data['clients'][$apiCall->originalHttpClientId][] = [
                'method' => $apiCall->method,
                'url' => $apiCall->url,
                'options' => $this->cloneVar($apiCall->options),
                'isMockedResponse' => $apiCall->isMockedResponse,
                'response' => [
                    'http_code' => $apiCall->response->getStatusCode(),
                    'metadata' => $this->cloneVar([
                        'info' => $apiCall->response->getInfo(),
                        'response_headers' => $apiCall->response->getHeaders(false),
                        'response_content' => $apiCall->response->getContent(false),
                    ]),
                ],
            ];
        }
    }

    public function getClients(): array
    {
        return $this->data['clients'] ?? [];
    }

    public function getMockedCount(): int
    {
        return $this->data['mocked_count'] ?? 0;
    }

    public function getPassedThroughCount(): int
    {
        return $this->data['passed_though_count'] ?? 0;
    }

    public function getMockedResponsesForClient(string $client): array
    {
        $requests = $this->data['clients'][$client] ?? [];

        return array_values(
            array_filter(
                $requests,
                static fn (array $request) => $request['isMockedResponse']
            ),
        );
    }

    public function getPassedThroughResponsesForClient(string $client): array
    {
        $requests = $this->data['clients'][$client] ?? [];

        return array_values(
            array_filter(
                $requests,
                static fn (array $request) => !$request['isMockedResponse']
            ),
        );
    }

    public function reset(): void
    {
        $this->data = [
            'clients' => [],
            'passed_though_count' => 0,
            'mocked_count' => 0,
        ];
    }
}

<?php

declare(strict_types=1);

namespace NicolasGuilloux\WiremockBundle\HttpClient;

use NicolasGuilloux\WiremockBundle\DataCollector\WiremockDataCollector;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * Decorator to attempt a Wiremock call before the original call.
 */
final class WiremockHttpClient implements HttpClientInterface
{
    private const NO_STUB_RESPONSE_REGEXES = [
        '/No response could be served as there are no stub mappings in this WireMock instance/',
        '/Request was not matched\n\s*=======================/',
        '/"message"\:"Route (.*) not found"/',
    ];

    public function __construct(
        private string $originalHttpClientId,
        private HttpClientInterface $inner,
        private HttpClientInterface $wiremockHttpClient,
        private LoggerInterface $logger,
        private ?WiremockDataCollector $dataCollector = null,
    ) {
    }

    /** @param array<string, mixed> $options */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->getMockedResponse($method, $url, $options)
            ?? $this->inner->request($method, $url, $options);
    }

    /** {@inheritDoc} */
    public function stream($responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->inner->stream($responses, $timeout);
    }

    /** @param array<string, mixed> $options */
    public function withOptions(array $options): static
    {
        return new self(
            originalHttpClientId: $this->originalHttpClientId,
            inner: $this->inner->withOptions($options),
            wiremockHttpClient: $this->wiremockHttpClient,
            logger: $this->logger,
        );
    }

    public static function isMockedResponse(int $statusCode, string $content): bool
    {
        if ($statusCode !== Response::HTTP_NOT_FOUND) {
            return true;
        }

        foreach (self::NO_STUB_RESPONSE_REGEXES as $regex) {
            if (preg_match($regex, $content)) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, mixed> $options */
    private function getMockedResponse(string $method, string $url, array $options = []): ?ResponseInterface
    {
        $options['headers']['X-Original-Http-Client'] = $this->originalHttpClientId;

        try {
            $response = $this->wiremockHttpClient->request($method, $url, $options);
            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);
            $isMockedResponse = self::isMockedResponse($statusCode, $content);

            $this->dataCollector?->registerResponse(
                $this->originalHttpClientId,
                $method,
                $url,
                $options,
                $isMockedResponse,
                $response,
            );

            return $isMockedResponse ? $response : null;
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to retrieve the mock gracefully.',
                [
                    'exception' => $e,
                    'method' => $method,
                    'url' => $url,
                    'options' => $options,
                ],
            );

            return null;
        }
    }
}

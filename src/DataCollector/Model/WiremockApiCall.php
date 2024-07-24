<?php

declare(strict_types=1);

namespace NicolasGuilloux\WiremockBundle\DataCollector\Model;

use Symfony\Contracts\HttpClient\ResponseInterface;

final class WiremockApiCall
{
    public function __construct(
        public string $originalHttpClientId,
        public string $method,
        public string $url,
        /** @var array<string, mixed> */
        public array $options,
        public bool $isMockedResponse,
        public ResponseInterface $response,
    ) {
    }
}

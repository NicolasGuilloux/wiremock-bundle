<?php

declare(strict_types=1);

namespace NicolasGuilloux\WiremockBundle\Tests\Resources\Model;

final class Log
{
    public function __construct(
        public string $level,
        public string $message,
        /** @var array<string, mixed> */
        public array $context,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace NicolasGuilloux\WiremockBundle\Tests\Resources\Stub;

use NicolasGuilloux\WiremockBundle\Tests\Resources\Model\Log;
use Psr\Log\AbstractLogger;

final class LoggerStub extends AbstractLogger
{
    /** @var Log[] */
    private array $logs = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->logs[] = new Log($level, (string) $message, $context);
    }

    public function getLogs(?string $level = null): array
    {
        if ($level === null) {
            return $this->logs;
        }

        return array_filter($this->logs, fn (Log $log) => $log->level === $level);
    }
}

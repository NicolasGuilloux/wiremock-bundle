<?php

declare(strict_types=1);

namespace NicolasGuilloux\WiremockBundle\Tests\Exception;

use NicolasGuilloux\WiremockBundle\Exception\BlacklistWhiteBothDefinedException;
use PHPUnit\Framework\TestCase;

final class BlacklistWhiteBothDefinedExceptionTest extends TestCase
{
    public function testConstruction(): void
    {
        $exception = new BlacklistWhiteBothDefinedException(
            whitelist: ['client1', 'client2'],
            blacklist: ['client3'],
        );

        $this->assertSame(['client1', 'client2'], $exception->getWhitelist());
        $this->assertSame(['client3'], $exception->getBlacklist());
        $this->assertSame(
            'You cannot define both whitelist_clients and blacklist_clients at the same time for wiremock.',
            $exception->getMessage(),
        );
    }
}

<?php

declare(strict_types=1);

namespace NicolasGuilloux\WiremockBundle\Tests\Resources\TestCase;

use NicolasGuilloux\PhpunitDependencyInjectionBundle\TestCase\AutowiringTestTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

abstract class KernelTestCase extends \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
{
    use AutowiringTestTrait {
        resolveValue as protected;
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        $this->autowire(self::getContainer());
    }

    /**
     * @template T
     *
     * @param string|class-string<T> $id
     *
     * @return T|object
     */
    protected static function getService(string $id): object
    {
        $service = self::getContainer()->get($id);

        return $service ?? throw new \LogicException('Service not found: ' . $id);
    }

    /**
     * Fix a bug with the .debug. prefix and the container optimization.
     *
     * @param string|\Stringable|Reference $argument
     *
     * @return \Stringable|string|object|null
     */
    private function resolveValue(ContainerInterface $container, $argument)
    {
        if ($argument instanceof Reference) {
            $argument = str_replace('.debug.', '', (string) $argument);

            return $container->get($argument);
        }

        return $argument;
    }
}

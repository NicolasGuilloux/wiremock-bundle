<?php

declare(strict_types=1);

namespace NicolasGuilloux\WiremockBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RemoveWiremockFromHttpClientProfilerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('data_collector.http_client')) {
            return;
        }

        if (!$container->getParameter('wiremock.remove_from_http_client_profiler')) {
            return;
        }

        $definition = $container->findDefinition('data_collector.http_client');
        $wiremockHttpClientId = $container->getParameter('wiremock.http_client');
        $methodCalls = array_filter(
            $definition->getMethodCalls(),
            static fn (array $methodCall) => ($methodCall[1][0] ?? null) !== $wiremockHttpClientId,
        );

        $definition->setMethodCalls($methodCalls);
    }
}

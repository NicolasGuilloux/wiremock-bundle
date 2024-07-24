<?php

declare(strict_types=1);

namespace NicolasGuilloux\WiremockBundle\DependencyInjection\CompilerPass;

use NicolasGuilloux\WiremockBundle\Exception\BlacklistWhiteBothDefinedException;
use NicolasGuilloux\WiremockBundle\HttpClient\WiremockHttpClient;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class WiremockHttpClientCompilerPass implements CompilerPassInterface
{
    private const HTTP_CLIENT_TAG = 'http_client.client';
    private const RESERVED_HTTP_CLIENTS = [
        'test.client',
        'http_client',
    ];

    public function process(ContainerBuilder $container): void
    {
        $taggedServices = $container->findTaggedServiceIds(self::HTTP_CLIENT_TAG);
        $wiremockHttpClientId = $container->getParameter('wiremock.http_client');
        $blacklist = $container->getParameter('wiremock.blacklist_clients') ?? [];
        $whitelist = $container->getParameter('wiremock.whitelist_clients') ?? [];

        if (!empty($whitelist) && !empty($blacklist)) {
            throw new BlacklistWhiteBothDefinedException($whitelist, $blacklist);
        }

        $blacklist += self::RESERVED_HTTP_CLIENTS;

        foreach ($taggedServices as $id => $tags) {
            if ($id === $wiremockHttpClientId || in_array($id, $blacklist, true)) {
                continue;
            }

            if (!empty($whitelist) && !in_array($id, $whitelist, true)) {
                continue;
            }

            $definition = new Definition(WiremockHttpClient::class);
            $definition->setDecoratedService($id, priority: 6);
            $definition->setArgument('$originalHttpClientId', $id);
            $definition->setArgument('$wiremockHttpClient', new Reference($wiremockHttpClientId));
            $definition->setArgument('$inner', new Reference('.inner'));
            $definition->setAutoconfigured(true);
            $definition->setAutowired(true);
            $container->setDefinition('.wiremock.' . $id, $definition);
        }
    }
}

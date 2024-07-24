<?php

declare(strict_types=1);

namespace NicolasGuilloux\WiremockBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class Configuration implements ConfigurationInterface
{
    public const CONFIG_NODE = 'wiremock';

    public static function get(string $path, ParameterBagInterface $parameterBag): mixed
    {
        return $parameterBag->get(self::CONFIG_NODE . '.' . $path);
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::CONFIG_NODE);
        $rootNode = \method_exists(TreeBuilder::class, 'getRootNode')
            ? $treeBuilder->getRootNode()
            : $treeBuilder->root(self::CONFIG_NODE);

        $this->buildConfig($rootNode->children());

        return $treeBuilder;
    }

    private function buildConfig(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder->scalarNode('http_client')->defaultValue('wiremock.client');
        $nodeBuilder->arrayNode('whitelist_clients')->prototype('scalar');
        $nodeBuilder->arrayNode('blacklist_clients')->prototype('scalar');
        $nodeBuilder->booleanNode('remove_from_http_client_profiler')->defaultFalse();
    }
}

<?php

declare(strict_types=1);

namespace NicolasGuilloux\WiremockBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
final class WiremockExtension extends Extension
{
    /** @param array<string, mixed> $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $bundleConfig = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter(Configuration::CONFIG_NODE, $bundleConfig);
        self::bindParameters($container, Configuration::CONFIG_NODE, $bundleConfig);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources'));
        $loader->load('services.xml');
    }

    /** @param array<string, mixed> $config */
    private static function bindParameters(ContainerBuilder $container, string $name, array $config): void
    {
        foreach ($config as $key => $parameter) {
            $container->setParameter($name . '.' . $key, $parameter);

            if (is_array($parameter)) {
                self::bindParameters($container, $name . '.' . $key, $parameter);
            }
        }
    }
}

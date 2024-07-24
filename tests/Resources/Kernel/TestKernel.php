<?php

declare(strict_types=1);

namespace NicolasGuilloux\WiremockBundle\Tests\Resources\Kernel;

use NicolasGuilloux\PhpunitDependencyInjectionBundle\PhpunitDependencyInjectionBundle;
use NicolasGuilloux\WiremockBundle\WiremockBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';
    private const BUNDLES = [
        FrameworkBundle::class,
        WebProfilerBundle::class,
        TwigBundle::class,
        PhpunitDependencyInjectionBundle::class,
        WiremockBundle::class,
    ];

    public function __construct()
    {
        parent::__construct('test', true);
    }

    /** @return string[] */
    public function registerBundles(): iterable
    {
        foreach (self::BUNDLES as $bundle) {
            yield new $bundle();
        }
    }

    public function getConfigurationDir(): ?string
    {
        return __DIR__ . '/config';
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $confDir = $this->getConfigurationDir();

        if ($confDir === null) {
            return;
        }

        $loader->load($confDir . '/{packages}/*' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/{packages}/' . $this->environment . '/**/*' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/{services}' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/{services}_' . $this->environment . self::CONFIG_EXTS, 'glob');
    }
}

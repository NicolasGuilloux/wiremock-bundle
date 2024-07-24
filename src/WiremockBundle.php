<?php

declare(strict_types=1);

namespace NicolasGuilloux\WiremockBundle;

use NicolasGuilloux\WiremockBundle\DependencyInjection\CompilerPass\RemoveWiremockFromHttpClientProfilerCompilerPass;
use NicolasGuilloux\WiremockBundle\DependencyInjection\CompilerPass\WiremockHttpClientCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class WiremockBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new WiremockHttpClientCompilerPass(), priority: -100);
        $container->addCompilerPass(new RemoveWiremockFromHttpClientProfilerCompilerPass());
    }
}

<?php
namespace Illumina\CasBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class AddTemplatePathPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $loaderDefinition = null;
        
        if ($container->hasDefinition('twig.loader.filesystem')) {
            $loaderDefinition = $container->getDefinition('twig.loader.filesystem');
        } elseif ($container->hasDefinition('twig.loader')) {
            $loaderDefinition = $container->getDefinition('twig.loader');
        }
        
        if ( empty($loaderDefinition) ) {
            return;
        }
        
        $refl = new \ReflectionClass('Illumina\\CasBundle\\IlluminaCasBundle');
        $path = dirname($refl->getFileName()).'/Resources/views/Cas';
        $loaderDefinition->addMethodCall('addPath', array($path));
    }
}

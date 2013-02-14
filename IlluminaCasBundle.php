<?php
namespace Illumina\CasBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Illumina\CasBundle\DependencyInjection\Compiler\AddTemplatePathPass;

class IlluminaCasBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
    
        // Otherwise the service may not have been initialised
        $container->addCompilerPass(new AddTemplatePathPass());
    }
}

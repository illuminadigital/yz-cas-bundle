<?php
namespace Illumina\CasBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;

class IlluminaCasExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        
        $container->setParameter('illumina_cas.server_url', $config['server_url']);
        $container->setParameter('illumina_cas.key', $config['key']);
        $container->setParameter('illumina_cas.secret', $config['secret']);
        $container->setParameter('illumina_cas.site', $config['site']);
    }
}

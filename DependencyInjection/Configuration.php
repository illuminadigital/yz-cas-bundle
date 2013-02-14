<?php
namespace Illumina\CasBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('illumina_cas');
        
        $rootNode
            ->children()
                ->scalarNode('server_url')
                    ->defaultNull()
                    ->info('The endpoint URL of the common admin system')
                    ->example('http://www.foo.bar/cas')
                ->end()
                ->scalarNode('key')
                    ->defaultNull()
                    ->info('The private key for the common admin system')
                    ->example('WEarQRBHuiWtsnveoabnq304y039u5gw4')
                ->end()
                ->scalarNode('secret')
                    ->defaultNull()
                    ->info('The secret for the common admin system')
                    ->example('SRTbn9sdrtkseQRB6rght89tltnETjhtW')
                ->end()
                ->scalarNode('site')
                    ->defaultNull()
                    ->info('The (default) site identifier for the common admin system')
                    ->example('abp')
                ->end()
            ->end();
        
        return $treeBuilder;
    }
}

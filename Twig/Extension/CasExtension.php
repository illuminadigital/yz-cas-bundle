<?php
namespace Illumina\CasBundle\Twig\Extension;

use Illumina\CasBundle\Twig\TokenParser\CasThemeTokenParser;
use Illumina\CasBundle\Twig\CasView;

use Symfony\Component\DependencyInjection\ContainerInterface;


class CasExtension extends \Twig_Extension
{
    protected $container;
    
    /* Public to save a method call */
    public $renderer;
    
    public function __construct($renderer, ContainerInterface $container)
    {
        $this->renderer = $renderer;
        $this->container = $container;
    }
    
    public function initRuntime(\Twig_Environment $environment)
    {
        $this->renderer->setEnvironment($environment);
    }
    
    public function getTokenParsers()
    {
        return array(
            new CasThemeTokenParser(),
        );
    }
    
    public function getFunctions()
    {
        return array(
            'cas_link_entry' => new \Twig_Function_Node('Illumina\\CasBundle\\Twig\\Node\\SearchAndRenderBlockNode', array('is_safe' => array('html'))),
            'cas_link_rows' => new \Twig_Function_Node('Illumina\\CasBundle\\Twig\\Node\\SearchAndRenderBlockNode', array('is_safe' => array('html'))),
            'cas_link_row' => new \Twig_Function_Node('Illumina\\CasBundle\\Twig\\Node\\SearchAndRenderBlockNode', array('is_safe' => array('html'))),
            'cas_links' => new \Twig_SimpleFunction('cas_links', array($this, 'links')),
        );
    }
    
    public function getName()
    {
        return 'cas';
    }
    
    public function links($area, $subtype, $type = 'views', $args = array())
    {
        $retriever = $this->container->get('cas.contentretriever');
        
        return new CasView($retriever->retrieveList($area, $subtype, $type, $args), array('area' => $area, 'subtype' => $subtype, 'type' => $type));
    }
}

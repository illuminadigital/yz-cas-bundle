<?php
namespace Illumina\CasBundle\Twig\Node;

class CasThemeNode extends \Twig_Node
{
    public function __construct(\Twig_NodeInterface $cas, \Twig_NodeInterface $resources, $lineno, $tag = NULL)
    {
        parent::__construct(array('cas' => $cas, 'resources' => $resources), array(), $lineno, $tag);
    }

    /**
     * Compiles the node to PHP
     * 
     * @param \Twig_Compiler $compiler
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write('$this->env->getExtension(\'cas\')->renderer->setTheme(')
            ->setcompile($this->getNode('cas'))
            ->raw(', ')
            ->subcompile($this->getNode('resources'))
            ->raw(");\n")
        ;
    }
}

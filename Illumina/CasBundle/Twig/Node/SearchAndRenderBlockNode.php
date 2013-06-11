<?php
namespace Illumina\CasBundle\Twig\Node;

class SearchAndRenderBlockNode extends \Twig_Node_Expression_Function
{
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->raw('$this->env->getExtension(\'cas\')->renderer->searchAndRenderBlock(');
        
        preg_match('/_([^_]+_[^_]+)$/', $this->getAttribute('name'), $matches);
        
        $arguments = iterator_to_array($this->getNode('arguments'));
        $blockNameSuffix = $matches[1];
        
        $compiler->subcompile($arguments[0]);
        $compiler->raw(', \'' . $blockNameSuffix . '\'');

        //$variables = array($arguments[0]);
        $variables = $arguments[0];
//        $compiler->raw(', ');
//        $compiler->subcompile($variables);
        
/*
          if (isset($arguments[0])) {
            $compiler->subcompile($arguments[0]);
            $compiler->raw(', \'' . $blockNameSuffix . '\'');
            
            if (isset($arguments[1])) {
                
                $variables = $arguments[1];
                
                $compiler->raw(', ');
                
                $compiler->subcompile($variables);
            }
        }
*/
        
        $compiler->raw(")\n");
    }
}

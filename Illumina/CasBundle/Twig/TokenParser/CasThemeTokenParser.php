<?php
namespace Illumina\CasBundle\Twig\TokenParser;

use Illumina\CasBundle\Twig\Node\CasThemeNode;

class CasThemeTokenParser extends \Twig_TokenParser
{
    /**
     * Parses a token and returns a node
     * 
     * @see \Symfony\Bridge\Twig\TokenParser\FormThemeTokenParser
     * 
     * @param \Twig_Token $token
     * 
     * @return \Twig_NodeInterface
     */
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        
        $cas = $this->parser->getExpressionParser()->parseExpression();
        
        if ($this->parser->getStream()->test(\Twig_Token::NAME_TYPE, 'with')) {
            $this->parser->getStream()->next();
            $resources = $this->parser->getExpressionParser()->parseExpression();
        } else {
            $resources = new \Twig_Node_Expression_Array(array(), $stream->getCurrent()->getLine());
            do {
                $resources->addElement($this->parser->getExpressionParser()->parseExpression());
            } while (!$stream->test(\Twig_Token::BLOCK_END_TYPE));
        }
        
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);
        
        return new CasThemeNode($cas, $resources, $lineno, $this->getTag());
    }
    
    /**
     * Gets the tag name associated with this token parser
     */
    public function getTag()
    {
        return 'cas_theme';
    }
}

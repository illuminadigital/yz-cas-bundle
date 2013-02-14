<?php
namespace Illumina\CasBundle\Twig;

class CasView implements \Iterator
{
    protected $viewData;
    protected $vars;
    
    public function __construct($viewData, array $vars = array()) {
        $this->viewData = $viewData;
        
        $defaultVars = array(
             'block_prefix' => sprintf('%s_%s_%s', $vars['area'], $vars['type'], $vars['subtype']),
             'block_prefixes' => array(
                 //sprintf('cas_%s_%s_%s_link', $vars['area'], $vars['type'], $vars['subtype']),
                 //sprintf('cas_%s_%s_link', $vars['type'], $vars['subtype']),
                 //sprintf('cas_%s_link', $vars['subtype']),
                 //'cas_link', 
                 sprintf('cas_%s_%s_%s', $vars['area'], $vars['type'], $vars['subtype']),
                 sprintf('cas_%s_%s', $vars['type'], $vars['subtype']),
                 sprintf('cas_%s', $vars['subtype']),
                 'cas', 
             ),
             'cache_key' => 'foo',
        );
        
        $this->vars = array_merge($defaultVars, $vars);
    }
    
    public function rewind() 
    {
        if ( ! is_array($this->viewData)) {
            return;
        }
        
       return reset($this->viewData);
    }
    
    public function current()
    {
        if ( ! is_array($this->viewData)) {
            return;
        }
        
        return new CasView(current($this->viewData), $this->vars);
    }
    
    public function key()
    {
        if ( ! is_array($this->viewData)) {
            return;
        }
        
        return key($this->viewData);
    }
    
    public function next()
    {
        if ( ! is_array($this->viewData)) {
            return;
        }
        
        return new CasView(next($this->viewData), $this->vars);
    }
    
    public function valid()
    {
        if ( ! is_array($this->viewData)) {
            return;
        }
        
        $key = key($this->viewData);
        
        $isValid = ($key !== NULL && $key !== FALSE);
        
        return $isValid;
    }
    
    public function __get($key)
    {
        //error_log('In __get looking for ' . $key);
        if (isset($this->$key)) {
            return $this->$key;
        } else if (is_object($this->viewData) && isset($this->viewData->$key)) {
            return $this->viewData->$key;
        } else if (is_array($this->viewData) && array_key_exists($key, $this->viewData)) {
            return $this->viewData[$key];
        }
        
        //var_dump('__get miss: ' . $key);
        /*
        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $key . ' in ' . $trace[0]['file'] . ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        */
        return null;
    }
    
    public function __call($name, $args)
    {
        if ('get' == substr($name, 0, 3)) {
            $key = strtolower(substr($name, 3, 1)) . substr($name, 4);
            
            return $this->$key;
        }
        
        return FALSE;
    }
}

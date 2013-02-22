<?php
namespace Illumina\CasBundle\Twig;

class CasView implements \Iterator, \ArrayAccess
{
    protected $viewData;
    protected $vars;
    
    public function __construct($viewData, $vars = NULL) {
        if ($vars == NULL) {
            $vars = array();
        }
        
        if (is_object($viewData) && isset($viewData->results) && isset($viewData->num_rows)) {
            $this->viewData = $viewData->results;
            $this->numItems = $viewData->num_rows;
        } else {
            $this->viewData = $viewData;
        }
        
        if (isset($vars['area'])) {
            $block_prefix = sprintf('cas_%s_%s', $vars['area'], $vars['type']); 
            $block_prefixes = array(
                $block_prefix,    
                sprintf('cas_%s', $vars['type']),
                'cas', 
            );
        } else {
            $block_prefix = 'cas';
            $block_prefixes = array(
                'cas',
            );        
        }
        
        $defaultVars = array(
            'block_prefix' => $block_prefix,
            'block_prefixes' => $block_prefixes, 
            'cache_key' => 'foo',
            'links' => array(
                 array('link_url' => 'foo', 'title' => 'bar'),
            ),
        );
        
        $this->vars = $vars;
        foreach ($defaultVars as $key => $value) {
            if (empty($this->vars[$key])) {
                $this->vars[$key] = $value;
            }
        }
    }
    
    public function rewind() 
    {
        if ( ! is_array($this->viewData) && ! is_object($this->viewData)) {
            return;
        }
        
       return reset($this->viewData);
    }
    
    public function current()
    {
        if ( ! is_array($this->viewData) && ! is_object($this->viewData)) {
            return;
        }
        
        return new CasView(current($this->viewData), $this->vars);
    }
    
    public function key()
    {
        if ( ! is_array($this->viewData) && ! is_object($this->viewData)) {
            return;
        }
        
        return key($this->viewData);
    }
    
    public function next()
    {
        if ( ! is_array($this->viewData) && ! is_object($this->viewData)) {
            return;
        }
        
        return new CasView(next($this->viewData), $this->vars);
    }
    
    public function valid()
    {
        if ( ! is_array($this->viewData) && ! is_object($this->viewData)) {
            return;
        }
        
        $key = key($this->viewData);
        
        $isValid = ($key !== NULL && $key !== FALSE);
        
        return $isValid;
    }
    
    public function __get($key)
    {
        //error_log('In __get looking for ' . $key);
        if (property_exists($this, $key)) {
            // return new CasView($this->$key, $this->vars);
            return $this->$key;
        } else if (is_object($this->viewData) && isset($this->viewData->$key)) {
            return $this->viewData->$key;
        } else if (is_array($this->viewData) && array_key_exists($key, $this->viewData)) {
            return new CasView($this->viewData[$key], $this->vars);
        }
        
        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $key . ' in ' . $trace[0]['file'] . ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }
    
    public function __set($key, $value) 
    {
        if (is_array($this->vars)) {
            $this->vars[$key] = $value;
        } else if (is_object($this->vars)) {
            $this->vars->$key = $value;
        } else {
            $this->$key = $value;
        }
    }
    
    public function __isset($key)
    {
        //error_log('In __isset looking for ' . $key);
        if (is_object($this->viewData) && isset($this->viewData->$key)) {
            return $this->viewData->$key;
        } else if (is_array($this->viewData) && array_key_exists($key, $this->viewData)) {
            return $this->viewData[$key];
        }
    
        return FALSE;
    }
    
    public function __call($name, $args)
    {
        if ('get' == substr($name, 0, 3)) {
            $key = strtolower(substr($name, 3, 1)) . substr($name, 4);
            
            return $this->$key;
        }
        
        return FALSE;
    }
    
    public function offsetExists($offset) 
    {
        return $this->__isset($offset);
    }
    
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }
    
    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }
    
    public function offsetUnset($offset)
    {
        // undefined
    }
    
    public function __toString()
    {
        try {
            return (string) $this->viewData;
        }
        catch (Exception $e) {
            return '';
        }
    }
}

<?php

namespace CloudCompli\WQInvestigator\Support\Dataset;

trait OptionsTrait
{
    protected $_options = [];
    
    public function setOption($key, $value)
    {
        $this->_options[$key] = $value;
    }
    
    public function setOptions($options)
    {
        $this->_options = array_merge($this->_options, $options);
    }
    
    public function withOptions($options, $callback)
    {
        $originalOptions = array_slice($this->_options,0,count($this->_options));
        
        $this->setOptions($options);
        $retval = $callback($this);
        $this->_options = $originalOptions;
        return $retval;
    }
}
<?php

namespace CloudCompli\WQInvestigator\Support\Dataset;

class FileDataset
{
    use OptionsTrait;
    
    protected $_data = [];
    protected $_paths;
    
    public function __construct($paths)
    {
        if(!is_array($paths))
            $paths = [$paths];
        
        $this->_paths = $paths;
    }
    
    public function filter($filterOptions, $callback)
    {
        return $this->withOptions(['filter' => $filterOptions], $callback);
    }
    
    public function getData()
    {
        if(isset($this->_options['filter'])){
            $data = [];
            foreach($this->_data as $row){
                $passing = true;
                foreach($this->_options['filter'] as $key => $value){
                    if(!is_array($value)){
                        $value = [$value];
                    }
                    if(!isset($row[$key]) || !in_array($row[$key], $value)){
                        $passing = false;
                        break;
                    }
                }
                if($passing){
                    $data[] = $row;
                }
            }
            return $data;
        }else{
            return $this->_data;
        }
    }
}
<?php

namespace CloudCompli\WQInvestigator\Support\Dataset;

class CsvDataset extends FileDataset
{
    public function __construct($paths)
    {
        parent::__construct($paths);
        
        foreach($this->_paths as $path){
            if (($handle = fopen($path, "r")) !== FALSE) {
                $columns = array_map(function($column){
                    return preg_replace('/\s+/', '_', strtolower($column));
                }, fgetcsv($handle, 65536, ","));
                while (($data = fgetcsv($handle, 65536, ",")) !== FALSE) {
                    $row = [];
                    foreach($data as $idx => $column){
                        $row[$columns[$idx]] = $column;
                    }
                    $this->_data[] = $row;
                }
                fclose($handle);
            }
        }
    }
}
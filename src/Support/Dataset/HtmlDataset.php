<?php

namespace CloudCompli\WQInvestigator\Support\Dataset;

class HtmlDataset extends FileDataset
{
    public function __construct($paths)
    {
        parent::__construct($paths);
        
        foreach($this->_paths as $path){
            $xml = simplexml_load_file($path);
            $columns = [];
            foreach($xml->body->form->table->thead->tr->th as $th){
                $columns[] = preg_replace('/\s+/', '_', str_replace('/', '_', strtolower((string)$th)));
            }
            foreach($xml->body->form->table->tbody->tr as $tr){
                $row = [];
                $i = 0;
                foreach($tr->children() as $td){
                    $row[$columns[$i++]] = (string)$td;
                }
                $this->_data[] = $row;
            }
        }
    }
}
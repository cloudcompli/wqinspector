<?php

namespace CloudCompli\WQInvestigator\SMARTS;

use CloudCompli\WQInvestigator\Support\Dataset\SocrataDataset;
use Socrata;

class StormwaterViolations extends SocrataDataset
{
    public function get($url, $queryParameters = [])
    {
        if(array_key_exists('region_code', $this->_options) && !array_key_exists('region_code', $queryParameters)){
            $queryParameters['regulated_facility_region'] = $this->_options['region_code'];
        }
        
        return parent::get($url, $queryParameters);
    }
    
    public function compileWhere()
    {
        $where = [];
        
        if(array_key_exists('before', $this->_options) && array_key_exists('after', $this->_options)){
            $where[] = "occurred_on > '".$this->_options['after']."' and occurred_on < '".$this->_options['before']."'";
        }
        
        if(array_key_exists('within_circle', $this->_options)){
            $where[] = "within_circle(location_1, ".$this->_options['within_circle'][0].", ".$this->_options['within_circle'][1].", ".$this->_options['within_circle'][2].")";
        }
        
        if(array_key_exists('violation_type', $this->_options)){
            $where[] = implode(' OR ', array_map(function($violationType){
                return "violation_type = '".$violationType."'";
            }, $this->_options['violation_type']));
        }
        
        return count($where) > 0 ? ('('.implode(') AND (', $where).')') : null;
    }
    
    public function makeQueryParameters($params)
    {
        if(($compiledWhere = $this->compileWhere()) !== null){
            if(array_key_exists('$where', $params)){
                $params['$where'] = '('.$params['where'].') AND ('.$compiledWhere.')';
            }else{
                $params['$where'] = $compiledWhere;
            }
        }
        
        return $params;
    }
    
    public function getViolationReports()
    {
        $queryParameters = $this->makeQueryParameters([]);
        
        $result = [];
        $this->getForEachChunk('/resource/xsyg-h4ri.json', $queryParameters, function($response) use (&$result) {
            foreach($response as $row){
                $result[] = $row;
            }
        });
        return $result;
    }
    
    public function getViolationTypes()
    {
        $queryParameters = $this->makeQueryParameters([
            '$select' => 'violation_type'
        ]);
        
        $result = [];
        $this->getForEachChunk('/resource/xsyg-h4ri.json', $queryParameters, function($response) use (&$result) {
            foreach($response as $row){
                if(!in_array($row['violation_type'], $result)){
                    $result[] = $row['violation_type'];
                }
            }
        });
        return $result;
    }
}
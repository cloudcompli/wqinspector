<?php

namespace CloudCompli\WQInvestigator\CIWQS;

use CloudCompli\WQInvestigator\Support\Dataset\SocrataDataset;
use CloudCompli\WQInvestigator\Support\Soql\SoqlLiteral;
use Socrata;

class ESMR extends SocrataDataset
{
    public function get($url, $queryParameters = [])
    {
        if(array_key_exists('region_code', $this->_options) && !array_key_exists('region_code', $queryParameters)){
            $queryParameters['region_code'] = $this->_options['region_code'];
        }
        
        return parent::get($url, $queryParameters);
    }
    
    public function compileWhere()
    {
        $where = [];
        
        if(array_key_exists('before', $this->_options) && array_key_exists('after', $this->_options)){
            $where[] = "sample_date between ".SoqlLiteral::text($this->_options['after'])." and ".SoqlLiteral::text($this->_options['before']);
        }

        if(array_key_exists('within_circle', $this->_options)){
            $where[] = "within_circle(location, "
                .SoqlLiteral::number($this->_options['within_circle'][0]).", "
                .SoqlLiteral::number($this->_options['within_circle'][1]).", "
                .SoqlLiteral::number($this->_options['within_circle'][2]).")";
        }
        
        return count($where) > 0 ? ('('.implode(') AND (', $where).')') : null;
    }
    
    /**
     * Merge the option-derived clause into a caller's query parameters.
     *
     * A `$where` the caller passes in is concatenated as-is — this method cannot
     * tell a field name from a value, so it cannot escape it for you. Build any
     * caller-supplied clause with SoqlLiteral. The option-derived half is always
     * escaped by compileWhere().
     *
     * @param array $params
     * @return array
     */
    public function makeQueryParameters($params)
    {
        if(($compiledWhere = $this->compileWhere()) !== null){
            if(array_key_exists('$where', $params)){
                $params['$where'] = '('.$params['$where'].') AND ('.$compiledWhere.')';
            }else{
                $params['$where'] = $compiledWhere;
            }
        }
        
        return $params;
    }
    
    public function getData($baseQueryParameters = [])
    {
        $queryParameters = $this->makeQueryParameters($baseQueryParameters);
        
        $result = [];
        $this->getForEachChunk('/resource/64tg-janj.json', $queryParameters, function($response) use (&$result) {
            foreach($response as $row){
                $result[] = $row;
            }
        });
        return $result;
    }
    
    public function getParameterReports($parameterName)
    {
        return $this->getData([
            'parameter' => $parameterName
        ]);
    }
    
    public function getParameterByRegulatoryMeasureId($parameterName)
    {
        $facilities = [];
        
        foreach($this->getParameterReports($parameterName) as $report){
            if(!array_key_exists($report['reg_meas_id'], $facilities)){
                $facilities[$report['reg_meas_id']] = [
                    'facility_name' => $report['facility_name'],
                    'mon_locations' => []
                ];
            }
            if(!array_key_exists($report['mon_location'], $facilities[$report['reg_meas_id']]['mon_locations'])){
                $facilities[$report['reg_meas_id']]['mon_locations'][$report['mon_location']] = [
                    'coordinates' => isset($report['location']['coordinates']) ? $report['location']['coordinates'] : null, 
                    'data' => []
                ];
            }
            $facilities[$report['reg_meas_id']]['mon_locations'][$report['mon_location']]['data'][$report['sample_date']] = $report['result'].' '.$report['units'];
        }
        
        return $facilities;
    }
    
    public function getParameters()
    {
        $queryParameters = $this->makeQueryParameters([
            '$select' => 'parameter'
        ]);
        
        $result = [];
        $this->getForEachChunk('/resource/64tg-janj.json', $queryParameters, function($response) use (&$result) {
            foreach($response as $row){
                if(!in_array($row['parameter'], $result)){
                    $result[] = $row['parameter'];
                }
            }
        });
        return $result;
    }
}
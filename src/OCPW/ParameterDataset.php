<?php

namespace CloudCompli\WQInvestigator\OCPW;

use CloudCompli\WQInvestigator\Support\Dataset\CsvDataset;
use Exception;

class ParameterDataset extends CsvDataset
{
    public function getParameters()
    {
        $parameters = [];
        foreach($this->_data as $row){
            if(!in_array($row['parameter'], $parameters)){
                $parameters[] = $row['parameter'];
            }
        }
        return $parameters;
    }
    
    public function getParameterResultsByStation($parameter = null)
    {
        $callback = function($estuaryMonitoringSource) {
            $stations = [];
            // load data into stations as [stationname][sampledate][sample#]
            foreach($estuaryMonitoringSource->getData() as $row){
                if(!isset($stations[$row['station']])){
                    $stations[$row['station']] = [];
                }
                if(!isset($stations[$row['station']][$row['date']])){
                    $stations[$row['station']][$row['date']] = [];
                }
                $stations[$row['station']][$row['date']][] = $row['result'];
            }
            // average [sample#] for each station into a single value
            foreach($stations as $stationName => $stationDateValues){
                foreach($stationDateValues as $date => $values){
                    $stations[$stationName][$date] = (float)array_sum($values)/count($values);
                }
            }
            return $stations;
        };
        
        if(isset($this->_options['filter']['parameter'])){
            if(!is_null($parameter) && $parameter != $this->_options['filter']['parameter']){
                throw new Exception(__METHOD__.' cannot use non-null $parameter when already filtering by parameter as an option');
            }else if(!is_string($this->_options['filter']['parameter'])){
                throw new Exception(__METHOD__.' cannot use multiple filter options');
            }else{
                // will already be filtered by parameter as option so just call callback
                return $callback($this);
            }
        }else if(is_null($parameter)){
            throw new Exception(__METHOD__.' requires either a non-null $parameter of a filter option');
        }else{
            // filter by parameter and then invoke callback
            return $this->filter(['parameter' => [$parameter]], $callback);
        }
    }
}
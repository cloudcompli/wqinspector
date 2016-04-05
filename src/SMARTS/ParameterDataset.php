<?php

namespace CloudCompli\WQInvestigator\SMARTS;

use CloudCompli\WQInvestigator\Support\Dataset\HtmlDataset;
use Exception;

class ParameterDataset extends HtmlDataset
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
    
    public function getParameterResultsBySite($parameter = null)
    {
        $callback = function($siteSource) {
            $sites = [];
            // load data into stations as [stationname][sampledate][sample#]
            foreach($siteSource->getData() as $row){
                if(!isset($sites[$row['site_facility_name']])){
                    $sites[$row['site_facility_name']] = [];
                }
                if(!isset($sites[$row['site_facility_name']][$row['date_time_of_sample_collection']])){
                    $sites[$row['site_facility_name']][$row['date_time_of_sample_collection']] = [];
                }
                $sites[$row['site_facility_name']][$row['date_time_of_sample_collection']][] = $row['result'];
            }
            // average [sample#] for each station into a single value
            foreach($sites as $siteName => $siteDateValues){
                foreach($siteDateValues as $date => $values){
                    $sites[$siteName][$date] = (float)array_sum($values)/count($values);
                }
            }
            return $sites;
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
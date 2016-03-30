<?php

namespace CloudCompli\WQInvestigator\Support\Dataset;

use Socrata;

class SocrataDataset
{
    protected $_socrata;
    protected $_cache = null;
    protected $_options = [];
    protected $_useCache = true;
    
    public function __construct()
    {
        
    }
    
    public function computeCacheKey($url, $options = [])
    {
        return md5($url.json_encode($options));
    }
    
    public function get($url, $queryParameters = [])
    {
        $cacheKey = $this->computeCacheKey($url, $queryParameters);
        
        if($this->_cache && $this->usingCache()){
            $result = $this->_cache->get($cacheKey);
        }else{
            $result = null;
        }
        
        if($result === null){
            $result = $this->_socrata->get($url, $queryParameters);
            if($this->_cache){
                $this->_cache->set($cacheKey, $result);
            }
        }
        
        return $result;
    }
    
    public function getForEachChunk($url, $queryParameters, $callback)
    {
        $offset = 0;
        $limit = 1000;
        
        while($response = $this->get($url, array_merge($queryParameters, [
            '$limit' => $limit,
            '$offset' => $offset
        ]))){
            $callback($response);
            $offset += $limit;
        }
    }
    
    public function setCacheHandler($cache)
    {
        $this->_cache = $cache;
    }
    
    public function setOption($key, $value)
    {
        $this->_options[$key] = $value;
    }
    
    public function setOptions($options)
    {
        $this->_options = array_merge($this->_options, $options);
    }
    
    public function setSocrataHandler(Socrata $socrata)
    {
        $this->_socrata = $socrata;
    }
    
    public function usingCache()
    {
        return $this->_useCache;
    }
    
    public function useCache($useCache = true)
    {
        $this->_useCache = $useCache;
    }
    
    public function withoutCache($callback)
    {
        $originalCacheValue = $this->usingCache();
        
        $this->useCache(false);
        $callback($this);
        $this->useCache($originalCacheValue);
    }
    
    public function withOptions($options, $callback)
    {
        $originalOptions = array_slice($this->_options,0,count($this->_options));
        
        $this->setOptions($options);
        $callback($this);
        $this->_options = $originalOptions;
    }
}
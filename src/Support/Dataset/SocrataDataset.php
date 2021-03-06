<?php

namespace CloudCompli\WQInvestigator\Support\Dataset;

use Socrata;

class SocrataDataset
{
    use OptionsTrait;
    
    protected $_socrata;
    protected $_cache = null;
    protected $_useCache = true;
    
    public function __construct($socrata = null)
    {
        if($socrata)
            $this->setSocrataHandler($socrata);
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
}
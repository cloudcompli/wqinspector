<?php

require dirname(__DIR__).'/vendor/autoload.php';
require __DIR__.'/config.php';

$socrata = new Socrata('https://greengov.data.ca.gov', $socrataToken);
$cache = phpFastCache\CacheManager::Files([
    "storage" => "files",
    "path" => __DIR__."/cache",
]);

$esmr = new CloudCompli\WQInvestigator\CIWQS\ESMR($socrata);
$esmr->setCacheHandler($cache);
$esmr->setOptions([
    'region_code' => '8',
    'after' => '2015-01-01T00:00:00',
    'before' => '2016-01-01T00:00:00'
]);

var_dump($esmr->getParameters());

$esmr->withOptions([
    'within_circle' => ['33.68813', '-117.819', '20000']
], function($esmr){
    var_dump($esmr->getParameterByRegulatoryMeasureId('Selenium, Total'));
});

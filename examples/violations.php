<?php

require dirname(__DIR__).'/vendor/autoload.php';
require __DIR__.'/config.php';

$socrata = new Socrata('https://greengov.data.ca.gov', $socrataToken);
$cache = phpFastCache\CacheManager::Files([
    "storage" => "files",
    "path" => __DIR__."/cache",
]);

$violations = new CloudCompli\WQInvestigator\SMARTS\StormwaterViolations($socrata);
$violations->setCacheHandler($cache);
$violations->setOptions([
    'region_code' => '8',
    'after' => '2015-01-01T00:00:00',
    'before' => '2016-01-01T00:00:00'
]);

var_dump($violations->getViolationTypes());

$violations->withOptions([
    'within_circle' => ['33.68813', '-117.819', '20000'],
    'violation_type' => [
        'Deficient BMP Implementation',
        'Unauthorized NSWD',
        'Unregulated Discharge',
        'Effluent'
    ]
], function($violations){
    var_dump($violations->getViolationReports());
});
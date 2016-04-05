<?php

require dirname(__DIR__).'/vendor/autoload.php';
require __DIR__.'/config.php';

$dataBasePath = __DIR__.'/data/ocwatersheds/';

$smartsParameters = new CloudCompli\WQInvestigator\SMARTS\ParameterDataset([
    $dataBasePath.'smarts-ind-1415.html',
    $dataBasePath.'smarts-ind-1516.html',
    $dataBasePath.'smarts-const-1415.html',
    $dataBasePath.'smarts-const-1516.html',
]);

var_dump($smartsParameters->getParameters());

$smartsParameters->filter([
    'parameter' => 'Selenium, Total'
], function($smartsParameters){
    var_dump($smartsParameters->getParameterResultsBySite());
    var_dump($smartsParameters->getData());
});

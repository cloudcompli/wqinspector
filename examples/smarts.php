<?php

require dirname(__DIR__).'/vendor/autoload.php';
require __DIR__.'/config.php';

$dataBasePath = __DIR__.'/data/ocwatersheds/';

$smarts = new CloudCompli\WQInvestigator\SMARTS\ParameterDataset([
    $dataBasePath.'smarts-ind-1415.html',
    $dataBasePath.'smarts-ind-1516.html',
    $dataBasePath.'smarts-const-1415.html',
    $dataBasePath.'smarts-const-1516.html',
]);

var_dump($smarts->getParameters());

$smarts->filter([
    'parameter' => 'Selenium, Total'
], function($smarts){
    var_dump($smarts->getParameterResultsBySite());
    var_dump($smarts->getData());
});

<?php

require dirname(__DIR__).'/vendor/autoload.php';
require __DIR__.'/config.php';

$dataBasePath = __DIR__.'/data/ocwatersheds/';

$sarMassEmissions = new CloudCompli\WQInvestigator\OCPW\ParameterDataset($dataBasePath.'sarme.csv');

//var_dump($sarMassEmissions->getParameters());

$sarMassEmissions->filter([
    'parameter' => 'Se', 
    'watershed' => 'Newport Bay'
], function($sarMassEmissions){
    var_dump($sarMassEmissions->getParameterResultsByStation());
    var_dump($sarMassEmissions->getData());
});

/*
$esmDataFile = $dataBasePath.'esm.csv';

$nsmpDataFiles = [];
$nsmpDataFiles[] = $dataBasePath.'nsmp_2015-q1.csv';
$nsmpDataFiles[] = $dataBasePath.'nsmp_2015-q2.csv';
$nsmpDataFiles[] = $dataBasePath.'nsmp_2015-q3.csv';

$ewm = new CloudCompli\WQInvestigator\OCPW\ParameterDataset($esmDataFile);
$nsmp = new CloudCompli\WQInvestigator\OCPW\ParameterDataset($nsmpDataFiles);

var_dump($ewm->getParameters());

var_dump($ewm->getParameterResultsByStation('Se'));
var_dump($nsmp->getParameterResultsByStation('Se'));

$ewm->filter(['parameter' => 'Se', 'watershed' => 'Newport Bay'], function($ewm){
    var_dump($ewm->getData());
});

$nsmp->filter(['parameter' => 'Se', 'watershed' => 'Newport Bay'], function($nsmp){
    var_dump($nsmp->getData());
});
*/
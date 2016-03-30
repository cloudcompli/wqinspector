<?php

require dirname(__DIR__).'/vendor/autoload.php';
require __DIR__.'/config.php';

$socrata = new Socrata('https://greengov.data.ca.gov', $socrataToken);
$cache = phpFastCache\CacheManager::Files([
    "storage" => "files",
    "path" => __DIR__."/cache",
]);

$esmr = new CloudCompli\WQInvestigator\CIWQS\ESMR($socrata);
$esmr->setSocrataHandler($socrata);
$esmr->setCacheHandler($cache);
$esmr->setOptions([
    'region_code' => '8',
    'after' => '2015-01-01T00:00:00',
    'before' => '2016-01-01T00:00:00'
]);

$parameters = $esmr->getParameters();
sort($parameters);

?>

<form action="esmr.php" method="GET">
    <select name="parameter" required>
        <?php foreach($parameters as $parameter){ ?>
        <option><?php echo $parameter; ?></option>
        <?php } ?>
    </select>
    <br>
    <label>Latitude</label>
    <input name="latitude" value="33.68813" required>
    <br>
    <label>Longitude</label>
    <input name="longitude" value="-117.819" required>
    <br>
    <label>Radius (meters)</label>
    <input name="radius" value="20000" required>
    <br>
    <input type="submit" value="Search">
</form>

<?php

if(isset($_GET['parameter']) 
        && isset($_GET['latitude']) 
        && isset($_GET['longitude']) 
        && isset($_GET['radius'])){
    
    echo '<script>';
    foreach(['parameter','latitude','longitude','radius'] as $field){
        echo 'document.querySelectorAll(\'[name="'.$field.'"]\')[0].value = "'.$_GET[$field].'";';
    }
    echo '</script>';
    
    $esmr->withOptions([
        'within_circle' => [$_GET['latitude'], $_GET['longitude'], $_GET['radius']]
    ], function($esmr){
        var_dump($esmr->getParameterByRegulatoryMeasureId($_GET['parameter']));
    });
    
}

?>
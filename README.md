# WQInspector

WQInspector parses weather data coming from state and municipal water quality and regulatory sources. These sources currently include:

* [California Integrated Water Quality System (CIWQS)](http://www.waterboards.ca.gov/ciwqs/)
* [Storm Water Multiple Application and Report Tracking System (SMARTS)](https://smarts.waterboards.ca.gov/smarts/faces/SwSmartsLogin.jsp)
* [OC Watersheds](http://ocwatersheds.com)

Some data is accessed via open data APIs, such as California's GreenGov pilot. Because not all sources are available via APIs though, other data is loaded from data extracts.

This software is part of a research project by [CloudCompli](http://cloudcompli.com) and [Orange County Public Works](http://ocpublicworks.com/) for the [CA Water Board Data Innovation Challenge](http://www.waterboards.ca.gov/resources/data_databases/data_innov_challenge.shtml).

# Installation

#### Requirements

The WQInspector requires:

* [PHP 5+](http://php.net/manual/en/install.php)
* [PHP Composer](https://getcomposer.org/download/)

#### Setup

Open the repo root directory and then install dependencies with composer:

```
composer install
```

And that's it.

#### Configuration

If you plan on querying any of the Socrata-backed data sources (like CIWQS eSMR or SMARTS Stormwater Violations from data.ca.gov), you must [register for a token](https://dev.socrata.com/register) and use it when you instantiate the `Socrata` object.

For the example code, you should add it to `example/config.php`.

# Usage

### `CIWQS\ESMR`

Instantiate the CIWQS ESMR querier by passing it a Socrata object:

```php
$socrata = new Socrata('https://greengov.data.ca.gov', $socrataToken);
$esmr = new CloudCompli\WQInvestigator\CIWQS\ESMR($socrata);
```

Because this is a web service call, initialize a cache handler before starting to do queries:

```php
$cache = phpFastCache\CacheManager::Files([
    "storage" => "files",
    "path" => __DIR__."/cache",
]);
$esmr->setCacheHandler($cache);
```

Set options to be applied to queries:

```php
$esmr->setOptions([
    'region_code' => '8',
    'after' => '2015-01-01T00:00:00',
    'before' => '2016-01-01T00:00:00'
]);
```

Options can be applied within a scope so that they don't apply to subsequent queries:

```php
$esmr->withOptions([
    'within_circle' => [$_GET['latitude'], $_GET['longitude'], $_GET['radius']]
], function($esmr){
    // ..
});
```

Get a list of parameters reported in at least one eSMR:

```php
$parameters = $esmr->getParameters();
```

Get a list of all regulated facilities that have reported a parameter, and the values for that parameter that have been reported by the site:

```php
$esmr->getParameterByRegulatoryMeasureId('Se');
```

### `OCPW\ParameterDataset`

The constructor takes the path to a CSV downloaded from OC Watersheds data archive:

```php
$sarMassEmissions = new CloudCompli\WQInvestigator\OCPW\ParameterDataset($dataBasePath.'sarme.csv');
```

The constructor also supports an array of file names:

```php
$nsmpDataFiles = [];
$nsmpDataFiles[] = $dataBasePath.'nsmp_2015-q1.csv';
$nsmpDataFiles[] = $dataBasePath.'nsmp_2015-q2.csv';
$nsmpDataFiles[] = $dataBasePath.'nsmp_2015-q3.csv';
$nsmp = new CloudCompli\WQInvestigator\OCPW\ParameterDataset($nsmpDataFiles);
```

Get a list of parameters in the data set:

```php
$sarMassEmissions->getParameters();
```

Filter by columns in the data set:

```php
$sarMassEmissions->filter([
    'parameter' => 'Se', 
    'watershed' => 'Newport Bay'
], function($sarMassEmissions){
    // ..
});
```

Get a list of all recorded measurements, applying any filters as specified above:

```php
$sarMassEmissions->getData();
```

Get a list of all regulated facilities that have reported a parameter, and the values for that parameter that have been reported by the site:

```php
$sarMassEmissions->getParameterResultsByStation('Se');
```

If the context sets the filter, you do not have to specify it as a parameter of this method call:

```php
$sarMassEmissions->filter([
    'parameter' => 'Se', 
    'watershed' => 'Newport Bay'
], function($sarMassEmissions){
    $seleniumResults = $sarMassEmissions->getParameterResultsByStation();
});
```

### `SMARTS\ParameterDataset`

The constructor takes the path to an HTML file exported from SMARTS:

```php
$smarts = new CloudCompli\WQInvestigator\SMARTS\ParameterDataset($dataBasePath.'smarts-ind-1415.html');
```

The constructor also supports an array of file names:

```php
$smarts = new CloudCompli\WQInvestigator\SMARTS\ParameterDataset([
    $dataBasePath.'smarts-ind-1415.html',
    $dataBasePath.'smarts-ind-1516.html',
    $dataBasePath.'smarts-const-1415.html',
    $dataBasePath.'smarts-const-1516.html',
]);
```

Get a list of parameters in the data set:

```php
$smarts->getParameters();
```

Filter by columns in the data set:

```php
$smarts->filter([
    'parameter' => 'Selenium, Total'
], function($smarts){
    // ..
});
```

Get a list of all recorded measurements, applying any filters as specified above:

```php
$smarts->getData();
```

Get a list of all regulated facilities that have reported a parameter, and the values for that parameter that have been reported by the site:

```php
$smarts->getParameterResultsBySite('Selenium, Total');
```

If the context sets the filter, you do not have to specify it as a parameter of this method call:

```php
$smarts->filter([
    'parameter' => 'Selenium, Total'
], function($smarts){
    $seleniumResluts = $smarts->getParameterResultsBySite();
});
```

### `SMARTS\StormwaterViolations`

Instantiate the SMARTS Stormwater Violations querier by passing it a Socrata object:

```php
$socrata = new Socrata('https://greengov.data.ca.gov', $socrataToken);
$violations = new CloudCompli\WQInvestigator\SMARTS\StormwaterViolations($socrata);
```

Because this is a web service call, initialize a cache handler before starting to do queries:

```php
$cache = phpFastCache\CacheManager::Files([
    "storage" => "files",
    "path" => __DIR__."/cache",
]);
$violations->setCacheHandler($cache);
```

Set options to be applied to queries:

```php
$violations->setOptions([
    'region_code' => '8',
    'after' => '2015-01-01T00:00:00',
    'before' => '2016-01-01T00:00:00'
]);
```

Options can be applied within a scope so that they don't apply to subsequent queries:

```php
$violations->withOptions([
    'within_circle' => ['33.68813', '-117.819', '20000']
], function($violations){
    // ...
});
```

Get a list of violation types reported in at least one report:

```php
$violations->getViolationTypes();
```

Get a list of all recorded violations:

```php
$violations->getViolationReports();
```

Get a list of all reports of certain violation types within a certain radius of a point:

```php
$violations->withOptions([
    'within_circle' => ['33.68813', '-117.819', '20000'],
    'violation_type' => [
        'Deficient BMP Implementation',
        'Unauthorized NSWD',
        'Unregulated Discharge',
        'Effluent'
    ]
], function($violations){
    $reports = $violations->getViolationReports();
});
```
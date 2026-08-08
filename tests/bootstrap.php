<?php

/*
 * Standalone PSR-4 autoloader for the library's own classes.
 *
 * These tests exercise SoQL clause construction, which is pure string work —
 * no Socrata client, no cache, no network. Autoloading src/ directly keeps the
 * suite runnable without a composer install, and keeps it independent of any
 * consuming application's bootstrap.
 */

/*
 * Registered with $prepend = true on purpose. A consuming application's composer
 * autoloader maps this same namespace at its own pinned tag, and if the suite is
 * run with that application's phpunit binary its autoloader is already registered
 * — so without prepending, the tests silently exercise the installed release
 * instead of the working tree. SoqlInjectionTest asserts the loaded path to keep
 * that honest.
 */
spl_autoload_register(function ($class) {
    $prefix = 'CloudCompli\\WQInvestigator\\';

    if(strpos($class, $prefix) !== 0){
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = dirname(__DIR__).'/src/'.str_replace('\\', '/', $relative).'.php';

    if(file_exists($path)){
        require_once $path;
    }
}, true, true);

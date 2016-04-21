<?php

date_default_timezone_set( 'Europe/Warsaw' );

use Symfony\Component\Debug\Debug;

require_once __DIR__.'/vendor/autoload.php';

$app = new Silex\Application();

Debug::enable();

require __DIR__.'/config/dev.php';
require __DIR__ . '/src/routs.php';

//echo phpinfo();exit;

$app->run();
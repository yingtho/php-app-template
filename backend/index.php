<?php
use Innometrics\Helper;

require_once('vendor/autoload.php');

$app = new Silex\Application(); // Allows easily build a application
$app['debug'] = true;

$inno = new Helper(); // Innometrics helper to work with profile cloud

/**
 * Init params from environment variables. Innometrics platform sets environment variables during install to Paas.
 * In case of manual install of backend part, you need to setup these manually.
 */
$vars = array(
    'bucketName'    => getenv('INNO_BUCKET_ID'),
    'appKey'        => getenv('INNO_APP_KEY'),
    'appName'       => getenv('INNO_APP_ID'),
    'groupId'       => getenv('INNO_COMPANY_ID'),
    'apiUrl'        => getenv('INNO_API_HOST'),
    'collectApp'    => getenv('INNO_APP_ID')
);
$inno->setVars($vars);

// POST request to "/" is always expected to recieve stream with events
$app->post('/', function() use($app) {
    return $app->json(array(
        'message' => 'Welcom to Innometrics profile cloud!'
    ));
});

// Starting application
$app->run();

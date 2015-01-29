<?php

use Symfony\Component\HttpFoundation\Request;
use Innometrics\Helper;

require_once('simple-cache.php');
require_once('vendor/autoload.php');

$app = new Silex\Application();
$app['debug'] = true;

$inno = new Helper();
$cache = new SimpleCache(getenv('INNO_DB_URL'));

$inno->setVars(array(
    'bucketName'    => getenv('INNO_BUCKET_ID'),
    'appKey'        => getenv('INNO_APP_KEY'),
    'appName'       => getenv('INNO_APP_ID'),
    'groupId'       => getenv('INNO_COMPANY_ID'),
    'apiUrl'        => getenv('INNO_API_HOST')
));
$inno->setVar('collectApp', getenv('INNO_APP_ID'));

$app->get('/', function() {
    return '';
});

$app->post('/', function(Request $request) use($app, $inno, $cache) {
    try {
        $data = $inno->getStreamData($request->getContent());
    } catch (\ErrorException $error) {
        return $app->json(array(
            'error' => $error
        ));
    }

    $dataIsOk = isset($data->profile->id, $data->event->definitionId, $data->event->createdAt, $data->data);
    if(!$dataIsOk) {
        return $app->json(array(
            'error' => 'Stream data is not correct'
        ));
    }

    $cache->add(json_encode(array(
        'profile'       => $data->profile->id,
        'created_at'    => $data->event->createdAt,
        'event'         => $data->event->definitionId,
        'values'        => $data->data,
        'link'          => $inno->webProfileAppUrl($inno->getVars())
    )));

    try {
        $settings = $inno->getSettings();
    } catch (\ErrorException $error) {
        return $app->json(array(
            'error' => $error
        ));
    }

    $result = $inno->setAttributes($settings);
    if ($result === false) {
        return $app->json(array(
            'error' => 'Saving attributes failed'
        ));
    }

    return $app->json(array(
        'error' => null,
        'data' => $settings
    ));
});

$app->get('/last-ten-values', function() use($app, $cache) {
    $values = $cache->get();
    if (count($values) > 10) {
        $values = array_slice($values, -10);
    }
    $cache->set($values);
    return $app->json(array(
        'error' => null,
        'data' => $values
    ));
});

$app->run();

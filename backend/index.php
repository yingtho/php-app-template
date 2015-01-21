<?php

use Symfony\Component\HttpFoundation\Request;

require('simple-cache.php');
require('inno-helper/index.php');
require('vendor/autoload.php');

$app = new Silex\Application();
$app['debug'] = true;

$inno = new InnoHelper();
$cache = new SimpleCache(getenv('INNO_DB_URL'));

$vars = new \stdClass();
$vars->bucketName = getenv('INNO_BUCKET');
$vars->appKey = getenv('INNO_APP_KEY');
$vars->appName = getenv('INNO_APP_NAME');
$vars->groupId = getenv('INNO_COMPANY_ID');
$vars->apiUrl = getenv('INNO_API_URL');
$inno->setVars($vars);
$inno->setVar('collectApp', getenv('INNO_APP_NAME'));

$app->get('/', function() {
    return '';
});

$app->post('/', function(Request $request) use($app, $inno, $cache, $vars) {
    return $inno->getDatas($request, function($error, $data = array()) use($app, $request, $cache, $inno, $vars) {
        if(!isset($data['event']['createdAt']) || !isset($data['data']) || !isset($data['event']['definitionId']) || !isset($data['profile']['id'])) {
            return $app->json(array(
                'error' => 'Stream data is not correct'
            ));
        }
        $cache->add(json_encode(array(
            'created_at' => $data['event']['createdAt'],
            'values' => $data['data'],
            'event' => $data['event']['definitionId'],
            'profile' => $data['profile']['id'],
            'link' => $inno->webProfilesAppUrl($vars)
        )));
        return $inno->getSettings((object)array(
            'vars' => $inno->getVars()
        ), function ($error, $settings) use($app, $request, $inno) {
            return $inno->setAttributes((object)array(
                'vars' => $inno->getVars(),
                'data' => $settings
            ), function ($error) use($app, $request, $settings) {
                return $app->json(array(
                    'error' => null,
                    'data' => $settings
                ));
            });

        });
    });
});

$app->get('/last-ten-values', function() use($app, $cache) {
    $values = $cache->get();
    if (count($values) > 10) {
        $values = array_slice($values, -10, 10);
    }
    $cache->set($values);
    return $app->json(array(
        'error' => null,
        'data' => $values
    ));
});

$app->run();

<?php

use Symfony\Component\HttpFoundation\Request;

require_once('simple-cache.php');
require_once('inno-helper/index.php');
require_once('vendor/autoload.php');

$app = new Silex\Application();
$app['debug'] = true;

$inno = new InnoHelper();
$cache = new SimpleCache(getenv('INNO_DB_URL'));

$vars = new \stdClass();
$vars->bucketName = getenv('INNO_BUCKET_ID');
$vars->appKey = getenv('INNO_APP_KEY');
$vars->appName = getenv('INNO_APP_ID');
$vars->groupId = getenv('INNO_COMPANY_ID');
$vars->apiUrl = getenv('INNO_API_HOST');
$inno->setVars($vars);
$inno->setVar('collectApp', getenv('INNO_APP_ID'));

$app->get('/', function() {
    return '';
});

$app->post('/', function(Request $request) use($app, $inno, $cache, $vars) {
    return $inno->getDatas($request, function($error, $data = array()) use($app, $cache, $inno, $vars) {
        if($error) {
            return $app->json(array(
                'error' => $error
            ));
        }
        $dataIsOk = isset($data['event']['createdAt'], $data['data'], $data['event']['definitionId'], $data['profile']['id']);
        if(!$dataIsOk) {
            return $app->json(array(
                'error' => 'Stream data is not correct'
            ));
        }
        $cache->add(json_encode(array(
            'created_at' => $data['event']['createdAt'],
            'values' => $data['data'],
            'event' => $data['event']['definitionId'],
            'profile' => $data['profile']['id'],
            'link' => $inno->webProfileAppUrl($vars)
        )));
        return $inno->getSettings((object)array(
            'vars' => $inno->getVars()
        ), function ($error, $settings = array()) use($app, $inno) {
            if($error) {
                return $app->json(array(
                    'error' => $error
                ));
            }
            return $inno->setAttributes((object)array(
                'vars' => $inno->getVars(),
                'data' => $settings
            ), function ($error) use($app, $settings) {
                if($error) {
                    return $app->json(array(
                        'error' => $error
                    ));
                }
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
        $values = array_slice($values, -10);
    }
    $cache->set($values);
    return $app->json(array(
        'error' => null,
        'data' => $values
    ));
});

$app->run();

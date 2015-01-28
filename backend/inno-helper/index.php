<?php

class InnoHelper
{
    /**
     * Assoc array with environment vars
     * @var object
     */
    private $vars;

    public function __construct() {
        $this->setVars(array());
    }

    /**
     * Create URL to certain profile
     * @example http://api.innomdc.com/v1/companies/4/buckets/testbucket/profiles/vze0bxh4qpso67t2dxfc7u81a5nxvefc
     * @param object|array $params=null
     * @return string
     */
    public function webProfileAppUrl($params = null) {
        $vars = $this->getVars();
        $params = is_null($params) ? $vars : (object)$params;
        return sprintf('%s/v1/companies/%s/buckets/%s/profiles/%s', $vars->apiUrl, $params->groupId, $params->bucketName, $params->profileId);
    }

    /**
     * Create URL to certain profile using App key
     * @example http://api.innomdc.com/v1/companies/4/buckets/testbucket/profiles/vze0bxh4qpso67t2dxfc7u81a5nxvefc?app_key=8HJ3hnaxErdJJ62H
     * @param object|array $params
     * @return string
     */
    private function profileAppUrl($params = null) {
        $params = is_null($params) ? $this->getVars() : (object)$params;
        return sprintf('%s?app_key=%s', $this->webProfileAppUrl($params), $params->appKey);
    }

    /**
     * Create URL to app settings
     * @example http://api.innomdc.com/v1/companies/4/buckets/testbucket/apps/testapp/custom?app_key=8HJ3hnaxErdJJ62H
     * @param object|array $params
     * @return string
     */
    private function settingsAppUrl($params = null) {
        $vars = $this->getVars();
        $params = is_null($params) ? $vars : (object)$params;
        return sprintf('%s/v1/companies/%s/buckets/%s/apps/%s/custom?app_key=%s', $vars->apiUrl, $params->groupId, $params->bucketName, $params->appName, $params->appKey);
    }

    /**
     * Make a http request
     * @param array $params
     * @return mixed
     */
    private function request($params) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, 0);
        switch (isset($params['type']) ? $params['type'] : 'get') {
            case 'post':
                curl_setopt($curl, CURLOPT_POST, 1);
                if(!empty($params['body'])) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params['body']));
                }
                break;
            case 'get':
            default:
                if(!empty($params['qs'])) {
                    $params['url'] .= '?'.http_build_query($params['qs']);
                }
                break;
        }
        if(!empty($params['headers'])) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $params['headers']);
        }
        curl_setopt($curl, CURLOPT_URL, $params['url']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close ($curl);
        
        return $response;
    }

    /**
     * Get environment vars
     * @example
     * {
     *      bucketName: 'testbucket',
     *      appKey: '8HJ3hnaxErdJJ62H',
     *      appName: 'testapp',
     *      groupId: '4',
     *      apiUrl: 'http://api.innomdc.com',
     *      collectApp: 'web',
     *      section: 'testsection',
     *      profileId: 'omrd9lsa70bqukicsctlcvcu97xwehgm'
     * }
     * @return object
     */
    public function getVars() {
        return $this->vars;
    }

    /**
     * Set environment vars
     * @param object|array $vars
     */
    public function setVars($vars) {
        $this->vars = (object)$vars;
    }

    /**
     * Set environment var by name
     * @param string $name
     * @param mixed $value
     */
    public function setVar($name, $value) {
        $this->vars->{$name} = $value;
    }

    /**
     * Parse start session data
     * @param string $content
     * @return object
     */
    public function getDatas($content) {
        $data = $this->parseStreamData($content);

        $this->setVar('profileId', $data->profile->id);
        $this->setVar('collectApp', $data->session->collectApp);
        $this->setVar('section', $data->session->session);

        return $data;
    }

    /**
     * @param mixed $rawData
     * @return object
     * @throws ErrorException
     */
    public function parseStreamData ($rawData) {
        $data = $rawData;
        if (!is_object($data)) {
            $data = json_decode($data, true);
        }

        if (!isset($data['profile'])) {
            throw new \ErrorException('Profile not found');
        }
        $profile = $data['profile'];

        if(!isset($profile['id'])) {
            throw new \ErrorException('Profile id not found');
        }

        if(!isset($profile['sessions'][0])) {
            throw new \ErrorException('Session not found');
        }
        $session = $profile['sessions'][0];

        if(!isset($session['collectApp'])) {
            throw new \ErrorException('CollectApp not found');
        }

        if(!isset($session['section'])) {
            throw new \ErrorException('Section not found');
        }

        if(!isset($session['events'][0]['data'])) {
            throw new \ErrorException('Data not set');
        }

        $result = array(
            'profile'   => $profile,
            'session'   => $session,
            'event'     => $session['events'][0],
            'data'      => $session['events'][0]['data']
        );

        return (object)$result;
    }

    /**
     * Get application settings
     * @param object|array $params=null
     * @return mixed
     * @throws ErrorException
     */
    public function getSettings($params = null) {
        $params = (object)$params;
        $vars = $this->mergeVars($this->getVars(), $params);
        $url = $this->settingsAppUrl(array(
            'groupId'       => $vars->groupId,
            'bucketName'    => $vars->bucketName,
            'appKey'        => $vars->appKey,
            'appName'       => $vars->appName
        ));

        $response = $this->request(array('url' => $url));
        $body = json_decode($response);
        if(!isset($body->custom)) {
            throw new \ErrorException('Custom settings not found');
        }

        return $body->custom;
    }

    /**
     * Update attributes of the profile
     * @param object|array $attributes
     * @param object|array $params=null
     * @return mixed
     */
    public function setAttributes($attributes, $params = null) {
        $attributes = (object)$attributes;
        $params = (object)$params;
        $vars = $this->mergeVars($this->getVars(), $params);

        $url = $this->profileAppUrl(array(
            'groupId'       => $vars->groupId,
            'bucketName'    => $vars->bucketName,
            'appKey'        => $vars->appKey,
            'profileId'     => $vars->profileId
        ));

        $requestParams = array(
            'url'   => $url,
            'type'  => 'post',
            'headers' => array(
                'Content-Type: application/json',
                'Accept: application/json'
            ),
            'body' => array(
                'id' => $vars->profileId,
                'attributes' => array(array(
                    'collectApp'    => $vars->collectApp,
                    'section'       => $vars->section,
                    'data'          => $attributes
                ))
            )
        );
        return $this->request($requestParams);
    }

    /**
     * Get attributes of the profile
     * @param object|array $params=null
     * @return array
     * @throws ErrorException
     */
    public function getAttributes($params = null) {
        $params = (object)$params;
        $vars = $this->mergeVars($this->getVars(), $params);

        $url = $this->profileAppUrl(array(
            'groupId'       => $vars->groupId,
            'bucketName'    => $vars->bucketName,
            'appKey'        => $vars->appKey,
            'profileId'     => $vars->profileId
        ));

        $response = $this->request(array('url' => $url));

        $body = json_decode($response);

        if(!isset($body->profile)) {
            throw new \ErrorException('Profile not found');
        }
        $attributes = array();
        if (!empty($body->profile->attributes)) {
            $attributes = $body->profile->attributes;
        }
        return $attributes;
    }

    /**
     * @param object|array $main
     * @param object|array $overrides
     * @return object
     */
    private function mergeVars($main, $overrides) {
        $main = (object)$main;
        $overrides = (object)$overrides;
        $keys = array_merge(get_object_vars($main), get_object_vars($overrides));
        $vars = array();
        foreach ($keys as $k=>$v) {
            $vars[$k] = isset($overrides->$k)?:$main->$k;
        }
        return (object)$vars;
    }
}

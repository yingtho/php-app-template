<?php


namespace Innometrics;

/**
 * InnoHelper TODO add description
 * @copyright 2015 Innometrics
 */
class InnoHelper
{
    /**
     * Object with environment vars
     * @var object
     */
    protected $vars;

    public function __construct() {
        $this->setVars(array());
    }

    /**
     * Form URL to certain profile
     *
     * <b>Example:</b>
     *      $url = $helper->webProfileAppUrl(array(
     *          "groupId"       => "42",
     *          "bucketName"    => "testbucket",
     *          "profileId"     => "vze0bxh4qpso67t2dxfc7u81a5nxvefc"
     *      ));
     *      echo $url;
     *      ------->
     *      http://api.innomdc.com/v1/companies/42/buckets/testbucket/profiles/vze0bxh4qpso67t2dxfc7u81a5nxvefc
     *
     * @param object|array $params Custom parameters to form URL. If some/all parameters omitted, they will be taken from stored env. vars
     * @return string URL to make API request
     */
    public function webProfileAppUrl($params = null) {
        $vars = $this->getVars();
        $params = is_null($params) ? $vars : (object)$params;
        return sprintf('%s/v1/companies/%s/buckets/%s/profiles/%s', $vars->apiUrl, $params->groupId, $params->bucketName, $params->profileId);
    }

    /**
     * Form URL to certain profile using App key
     *
     * * <b>Example:</b>
     *      $url = $this->profileAppUrl(array(
     *          "profileId"     => "vze0bxh4qpso67t2dxfc7u81a5nxvefc",
     *          "appKey"        => "3R0o0m5a8n7"
     *      ));
     *      echo $url;
     *      ------->
     *      http://api.innomdc.com/v1/companies/42/buckets/testbucket/profiles/vze0bxh4qpso67t2dxfc7u81a5nxvefc?app_key=3R0o0m5a8n7
     *
     * @param object|array $params Custom parameters to form URL
     * @return string URL to make API request
     *
     */
    protected function profileAppUrl($params = null) {
        $params = is_null($params) ? $this->getVars() : (object)$params;
        return sprintf('%s?app_key=%s', $this->webProfileAppUrl($params), $params->appKey);
    }

    /**
     * Form URL to app settings
     *
     * <b>Example:</b>
     *      $url = $this->settingsAppUrl(array(
     *          "bucketName"    => "testbucket",
     *          "appName"       => "testapp"
     *      ));
     *      echo $url;
     *      ------->
     *      http://api.innomdc.com/v1/companies/42/buckets/testbucket/apps/testapp/custom?app_key=8HJ3hnaxErdJJ62H
     *
     * @param object|array $params Custom parameters to form URL
     * @return string URL to make API request
     */
    protected function settingsAppUrl($params = null) {
        $vars = $this->getVars();
        $params = is_null($params) ? $vars : (object)$params;
        return sprintf('%s/v1/companies/%s/buckets/%s/apps/%s/custom?app_key=%s', $vars->apiUrl, $params->groupId, $params->bucketName, $params->appName, $params->appKey);
    }

    /**
     * Internal method to make http requests, curl used
     * @param array $params List of parameters to configure request
     * * $params['url']     - string, required.
     * * $params['type']    - string. Defines type of request. Possible values: 'POST' or 'GET' ('GET' used by default)
     * * $params['body']    - string. Request body.
     * * $params['qs']      - array. Key=>value pairs used to create "query" part of URL
     * * $params['headers'] - array. Custom HTTP headers
     * @return string|bool string with response or false if request failed
     */
    protected static function request($params) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, 0);
        switch (strtolower(isset($params['type']) ? : 'get')) {
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
     *
     * <b>Example:</b>
     *      $vars = $helper->getVars();
     *      var_dump($vars);
     *      ------->
     *      stdClass Object
     *      (
     *          [bucketName]    => "testbucket",
     *          [appKey]        => "8HJ3hnaxErdJJ62H",
     *          [appName]       => "testapp",
     *          [groupId]       => "4",
     *          [apiUrl]        => "http://api.innomdc.com",
     *          [collectApp]    => "web",
     *          [section]       => "testsection",
     *          [profileId]     => "omrd9lsa70bqukicsctlcvcu97xwehgm"
     *       )
     *
     * @return object
     */
    public function getVars() {
        return $this->vars;
    }

    /**
     * Set environment vars
     *
     * <b>Example:</b>
     *      $helper->setVars(array(
     *          "bucketName"    => "mybucket",
     *          "appName"       => "coolapp"
     *      ));
     *
     * @param object|array $vars Key=>value pairs with environment vars
     */
    public function setVars($vars) {
        $this->vars = (object)$vars;
    }

    /**
     * Set environment variable by name
     *
     * <b>Example:</b>
     *      $helper->setVar("bucketName", "mybucket");
     *
     * @param string $name Variable name
     * @param mixed $value Variable value
     */
    public function setVar($name, $value) {
        $this->vars->{$name} = $value;
    }

    /**
     * Parse start session data and set found environment variables
     *
     * <b>Example:</b>
     *      ........
     *      $content = $response->getContent();
     *      try {
     *          $data = $helper->getStreamData($content);
     *          var_dump($data);
     *          ------->
     *          stdClass Object
     *              (
     *                  [profile]   => stdClass Object,
     *                  [session]   => stdClass Object,
     *                  [event]     => stdClass Object,
     *                  [data]      => stdClass Object
     *              )
     *
     *      } catch (\ErrorException $e) {
     *          // content has not profile data
     *      }
     *
     * @param string $content
     * @return object Object with properties: profile, session, events, data
     */
    public function getStreamData($content) {
        $data = $this->parseStreamData($content);

        $this->setVar('profileId', $data->profile->id);
        $this->setVar('collectApp', $data->session->collectApp);
        $this->setVar('section', $data->session->session);

        return $data;
    }

    /**
     * Extract stream data from raw content.
     * Tries to find profile and its related parts
     *
     * <b>Example:</b>
     *      ........
     *      $content = $response->getContent();
     *      try {
     *          $data = $helper->parseStreamData($content);
     *          var_dump($data);
     *          ------->
     *          stdClass Object
     *              (
     *                  [profile]   => stdClass Object,
     *                  [session]   => stdClass Object,
     *                  [event]     => stdClass Object,
     *                  [data]      => stdClass Object
     *              )
     *
     *      } catch (\ErrorException $e) {
     *          // content has not profile data
     *      }
     *
     * @param mixed $rawData Data to parse
     * @return object Object with properties: profile, session, events, data
     * @throws \ErrorException If profile or some its required parts are not found exception will be thrown
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
     *
     * @param object|array $params Custom parameters to get settings
     * @return array
     * @throws \ErrorException If settings are not found exception will be thrown
     */
    public function getSettings($params = null) {
        $params = (object)$params;
        $vars = self::mergeVars($this->getVars(), $params);
        $url = $this->settingsAppUrl(array(
            'groupId'       => $vars->groupId,
            'bucketName'    => $vars->bucketName,
            'appKey'        => $vars->appKey,
            'appName'       => $vars->appName
        ));

        $response = self::request(array('url' => $url));
        $body = json_decode($response);
        if(!isset($body->custom)) {
            throw new \ErrorException('Custom settings not found');
        }

        return $body->custom;
    }

    /**
     * Update attributes of the profile
     * @param object|array $attributes Key=>value pairs with attributes
     * @param object|array $params Custom parameters to update settings
     * @return bool|string String with response or false if request failed
     */
    public function setAttributes($attributes, $params = null) {
        $attributes = (object)$attributes;
        $params = (object)$params;
        $vars = self::mergeVars($this->getVars(), $params);

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
        return self::request($requestParams);
    }

    /**
     * Get attributes of the profile
     *
     * <b>Example:</b>
     *      [{
     *          "collectApp" => "web",
     *          "section"    => "sec1",
     *          "data"       => [
     *              "attr1" => 1,
     *              "attr2" => 'hello'
     *          ]
     *      }, {
     *          "collectApp" => "myapp",
     *          "section"    => "mysec",
     *          "data"       => [
     *              "foo"   => "bar",
     *              "hello" => "world"
     *          ]
     *      }]
     *
     * @param object|array $params Custom parameters to update settings
     * @return array Profile attributes
     * @throws \ErrorException If profile not found in request response exception will be thrown
     */
    public function getAttributes($params = null) {
        $params = (object)$params;
        $vars = self::mergeVars($this->getVars(), $params);

        $url = $this->profileAppUrl(array(
            'groupId'       => $vars->groupId,
            'bucketName'    => $vars->bucketName,
            'appKey'        => $vars->appKey,
            'profileId'     => $vars->profileId
        ));

        $response = self::request(array('url' => $url));

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
     * Helper method to merge 2 object/assoc array to one object
     * Values in $overrides will overwrite values in $main if they have same keys
     *
     * <b>Example:</b>
     *      $a = array('a' => 1, 'b' => 2);
     *      $b = (object)array('b' => 10, 'c' => 'asd');
     *      $helper::mergeVars($a, $b);
     *      ------->
     *      stdClass Object
     *       (
     *           [a] => 1
     *           [b] => 10
     *           [c] => 'asd'
     *       )
     *
     * @param object|array $main
     * @param object|array $overrides
     * @return object
     */
    protected static function mergeVars($main, $overrides) {
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

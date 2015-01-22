<?php

class InnoHelper
{
    private $vars = array();

    public function webProfilesAppUrl($obj) {
        return sprintf('%s/v1/companies/%s/buckets/%s/profiles/%s', $this->vars->apiUrl, $obj->groupId, $obj->bucketName, $obj->profileId);
    }

    private function profilesAppUrl($obj) {
        return sprintf('%s?app_key=%s', $this->webProfilesAppUrl($obj), $obj->appKey);
    }

    private function settingsAppUrl($obj) {
        return sprintf('%s/v1/companies/%s/buckets/%s/apps/%s/custom?app_key=%s', $this->vars->apiUrl, $obj->groupId, $obj->bucketName, $obj->appName, $obj->appKey);
    }

    private function request($params, $callback) {
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
        
        return $callback($response);
    }

    /**
     * Working with vars
     */
    public function getVars() {
        return $this->vars;
    }
    public function setVars($obj) {
        $this->vars = $obj;
    }
    public function setVar($name, $value) {
        $this->vars->{$name} = $value;
    }

    /**
     * Parse start session data
     */
    public function getDatas($request, $callback) {
        if(strpos($request->headers->get('Content-Type'), 'application/json') === 0) {
            $data = json_decode($request->getContent(), true);
        }
        
        if(!isset($data['profile'])) {
            return $callback(new \ErrorException('Profile not found'));
        }
        $profile = $data['profile'];

        if(!isset($profile['sessions'][0])) {
            return $callback(new \ErrorException('Session not found'));
        }
        $session = $profile['sessions'][0];

        if(!isset($session['collectApp'])) {
            return $callback(new \ErrorException('CollectApp not found'));
        }
        $this->setVar('collectApp', $session['collectApp']);

        if(!isset($session['section'])) {
            return $callback(new \ErrorException('Section not found'));
        }
        $this->setVar('section', $session['section']);

        if(!isset($session['events'][0]['data'])) {
            return $callback(new \ErrorException('Data not set'));
        }
        if(!isset($profile['id'])) {
            return $callback(new \ErrorException('Profile id not found'));
        }
        $this->setVar('profileId', $profile['id']);

        return $callback(null, array(
            'profile' => $profile, 
            'session' => $session, 
            'event' => $session['events'][0], 
            'data' => $session['events'][0]['data']));
    }

    /**
     * Get settings application
     */
    public function getSettings($params, $callback) {
        $obj = new \stdClass();
        $obj->groupId = $params->vars->groupId;
        $obj->bucketName = $params->vars->bucketName;
        $obj->appKey = $params->vars->appKey;
        $obj->appName = $params->vars->appName;
        $url = $this->settingsAppUrl($obj);

        return $this->request(array('url' => $url), function($response) use($callback) {
            $body = json_decode($response);
            if(!isset($body->custom)) {
                return $callback(new \ErrorException('Custom settings not found'));
            }
            return $callback(null, $body->custom);
        });
    }

    /**
     * Update data profile by id
     */
    public function setAttributes($params, $callback) {
        $obj = new \stdClass();
        $obj->groupId = $params->vars->groupId;
        $obj->bucketName = $params->vars->bucketName;
        $obj->appKey = $params->vars->appKey;
        $obj->profileId = $params->vars->profileId;
        $url = $this->profilesAppUrl($obj);
        $params = array(
            'url' => $url, 
            'type' => 'post',
            'headers' => array(
                'Content-Type: application/json',
                'Accept: application/json'
            ),
            'body' => array(
                'id' => $params->vars->profileId,
                'attributes' => array(array(
                    'collectApp' => $params->vars->collectApp,
                    'section' => $params->vars->section,
                    'data' => $params->data
                ))
            )
        );
        return $this->request($params, function($response) use($callback) {
            return $callback(null);
        });
    }

    /**
     * Get data profile by id
     */
    public function getAttributes($params, $callback) {
        $obj = new \stdClass();
        $obj->groupId = $params->vars->groupId;
        $obj->bucketName = $params->vars->bucketName;
        $obj->appKey = $params->vars->appKey;
        $obj->profileId = $params->vars->profileId;
        $url = $this->profilesAppUrl($obj);

        return $this->request(array('url' => $url), function($response) use($callback) {
            $body = json_decode($response);
            
            if(!isset($body->profile)) {
                return $callback(new \ErrorException('Profile not found'));
            }
            $attributes = array();
            if (!empty($body->profile->attributes)) {
                $attributes = $body->profile->attributes;
            }
            return $callback(null, $attributes);
        });
    }
}

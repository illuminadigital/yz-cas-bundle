<?php
namespace Illumina\CasBundle\DependencyInjection;

class ContentRetrieverService
{
    protected $serverUrl;
    protected $key;
    protected $secret;
    protected $site;
    
    public function __construct($serverUrl, $key, $secret, $site)
    {
        if (substr($serverUrl, -1) !== '/') {
            $serverUrl .= '/';
        }
        
        $this->serverUrl = $serverUrl;
        $this->key = $key;
        $this->secret = $secret;
        $this->site = $site;
        
        $options = array(
            'consumer_key' => $key,
            'consumer_secret' => $secret,
        );
        
        \OAuthStore::instance("2Leg", $options);
    }
    
    protected function sendRequest($url, $params = NULL, $method = 'GET', $options = NULL)
    {
        try {
            $request = new \OAuthRequester($url, $method, $params);
            
            $curl_options = array(
                CURLOPT_HTTPHEADER => array (
                    'Accept: application/json'
                ),
            );
            
            if ( ! empty($options) && is_array($options) )
            {
                $curl_options = array_merge($curl_options, $options);
            }
            
            $result = $request->doRequest(0, $curl_options);
            
            return json_decode($result['body']);
        }
        catch (\OAuthException2 $e) {
            return FALSE;
        }
    }
    
    public function retrieveList($area, $subtype, $type = 'views', $args = array(), $site = NULL)
    {
        $url = sprintf('%scontent/%s', $this->serverUrl, $subtype);
        $params = array(
            'args[0]' => (empty($site) ? $this->site : $site),
        );

        if (!empty($area)){
            $params['args[1]'] = $area;
        }
        
        if ( ! empty($args) ) {
            $counter = 2;
            foreach ($args as $key => $arg) {
                if (is_numeric($key)) {
                    $key = sprintf('args[%d]', $counter ++);
                }
                
                $params[$key] = $arg;
            }
        }
        
        return $this->sendRequest($url, $params);
    }
    
    public function retrieveContent()
    {
    }
}
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
            'token_secret' => '',
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
    
    protected function buildURL($type, $area = NULL, $args = array(), $site = NULL)
    {
        $url = sprintf('%scontent/%s', $this->serverUrl, $type);

        return $url;
    }
    
    protected function buildParams($type, $area = NULL, $args = array(), $site = NULL)
    {
        $params = array(
                'args[0]' => (empty($site) ? $this->site : $site),
        );
        
        if ( ! empty($area)){
            $params['args[1]'] = $area;
        }

        if( ! empty ($args['filters']) ){

            $i=2;

            foreach($args['filters'] as $extraFilter){

                $params['args['.$i.']']  = $extraFilter;
                $i++;
            }

        }

        if ( ! empty($args['page']) && $args['page'] > 1) {
            $numItems = ( ! empty($args['numItems']) ? (int) $args['numItems'] : 20 );
            $page = (int) $args['page'] - 1;
            
            $params['offset'] = $numItems * $page;
            $params['limit'] = $numItems; 
        } else if ( ! empty($args['numItems']) ) {
            $params['limit'] = (int) $args['numItems'];
        }
        
        unset($args['page'], $args['numItems']);
        
        if ( ! empty($args) ) {
            $counter = 2;
            foreach ($args as $key => $arg) {
                if (is_numeric($key)) {
                    $key = sprintf('args[%d]', $counter ++);
                }
        
                $params[$key] = $arg;
            }
        }
        unset($params['filters']);
        return $params;
    }
    
    public function retrieveList($type, $area = NULL, $args = array(), $site = NULL)
    {
        $url = $this->buildURL($type, $area, $args, $site);
        $params = $this->buildParams($type, $area, $args, $site);

        return $this->sendRequest($url, $params);
    }
    
    public function retrieveContent($type, $area = NULL, $args = array(), $site = NULL)
    {
        $args['numItems'] = 1;
        $args['page'] = 0;
          
        $url = $this->buildURL($type, $area, $args, $site);
        $params = $this->buildParams($type, $area, $args, $site);
        
        $result = $this->sendRequest($url, $params);
        
        // If we get an array rather than an object, return the item itself
        if ( is_array($result) ) {
            $result = array_shift($result);
            
        // Or if it looks like the wrapper structure
        } else if ( isset($result->results) && isset($result->num_rows) ) {
            $result = array_shift($result->results);
        }
    
        return $result;
    }
}
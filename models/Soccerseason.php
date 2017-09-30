<?php
/**
 * Soccerseason implements calls to underlying subresources.
 *
 * @author Daniel Freitag <daniel@football-data.org>
 * @date 04.11.2015 
 * 
 */
class Soccerseason {
    
    public $config;   
    public $reqPrefs = array();    
    public $payload;
    
    /**
     * The object gets instantiated with the payload of a request to a specific 
     * soccerseason resource.     
     * 
     * @param type $payload
     */    
    public function __construct($payload) {
        $payload->caption = preg_replace('/Primera Division/', 'LaLiga | LFP de España', $payload->caption);
        $this->payload = $payload;
        $config = parse_ini_file('config.ini', true);
        
        $this->reqPrefs['http']['method'] = 'GET';
        $this->reqPrefs['http']['header'] = 'X-Auth-Token: ' . $config['authToken'];
    }
    
    /**
     * Function returns all fixtures for the instantiated soccerseason.
     * 
     * @return array of fixture objects
     */    
    public function getAllFixtures() {        
        $uri = $this->payload->_links->fixtures->href;
        // load from cache if exists
        $nomCacheFile = preg_replace('/[\.\/:?=&\']/', '_', $uri);
        $di = \Phalcon\DI\FactoryDefault::getDefault();
        $wwwroot = $di->get('path')['root'];

        $cacheFile = "$wwwroot/temp/" . date("YmdG") . "_".$nomCacheFile."_cacheFile.tmp";
        //$cacheFile = $this->utils->getTempDir() . date("YmdG") . "_".$nomCacheFile."_cacheFile.tmp";

        if(file_exists($cacheFile)){
            $response = file_get_contents($cacheFile);
        }else{
            $response = file_get_contents($uri, false, stream_context_create($this->reqPrefs)); 
            // save cache file
            file_put_contents($cacheFile, $response);
        }
        
        return json_decode($response)->fixtures;
    }
    
    /**
     * Function returns all fixtures for a given matchday.
     * 
     * @param type $matchday
     * @return array of fixture objects
     */    
    public function getFixturesByMatchday($matchday = 1) {        
        $uri = $this->payload->_links->fixtures->href . '/?matchday=' . $matchday;
        // load from cache if exists
        $nomCacheFile = preg_replace('/[\.\/:?=&\']/', '_', $uri);
        $di = \Phalcon\DI\FactoryDefault::getDefault();
        $wwwroot = $di->get('path')['root'];

        $cacheFile = "$wwwroot/temp/" . date("YmdG") . "_".$nomCacheFile."_cacheFile.tmp";
        //$cacheFile = $this->utils->getTempDir() . date("YmdG") . "_".$nomCacheFile."_cacheFile.tmp";

        if(file_exists($cacheFile)){
            $response = file_get_contents($cacheFile);
        }else{
            $response = file_get_contents($uri, false, stream_context_create($this->reqPrefs)); 
            // save cache file
            file_put_contents($cacheFile, $response);
        }
        
        $response = json_decode($response);
        
        return (is_object($response)) ? $response->fixtures : [];
    }
    
    /**
     * Function returns all teams participating in the instantiated soccerseason.
     * 
     * @return array of team objects
     */    
    public function getTeams() {        
        $uri = $this->payload->_links->teams->href;
        // load from cache if exists
        $nomCacheFile = preg_replace('/[\.\/:?=&\']/', '_', $uri);
        $di = \Phalcon\DI\FactoryDefault::getDefault();
        $wwwroot = $di->get('path')['root'];

        $cacheFile = "$wwwroot/temp/" . date("Ym") . "_".$nomCacheFile."_cacheFile.tmp";
        //$cacheFile = $this->utils->getTempDir() . date("Ym") . "_".$nomCacheFile."_cacheFile.tmp";

        if(file_exists($cacheFile)){
            $response = file_get_contents($cacheFile);
        }else{
            $response = file_get_contents($uri, false, stream_context_create($this->reqPrefs)); 
            // save cache file
            file_put_contents($cacheFile, $response);
        } 
        
        $response = json_decode($response);
        
        return $response->teams;
    }
    
    /**
     * Function returns the current league table for the instantiated soccerseason.
     * 
     * @return object leagueTable
     */
    public function getLeagueTable() {        
        $uri = $this->payload->_links->leagueTable->href;
        // load from cache if exists
        $nomCacheFile = preg_replace('/[\.\/:?=&\']/', '_', $uri);
        $di = \Phalcon\DI\FactoryDefault::getDefault();
        $wwwroot = $di->get('path')['root'];

        $cacheFile = "$wwwroot/temp/" . date("YmdG") . "_".$nomCacheFile."_cacheFile.tmp";
        //$cacheFile = $this->utils->getTempDir() . date("YmdG") . "_".$nomCacheFile."_cacheFile.tmp";

        if(file_exists($cacheFile)){
            $response = file_get_contents($cacheFile);
        }else{
            $response = file_get_contents($uri, false, stream_context_create($this->reqPrefs));
            // save cache file
            file_put_contents($cacheFile, $response);
        } 
        
        return json_decode($response);
    }
}

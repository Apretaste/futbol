<?php

/**
 * Soccerseason implements calls to underlying subresources.
 *
 * @author  Daniel Freitag <daniel@football-data.org>
 * @author  Kuma [@kumahacker] <kumahavana@gmail.com>
 * @version 2.0
 * @date    19.10.2017
 */
class Soccerseason
{

	public $config;
	public $req_preferences = [];
	public $payload;

	/**
	 * The object gets instantiated with the payload of a request to a specific
	 * soccerseason resource.
	 *
	 * @param object $payload
	 */
	public function __construct($payload)
	{
		$payload->caption = preg_replace('/Primera Division/', 'LaLiga | LFP de España', $payload->caption);
		$this->payload                           = $payload;
		$config                                  = parse_ini_file('config.ini', true);
		$this->req_preferences['http']['method'] = 'GET';
		$this->req_preferences['http']['header'] = 'X-Auth-Token: ' . $config['authToken'];
	}

	/**
	 * Get remote contents with cURL
	 *
	 * @param $url
	 *
	 * @return bool|mixed
	 */
	private function file_get_contents_curl($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			$this->req_preferences['http']['header']
		]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		/* Check for 404 (file not found). */
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($httpCode == 404)
		{
			/* Handle 404 here. */
			$data = false;
		}
		curl_close($ch);

		return $data;
	}


	/**
	 * Generic method for get remote content
	 *
	 * @param $uri
	 *
	 * @return mixed
	 */
	public function getRemoteContent($uri)
	{
		// load from cache if exists
		$nomCacheFile = preg_replace('/[\.\/:?=&\']/', '_', $uri);
		$di           = \Phalcon\DI\FactoryDefault::getDefault();
		$www_root     = $di->get('path')['root'];

		$cacheFile = "$www_root/temp/" . date("YmdG") . "_" . $nomCacheFile . "_cacheFile.tmp";

		if(file_exists($cacheFile)) $response = file_get_contents($cacheFile);
		else
		{
			$response = $this->file_get_contents_curl($uri);

			// save cache file
			file_put_contents($cacheFile, $response);
		}

		return json_decode($response);
	}

	/**
	 * Function returns all fixtures for the instantiated soccerseason.
	 *
	 * @return array of fixture objects
	 */
	public function getAllFixtures()
	{
		$uri = $this->payload->_links->fixtures->href;

		return $this->getRemoteContent($uri)->fixtures;
	}

	/**
	 * Function returns all fixtures for a given matchday.
	 *
	 * @param integer $match_day
	 *
	 * @return array of fixture objects
	 */
	public function getFixturesByMatchDay($match_day = 1)
	{
		$uri      = $this->payload->_links->fixtures->href . '/?matchday=' . $match_day;
		$response = $this->getRemoteContent($uri);

		return (is_object($response)) ? $response->fixtures : [];
	}

	/**
	 * Function returns all teams participating in the instantiated soccerseason.
	 *
	 * @return array of team objects
	 */
	public function getTeams()
	{
		$uri = $this->payload->_links->teams->href;

		return $this->getRemoteContent($uri)->teams;
	}

	/**
	 * Function returns the current league table for the instantiated soccerseason.
	 *
	 * @return object leagueTable
	 */
	public function getLeagueTable()
	{
		$uri = $this->payload->_links->leagueTable->href;

		return $this->getRemoteContent($uri);
	}


}

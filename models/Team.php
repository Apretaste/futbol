<?php

/**
 * Team class implements calls to underlying subresources.
 *
 * @author  Daniel Freitag <daniel@football-data.org>
 * @author  Kuma [@kumahacker] <kumahavana@gmail.com>
 * @version 2.0
 * @date    19.10.2017
 */
class Team extends FutbolCommon
{
	/**
	 * An object is instantiated with the payload of a request to a team resource.
	 *
	 * @param object $payload
	 */
	public function __construct($payload)
	{
		$this->_payload = $payload;
		$config         = parse_ini_file('config.ini', true);

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
	 * @param        $uri
	 * @param string $date
	 *
	 * @return mixed
	 */
	public function getRemoteContent($uri, $date = null)
	{
		if(is_null($date)) $date = date("YmdG");

		// load from cache if exists
		$nomCacheFile = preg_replace('/[\.\/:?=&\']/', '_', $uri);
		$di           = \Phalcon\DI\FactoryDefault::getDefault();
		$www_root     = $di->get('path')['root'];

		$cacheFile = "$www_root/temp/{$date}_{$nomCacheFile}_cacheFile.tmp";

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
	 * Function returns all fixtures for the team for this season.
	 *
	 * @param string $venue
	 * @param string $timeFrame
	 *
	 * @return boolean | array of stdObjects representing fixtures
	 */
	public function getFixtures($venue = "", $timeFrame = "")
	{

		if (!isset($this->_payload->_links->fixtures->href))
			return false;

		$uri = $this->_payload->_links->fixtures->href . '/?venue=' . $venue . '&timeFrame=' . $timeFrame;

		return $this->getRemoteContent($uri, date("YmdG"));
	}

	/**
	 * Function returns all players of the team
	 *
	 * @return array of fixture objects
	 */
	public function getPlayers()
	{
		$uri      = $this->_payload->_links->players->href;
		$response = $this->getRemoteContent($uri, date("Ym"));

		return $response->players;
	}
}

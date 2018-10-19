<?php

/**
 * Soccerseason implements calls to underlying subresources.
 *
 * @author  Daniel Freitag <daniel@football-data.org>
 * @author  Kuma [@kumahacker] <kumahavana@gmail.com>
 * @version 2.0
 * @date    19.10.2017
 */
class Soccerseason extends FutbolCommon
{

	public $payload;

	/**
	 * The object gets instantiated with the payload of a request to a specific
	 * soccerseason resource.
	 *
	 * @param object $payload
	 */
	public function __construct($payload)
	{
		$payload->name = preg_replace('/Primera Division/', 'LaLiga | LFP de España', $payload->name);
		$this->payload                           = $payload;
		$config                                  = parse_ini_file('config.ini', true);
		$this->req_preferences['http']['method'] = 'GET';
		$this->req_preferences['http']['header'] = 'X-Auth-Token: ' . $config['authToken'];
	}

	/**
	 * Function returns all fixtures for the instantiated soccerseason.
	 *
	 * @return array of fixture objects
	 */
	public function getAllFixtures()
	{
		$uri      = "http://api.football-data.org/v2/competitions/".$this->payload->id."/matches";
		$content = $this->getRemoteContent($uri);
		var_dump($uri);
		if (!isset($content->fixtures))
		{
			$utils = new Utils();
			$utils->createAlert("[Futbol] Unknown content from $uri: ".json_encode($content));
			return [];
		}

		return $content->fixtures;
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
		$uri      = "http://api.football-data.org/v2/competitions/".$this->payload->id."/matches?matchday=" . $match_day;
		$response = $this->getRemoteContent($uri);
		return (is_object($response)) ? $response->matches : [];
	}

	/**
	 * Function returns all teams participating in the instantiated soccerseason.
	 *
	 * @return array of team objects
	 */
	public function getTeams()
	{
		$uri= "http://api.football-data.org/v2/competitions/".$this->payload->id."/teams";

		return $this->getRemoteContent($uri)->teams;
	}

	/**
	 * Function returns the current league table for the instantiated soccerseason.
	 *
	 * @return object leagueTable
	 */
	public function getLeagueTable()
	{
		//$uri = $this->payload->_links->leagueTable->href;
		
		return $this->getRemoteContent("http://api.football-data.org/v2/competitions/".$this->payload->id."/standings");
	}

}

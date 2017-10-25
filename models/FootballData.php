<?php

/*
Estructura del objecto season:
stdClass Object (
	[_links] => stdClass Object (
				[self] => stdClass Object (
							[href] => http://api.football-data.org/v1/soccerseasons/424
						)
				[teams] => stdClass Object (
							[href] => http://api.football-data.org/v1/soccerseasons/424/teams
						)
				[fixtures] => stdClass Object (
								[href] => http://api.football-data.org/v1/soccerseasons/424/fixtures
							)
				[leagueTable] => stdClass Object (
									[href] => http://api.football-data.org/v1/soccerseasons/424/leagueTable
								)
	)
	[id] => 424
	[caption] => European Championships France 2016
	[league] => EC
	[year] => 2016
	[currentMatchday] => 7
	[numberOfMatchdays] => 7
	[numberOfTeams] => 24
	[numberOfGames] => 51
	[lastUpdated] => 2016-07-10T21:32:20Z
)

Estructura del objeto fixture:

stdClass Object (
	[_links] => stdClass Object (
				[self] => stdClass Object (
							[href] => http://api.football-data.org/v1/fixtures/155344
						)
				[soccerseason] => stdClass Object (
									[href] => http://api.football-data.org/v1/soccerseasons/440
								)
				[homeTeam] => stdClass Object (
								[href] => http://api.football-data.org/v1/teams/495
							)
				[awayTeam] => stdClass Object (
								[href] => http://api.football-data.org/v1/teams/4
							)
			)
	[date] => 2017-02-14T19:45:00Z
	[status] => FINISHED
	[matchday] => 7
	[homeTeamName] => SL Benfica
	[awayTeamName] => Borussia Dortmund
	[result] => stdClass Object (
				[goalsHomeTeam] => 1
				[goalsAwayTeam] => 0
				[halfTime] => stdClass Object (
								[goalsHomeTeam] => 0
								[goalsAwayTeam] => 0
							)
			)
)
*/
include 'Soccerseason.php';
include 'Team.php';

/**
 * This service class encapsulates football-data.org's RESTful API.
 *
 * @author  Daniel Freitag <daniel@football-data.org>
 * @author  Kuma [@kumahacker] <kumahavana@gmail.com>
 * @version 2.0
 * @date    19.10.2017
 */
class FootballData
{
	public $config;
	public $baseUri;
	public $req_preferences = [];

	public function __construct()
	{
		$this->config = parse_ini_file('config.ini', true);

		// some lame hint for the impatient
		if($this->config['authToken'] == 'YOUR_AUTH_TOKEN' || ! isset($this->config['authToken']))
		{
			exit('Get your API-Key first and edit config.ini');
		}

		$this->baseUri = $this->config['baseUri'];

		$this->req_preferences['http']['method'] = 'GET';
		$this->req_preferences['http']['header'] = 'X-Auth-Token: ' . $this->config['authToken'];
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
	 * Function returns all soccer season avaiable in the api.
	 *
	 * @return \ array of Soccerseasons avaiable
	 */
	public function getSoccerseasons()
	{
		$uri    = $this->baseUri . 'soccerseasons/';
		$result = $this->getRemoteContent($uri, date("Y"));

		return $result; //new Soccerseason($result);
	}

	/**
	 * Function returns a specific soccer season identified by an id.
	 *
	 * @param Integer $id
	 *
	 * @return \Soccerseason object
	 */
	public function getSoccerseasonById($id)
	{
		$uri    = $this->baseUri . 'soccerseasons/' . $id;
		$result = $this->getRemoteContent($uri, date("Ymd"));

		if( ! isset($result->caption)) return null;

		return new Soccerseason($result);
	}

	/**
	 * Function returns all available fixtures for a given date range.
	 *
	 * @param DateString 'Y-m-d' $start
	 * @param DateString 'Y-m-d' $end
	 *
	 * @return array of fixture objects
	 */
	public function getFixturesForDateRange($start, $end)
	{
		$uri = $this->baseUri . 'fixtures/?timeFrameStart=' . $start . '&timeFrameEnd=' . $end;

		return $this->getRemoteContent($uri);
	}

	/**
	 * Function returns one unique fixture identified by a given id.
	 *
	 * @param int $id
	 *
	 * @return object fixture
	 */
	public function getFixtureById($id)
	{
		$uri = $this->baseUri . 'fixtures/' . $id;

		return $this->getRemoteContent($uri);
	}

	/**
	 * Function returns one unique team identified by a given id.
	 *
	 * @param int $id
	 *
	 * @return object team
	 */
	public function getTeamById($id)
	{
		$uri    = $this->baseUri . 'teams/' . $id;
		$result = $this->getRemoteContent($uri, date("Ym"));

		return new Team($result);
	}

	/**
	 * Function returns all teams matching a given keyword.
	 *
	 * @param string $keyword
	 *
	 * @return array
	 */
	public function searchTeam($keyword)
	{
		$uri = $this->baseUri . 'teams/?name=' . $keyword;

		return $this->getRemoteContent($uri, date("Ym"));
	}
}


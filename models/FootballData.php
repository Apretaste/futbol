<?php

include 'Soccerseason.php';
include 'Team.php';

/**
 * This service class encapsulates football-data.org's RESTful API.
 *
 * @author Daniel Freitag <daniel@football-data.org>
 * @date 04.11.2015
 *
 */
class FootballData
{
	public $config;
	public $baseUri;
	public $reqPrefs = array();

	public function __construct() {
		$this->config = parse_ini_file('config.ini', true);

	// some lame hint for the impatient
	if($this->config['authToken'] == 'YOUR_AUTH_TOKEN' || !isset($this->config['authToken'])) {
		exit('Get your API-Key first and edit config.ini');
	}

		$this->baseUri = $this->config['baseUri'];

		$this->reqPrefs['http']['method'] = 'GET';
		$this->reqPrefs['http']['header'] = 'X-Auth-Token: ' . $this->config['authToken'];
	}

	/**
	 * Function returns all soccer season avaiable in the api.
	 *
	 * @return \ array of Soccerseasons avaiable
	 */
	public function getSoccerseasons() {
		$uri = $this->baseUri . 'soccerseasons/';
		// load from cache if exists
		$nomCacheFile = preg_replace('/[\.\/:?=&\']/', '_', $uri);
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		$cacheFile = "$wwwroot/temp/" . date("Y") . "_".$nomCacheFile."_cacheFile.tmp";
		//$cacheFile = $this->utils->getTempDir() . date("Y") . "_".$nomCacheFile."_cacheFile.tmp";

		if(file_exists($cacheFile)){
			$response = file_get_contents($cacheFile);
		}else{
			$response = file_get_contents($uri, false, stream_context_create($this->reqPrefs));
			// save cache file
			file_put_contents($cacheFile, $response);
		}

		$result = json_decode($response);

		return $result;//new Soccerseason($result);
	}

	/**
	 * Function returns a specific soccer season identified by an id.
	 *
	 * @param Integer $id
	 * @return \Soccerseason object
	 */
	public function getSoccerseasonById($id) {
		$uri = $this->baseUri . 'soccerseasons/' . $id;
		// load from cache if exists
		$nomCacheFile = preg_replace('/[\.\/:?=&\']/', '_', $uri);
		$di = \Phalcon\DI\FactoryDefault::getDefault();
		$wwwroot = $di->get('path')['root'];

		$cacheFile = "$wwwroot/temp/" . date("Ymd") . "_".$nomCacheFile."_cacheFile.tmp";
		//$cacheFile = $this->utils->getTempDir() . date("Ymd") . "_".$nomCacheFile."_cacheFile.tmp";

		if(file_exists($cacheFile)){
			$response = file_get_contents($cacheFile);
		}else{
			$response = file_get_contents($uri, false, stream_context_create($this->reqPrefs));
			// save cache file
			file_put_contents($cacheFile, $response);
		}

		$result = json_decode($response);

		return new Soccerseason($result);
	}

	/**
	 * Function returns all available fixtures for a given date range.
	 *
	 * @param DateString 'Y-m-d' $start
	 * @param DateString 'Y-m-d' $end
	 * @return array of fixture objects
	 */
	public function getFixturesForDateRange($start, $end) {
		$uri = $this->baseUri . 'fixtures/?timeFrameStart=' . $start . '&timeFrameEnd=' . $end;
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

	/**
	 * Function returns one unique fixture identified by a given id.
	 *
	 * @param int $id
	 * @return stdObject fixture
	 */
	public function getFixtureById($id) {
		$uri = $this->baseUri . 'fixtures/' . $id;
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

	/**
	 * Function returns one unique team identified by a given id.
	 *
	 * @param int $id
	 * @return stdObject team
	 */
	public function getTeamById($id) {
		$uri = $this->baseUri . 'teams/' . $id;
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

		$result = json_decode($response);

		return new Team($result);
	}

	/**
	 * Function returns all teams matching a given keyword.
	 *
	 * @param string $keyword
	 * @return list of team objects
	 */
	public function searchTeam($keyword) {
		$uri = $this->baseUri . 'teams/?name=' . $keyword;
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

		return json_decode($response);
	}
}

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

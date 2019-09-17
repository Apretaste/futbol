<?php

class Service
{
	/**
	 * Display the list of leagues
	 *
	 * @author salvipascual
	 * @param Request
	 * @param Response
	 */
	public function _main (Request $request, Response $response)
	{
		// get all opened leagues
		$teams = $this->getTeams();

		// send information to the view
		$response->setCache();
		$response->setTemplate("home.ejs", ["teams"=>$teams]);
	}

	/**
	 * Muestra las posiciones dentro de una liga
	 *
	 * @author salvipascual
	 * @param Request
	 * @param Response
	 */
	public function _marcador (Request $request, Response $response)
	{
		// pull the league
		$league = $request->input->data->id;
		$season = date('Y');

		// get data online
		$uri = "http://api.football-data.org/v2/competitions/$league/standings?season=$season";
		$data = $this->api($uri, 'YmdH');

		// create content for the view
		$content = [
			"league"=>$league,
			"seasonStart" => $data->season->startDate,
			"seasonEnd" => $data->season->endDate,
			"day" => $data->season->currentMatchday,
			"standings" => []
		];

		// format the results for the view
		foreach ($data->standings[0]->table as $std) {
			$standing = new StdClass();
			$standing->position = $std->position;
			$standing->teamId = $std->team->id;
			$standing->teamName = $std->team->name;
			$standing->won = $std->won;
			$standing->draw = $std->draw;
			$standing->lost = $std->lost;
			$standing->points = $std->points;
			$standing->goalsFor = $std->goalsFor;
			$standing->goalsAgainst = $std->goalsAgainst;
			$standing->goalDiff = $std->goalDifference;
			$content['standings'][] = $standing;
		}

		// send information to the view
		$response->setCache('day');
		$response->setTemplate("marcador.ejs", $content);
	}

	/**
	 * Muestra los proximos juegos de una liga
	 *
	 * @author salvipascual
	 * @param Request
	 * @param Response
	 */
	public function _siguientes (Request $request, Response $response)
	{
		// pull the league
		$league = $request->input->data->id;
		$season = date('Y');

		// get data online
		$uri = "http://api.football-data.org/v2/competitions/$league/matches?status=SCHEDULED&season=$season";
		$data = $this->api($uri, 'YmdH');

		// format the results for the view
		$matches = [];
		foreach ($data->matches as $m) {
			$match = new StdClass();
			$match->date = date("M d", strtotime($m->utcDate));
			$match->time = date("g:ia", strtotime($m->utcDate));
			$match->homeId = $m->homeTeam->id;
			$match->homeName = $m->homeTeam->name;
			$match->awayId = $m->awayTeam->id;
			$match->awayName = $m->awayTeam->name;
			$matches[] = $match;
		}

		// sort by date
		function cmp($a, $b) { return strcmp($b->date.' '.$b->time, $a->date.' '.$a->time); }
		usort($matches, "cmp");

		// send information to the view
		$response->setCache('day');
		$response->setTemplate("siguientes.ejs", ["league"=>$league, "matches"=>$matches]);
	}

	/**
	 * Muestra los resultados de la liga hasta ahora
	 *
	 * @author salvipascual
	 * @param Request
	 * @param Response
	 */
	public function _resultados (Request $request, Response $response)
	{
		// pull the league
		$league = $request->input->data->id;
		$season = date('Y');

		// get data online
		$uri = "http://api.football-data.org/v2/competitions/$league/matches?status=FINISHED&season=$season";
		$data = $this->api($uri, 'YmdH');

		// format the results for the view
		$matches = [];
		foreach ($data->matches as $m) {
			$match = new StdClass();
			$match->date = date("M d", strtotime($m->utcDate));
			$match->time = date("g:ia", strtotime($m->utcDate));
			$match->homeId = $m->homeTeam->id;
			$match->homeName = $m->homeTeam->name;
			$match->homeScore = $m->score->fullTime->homeTeam;
			$match->awayId = $m->awayTeam->id;
			$match->awayName = $m->awayTeam->name;
			$match->awayScore = $m->score->fullTime->awayTeam;
			$matches[] = $match;
		}

		// sort by date
		function cmp($a, $b) { return strcmp($b->date.' '.$b->time, $a->date.' '.$a->time); }
		usort($matches, "cmp");

		// send information to the view
		$response->setCache('day');
		$response->setTemplate("resultados.ejs", ["league"=>$league, "matches"=>$matches]);
	}

	/**
	 * Muestra detalles del equipo de una liga 
	 *
	 * @author salvipascual
	 * @param Request
	 * @param Response
	 */
	public function _equipo (Request $request, Response $response)
	{
		// pull the team
		$team = $request->input->data->id;

		// get data online
		$uri = "http://api.football-data.org/v2/teams/$team";
		$data = $this->api($uri);

		// create content for the view
		$content = [
			"name" => $data->name,
			"area" => $data->area->name,
			"picture" => basename($data->crestUrl),
			"founded" => $data->founded,
			"venue" => $data->venue,
			"players" => []
		];

		// get team players
		foreach ($data->squad as $squad) {
			$player = new StdClass();
			$player->name = $squad->name;
			$player->number = $squad->shirtNumber;
			$player->position = $squad->position;
			$player->dob = date("M d, Y", strtotime($squad->dateOfBirth));
			$player->country = $squad->countryOfBirth;
			$player->role = $squad->role;
			$content['players'][] = $player;
		}

		// send information to the view
		$response->setCache();
		$response->setTemplate("equipo.ejs", $content, [$data->crestUrl]);
	}


	/**
	 * Get all available teams 
	 *
	 * @return Array
	 */
	private function getTeams()
	{
		$teams = [];

		$team = new StdClass();
		$team->leagueCode = "PD";
		$team->leagueName = "Primera División Española";
		$team->countryCode = "es";
		$team->countryName = "España";
		$teams[] = $team;

		$team = new StdClass();
		$team->leagueCode = "CL";
		$team->leagueName = "UEFA Champions League";
		$team->countryCode = "eu";
		$team->countryName = "Europa";
		$teams[] = $team;

		$team = new StdClass();
		$team->leagueCode = "PL";
		$team->leagueName = "Premier League";
		$team->countryCode = "gb";
		$team->countryName = "England";
		$teams[] = $team;

		$team = new StdClass();
		$team->leagueCode = "BL1";
		$team->leagueName = "Bundesliga";
		$team->countryCode = "de";
		$team->countryName = "Alemania";
		$teams[] = $team;

		$team = new StdClass();
		$team->leagueCode = "DED";
		$team->leagueName = "Eredivisie";
		$team->countryCode = "nl";
		$team->countryName = "Holanda";
		$teams[] = $team;

		$team = new StdClass();
		$team->leagueCode = "FL1";
		$team->leagueName = "French League One";
		$team->countryCode = "fr";
		$team->countryName = "Francia";
		$teams[] = $team;

		$team = new StdClass();
		$team->leagueCode = "PPL";
		$team->leagueName = "Portugal Primeira Liga";
		$team->countryCode = "pt";
		$team->countryName = "Portugal";
		$teams[] = $team;

		$team = new StdClass();
		$team->leagueCode = "EFL";
		$team->leagueName = "English Football League Two";
		$team->countryCode = "gb";
		$team->countryName = "United Kingdom";
		$teams[] = $team;

		$team = new StdClass();
		$team->leagueCode = "SA";
		$team->leagueName = "Serie A";
		$team->countryCode = "it";
		$team->countryName = "Italia";
		$teams[] = $team;

		$team = new StdClass();
		$team->leagueCode = "BSA";
		$team->leagueName = "Brasileiro Serie A";
		$team->countryCode = "br";
		$team->countryName = "Brasil";
		$teams[] = $team;

		return $teams;
	}

	/**
	 * Access remote content
	 *
	 * @param String $uri
	 * @param String $date
	 * @return Array
	 */
	private function api($uri, $date="Y")
	{
		// load from cache if exists
		$cache = Utils::getTempDir() . date($date) . "_" . md5($uri) . ".tmp";
		if(file_exists($cache)) $data = unserialize(file_get_contents($cache));

		// get from the internet
		else {
			// get the token
			$token = 'b8044b406aca4851ac7ceeea79fccaea';

			// access the api
			$reqPrefs['http']['method'] = "GET";
			$reqPrefs['http']['header'] = "X-Auth-Token: $token";
			$context = stream_context_create($reqPrefs);
			$data = json_decode(file_get_contents($uri, false, $context));

			// save cache file
			file_put_contents($cache, serialize($data));
		}

		// return data
		return $data;
	}
}
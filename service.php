<?php

// locate dates in Spanish
setlocale(LC_ALL, "es_ES", 'Spanish_Spain', 'Spanish');

use Apretaste\Core;

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

		if (!is_object($data)) {
			$season = date('Y') - 1;
			$uri = "http://api.football-data.org/v2/competitions/$league/standings?season=$season";
			$data = $this->api($uri, 'YmdH');
		}

		// create content for the view
		$content = [
			"league" => $this->getTeams($league),
			"seasonStart" => strftime("%e de %b", strtotime($data->season->startDate)),
			"seasonEnd" => strftime("%e de %b", strtotime($data->season->endDate)),
			"day" => $data->season->currentMatchday,
			"standings" => []
		];

		// format the results for the view
		if (isset($data->standings)) foreach ($data->standings[0]->table as $std) {
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

		if (!is_object($data)) {
			$season = date('Y') - 1;
			$uri = "http://api.football-data.org/v2/competitions/$league/matches?status=SCHEDULED&season=$season";
			$data = $this->api($uri, 'YmdH');
		}

		// create content for the view
		$content = [
			"league" => $this->getTeams($league),
			"matches" => []
		];

		// format the results for the view
		if (isset($data->matches)) foreach ($data->matches as $m) {
			$match = new StdClass();
			$match->date = strftime("%e %b", strtotime($m->utcDate));
			$match->time = date("g:ia", strtotime($m->utcDate));
			$match->homeId = $m->homeTeam->id;
			$match->homeName = $m->homeTeam->name;
			$match->awayId = $m->awayTeam->id;
			$match->awayName = $m->awayTeam->name;
			$content['matches'][] = $match;
		}

		// send information to the view
		$response->setCache('day');
		$response->setTemplate("siguientes.ejs", $content);
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

		if (!is_object($data)) {
			$season = date('Y') - 1;
			$uri = "http://api.football-data.org/v2/competitions/$league/matches?status=FINISHED&season=$season";
			$data = $this->api($uri, 'YmdH');
		}

		// create content for the view
		$content = [
			"league" => $this->getTeams($league),
			"matches" => []
		];

		// format the results for the view
		if (isset($data->matches)) foreach ($data->matches as $m) {
			$match = new StdClass();
			$match->date = strftime("%e %b", strtotime($m->utcDate));
			$match->time = date("g:ia", strtotime($m->utcDate));
			$match->homeId = $m->homeTeam->id;
			$match->homeName = $m->homeTeam->name;
			$match->homeScore = $m->score->fullTime->homeTeam;
			$match->awayId = $m->awayTeam->id;
			$match->awayName = $m->awayTeam->name;
			$match->awayScore = $m->score->fullTime->awayTeam;
			$content['matches'][] = $match;
		}

		// sort by date
		$content['matches'] = array_reverse($content['matches']);

		// send information to the view
		$response->setCache('day');
		$response->setTemplate("resultados.ejs", $content);

		Challenges::complete("view-futbol", $request->person->id);
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
			"founded" => $data->founded,
			"venue" => $data->venue,
			"players" => []
		];

		// get team players
		if (isset($data->squad)) foreach ($data->squad as $squad) {
			$player = new StdClass();
			$player->name = $squad->name;
			$player->number = $squad->shirtNumber;
			$player->position = $this->t($squad->position);
			$player->dob = strftime("%e/%m/%Y", strtotime($squad->dateOfBirth));
			$player->country = $squad->countryOfBirth;
			$player->role = ucwords(strtolower(str_replace('_', ' ', $squad->role)));
			$content['players'][] = $player;
		}

		// send information to the view
		$response->setCache();
		$response->setTemplate("equipo.ejs", $content);
	}


	/**
	 * Get all available teams
	 *
	 * @param String $code
	 * @return Array
	 */
	private function getTeams($code=false)
	{
		$teams = [];

		$team = new StdClass();
		$team->leagueCode = "PD";
		$team->leagueName = "Primera División Española";
		$team->countryCode = "es";
		$team->countryName = "España";
		if($code == $team->leagueCode) return $team;
		$teams[] = $team;

		$team = new StdClass();
		$team->leagueCode = "CL";
		$team->leagueName = "UEFA Champions League";
		$team->countryCode = "";
		$team->countryName = "Europa";
		if($code == $team->leagueCode) return $team;
		$teams[] = $team;

		$team = new StdClass();
		$team->leagueCode = "PL";
		$team->leagueName = "Premier League";
		$team->countryCode = "gb";
		$team->countryName = "England";
		if($code == $team->leagueCode) return $team;
		$teams[] = $team;

		$team = new StdClass();
		$team->leagueCode = "BL1";
		$team->leagueName = "Bundesliga";
		$team->countryCode = "de";
		$team->countryName = "Alemania";
		if($code == $team->leagueCode) return $team;
		$teams[] = $team;

		$team = new StdClass();
		$team->leagueCode = "DED";
		$team->leagueName = "Eredivisie";
		$team->countryCode = "nl";
		$team->countryName = "Holanda";
		if($code == $team->leagueCode) return $team;
		$teams[] = $team;

		$team = new StdClass();
		$team->leagueCode = "FL1";
		$team->leagueName = "French League One";
		$team->countryCode = "fr";
		$team->countryName = "Francia";
		if($code == $team->leagueCode) return $team;
		$teams[] = $team;

		$team = new StdClass();
		$team->leagueCode = "PPL";
		$team->leagueName = "Portugal Primeira Liga";
		$team->countryCode = "pt";
		$team->countryName = "Portugal";
		if($code == $team->leagueCode) return $team;
		$teams[] = $team;

		$team = new StdClass();
		$team->leagueCode = "EFL";
		$team->leagueName = "English Football League Two";
		$team->countryCode = "gb";
		$team->countryName = "United Kingdom";
		if($code == $team->leagueCode) return $team;
		$teams[] = $team;

		$team = new StdClass();
		$team->leagueCode = "SA";
		$team->leagueName = "Serie A";
		$team->countryCode = "it";
		$team->countryName = "Italia";
		if($code == $team->leagueCode) return $team;
		$teams[] = $team;

		$team = new StdClass();
		$team->leagueCode = "BSA";
		$team->leagueName = "Brasileiro Serie A";
		$team->countryCode = "br";
		$team->countryName = "Brasil";
		if($code == $team->leagueCode) return $team;
		$teams[] = $team;

		return $teams;
	}

	/**
	 * Translate to Spanish
	 *
	 * @param String $word
	 * @return String
	 */
	private function t($word)
	{
		// array to translate to Spanish
		$sp = [
			"Goalkeeper" => "Portero",
			"Defender" => "Defensa",
			"Midfielder" => "Centrocampo",
			"Attacker" => "Delantero",
		];

		// return word or empty
		return isset($sp[$word]) ? $sp[$word] : "";
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
		$data = false;
		// load from cache if exists
		$cache = Utils::getTempDir() . date($date) . "_" . md5($uri) . ".tmp";
		if(file_exists($cache) && false) $data = unserialize(file_get_contents($cache));

		// get from the internet
		else {
			// get the token
			$token = 'd08dda4df1954b9781e83bd7fedc20c3';

			$data = Utils::file_get_contents_curl($uri, [
				"X-Auth-Token: $token"
			]);

			$data = @json_decode($data);

			// access the api
			/*$reqPrefs['http']['method'] = "GET";
			$reqPrefs['http']['header'] = "X-Auth-Token: $token";
			$context = stream_context_create($reqPrefs);
			$data = json_decode(file_get_contents($uri, false, $context));
*/
			// save cache file
			file_put_contents($cache, serialize($data));
		}

		Core::log("$uri: ".substr(json_encode($data),0,100), "futbol");

		// return data
		return $data;
	}
}

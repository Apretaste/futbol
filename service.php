<?php

use Apretaste\Request;
use Apretaste\Response;
use Apretaste\Challenges;
use Framework\Alert;
use Framework\Crawler;
use Framework\Core;

class Service
{
	/**
	 * Display the list of leagues
	 *
	 * @param Request $request
	 * @param Response $response
	 * @author salvipascual
	 */
	public function _main(Request $request, Response $response)
	{
		// get all opened leagues
		$teams = $this->getTeams();

		// send information to the view
		$response->setCache();
		$response->setTemplate('home.ejs', ['teams' => $teams]);
	}

	/**
	 * Muestra las posiciones dentro de una liga
	 *
	 * @param Request $request
	 * @param Response $response
	 * @author salvipascual
	 */
	public function _marcador(Request $request, Response $response)
	{
		// pull the league
		$league = $request->input->data->id;
		$season = date('Y');

		// get data online
		$data = $this->api("http://api.football-data.org/v2/competitions/$league/standings?season=$season");

		if (!is_object($data)) {
			$season = date('Y') - 1;
			$data = $this->api("http://api.football-data.org/v2/competitions/$league/standings?season=$season");

			if (!is_object($data) || !isset($data->season)) {
				$alert = new Alert(500, "Error consultando la Api de futbol: http://api.football-data.org/v2/competitions/$league/standings?season=$season");
				$alert->post();
				$response->setTemplate("message.ejs", [
					'header' => 'Hubo un problema',
					'text' => 'Tenemos un problema consultando estos datos. El equipo tecnico se esta encargando. Disculpa las molestias.',
					'icon' => 'sentiment_very_dissatisfied',
					'button' => ['href' => 'FUTBOL', 'caption' => 'Volver']
				]);
				return;
			}
		}

		// create content for the view
		$content = [
			'league' => $this->getTeams($league),
			'seasonStart' => strftime('%e de %b', strtotime($data->season->startDate)),
			'seasonEnd' => strftime('%e de %b', strtotime($data->season->endDate)),
			'day' => $data->season->currentMatchday,
			'standings' => []
		];

		// format the results for the view
		if (!empty($data->standings)) {
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
		}

		// send information to the view
		$response->setCache('day');
		$response->setTemplate('marcador.ejs', $content);
	}

	/**
	 * Muestra los proximos juegos de una liga
	 *
	 * @param Request $request
	 * @param Response $response
	 * @author salvipascual
	 */
	public function _siguientes(Request $request, Response $response)
	{
		// pull the league
		$league = $request->input->data->id;
		$season = date('Y');

		// get data online
		$data = $this->api("http://api.football-data.org/v2/competitions/$league/matches?status=SCHEDULED&season=$season");

		if (!is_object($data)) {
			$season = date('Y') - 1;
			$data = $this->api("http://api.football-data.org/v2/competitions/$league/matches?status=SCHEDULED&season=$season");
		}

		// create content for the view
		$content = [
			'league' => $this->getTeams($league),
			'matches' => []
		];

		// format the results for the view
		if (!empty($data->matches)) {
			foreach ($data->matches as $m) {
				$match = new StdClass();
				$match->date = strftime('%e %b', strtotime($m->utcDate));
				$match->time = date('g:ia', strtotime($m->utcDate));
				$match->homeId = $m->homeTeam->id;
				$match->homeName = $m->homeTeam->name;
				$match->awayId = $m->awayTeam->id;
				$match->awayName = $m->awayTeam->name;
				$content['matches'][] = $match;
			}
		}

		// send information to the view
		$response->setCache('day');
		$response->setTemplate('siguientes.ejs', $content);
	}

	/**
	 * Muestra los resultados de la liga hasta ahora
	 *
	 * @param Request $request
	 * @param Response $response
	 * @author salvipascual
	 */
	public function _resultados(Request $request, Response $response)
	{
		// pull the league
		$league = $request->input->data->id;
		$season = date('Y');

		// get data online
		$data = $this->api("http://api.football-data.org/v2/competitions/$league/matches?status=FINISHED&season=$season");

		if (!is_object($data)) {
			$season = date('Y') - 1;
			$data = $this->api("http://api.football-data.org/v2/competitions/$league/matches?status=FINISHED&season=$season");
		}

		// create content for the view
		$content = [
			'league' => $this->getTeams($league),
			'matches' => []
		];

		// format the results for the view
		if (!empty($data->matches)) {
			foreach ($data->matches as $m) {
				$match = new StdClass();
				$match->date = strftime('%e %b', strtotime($m->utcDate));
				$match->time = date('g:ia', strtotime($m->utcDate));
				$match->homeId = $m->homeTeam->id;
				$match->homeName = $m->homeTeam->name;
				$match->homeScore = $m->score->fullTime->homeTeam;
				$match->awayId = $m->awayTeam->id;
				$match->awayName = $m->awayTeam->name;
				$match->awayScore = $m->score->fullTime->awayTeam;
				$content['matches'][] = $match;
			}
		}

		// sort by date
		$content['matches'] = array_reverse($content['matches']);

		// set challenge
		Challenges::complete('view-futbol', $request->person->id);

		// send information to the view
		$response->setCache('day');
		$response->setTemplate('resultados.ejs', $content);
	}

	/**
	 * Muestra detalles del equipo de una liga
	 *
	 * @param Request $request
	 * @param Response $response
	 *
	 * @throws \Framework\Alert
	 * @author salvipascual
	 */
	public function _equipo(Request $request, Response $response)
	{
		// pull the team
		$team = $request->input->data->id;

		// get data online
		$data = $this->api("http://api.football-data.org/v2/teams/$team");

		// create content for the view
		$content = [
			'name' => $data->name,
			'area' => $data->area->name,
			'founded' => $data->founded,
			'venue' => $data->venue,
			'players' => []
		];

		// get team players
		if (isset($data->squad)) {
			foreach ($data->squad as $squad) {
				$player = new StdClass();
				$player->name = $squad->name;
				$player->number = $squad->shirtNumber;
				$player->position = ($squad->role=="COACH" || $squad->role=="ASSISTANT_COACH") ? 'Entrenador' : $this->t($squad->position);
				$player->dob = strftime('%e/%m/%Y', strtotime($squad->dateOfBirth));
				$player->country = $squad->countryOfBirth;
				$content['players'][] = $player;
			}
		}

		// send information to the view
		$response->setCache();
		$response->setTemplate('equipo.ejs', $content);
	}

	/**
	 * Get all available teams
	 *
	 * @param String $code
	 * @return array|\StdClass
	 */
	private function getTeams($code = false)
	{
		// crate the array of teams
		$teams = [
			'PD' => (object) [
				'leagueCode' => 'PD',
				'leagueName' => 'Primera División Española',
				'countryCode' => 'es',
				'countryName' => 'España'
			],
			'CL' => (object) [
				'leagueCode' => 'CL',
				'leagueName' => 'UEFA Champions League',
				'countryCode' => 'eu',
				'countryName' => 'Europa'
			],
			'PL' => (object) [
				'leagueCode' => 'PL',
				'leagueName' => 'Premier League',
				'countryCode' => 'gb',
				'countryName' => 'England'
			],
			'BL1' => (object) [
				'leagueCode' => 'BL1',
				'leagueName' => 'Bundesliga',
				'countryCode' => 'de',
				'countryName' => 'Alemania'
			],
			'DED' => (object) [
				'leagueCode' => 'DED',
				'leagueName' => 'Eredivisie',
				'countryCode' => 'nl',
				'countryName' => 'Holanda'
			],
			'FL1' => (object) [
				'leagueCode' => 'FL1',
				'leagueName' => 'French League One',
				'countryCode' => 'fr',
				'countryName' => 'Francia'
			],
			'PPL' => (object) [
				'leagueCode' => 'PPL',
				'leagueName' => 'Portugal Primeira Liga',
				'countryCode' => 'pt',
				'countryName' => 'Portugal'
			],
			'EFL' => (object) [
				'leagueCode' => 'EFL',
				'leagueName' => 'English Football League Two',
				'countryCode' => 'gb',
				'countryName' => 'United Kingdom'
			],
			'SA' => (object) [
				'leagueCode' => 'SA',
				'leagueName' => 'Serie A',
				'countryCode' => 'it',
				'countryName' => 'Italia'
			],
			'BSA' => (object) [
				'leagueCode' => 'BSA',
				'leagueName' => 'Brasileiro Serie A',
				'countryCode' => 'br',
				'countryName' => 'Brasil'
			]
		];

		// return the array of teams
		return $code ? $teams[$code] : array_values($teams);
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
			'Goalkeeper' => 'Portero',
			'Defender' => 'Defensa',
			'Midfielder' => 'Centrocampo',
			'Attacker' => 'Delantero',
		];

		// return word or empty
		return $sp[$word] ?? '';
	}

	/**
	 * Access remote content
	 *
	 * @param String $uri
	 * @param String $date
	 * @return mixed
	 */
	private function api($uri)
	{
		// get the token
		$token = 'd08dda4df1954b9781e83bd7fedc20c3';

		// access the api
		try {
			$data = Crawler::getCache($uri, 'GET', null, ["X-Auth-Token: $token"]);
		} catch (Exception $e) {
			return false;
		}

		// return data
		return json_decode($data);
	}
}

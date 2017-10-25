<?php

include 'models/FootballData.php';

/**
 * Apretaste Futbol subservice
 *
 * @author  Daniel Freitag <daniel@football-data.org>
 * @author  Kuma [@kumahacker] <kumahavana@gmail.com>
 * @version 2.0
 * @date    19.10.2017
 */
class Futbol extends Service
{
	public $apiFD = null;
	public $soccerSeasons = null; //Get all soccer seasons available

	/**
	 * Function executed when the service is called
	 *
	 * @param Request
	 *
	 * @return Response
	 * */
	public function _main(Request $request)
	{
		$apiFD = new FootballData();
		//Get all soccer seasons available
		$soccerSeasons = $apiFD->getSoccerseasons();
		if(empty($request->query) || (strtolower($request->query) != 'liga') || (strtolower($request->query) != 'jornada') || (strtolower($request->query) != 'equipo'))
		{

			$response = new Response();
			$response->setResponseSubject("¿Cual liga deseas consultar?");
			$response->createFromTemplate("selectLiga.tpl", ["ligas" => $soccerSeasons]);

			return $response;
		}
	}

	/**
	 * Search a journey by id
	 *
	 * @param $query
	 * @param $apiFD
	 *
	 * @return \Response
	 */
	private function searchJourneyById($query, $apiFD)
	{
		$query_data    = explode(" ", $query);
		$id_league     = $query_data[0];
		$journey       = isset($query_data[1]) ? $query_data[1] : 1;
		$soccer_season = $apiFD->getSoccerseasonById($id_league);

		if(strtoupper($journey) == "TODAS")
		{
			$fixture          = $soccer_season->getAllFixtures();
			$response_subject = "Todos los resultados de la " . $soccer_season->payload->caption;
		}
		else
		{
			$fixture          = $soccer_season->getFixturesByMatchday($journey);
			$response_subject = $soccer_season->payload->caption . ", Jornada {$journey}";
		}

		// create the response
		$response = new Response();
		$response->setResponseSubject($response_subject);
		$response->createFromTemplate("showLeagueLastResults.tpl", [
			"titulo" => $response_subject,
			"liga" => $soccer_season,
			"jornada" => $journey,
			"fixture" => $fixture
		]);

		return $response;
	}

	/**
	 * Subservice LIGA
	 *
	 * @param \Request $request
	 *
	 * @return \Response
	 */
	public function _liga(Request $request)
	{
		$apiFD = new FootballData();

		// Get all soccer seasons available
		$soccerSeasons = $apiFD->getSoccerseasons();
		if(empty($request->query))
		{
			$response = new Response();
			$response->setResponseSubject("¿Cual liga deseas consultar?");
			$response->createFromTemplate("selectLiga.tpl", ["ligas" => $soccerSeasons]);

			return $response;
		}
		else
		{
			return $this->searchInfoLeagueById($request->query, $apiFD);
		}
	}

	/**
	 * Search information about league
	 *
	 * @param $query
	 * @param $apiFD
	 *
	 * @return \Response
	 */
	private function searchInfoLeagueById($query, $apiFD)
	{
		$soccerseason      = $apiFD->getSoccerseasonById($query);

		if (is_null($soccerseason))
		{
			$response = new Response();
			$response->setResponseSubject("No encontramos informacion de la liga en estos momentos.");
			$response->createFromText("No encontramos informaci&oacute;n de la liga en estos momentos. Por favor intente m&aacute;s tarde.");
			return $response;
		}

		$tableLeague       = $soccerseason->getLeagueTable();
		$tipoTorneo        = isset($tableLeague->standing) ? 'liga' : 'copa';
		$currentMatchday   = $soccerseason->payload->currentMatchday;
		$numberOfMatchdays = $soccerseason->payload->numberOfMatchdays;
		$nextMatchday      = ($currentMatchday < $numberOfMatchdays) ? ($currentMatchday + 1) : $numberOfMatchdays;
		$nextFixture       = $soccerseason->getFixturesByMatchday($nextMatchday);
		// create a json object to send to the template
		$responseContent = [
			"tipoTorneo" => $tipoTorneo,
			"liga" => $soccerseason,
			"posicionesLiga" => $tableLeague,
			"nextFixture" => $nextFixture,
		];
		// create the response
		$response = new Response();
		$response->setResponseSubject("Informacion de la liga...");
		$response->createFromTemplate("showLeagueInfo.tpl", $responseContent);

		return $response;
	}

	/**
	 * Subservice JORNADA
	 *
	 * @param \Request $request
	 *
	 * @return \Response
	 */
	public function _jornada(Request $request)
	{
		$apiFD = new FootballData();

		//Get all soccer seasons available
		$soccerSeasons = $apiFD->getSoccerseasons();
		if(empty($request->query))
		{
			$response = new Response();
			$response->setResponseSubject("¿Cual jornada y de que liga deseas consultar?");
			$response->createFromTemplate("selectLiga.tpl", ["ligas" => $soccerSeasons]);

			return $response;
		}
		else
			return $this->searchJourneyById($request->query, $apiFD);

	}

	/**
	 * Subservice EQUIPO
	 *
	 * @param \Request $request
	 *
	 * @return \Response
	 */
	public function _equipo(Request $request)
	{
		$apiFD = new FootballData();

		//Get all soccer seasons available
		$soccerSeasons = $apiFD->getSoccerseasons();
		if(empty($request->query))
		{
			$response = new Response();
			$response->setResponseSubject("¿Cual jornada y de que liga deseas consultar?");
			$response->createFromTemplate("selectLiga.tpl", ["ligas" => $soccerSeasons]);

			return $response;
		}
		else
		{
			return $this->searchTeamById($request->query, $apiFD);
		}
	}

	/**
	 * Search team by ID
	 *
	 * @param $query
	 * @param $apiFD
	 *
	 * @return \Response
	 */
	private function searchTeamById($query, $apiFD)
	{
		$query_data       = explode(" ", $query);
		$id_league        = $query_data[0];
		$equipo           = $query_data[1];
		$soccer_season    = $apiFD->getSoccerseasonById($id_league);
		$equipos          = null;
		$fixturesHome     = null;
		$fixturesAway     = null;
		$players          = null;
		$imgTeamCacheFile = null;

		if(strtoupper($equipo) == "TODOS")
		{
			$equipos     = $soccer_season->getTeams();
			$textoAsunto = "Equipos que compiten en la " . $soccer_season->payload->caption;
		}
		else
		{
			$teamName = substr($query, 4);
			// search for desired team
			$searchQuery = $apiFD->searchTeam(urlencode($teamName));

			if( ! isset($searchQuery->teams[0]->id))
			{
				$response = new Response();
				$response->setResponseSubject("Equipo no encontrado");
				$response->createFromText("No encontramos el equipo que buscabas");

				return $response;
			}

			$equipos       = $apiFD->getTeamById($searchQuery->teams[0]->id);

			$fixturesHome  = $equipos->getFixtures('home')->fixtures;
			$fixturesAway  = $equipos->getFixtures('away')->fixtures;
			$players       = $equipos->getPlayers();
			$imgTeamSource = $equipos->_payload->crestUrl;
			$extension     = substr($imgTeamSource, - 4);

			$di               = \Phalcon\DI\FactoryDefault::getDefault();
			$wwwroot          = $di->get('path')['root'];
			$imgTeamCacheFile = "$wwwroot/temp/" . "team_" . $id_league . "_" . $searchQuery->teams[0]->id . "_logoCacheFile.png"; //

			if( ! file_exists($imgTeamCacheFile))
			{
				$imgTeamSource = $this->file_get_contents_curl($imgTeamSource);
				if($imgTeamSource != false)
				{
					if(strtolower($extension) == '.svg')
					{
						$image = new Imagick();
						$image->readImageBlob($imgTeamSource); //imagen svg
						$image->setImageFormat("png24");
						$image->resizeImage(1024, 768, imagick::FILTER_LANCZOS, 1);
						$image->writeImage($imgTeamCacheFile); //imagen png
					}
					else
					{
						file_put_contents($imgTeamCacheFile, $imgTeamSource);
					}
				}
				else
				{
					$image  = new Imagick();
					$dibujo = new ImagickDraw();
					$dibujo->setFontSize(30);

					$image->newImage(100, 100, new ImagickPixel('#d3d3d3')); //imagen fondo gris
					/* Crear texto */
					$image->annotateImage($dibujo, 10, 45, 0, ' 404!');
					$image->setImageFormat("png24");
					$image->resizeImage(1024, 768, imagick::FILTER_LANCZOS, 1);
					$image->writeImage($imgTeamCacheFile);
				}
			}
			$textoAsunto = "Datos del " . $teamName;
		}

		// create a json object to send to the template
		$responseContent = [
			"titulo" => $textoAsunto,
			"liga" => $soccer_season,
			"equipo" => $equipo,
			"equipos" => $equipos,
			"juegosHome" => $fixturesHome,
			"juegosAway" => $fixturesAway,
			"jugadores" => $players,
			"imgTeam" => $imgTeamCacheFile
		];

		// get the images to embed into the email
		$images = [
			"imgTeam" => $imgTeamCacheFile
		];

		// create the response
		$response = new Response();
		$response->setResponseSubject($textoAsunto);
		$response->createFromTemplate("showLeagueTeams.tpl", $responseContent, $images);

		return $response;
	}

	private function file_get_contents_curl($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
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
}

<?php

use Goutte\Client; // UNCOMMENT TO USE THE CRAWLER OR DELETE
use Symfony\Component\DomCrawler\Crawler;
include 'models/FootballData.php';

class Futbol extends Service{
	public $apiFD = null;
	//Get all soccer seasons avaiable
	public $soccerSeasons = null;
	/**
	 * Function executed when the service is called
	 * 
	 * @param Request
	 * @return Response
	 * */
	public function _main(Request $request){
		$apiFD = new FootballData();
		//Get all soccer seasons avaiable
		$soccerSeasons = $apiFD->getSoccerseasons();
		if (empty($request->query) || (strtolower($request->query) != 'liga') || (strtolower($request->query) != 'jornada') || (strtolower($request->query) != 'equipo')){                       

			$response = new Response();
			$response->setResponseSubject("¿Cual liga deseas consultar?");
			$response->createFromTemplate("selectLiga.tpl", array("ligas" => $soccerSeasons));
			return $response;
		}
	}

	public function _jornada(Request $request){	
		$apiFD = new FootballData();
		//Get all soccer seasons avaiable
		$soccerSeasons = $apiFD->getSoccerseasons();
		if (empty($request->query)){
			$response = new Response();
			$response->setResponseSubject("¿Cual jornada y de que liga deseas consultar?");
			$response->createFromTemplate("selectLiga.tpl", array("ligas" => $soccerSeasons));
			return $response;
		}else{
			return $this->searchJornadaXid($request->query, $apiFD);
		}
	}

	private function searchJornadaXid($query, $apiFD){
		$datos = explode(" ", $query);
		$idLiga = $datos[0];
		$jornada = $datos[1];
		$soccerseason = $apiFD->getSoccerseasonById($idLiga);
		if (strtoupper($jornada) == "TODAS"){
			$fixture = $soccerseason->getAllFixtures();
			$textoAsunto = "Todos los resultados de la ".$soccerseason->payload->caption;
		}else{
			$fixture = $soccerseason->getFixturesByMatchday($jornada);
			$textoAsunto = $soccerseason->payload->caption. ", Jornada ".$jornada."";
		}
		// create a json object to send to the template
		$responseContent = array(
			"titulo" => $textoAsunto,
			"liga" => $soccerseason,
			"jornada" => $jornada,
			"fixture" => $fixture
		);
		// create the response
		$response = new Response();
		$response->setResponseSubject($textoAsunto);
		$response->createFromTemplate("showLeagueLastResults.tpl", $responseContent);
		return $response;
	}

	public function _liga(Request $request){	
		$apiFD = new FootballData();
		//Get all soccer seasons avaiable
		$soccerSeasons = $apiFD->getSoccerseasons();
		if (empty($request->query)){
			$response = new Response();
			$response->setResponseSubject("¿Cual liga deseas consultar?");
			$response->createFromTemplate("selectLiga.tpl", array("ligas" => $soccerSeasons));
			return $response;
		}else{
			return $this->searchInfoLigaXid($request->query, $apiFD);
		}
	}
	
	private function searchInfoLigaXid($query, $apiFD){
		$soccerseason = $apiFD->getSoccerseasonById($query);
        $tableLeague = $soccerseason->getLeagueTable();
        $tipoTorneo = isset($tableLeague->standing) ? 'liga' : 'copa';
        $currentMatchday = $soccerseason->payload->currentMatchday;
        $numberOfMatchdays = $soccerseason->payload->numberOfMatchdays;
        $nextMatchday = ($currentMatchday < $numberOfMatchdays) ? ($currentMatchday + 1): $numberOfMatchdays;
        $nextFixture = $soccerseason->getFixturesByMatchday($nextMatchday);
		// create a json object to send to the template
		$responseContent = array(
			"tipoTorneo" => $tipoTorneo,
			"liga" => $soccerseason,
			"posicionesLiga" => $tableLeague,
			"nextFixture" => $nextFixture,
		);
		// create the response
		$response = new Response();
		$response->setResponseSubject("Informacion de la liga...");
		$response->createFromTemplate("showLeagueInfo.tpl", $responseContent);
		return $response;
	}

	public function _equipo(Request $request){	
		$apiFD = new FootballData();
		//Get all soccer seasons avaiable
		$soccerSeasons = $apiFD->getSoccerseasons();
		if (empty($request->query)){
			$response = new Response();
			$response->setResponseSubject("¿Cual jornada y de que liga deseas consultar?");
			$response->createFromTemplate("selectLiga.tpl", array("ligas" => $soccerSeasons));
			return $response;
		}else{
			return $this->searchEquipoXid($request->query, $apiFD);
		}
	}

	private function searchEquipoXid($query, $apiFD){
		$datos = explode(" ", $query);
		$idLiga = $datos[0];
		$equipo = $datos[1];
		$soccerseason = $apiFD->getSoccerseasonById($idLiga);
		$equipos = null;
		$fixturesHome = null;
		$fixturesAway = null;
		$players = null;
		$imgTeamCacheFile = null;

		if (strtoupper($equipo) == "TODOS"){
			$equipos = $soccerseason->getTeams();
			$textoAsunto = "Equipos que compiten en la ".$soccerseason->payload->caption;
		}else{
			$teamName = substr($query, 4);
			// search for desired team
            $searchQuery = $apiFD->searchTeam(urlencode($teamName));

			$equipos = $apiFD->getTeamById($searchQuery->teams[0]->id);
            $fixturesHome = $equipos->getFixtures('home')->fixtures;
            $fixturesAway = $equipos->getFixtures('away')->fixtures;
            $players = $equipos->getPlayers();
            $imgTeamSource = $equipos->_payload->crestUrl;
            $extension = substr($imgTeamSource, -4);

            $di = \Phalcon\DI\FactoryDefault::getDefault();
        	$wwwroot = $di->get('path')['root'];
        	$imgTeamCacheFile = "$wwwroot/temp/" . "team_".$idLiga."_".$searchQuery->teams[0]->id."_logoCacheFile.png"; //
        	
			if(!file_exists($imgTeamCacheFile)){
				$imgTeamSource = $this->file_get_contents_curl($imgTeamSource);
				if ($imgTeamSource != false){
					if (strtolower($extension) == '.svg'){
						$image = new Imagick();
						$image->readImageBlob($imgTeamSource); //imagen svg
						$image->setImageFormat("png24");
						$image->resizeImage(1024, 768, imagick::FILTER_LANCZOS, 1); 
						$image->writeImage($imgTeamCacheFile); //imagen png
					}else{
						file_put_contents($imgTeamCacheFile, $imgTeamSource);
					}
				}else{
					$image = new Imagick();
					$dibujo = new ImagickDraw();
					$dibujo->setFontSize( 30 );
					
					$image->newImage(100, 100, new ImagickPixel('#d3d3d3')); //imagen fondo gris
					/* Crear texto */
					$image->annotateImage($dibujo, 10, 45, 0, ' 404!');
					$image->setImageFormat("png24");
					$image->resizeImage(1024, 768, imagick::FILTER_LANCZOS, 1); 
					$image->writeImage(
						$imgTeamCacheFile);
				}
			}
			$textoAsunto = "Datos del ".$teamName;
		}
		// create a json object to send to the template
		$responseContent = array(
			"titulo" => $textoAsunto,
			"liga" => $soccerseason,
			"equipo" => $equipo,
			"equipos" => $equipos,
			"juegosHome" => $fixturesHome,
			"juegosAway" => $fixturesAway,
			"jugadores" => $players,
			"imgTeam" => $imgTeamCacheFile
		);
		// get the images to embed into the email
		$images = array(
			"imgTeam" => $imgTeamCacheFile
		);
		// create the response
		$response = new Response();
		$response->setResponseSubject($textoAsunto);
		$response->createFromTemplate("showLeagueTeams.tpl", $responseContent, $images);
		return $response;
	}

	private function file_get_contents_curl($url){
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
		if($httpCode == 404) {
		    /* Handle 404 here. */
		    $data = false;
		}
	    curl_close($ch);
	    return $data;
    }
}

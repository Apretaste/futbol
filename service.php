<?php

include __DIR__.'/models/FootballData.php';

/**
 * Futbol Service
 *
 * @author  Kuma [@kumahacker] <kumahavana@gmail.com>
 * @version 3.0
 */
class FutbolService extends ApretasteService
{

    public $apiFD = null;

    public $soccerSeasons = null; // Get all soccer seasons available

    /**
     * Function executed when the service is called
     *
     * @param Request
     *
     **/
    public function _main()
    {
        $apiFD = new FootballData();

        // Get all soccer seasons available
        $soccerSeasons = $apiFD->getSoccerseasons();

        if (empty($this->request->input->data->query) || (strtolower($this->request->input->data->query) != 'liga') || (strtolower($this->request->input->data->query) != 'jornada') || (strtolower($this->request->input->data->query) != 'equipo')) {

            $this->response->setCache();
            $this->response->setLayout('futbol.ejs');
            $this->response->setTemplate("selectLiga.ejs", [
                "ligas" => $soccerSeasons->competitions
            ]);

        }
    }

    /**
     * Search a journey by id
     *
     * @param $query
     * @param $apiFD
     *
     */
    private function searchJourneyById($query, $apiFD)
    {
        $query_data = explode(" ", $query);
        $id_league = $query_data[0];
        $journey = isset($query_data[1]) ? $query_data[1] : 1;
        $soccer_season = $apiFD->getSoccerseasonById($id_league);

        if (is_null($soccer_season)) {

            $this->simpleMessage(
                "No encontramos informacion de la liga en estos momentos.",
                "No encontramos informaci&oacute;n de la liga en estos momentos. Por favor intente m&aacute;s tarde.");

            return;
        }

        if (strtoupper($journey) == "TODAS") {
            $fixture = $soccer_season->getAllFixtures();

            $response_subject = "Todos los resultados de la ".$soccer_season->payload->name;
        } else {
            $fixture = $soccer_season->getFixturesByMatchday($journey);
            $response_subject = $soccer_season->payload->name.", Jornada {$journey}";
        }

        // create the response
        $this->response->setLayout('futbol.ejs');
        $this->response->setTemplate("showLeagueLastResults.ejs", [
            "titulo"  => $response_subject,
            "liga"    => $soccer_season,
            "jornada" => $journey,
            "fixture" => $fixture
        ]);
    }

    /**
     * Subservice LIGA
     *
     * @param \Request $request
     *
     */
    public function _liga()
    {
        $apiFD = new FootballData();

        // Get all soccer seasons available
        $soccerSeasons = $apiFD->getSoccerseasons();

        if (empty($this->request->input->data->query)) {
            $this->response->setLayout('futbol.ejs');
            $this->response->setTemplate("selectLiga.ejs", [
                "ligas" => $soccerSeasons->competitions
            ]);

            return;
        } else {
            $this->searchInfoLeagueById($this->request->input->data->query, $apiFD);
        }
    }

    /**
     * Search information about league
     *
     * @param $query
     * @param $apiFD
     *
     */
    private function searchInfoLeagueById($query, $apiFD)
    {
        $soccerseason = $apiFD->getSoccerseasonById($query);

        if (is_null($soccerseason)) {

            $this->response->setResponseSubject();
            $this->simpleMessage(
                "No encontramos informacion de la liga en estos momentos.",
                "No encontramos informaci&oacute;n de la liga en estos momentos. Por favor intente m&aacute;s tarde.");

            return;
        }

        $tableLeague = $soccerseason->getLeagueTable();
        $tipoTorneo = ($tableLeague->standings[0]->stage == "GROUP_STAGE") ? 'copa' : 'liga';
        $currentMatchday = $soccerseason->payload->currentSeason->currentMatchday;
        //$numberOfMatchdays = $soccerseason->payload->numberOfMatchdays;
        //$nextMatchday      = ($currentMatchday < $numberOfMatchdays) ? ($currentMatchday + 1) : $numberOfMatchdays;
        $nextFixture = $soccerseason->getFixturesByMatchday($currentMatchday + 1);
        // create a json object to send to the template
        $responseContent = [
            "tipoTorneo"     => $tipoTorneo,
            "liga"           => $soccerseason,
            "posicionesLiga" => $tableLeague,
            "nextFixture"    => $nextFixture
        ];
        $this->response->setLayout('futbol.ejs');
        $this->response->setTemplate("showLeagueInfo.ejs", $responseContent);
    }

    /**
     * Subservice JORNADA
     *
     * @param \Request $request
     */
    public function _jornada()
    {
        $apiFD = new FootballData();

        //Get all soccer seasons available
        $soccerSeasons = $apiFD->getSoccerseasons();

        if (empty($this->request->input->data->query)) {
            $this->response->setLayout('futbol.ejs');
            $this->response->setTemplate("selectLiga.ejs", [
                "ligas" => $soccerSeasons->competitions
            ]);

            return;
        }

        $this->searchJourneyById($this->request->input->data->query, $apiFD);
    }

    /**
     * Subservice EQUIPO
     *
     * @param \Request $request
     *
     */
    public function _equipo()
    {
        $apiFD = new FootballData();

        //Get all soccer seasons available
        $soccerSeasons = $apiFD->getSoccerseasons();
        if (empty($this->request->input->data->query)) {
            $this->response->setLayout('futbol.ejs');
            $this->response->setTemplate("showLeagueTeams.ejs", [
                "ligas" => $soccerSeasons
            ]);

            return;
        }

        $this->searchTeamById($this->request->input->data->query, $apiFD);
    }

    /**
     * Search team by ID
     *
     * @param $query
     * @param $apiFD
     */
    private function searchTeamById($query, $apiFD)
    {
        $query_data = explode(" ", $query);
        $id_league = $query_data[0];
        $equipo = $query_data[1];
        $soccer_season = $apiFD->getSoccerseasonById($id_league);

        if (is_null($soccer_season)) {

            $this->simpleMessage(
                "No encontramos informacion de la liga en estos momentos.",
                "No encontramos informaci&oacute;n de la liga en estos momentos. Por favor intente m&aacute;s tarde.");

            return;
        }

        $equipos = null;
        $fixturesHome = null;
        $fixturesAway = null;
        $players = null;
        $imgTeamCacheFile = null;

        if (strtoupper($equipo) == "TODOS") {
            $equipos = $soccer_season->getTeams();
            $textoAsunto = "Equipos que compiten en la ".$soccer_season->payload->name;

        } else {
            /*$teamName = substr($query, 4);
            // search for desired team
            $searchQuery = $apiFD->searchTeam(urlencode($teamName));

            if( ! isset($searchQuery->teams[0]->id))
            {
                
                $this->response->setResponseSubject("Equipo no encontrado");
                $this->response->createFromText("No encontramos el equipo que buscabas");

                return;
            }*/

            $equipos = $apiFD->getTeamById($equipo);
            $fixturesHome = $equipos->getFixtures('HOME')->matches;
            $fixturesAway = $equipos->getFixtures('AWAY')->matches;
            //$players       = $equipos->getPlayers();

            $imgTeamSource = $equipos->_payload->crestUrl;
            $extension = substr($imgTeamSource, -4);

            $di = \Phalcon\DI\FactoryDefault::getDefault();
            $wwwroot = $di->get('path')['root'];
            $imgTeamCacheFile = "$wwwroot/temp/"."team_".$id_league."_".$equipo."_logoCacheFile.svg";

            if (!file_exists($imgTeamCacheFile)) {

                $imgTeamSource = $this->file_get_contents_curl($imgTeamSource);
                if ($imgTeamSource != false) {
                    if (strtolower($extension) == '.svg') {
                        file_put_contents($imgTeamCacheFile, $imgTeamSource);
                        /*$image = new Imagick();
                        $image->readImageBlob($imgTeamSource); //imagen svg
                        $image->setImageFormat("png24");
                        $image->resizeImage(1024, 768, imagick::FILTER_LANCZOS, 1);
                        $image->writeImage($imgTeamCacheFile); //imagen png*/
                    } else {
                        file_put_contents($imgTeamCacheFile, $imgTeamSource);
                    }
                }

                /*else
                {
                    $image  = new Imagick();
                    $dibujo = new ImagickDraw();
                    $dibujo->setFontSize(30);

                    $image->newImage(100, 100, new ImagickPixel('#d3d3d3')); 
                    $image->annotateImage($dibujo, 10, 45, 0, ' 404!');
                    $image->setImageFormat("png24");
                    $image->resizeImage(1024, 768, imagick::FILTER_LANCZOS, 1);
                    $image->writeImage($imgTeamCacheFile);
                }*/
            }
            $textoAsunto = "Datos del ".$equipos->_payload->name;
        }

        // create a json object to send to the template
        $responseContent = [
            "titulo"     => $textoAsunto,
            "liga"       => $soccer_season,
            "equipo"     => $equipo,
            "equipos"    => $equipos,
            "juegosHome" => $fixturesHome,
            "juegosAway" => $fixturesAway,
            "jugadores"  => $players,
            "imgTeam"    => $imgTeamCacheFile
        ];

        // get the images to embed into the email
        $images = [
            "imgTeam" => $imgTeamCacheFile
        ];
        $this->response->setLayout('futbol.ejs');
        $this->response->setTemplate("showLeagueTeams.ejs", $responseContent, $images);
    }

    /**
     * @param $url
     *
     * @return bool|string
     */
    private function file_get_contents_curl($url)
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-Auth-Token:b8044b406aca4851ac7ceeea79fccaea"
        ]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode == 404) {
            /* Handle 404 here. */
            $data = false;
        }
        curl_close($ch);

        return $data;
    }
}

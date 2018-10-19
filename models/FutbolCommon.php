<?php

class FutbolCommon {

	public $config;
	public $_payload;
	public $req_preferences = [];

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
		curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Auth-Token:b8044b406aca4851ac7ceeea79fccaea"
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
			/* Handle 40
			4 here. */
			
			$data = false;
		}
		curl_close($ch);
		return $data;
	}


	/**
	 * Generic method for get remote content
	 *
	 * @param $uri
	 * @param string $date
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

		if(file_exists($cacheFile)) {
			$response = file_get_contents($cacheFile);
			$data = json_decode($response);
			if (!is_null($data) && !empty($data))
				return $data;
		}

		$response = $this->file_get_contents_curl($uri);

		// save cache file
		file_put_contents($cacheFile, $response);

		return json_decode($response);
	}

}
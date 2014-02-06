<?php

class CurlDownloader
{

	public function download($url, array $post = NULL, $timeout = 20, $headers = Array()) {

		$connection = curl_init();

		if (!empty($post)) {
			curl_setopt($connection, CURLOPT_POST, 1);
			curl_setopt($connection, CURLOPT_POSTFIELDS, $post);
		}
		
		curl_setopt($connection, CURLOPT_URL, $url); /* set request url (with xml if get method) */

		$cookie_file = __DIR__ . '/../../temp/curl_cookies.txt';

		curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1); /* set it to return the transfer as a string from curl_exec */
		curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0); /* stop CURL from verifying the peer's certificate */
		curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0); /* stop CURL from verifying the host's certificate */
		curl_setopt($connection, CURLOPT_SSLVERSION, 3);
		curl_setopt($connection, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($connection, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:11.0) Gecko/20100101 Firefox/11.0');
		if($headers){
			curl_setopt($connection, CURLOPT_HTTPHEADER, $headers);
		}

		if (!file_exists($cookie_file)) {
			if ($fp = fopen($cookie_file, 'w')) {
				fclose($fp);
			}
			else {
				NDebugger::log('The cookie file could not be opened. Make sure this directory has the correct permissions');
			}
		}

		if (file_exists($cookie_file)) {
			curl_setopt($connection, CURLOPT_COOKIEFILE, $cookie_file);
			curl_setopt($connection, CURLOPT_COOKIEJAR, $cookie_file);
		}

		$content = curl_exec($connection);

		curl_close($connection);

		return $content;
	}

}
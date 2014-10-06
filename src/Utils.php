<?php

require_once("TextFormat.php");

use pocketmine\utils\TextFormat;

class Utils{
	const CHANNEL_TOPIC = "332";
	const CHANNEL_USERS = "353";
	public static $timezoneDelta = 0;
	public static function console($line){
		$line = TextFormat::AQUA . date("[H:i:s] ", time() + self::$timezoneDelta) . TextFormat::RESET . "$line" . PHP_EOL . TextFormat::RESET;
		echo TextFormat::toANSI($line);
	}
	public static function tmpLine($line){
		$line = TextFormat::AQUA . date("[H:i:s] ", time() + self::$timezoneDelta) . TextFormat::RESET . "$line\r" . TextFormat::RESET;
		echo TextFormat::toANSI($line);
		echo "\r";
	}
	public static function queryURL($url, $timeout = 5, $post = false, $args = []){
		$res = curl_init($url);
		curl_setopt($res, CURLOPT_HTTPHEADER, ["User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0 PocketMine-MP"]);
		curl_setopt($res, CURLOPT_AUTOREFERER, true);
		curl_setopt($res, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($res, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($res, CURLOPT_POST, $post ? 1:0);
		if($post){
			curl_setopt($res, CURLOPT_POSTFIELDS, $args);
		}
		curl_setopt($res, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($res, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($res, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($res, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($res, CURLOPT_CONNECTTIMEOUT, (int) $timeout);
		$result = curl_exec($res);
		curl_close($res);
		return $result;
	}
}

echo "Checking timezone..." . PHP_EOL;
/*
 * Copyright PocketMine Team
 * This snippet is copied from https://github.com/PocketMine/PocketMine-MP/blob/cd65179/src/pocketmine/PocketMine.php
 */
if(!ini_get("date.timezone")){
	//If system timezone detection fails or timezone is an invalid value.
	if($response = Utils::queryURL("http://ip-api.com/json")
		and $ip_geolocation_data = json_decode($response, true)
		and $ip_geolocation_data['status'] != 'fail'
		and date_default_timezone_set($ip_geolocation_data['timezone']))
	{
		//Again, for redundancy.
		ini_set("date.timezone", $ip_geolocation_data['timezone']);
	}else{
		ini_set("date.timezone", "UTC");
		date_default_timezone_set("UTC");
		echo "[WARNING] Timezone could not be automatically determined. An incorrect timezone will result in incorrect timestamps on console logs. It has been set to \"UTC\" by default. You can change it on the php.ini file.";
	}
}else{
	/*
	 * This is here so that stupid idiots don't come to us complaining and fill up the issue tracker when they put an incorrect timezone abbreviation in php.ini apparently.
	 */
	$default_timezone = date_default_timezone_get();
	if(strpos($default_timezone, "/") === false){
		$default_timezone = timezone_name_from_abbr($default_timezone);
		ini_set("date.timezone", $default_timezone);
		date_default_timezone_set($default_timezone);
	}
}

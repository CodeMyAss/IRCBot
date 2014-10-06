<?php

require_once("TextFormat.php");

use pocketmine\utils\TextFormat;

class Utils{
	const CHANNEL_TOPIC = "332";
	const CHANNEL_USERS = "353";
	public static function console($line){
		$line = TextFormat::AQUA . date("[H:i:s] ") . TextFormat::RESET . "$line" . PHP_EOL . TextFormat::RESET;
		echo TextFormat::toANSI($line);
	}
	public static function tmpLine($line){
		$line = TextFormat::AQUA . date("[H:i:s] ") . TextFormat::RESET . "$line\r" . TextFormat::RESET;
		echo TextFormat::toANSI($line);
		echo "\r";
	}
}

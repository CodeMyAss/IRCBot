<?php

use pocketmine\utils\TextFormat;

class Bot{
	public $sk;
	public $running = true;
	public function __construct(){
		$this->sk = stream_socket_client("tcp://chat.freenode.net:6667", $errno, $errstr, 10) or die($errstr);
		$this->login();
		$this->loop();
	}
	private function login(){
		$this->send("NICK LegendsOfMCPE");
		$this->send("USER LegendsOfMCPE 0 * :LegendsOfMCPE Bot");
		$this->send("PRIVMSG NickServ :IDENTIFY LegendsOfMCPE " . PASSWORD);
		$this->send("JOIN #LegendOfMCPE");
	}
	private function loop(){
		while($this->running){
			$line = $this->receive();
			if(!$line){
				continue;
			}
			if(preg_match("~^ping :(.*)?~i", $line, $pong)){
				$this->send("PONG $pong[1]");
				continue;
			}
			if(preg_match("^(:[^ ]* )?([A-Za-z0-9]*)( (.*))?$", $line, $matches)){
				$cmd = $matches[2];
				$args = isset($matches[4]) ? $matches[4]:"";
				if(($pos = strpos($args, " :")) !== false){
					$suffix = substr($args, $pos + 2);
					$args = substr($args, 0, $pos);
				}
				$args = explode(" ", $args);
				if(isset($suffix)){
					$args[] = $suffix;
				}
				$this->processLine($cmd, $args, substr((string) @$matches[1], 1));
			}
		}
	}
	public function send($line){
		return fwrite($this->sk, "$line\n", strlen($line) + 1) or die("Cannot send line $line");
	}
	public function receive(){
		return trim(fgets($this->sk));
	}

	public function processLine($cmd, $args, $prefix){
		if(is_numeric($cmd)){
			if($cmd === "001" or $cmd === "002" or $cmd === "004"){
				Utils::console(TextFormat::DARK_PURPLE . "[MOTD] $args[1]");
			}
			if($cmd !== Utils::CHANNEL_USERS and $cmd !== Utils::CHANNEL_TOPIC){
				return;
			}
		}
		switch(strtoupper($cmd)){
			case "NOTICE":
				Utils::console(TextFormat::YELLOW . "[NOTICE] $args[1]");
				break;
			case "JOIN":
				Utils::console("$prefix joined channel $args[0]");
				break;
			case "PART":
				Utils::console("$prefix left channel $args[0]. Reason: $args[1]");
				break;
			case "QUIT":
				Utils::console("$prefix quitted server. Reason: $args[0]");
				break;
			case "MODE":
				Utils::console(TextFormat::AQUA . "Mode change for $args[0] by $prefix: $args[1]");
				break;
			case "PRIVMSG":
				$speaker = strstr($prefix, "!", true);
				if($args[0] === "#LegendOfMCPE"){
					Utils::console(TextFormat::WHITE . "<$speaker> $args[1]");
					$this->onChat($args[0], $speaker, $args[1]);
				}
				else{
					Utils::console(TextFormat::YELLOW . "[$speaker -> me] $args[1]");
					$this->onPing($speaker, $speaker, $args[1]);
				}
				break;
			case "NICK":
				$orig = strstr($prefix, "!", true);
				Utils::console(TextFormat::LIGHT_PURPLE . "$orig changed nick to $args[0]");
				break;
			case Utils::CHANNEL_TOPIC:
				Utils::console(TextFormat::LIGHT_PURPLE . "Topic for $args[1]: $args[2]");
				break;
			case Utils::CHANNEL_USERS:
				Utils::console(TextFormat::LIGHT_PURPLE . "Users on $args[1]: " . str_replace(" ", ", ", $args[3]));
				break;
		}
	}
	private function onChat($target, $speaker, $msg){
		foreach(["LegendsOfMCPE ", "LegendsOfMCPE: ", "LegendsOfMCPE, "] as $ping){
			if(strpos($msg, $ping) === 0){
				$this->onPing($target, $speaker, trim(substr($msg, strlen($ping))));
				return;
			}
		}
		if(substr($msg, -3) === "..."){
			$this->action($target, " eats $speaker's dots so that he can find the candy house.");
		}
	}
	public function onPing($source, $sender, $msg){
		$args = explode(" ", $msg);
		$cmd = array_shift($args);
		switch(strtolower($cmd)){
			case "ping":
				$this->ping($source, $sender, "Pong! Random text in case you're bored: {$this->queryURL("https://api.github.com/zen")}");
				break;
			case "repo":
				if(!isset($args[1])){
					$this->ping($source, $sender, "Usage: repo <repo> <last-commit | #<issue-id> | $<commit-sha>>");
					break;
				}
				$this->action($source, "wasn't taught how to browse from GitHub yet :( #BlamePEMapModder");
				break;
			default:
				$this->action($source, "doesn't understand $sender's language. :(");
				break;
		}
	}
	public function ping($target, $pinged, $msg){
		if($target === $pinged){
			$this->sendTo($target, $msg);
		}
		else{
			$this->sendTo($target, "$pinged: $msg");
		}
	}
	public function action($target, $msg){
		$this->sendTo($target, "\x01ACTION $msg\x01");
	}
	public function sendTo($target, $msg){
		$this->send("PRIVMSG $target :$msg");
	}
	public function queryURL($url, $timeout = 5, $post = false, $args = []){
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

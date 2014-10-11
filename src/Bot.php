<?php

use pocketmine\utils\TextFormat;

class Bot extends Thread{
	public $dotResponds = [
		"takes away %s's dots so that he/she can find the candy house.",
		"eats %s's dots because no one ever feeds it and it is very hungry."
	];
	public $sk;
	public $running = true;
	public function run(){
		$this->sk = stream_socket_client("tcp://chat.freenode.net:6667", $errno, $errstr, 10) or die($errstr);
		$this->login();
		$this->loop();
	}
	private function login(){
		$this->send("NICK LegendsOfMCPE");
		$this->send("USER LegendsOfMCPE 0 * :LegendsOfMCPE Bot");
		$this->send("PRIVMSG NickServ :IDENTIFY LegendsOfMCPE " . PASSWORD);
//		$this->send("PRIVMSG NickServ :RELEASE LegendsOfMCPE");
//		$this->send("NICK LegendsOfMCPE");
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
//			Utils::console(TextFormat::DARK_GREEN . "[DEBUG] Received line: $line");
			$matches = [$line, ""];
			$tmpLine = $line;
			if(substr($line, 0, 1) === ":"){
				$matches[1] = strstr($line, " ", true);
				$tmpLine = substr($tmpLine, 1 + strlen($matches[1]));
			}
			$tokens = explode(" ", $tmpLine);
			$matches[2] = array_shift($tokens);
			$matches[3] = " " . implode(" ", $tokens);
			$matches[4] = substr($matches[3], 1);
			if(true /*and preg_match("/^(:[^ ]* )?([A-Za-z0-9]*)( (.*))?$/", $line, $matches)*/){
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
		$line = trim(fgets($this->sk));
		return $line;
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
		Utils::console(TextFormat::WHITE . "<$speaker> $msg");
		if(trim($msg) === "You're doing good work, LegendsOfMCPE!"){
			$this->ping($target, $speaker, "Thank you for praising me!");
		}
		foreach(["LegendsOfMCPE ", "LegendsOfMCPE: ", "LegendsOfMCPE, "] as $ping){
			if(strpos($msg, $ping) === 0){
				$this->onPing($target, $speaker, trim(substr($msg, strlen($ping))));
				return;
			}
		}
		if(substr($msg, -3) === "..."){
			$this->action($target, sprintf(array_values($this->dotResponds)[mt_rand(0, count($this->dotResponds) - 1)], $speaker));
		}
	}
	public function onPing($source, $sender, $msg){
		$hasPerm = in_array(strtolower($sender), ["pemapmodder", "pemapmodder_", "pemapmodder__", "ijoshuahd", "iksaku", "xktiverz", "tutuff", "ldx", "dutok"]);
		$args = explode(" ", $msg);
		$cmd = array_shift($args);
		switch(strtolower($cmd)){
			case "ping":
				$this->ping($source, $sender, "Pong! Random text for you in case you're bored: " . Utils::queryURL("https://api.github.com/zen"));
				break;
			case "repo":
				if(!isset($args[1])){
					$this->ping($source, $sender, "Usage: repo <repo> <last-commit [branch] | #<issue-id> | <commit-sha>>");
					break;
				}
				$repo = array_shift($args);
				$arg1 = array_shift($args);
				if($arg1 === "last-commit"){
					if(!isset($args[0])){
						if($data = Utils::queryJSON("https://api.github.com/repos/LegendOfMCPE/$repo") and
							isset($data["default_branch"])){
							$branch = $data["default_branch"];
						}
						else{
							$this->ping($source, $sender, "Failed to get data about repo $repo!");
							break;
						}
					}
					else{
						$branch = $args[0];
					}
					if($data = Utils::queryJSON("https://api.github.com/repos/LegendOfMCPE/$repo/branches/$branch") and
						isset($data["commit"])){
						$this->ping($source, $sender, $this->showCommitData($repo, $data["commit"]["sha"]));
					}
					else{
						$this->ping($source, $sender, "Failed to get data about branch $branch of repo $repo!");
					}
				}
				elseif(substr($arg1, 0, 1) !== "#"){
					$this->ping($source, $sender, $this->showCommitData($repo, $arg1));
				}
				else{
					$this->action($source, "wasn't taught how to browse issues from GitHub yet :( #BlamePEMapModder");
				}
				break;
			case "die":
				if(!$hasPerm){
					$this->ping($source, $sender, "Who do you think you are! Kill yourself!");
					break;
				}
				$this->send("Fine. I resign.");
				$this->running = false;
				Utils::console("$sender killed me! :(");
				break;
			default:
				$this->action($source, "doesn't understand $sender's language. :(");
				break;
		}
	}
	private function showCommitData($repo, $sha){
		if(!is_array($data = Utils::queryJSON("https://api.github.com/repos/LegendOfMCPE/$repo/commits/$sha"))){
			return "Cannot find commit $sha";
		}
		$shortSHA = substr($sha, 0, 7);
		$author = $data["commit"]["author"]["name"];
		$username = $data["author"]["login"];
		$url = $data["html_url"];
		$output = "Commit $shortSHA by $username ($author): $url\n";
		$msg = $data["commit"]["message"];
		$additionalMsg = "";
		if(($pos = strpos($msg, "\n")) !== false){
			$additionalMsg = trim(substr($msg, $pos));
			$msg = substr($msg, 0, $pos);
			$additionalMsg = str_replace("\n", "; ", $additionalMsg);
		}
		$output .= "$msg: $additionalMsg\n";
		$adds = 0;
		$dels = 0;
		$mods = 0;
		foreach($data["files"] as $file){
			$adds += $file["additions"];
			$dels = $file["deletions"];
			$mods = $file["changes"];
		}
		$output .= "+$adds -$dels ±$mods";
		return $output;
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
	public function sendTo($target, $msgs){
		foreach(explode("\n", $msgs) as $msg){
			$this->send("PRIVMSG $target :$msg");
		}
	}
}

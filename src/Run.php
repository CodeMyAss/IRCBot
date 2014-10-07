<?php

$path = dirname(__FILE__);

require_once("$path/secrets.php");
require_once("$path/Utils.php");
require_once("$path/Bot.php");

new Bot;

exec("pause");

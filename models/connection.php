<?php

class Connection{

	static private $link = null;

	static public function config(){

		static $config = null;

		if($config === null){

			$config = require __DIR__."/../config/config.php";

		}

		return $config;

	}

	static public function connect(){

		if(self::$link === null){

			$c = self::config();

			$dsn = "mysql:host=".$c["db_host"].";port=".$c["db_port"].";dbname=".$c["db_name"].";charset=utf8";

			self::$link = new PDO($dsn, $c["db_user"], $c["db_password"], array(
				PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_BOTH
			));

			/* Relax strict mode so the app behaves the same on MySQL 5.7+/8 and MariaDB */
			self::$link -> exec("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");

			require_once __DIR__."/settings.model.php";

			$tz = ModelSettings::get("timezone");

			if($tz && in_array($tz, timezone_identifiers_list())){

				date_default_timezone_set($tz);

				$offset = (new DateTime("now", new DateTimeZone($tz))) -> format("P");

				self::$link -> exec("SET time_zone = '".$offset."'");

			}

		}

		return self::$link;

	}

}

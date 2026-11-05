<?php

require_once __DIR__."/connection.php";

class ModelSettings{

	static private $cache = null;

	/* Default values used until the admin saves the Settings page */
	static public function defaults(){

		return array(
			"businessName"   => "My Shop",
			"address"        => "",
			"phone"          => "",
			"email"          => "",
			"taxId"          => "",
			"logo"           => "",
			"currencySymbol" => "KSh",
			"currencyCode"   => "KES",
			"taxName"        => "VAT",
			"taxRate"        => 16,
			"taxInclusive"   => 1,
			"receiptFooter"  => "Thank you for shopping with us!",
			"receiptFormat"  => "ticket",
			"timezone"       => "Africa/Nairobi",
			"mpesaTill"      => "",
			"printerEnabled" => 0,
			"printerType"    => "windows",
			"printerAddress" => "",
			"openCashDrawer" => 0
		);

	}

	/*=============================================
	READ ALL SETTINGS (cached per request)
	=============================================*/

	static public function all(){

		if(self::$cache === null){

			self::$cache = self::defaults();

			$stmt = Connection::connect()->prepare("SELECT name, value FROM settings");

			if($stmt && $stmt -> execute()){

				foreach($stmt -> fetchAll(PDO::FETCH_ASSOC) as $row){

					self::$cache[$row["name"]] = $row["value"];

				}

			}

		}

		return self::$cache;

	}

	static public function get($name){

		$all = self::all();

		return isset($all[$name]) ? $all[$name] : null;

	}

	/*=============================================
	SAVE SETTINGS
	=============================================*/

	static public function save($data){

		$stmt = Connection::connect()->prepare("INSERT INTO settings(name, value) VALUES (:name, :value) ON DUPLICATE KEY UPDATE value = VALUES(value)");

		foreach($data as $name => $value){

			if(!$stmt -> execute(array(":name" => $name, ":value" => (string)$value))){

				return "error";

			}

		}

		self::$cache = null;

		return "ok";

	}

}

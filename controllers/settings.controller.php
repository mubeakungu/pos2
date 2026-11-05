<?php

require_once __DIR__."/../models/settings.model.php";

class ControllerSettings{

	static public function all(){

		return ModelSettings::all();

	}

	static public function get($name){

		return ModelSettings::get($name);

	}

	/*=============================================
	FORMAT AN AMOUNT WITH THE BUSINESS CURRENCY
	=============================================*/

	static public function money($amount){

		return trim(ModelSettings::get("currencySymbol"))." ".number_format((float)$amount, 2);

	}

	/*=============================================
	PAYMENT METHODS
	=============================================*/

	static public function paymentMethods(){

		return array(
			"cash"  => "Cash",
			"MPESA" => "M-Pesa",
			"CC"    => "Credit Card",
			"DC"    => "Debit Card"
		);

	}

	/* Turns a stored value like "MPESA-QJK3XY12AB" into "M-Pesa (Ref: QJK3XY12AB)" */
	static public function paymentLabel($stored){

		$methods = self::paymentMethods();

		$parts = explode("-", (string)$stored, 2);

		$name = isset($methods[$parts[0]]) ? $methods[$parts[0]] : $parts[0];

		if(isset($parts[1]) && $parts[1] !== ""){

			return $name." (Ref: ".$parts[1].")";

		}

		return $name;

	}

	/*=============================================
	SAVE THE SETTINGS FORM
	=============================================*/

	static public function ctrSaveSettings(){

		if(!isset($_POST["settingsBusinessName"])){

			return;

		}

		if(!isset($_SESSION["profile"]) || $_SESSION["profile"] != "Administrator"){

			return;

		}

		$fields = array(
			"businessName"   => "settingsBusinessName",
			"address"        => "settingsAddress",
			"phone"          => "settingsPhone",
			"email"          => "settingsEmail",
			"taxId"          => "settingsTaxId",
			"currencySymbol" => "settingsCurrencySymbol",
			"currencyCode"   => "settingsCurrencyCode",
			"taxName"        => "settingsTaxName",
			"receiptFooter"  => "settingsReceiptFooter",
			"receiptFormat"  => "settingsReceiptFormat",
			"timezone"       => "settingsTimezone",
			"mpesaTill"      => "settingsMpesaTill",
			"printerType"    => "settingsPrinterType",
			"printerAddress" => "settingsPrinterAddress"
		);

		$data = array();

		foreach($fields as $name => $post){

			$data[$name] = isset($_POST[$post]) ? trim($_POST[$post]) : "";

		}

		$data["taxRate"] = max(0, (float)(isset($_POST["settingsTaxRate"]) ? $_POST["settingsTaxRate"] : 0));
		$data["taxInclusive"] = isset($_POST["settingsTaxInclusive"]) ? 1 : 0;
		$data["printerEnabled"] = isset($_POST["settingsPrinterEnabled"]) ? 1 : 0;
		$data["openCashDrawer"] = isset($_POST["settingsOpenCashDrawer"]) ? 1 : 0;

		if($data["businessName"] === ""){

			self::alert("error", "The business name can't be empty");
			return;

		}

		if(!in_array($data["timezone"], timezone_identifiers_list())){

			$data["timezone"] = "Africa/Nairobi";

		}

		if(!in_array($data["receiptFormat"], array("ticket", "a4"))){

			$data["receiptFormat"] = "ticket";

		}

		if(!in_array($data["printerType"], array("windows", "network"))){

			$data["printerType"] = "windows";

		}

		/*=============================================
		LOGO UPLOAD
		=============================================*/

		if(isset($_POST["settingsRemoveLogo"])){

			$data["logo"] = "";

		}

		if(isset($_FILES["settingsLogo"]) && $_FILES["settingsLogo"]["error"] == UPLOAD_ERR_OK){

			$info = @getimagesize($_FILES["settingsLogo"]["tmp_name"]);

			$types = array(IMAGETYPE_PNG => "png", IMAGETYPE_JPEG => "jpg");

			if(!$info || !isset($types[$info[2]])){

				self::alert("error", "The logo must be a PNG or JPG image");
				return;

			}

			$dir = __DIR__."/../views/img/business";

			if(!is_dir($dir)){

				mkdir($dir, 0755, true);

			}

			$file = "logo-".time().".".$types[$info[2]];

			if(move_uploaded_file($_FILES["settingsLogo"]["tmp_name"], $dir."/".$file)){

				$data["logo"] = "views/img/business/".$file;

			}

		}

		if(ModelSettings::save($data) == "ok"){

			self::alert("success", "Settings saved", "settings");

		}else{

			self::alert("error", "The settings could not be saved. Did you run the database upgrade?");

		}

	}

	static private function alert($type, $title, $redirect = null){

		echo '<script>
			swal({
				type: "'.$type.'",
				title: '.json_encode($title).',
				showConfirmButton: true,
				confirmButtonText: "Close"
			})'.($redirect ? '.then(function(result){ window.location = "'.$redirect.'"; })' : '').';
		</script>';

	}

	/*=============================================
	PRINT A SALE ON THE THERMAL (ESC/POS) PRINTER
	Returns true, or an error message.
	=============================================*/

	static public function printTicket($sale, $customer, $seller){

		$s = ModelSettings::all();

		if(empty($s["printerEnabled"]) || trim($s["printerAddress"]) === ""){

			return true;

		}

		try{

			if($s["printerType"] == "network"){

				$parts = explode(":", $s["printerAddress"]);
				$port = isset($parts[1]) ? (int)$parts[1] : 9100;

				$connector = new Mike42\Escpos\PrintConnectors\NetworkPrintConnector($parts[0], $port, 5);

			}else{

				$connector = new Mike42\Escpos\PrintConnectors\WindowsPrintConnector($s["printerAddress"]);

			}

			$printer = new Mike42\Escpos\Printer($connector);

			$printer -> setJustification(Mike42\Escpos\Printer::JUSTIFY_CENTER);
			$printer -> setEmphasis(true);
			$printer -> text($s["businessName"]."\n");
			$printer -> setEmphasis(false);

			foreach(array("address" => "", "phone" => "Tel: ", "email" => "", "taxId" => "PIN: ") as $key => $label){

				if(trim($s[$key]) !== ""){

					$printer -> text($label.$s[$key]."\n");

				}

			}

			$printer -> feed(1);
			$printer -> text("Receipt No. ".$sale["code"]."\n");
			$printer -> text($sale["saledate"]."\n");
			$printer -> setJustification(Mike42\Escpos\Printer::JUSTIFY_LEFT);
			$printer -> text("Customer: ".$customer["name"]."\n");
			$printer -> text("Served by: ".$seller["name"]."\n");
			$printer -> text(str_repeat("-", 32)."\n");

			foreach(json_decode($sale["products"], true) as $item){

				$printer -> setJustification(Mike42\Escpos\Printer::JUSTIFY_LEFT);
				$printer -> text($item["description"]."\n");
				$printer -> setJustification(Mike42\Escpos\Printer::JUSTIFY_RIGHT);
				$printer -> text($item["quantity"]." x ".number_format($item["price"], 2)." = ".number_format($item["totalPrice"], 2)."\n");

			}

			$printer -> setJustification(Mike42\Escpos\Printer::JUSTIFY_RIGHT);
			$printer -> text(str_repeat("-", 32)."\n");
			$printer -> text("Net: ".self::money($sale["netPrice"])."\n");
			$printer -> text($s["taxName"]." (".(float)$s["taxRate"]."%): ".self::money($sale["tax"])."\n");
			$printer -> setEmphasis(true);
			$printer -> text("TOTAL: ".self::money($sale["totalPrice"])."\n");
			$printer -> setEmphasis(false);
			$printer -> text("Paid by: ".self::paymentLabel($sale["paymentMethod"])."\n");

			if(trim($s["mpesaTill"]) !== ""){

				$printer -> setJustification(Mike42\Escpos\Printer::JUSTIFY_CENTER);
				$printer -> text("M-Pesa: ".$s["mpesaTill"]."\n");

			}

			if(trim($s["receiptFooter"]) !== ""){

				$printer -> feed(1);
				$printer -> setJustification(Mike42\Escpos\Printer::JUSTIFY_CENTER);
				$printer -> text($s["receiptFooter"]."\n");

			}

			$printer -> feed(3);
			$printer -> cut();

			if(!empty($s["openCashDrawer"])){

				$printer -> pulse();

			}

			$printer -> close();

			return true;

		}catch(Exception $e){

			return $e -> getMessage();

		}

	}

}

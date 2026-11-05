<?php

session_start();

if(!isset($_SESSION["loggedIn"]) || $_SESSION["loggedIn"] != "ok" || $_SESSION["profile"] != "Administrator"){

	http_response_code(403);
	exit();

}

require_once "../../controllers/settings.controller.php";

require_once "../../controllers/sales.controller.php";
require_once "../../models/sales.model.php";
require_once "../../controllers/customers.controller.php";
require_once "../../models/customers.model.php";
require_once "../../controllers/users.controller.php";
require_once "../../models/users.model.php";

$report = new ControllerSales();
$report -> ctrDownloadReport();
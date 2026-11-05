<?php

session_start();

if(!isset($_SESSION["loggedIn"]) || $_SESSION["loggedIn"] != "ok"){

	http_response_code(403);
	exit("Please log in first.");

}

require_once "../../../controllers/sales.controller.php";
require_once "../../../models/sales.model.php";

require_once "../../../controllers/customers.controller.php";
require_once "../../../models/customers.model.php";

require_once "../../../controllers/users.controller.php";
require_once "../../../models/users.model.php";

require_once "../../../controllers/settings.controller.php";

class printBill{

public $code;
public $format = null;

public function getBillPrinting(){

	$sale = ControllerSales::ctrShowSales("code", $this->code);

	if(!$sale){

		exit("Sale not found.");

	}

	$customer = ControllerCustomers::ctrShowCustomers("id", $sale["idCustomer"]);
	$seller = ControllerUsers::ctrShowUsers("id", $sale["idSeller"]);
	$s = ControllerSettings::all();

	$format = $this->format ? $this->format : $s["receiptFormat"];

	$e = function($text){ return htmlspecialchars((string)$text, ENT_QUOTES, "UTF-8"); };
	$money = function($amount){ return htmlspecialchars(ControllerSettings::money($amount), ENT_QUOTES, "UTF-8"); };

	$products = json_decode($sale["products"], true);

	$taxLabel = $e($s["taxName"])." (".(float)$s["taxRate"]."%".($s["taxInclusive"] ? " incl." : "").")";

	/*=============================================
	BUSINESS HEADER
	=============================================*/

	$headerLines = "<b>".$e($s["businessName"])."</b>";

	if(trim($s["address"]) !== "") $headerLines .= "<br>".$e($s["address"]);
	if(trim($s["phone"]) !== "") $headerLines .= "<br>Tel: ".$e($s["phone"]);
	if(trim($s["email"]) !== "") $headerLines .= "<br>".$e($s["email"]);
	if(trim($s["taxId"]) !== "") $headerLines .= "<br>PIN: ".$e($s["taxId"]);

	$logoPath = ($s["logo"] != "") ? realpath(__DIR__."/../../../".$s["logo"]) : false;

	$footer = "";

	if(trim($s["mpesaTill"]) !== "") $footer .= "M-Pesa: ".$e($s["mpesaTill"])."<br>";
	if(trim($s["receiptFooter"]) !== "") $footer .= nl2br($e($s["receiptFooter"]));

	require_once('tcpdf_include.php');

	if($format == "a4"){

		/*=============================================
		A4 INVOICE
		=============================================*/

		$pdf = new TCPDF("P", "mm", "A4", true, "UTF-8", false);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetMargins(15, 15, 15);
		$pdf->AddPage();

		if($logoPath){

			$pdf->Image($logoPath, 15, 15, 0, 22);
			$pdf->SetY(40);

		}

		$html = '
		<table cellpadding="2" style="font-size:10px">
			<tr>
				<td style="width:300px">'.$headerLines.'</td>
				<td style="width:210px; text-align:right">
					<span style="font-size:16px"><b>INVOICE</b></span><br>
					No. '.$e($sale["code"]).'<br>
					Date: '.$e($sale["saledate"]).'
				</td>
			</tr>
		</table>
		<br><br>
		<table cellpadding="2" style="font-size:10px">
			<tr>
				<td style="width:255px"><b>Customer</b><br>'.$e($customer["name"]).
					($customer["phone"] ? '<br>'.$e($customer["phone"]) : '').
					($customer["email"] ? '<br>'.$e($customer["email"]) : '').'</td>
				<td style="width:255px; text-align:right"><b>Served by</b><br>'.$e($seller["name"]).'</td>
			</tr>
		</table>
		<br><br>
		<table cellpadding="5" style="font-size:10px">
			<tr style="background-color:#eeeeee">
				<td style="width:250px"><b>Product</b></td>
				<td style="width:60px; text-align:center"><b>Qty</b></td>
				<td style="width:100px; text-align:right"><b>Unit price</b></td>
				<td style="width:100px; text-align:right"><b>Amount</b></td>
			</tr>';

		foreach($products as $item){

			$html .= '
			<tr>
				<td style="border-bottom:1px solid #dddddd">'.$e($item["description"]).'</td>
				<td style="border-bottom:1px solid #dddddd; text-align:center">'.$e($item["quantity"]).'</td>
				<td style="border-bottom:1px solid #dddddd; text-align:right">'.$money($item["price"]).'</td>
				<td style="border-bottom:1px solid #dddddd; text-align:right">'.$money($item["totalPrice"]).'</td>
			</tr>';

		}

		$html .= '
		</table>
		<br><br>
		<table cellpadding="4" style="font-size:10px">
			<tr><td style="width:350px; text-align:right">Net amount</td><td style="width:160px; text-align:right">'.$money($sale["netPrice"]).'</td></tr>
			<tr><td style="width:350px; text-align:right">'.$taxLabel.'</td><td style="width:160px; text-align:right">'.$money($sale["tax"]).'</td></tr>
			<tr style="background-color:#eeeeee"><td style="width:350px; text-align:right"><b>TOTAL</b></td><td style="width:160px; text-align:right"><b>'.$money($sale["totalPrice"]).'</b></td></tr>
			<tr><td style="width:350px; text-align:right">Paid by</td><td style="width:160px; text-align:right">'.$e(ControllerSettings::paymentLabel($sale["paymentMethod"])).'</td></tr>
		</table>
		<br><br><br>
		<div style="font-size:10px; text-align:center">'.$footer.'</div>';

		$pdf->writeHTML($html, false, false, false, false, '');

	}else{

		/*=============================================
		TILL-ROLL TICKET (80mm wide)
		=============================================*/

		$height = 110 + count($products) * 9 + ($logoPath ? 20 : 0);

		$pdf = new TCPDF("P", "mm", array(80, $height), true, "UTF-8", false);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetMargins(4, 4, 4);
		$pdf->SetAutoPageBreak(true, 4);
		$pdf->AddPage();

		if($logoPath){

			$pdf->Image($logoPath, 25, 4, 30, 0, '', '', 'N', false, 300, 'C');

		}

		$html = '
		<div style="font-size:8px; text-align:center">'.$headerLines.'<br><br>
			Receipt No. '.$e($sale["code"]).'<br>'.$e($sale["saledate"]).'
		</div>
		<div style="font-size:8px">
			Customer: '.$e($customer["name"]).'<br>
			Served by: '.$e($seller["name"]).'<br>
			------------------------------------------------------------
		</div>
		<table style="font-size:8px">';

		foreach($products as $item){

			$html .= '
			<tr><td style="width:205px">'.$e($item["description"]).'</td></tr>
			<tr><td style="width:205px; text-align:right">'.$e($item["quantity"]).' x '.number_format((float)$item["price"], 2).' = '.number_format((float)$item["totalPrice"], 2).'</td></tr>';

		}

		$html .= '
		</table>
		<div style="font-size:8px">------------------------------------------------------------</div>
		<table style="font-size:8px">
			<tr><td style="width:105px">Net</td><td style="width:100px; text-align:right">'.$money($sale["netPrice"]).'</td></tr>
			<tr><td style="width:105px">'.$taxLabel.'</td><td style="width:100px; text-align:right">'.$money($sale["tax"]).'</td></tr>
			<tr><td style="width:105px; font-size:10px"><b>TOTAL</b></td><td style="width:100px; text-align:right; font-size:10px"><b>'.$money($sale["totalPrice"]).'</b></td></tr>
			<tr><td style="width:105px">Paid by</td><td style="width:100px; text-align:right">'.$e(ControllerSettings::paymentLabel($sale["paymentMethod"])).'</td></tr>
		</table>
		<br>
		<div style="font-size:8px; text-align:center">'.$footer.'</div>';

		$pdf->writeHTML($html, false, false, false, false, '');

	}

	$pdf->Output('receipt-'.$sale["code"].'.pdf');

}

}

$bill = new printBill();
$bill -> code = isset($_GET["code"]) ? $_GET["code"] : 0;
$bill -> format = (isset($_GET["format"]) && in_array($_GET["format"], array("a4", "ticket"))) ? $_GET["format"] : null;
$bill -> getBillPrinting();

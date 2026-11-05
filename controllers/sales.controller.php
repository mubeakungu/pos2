<?php

use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;


class ControllerSales{
	/*=============================================
	SHOW SALES
	=============================================*/

	static public function ctrShowSales($item, $value){

		$table = "sales";

		$answer = ModelSales::mdlShowSales($table, $item, $value);

		return $answer;

	}

	/*=============================================
	CREATE SALE
	=============================================*/

	static public function ctrCreateSale(){

		if(isset($_POST["newSale"])){

			if(empty($_POST["productsList"]) || empty($_POST["listPaymentMethod"]) || !is_array(json_decode($_POST["productsList"], true))){

				echo '<script>
					swal({
						type: "error",
						title: "Add at least one product and choose how the customer paid",
						showConfirmButton: true,
						confirmButtonText: "Close"
					});
				</script>';

				return;

			}

			/*=============================================
			UPDATE CUSTOMER'S PURCHASES AND REDUCE THE STOCK AND INCREASE SALES OF THE PRODUCT
			=============================================*/

			$productsList = json_decode($_POST["productsList"], true);

			$totalPurchasedProducts = array();

			foreach ($productsList as $key => $value) {

			   array_push($totalPurchasedProducts, $value["quantity"]);
				
			   $tableProducts = "products";

			    $item = "id";
			    $valueProductId = $value["id"];
			    $order = "id";

			    $getProduct = ProductsModel::mdlShowProducts($tableProducts, $item, $valueProductId, $order);

				$item1a = "sales";
				$value1a = $value["quantity"] + $getProduct["sales"];

			    $newSales = ProductsModel::mdlUpdateProduct($tableProducts, $item1a, $value1a, $valueProductId);

				$item1b = "stock";
				$value1b = $value["stock"];

				$newStock = ProductsModel::mdlUpdateProduct($tableProducts, $item1b, $value1b, $valueProductId);

			}

			$tableCustomers = "customers";

			$item = "id";
			$valueCustomer = $_POST["selectCustomer"];

			$getCustomer = ModelCustomers::mdlShowCustomers($tableCustomers, $item, $valueCustomer);

			$item1a = "purchases";
			$value1a = array_sum($totalPurchasedProducts) + $getCustomer["purchases"];

			$customerPurchases = ModelCustomers::mdlUpdateCustomer($tableCustomers, $item1a, $value1a, $valueCustomer);

			$item1b = "lastPurchase";


			$date = date('Y-m-d');
			$hour = date('H:i:s');
			$value1b = $date.' '.$hour;

			$dateCustomer = ModelCustomers::mdlUpdateCustomer($tableCustomers, $item1b, $value1b, $valueCustomer);
			/*=============================================
			SAVE THE SALE
			=============================================*/	

			$table = "sales";

			$data = array("idSeller"=>$_POST["idSeller"],
						   "idCustomer"=>$_POST["selectCustomer"],
						   "code"=>$_POST["newSale"],
						   "products"=>$_POST["productsList"],
						   "tax"=>$_POST["newTaxPrice"],
						   "netPrice"=>$_POST["newNetPrice"],
						   "totalPrice"=>$_POST["saleTotal"],
						   "paymentMethod"=>$_POST["listPaymentMethod"]);

			$answer = ModelSales::mdlAddSale($table, $data);

			if($answer == "ok"){

				/*=============================================
				PRINT ON THE THERMAL PRINTER (if enabled in Settings)
				=============================================*/

				$savedSale = ModelSales::mdlShowSales($table, "code", $_POST["newSale"]);
				$getSeller = UsersModel::MdlShowUsers("users", "id", $_POST["idSeller"]);

				$printResult = ControllerSettings::printTicket($savedSale, $getCustomer, $getSeller);

				if($printResult !== true){

					echo '<script>console.error('.json_encode("Receipt printer: ".$printResult).');</script>';

				}

				echo'<script>

				localStorage.removeItem("range");

				swal({
					  type: "success",
					  title: "The sale has been succesfully added",
					  showConfirmButton: true,
					  confirmButtonText: "Close"
					  }).then((result) => {
								if (result.value) {

								window.location = "sales";

								}
							})

				</script>';

			}

		}

	}
	/*=============================================
	EDIT SALE
	=============================================*/

	static public function ctrEditSale(){

		if(isset($_POST["editSale"])){

			/*=============================================
			FORMAT PRODUCTS AND CUSTOMERS TABLES
			=============================================*/
			$table = "sales";

			$item = "code";
			$value = $_POST["editSale"];

			$getSale = ModelSales::mdlShowSales($table, $item, $value);

			/*=============================================
			CHECK IF THERE'S ANY EDITED SALE
			=============================================*/

			if($_POST["productsList"] == ""){

				$productsList = $getSale["products"];
				$productChange = false;


			}else{

				$productsList = $_POST["productsList"];
				$productChange = true;
			}

			if($productChange){

				$products =  json_decode($getSale["products"], true);

				$totalPurchasedProducts = array();

				foreach ($products as $key => $value) {

					array_push($totalPurchasedProducts, $value["quantity"]);
					
					$tableProducts = "products";

					$item = "id";
					$value1 = $value["id"];
					$order = "id";

					$getProduct = ProductsModel::mdlShowProducts($tableProducts, $item, $value1, $order);

					$item1a = "sales";
					$value1a = $getProduct["sales"] - $value["quantity"];

					$newSales = ProductsModel::mdlUpdateProduct($tableProducts, $item1a, $value1a, $value1);

					$item1b = "stock";
					$value1b = $value["quantity"] + $getProduct["stock"];

					$stockNew = ProductsModel::mdlUpdateProduct($tableProducts, $item1b, $value1b, $value1);

				}

				$tableCustomers = "customers";

				$itemCustomer = "id";
				$valueCustomer = $_POST["selectCustomer"];

				$getCustomer = ModelCustomers::mdlShowCustomers($tableCustomers, $itemCustomer, $valueCustomer);

				$item1a = "purchases";
				$value1a = $getCustomer["purchases"] - array_sum($totalPurchasedProducts);

				$customerPurchases = ModelCustomers::mdlUpdateCustomer($tableCustomers, $item1a, $value1a, $valueCustomer);
				/*=============================================
				UPDATE THE CUSTOMER'S PURCHASES AND REDUCE THE STOCK AND INCREMENT PRODUCT SALES
				=============================================*/

				$productsList_2 = json_decode($productsList, true);

				$totalPurchasedProducts_2 = array();

				foreach ($productsList_2 as $key => $value) {

					array_push($totalPurchasedProducts_2, $value["quantity"]);
					
					$tableProducts_2 = "products";

					$item_2 = "id";
					$value_2 = $value["id"];
					$order = "id";

					$getProduct_2 = ProductsModel::mdlShowProducts($tableProducts_2, $item_2, $value_2, $order);

					$item1a_2 = "sales";
					$value1a_2 = $value["quantity"] + $getProduct_2["sales"];

					$newSales_2 = ProductsModel::mdlUpdateProduct($tableProducts_2, $item1a_2, $value1a_2, $value_2);

					$item1b_2 = "stock";
					$value1b_2 = $getProduct_2["stock"] - $value["quantity"];

					$newStock_2 = ProductsModel::mdlUpdateProduct($tableProducts_2, $item1b_2, $value1b_2, $value_2);

				}

				$tableCustomers_2 = "customers";

				$item_2 = "id";
				$value_2 = $_POST["selectCustomer"];

				$getCustomer_2 = ModelCustomers::mdlShowCustomers($tableCustomers_2, $item_2, $value_2);

				$item1a_2 = "purchases";
				$value1a_2 = array_sum($totalPurchasedProducts_2) + $getCustomer_2["purchases"];

				$customerPurchases_2 = ModelCustomers::mdlUpdateCustomer($tableCustomers_2, $item1a_2, $value1a_2, $value_2);

				$item1b_2 = "lastPurchase";


				$date = date('Y-m-d');
				$hour = date('H:i:s');
				$value1b_2 = $date.' '.$hour;

				$dateCustomer_2 = ModelCustomers::mdlUpdateCustomer($tableCustomers_2, $item1b_2, $value1b_2, $value_2);

			}
			/*=============================================
			SAVE PURCHASE CHANGES
			=============================================*/	

			$data = array("idSeller"=>$_POST["idSeller"],
						   "idCustomer"=>$_POST["selectCustomer"],
						   "code"=>$_POST["editSale"],
						   "products"=>$productsList,
						   "tax"=>$_POST["newTaxPrice"],
						   "netPrice"=>$_POST["newNetPrice"],
						   "totalPrice"=>$_POST["saleTotal"],
						   "paymentMethod"=>$_POST["listPaymentMethod"]);


			$answer = ModelSales::mdleditSale($table, $data);

			if($answer == "ok"){

				echo'<script>

				localStorage.removeItem("range");

				swal({
					  type: "success",
					  title: "The sale has been edited correctly",
					  showConfirmButton: true,
					  confirmButtonText: "Close"
					  }).then((result) => {
								if (result.value) {

								window.location = "sales";

								}
							})

				</script>';

			}

		}

	}
	/*=============================================
	Delete Sale
	=============================================*/

	static public function ctrDeleteSale(){

		if(isset($_GET["idSale"])){

			$table = "sales";

			$item = "id";
			$value = $_GET["idSale"];

			$getSale = ModelSales::mdlShowSales($table, $item, $value);

			/*=============================================
			Update last Purchase date
			=============================================*/

			$tableCustomers = "customers";

			$itemsales = null;
			$valuesales = null;

			$getSales = ModelSales::mdlShowSales($table, $itemsales, $valuesales);

			$saveDates = array();

			foreach ($getSales as $key => $value) {
				
				if($value["idCustomer"] == $getSale["idCustomer"]){

					array_push($saveDates, $value["saledate"]);

				}

			}

			if(count($saveDates) > 1){

				if($getSale["saledate"] > $saveDates[count($saveDates)-2]){

					$item = "lastPurchase";
					$value = $saveDates[count($saveDates)-2];
					$valueIdCustomer = $getSale["idCustomer"];

					$customerPurchases = ModelCustomers::mdlUpdateCustomer($tableCustomers, $item, $value, $valueIdCustomer);

				}else{

					$item = "lastPurchase";
					$value = $saveDates[count($saveDates)-1];
					$valueIdCustomer = $getSale["idCustomer"];

					$customerPurchases = ModelCustomers::mdlUpdateCustomer($tableCustomers, $item, $value, $valueIdCustomer);

				}


			}else{

				$item = "lastPurchase";
				$value = "0000-00-00 00:00:00";
				$valueIdCustomer = $getSale["idCustomer"];

				$customerPurchases = ModelCustomers::mdlUpdateCustomer($tableCustomers, $item, $value, $valueIdCustomer);

			}
			/*=============================================
			FORMAT PRODUCTS AND CUSTOMERS TABLE
			=============================================*/

			$products =  json_decode($getSale["products"], true);

			$totalPurchasedProducts = array();

			foreach ($products as $key => $value) {

				array_push($totalPurchasedProducts, $value["quantity"]);
				
				$tableProducts = "products";

				$item = "id";
				$valueProductId = $value["id"];
				$order = "id";

				$getProduct = ProductsModel::mdlShowProducts($tableProducts, $item, $valueProductId, $order);

				$item1a = "sales";
				$value1a = $getProduct["sales"] - $value["quantity"];

				$newSales = ProductsModel::mdlUpdateProduct($tableProducts, $item1a, $value1a, $valueProductId);

				$item1b = "stock";
				$value1b = $value["quantity"] + $getProduct["stock"];

				$stockNew = ProductsModel::mdlUpdateProduct($tableProducts, $item1b, $value1b, $valueProductId);

			}

			$tableCustomers = "customers";

			$itemCustomer = "id";
			$valueCustomer = $getSale["idCustomer"];

			$getCustomer = ModelCustomers::mdlShowCustomers($tableCustomers, $itemCustomer, $valueCustomer);

			$item1a = "purchases";
			$value1a = $getCustomer["purchases"] - array_sum($totalPurchasedProducts);

			$customerPurchases = ModelCustomers::mdlUpdateCustomer($tableCustomers, $item1a, $value1a, $valueCustomer);

			/*=============================================
			Delete Sale
			=============================================*/

			$answer = ModelSales::mdlDeleteSale($table, $_GET["idSale"]);

			if($answer == "ok"){

				echo'<script>

				swal({
					  type: "success",
					  title: "The sale has been deleted succesfully",
					  showConfirmButton: true,
					  confirmButtonText: "Close",
					  closeOnConfirm: false
					  }).then((result) => {
								if (result.value) {

								window.location = "sales";

								}
							})

				</script>';

			}		
		}

	}
	/*=============================================
	DATES RANGE
	=============================================*/	

	static public function ctrSalesDatesRange($initialDate, $finalDate){

		$table = "sales";

		$answer = ModelSales::mdlSalesDatesRange($table, $initialDate, $finalDate);

		return $answer;
		
	}

	/*=============================================
	DOWNLOAD EXCEL
	=============================================*/

	static public function ctrDownloadReport(){

		if(isset($_GET["report"])){

			$table = "sales";

			if(isset($_GET["initialDate"]) && isset($_GET["finalDate"])){

				$sales = ModelSales::mdlSalesDatesRange($table, $_GET["initialDate"], $_GET["finalDate"]);

			}else{

				$item = null;
				$value = null;

				$sales = ModelSales::mdlShowSales($table, $item, $value);

			}

			/*=============================================
			WE CREATE EXCEL FILE
			=============================================*/

			$name = $_GET["report"].'.xls';

			header('Expires: 0');
			header('Cache-control: private');
			header("Content-type: application/vnd.ms-excel"); // Excel file
			header("Cache-Control: cache, must-revalidate"); 
			header('Content-Description: File Transfer');
			header('Last-Modified: '.date('D, d M Y H:i:s'));
			header("Pragma: public"); 
			header('Content-Disposition:; filename="'.$name.'"');
			header("Content-Transfer-Encoding: binary");

			echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';

			echo ("<table border='0'> 

					<tr> 
					<td style='font-weight:bold; border:1px solid #eee;'>CODE</td> 
					<td style='font-weight:bold; border:1px solid #eee;'>customer</td>
					<td style='font-weight:bold; border:1px solid #eee;'>Seller</td>
					<td style='font-weight:bold; border:1px solid #eee;'>quantity</td>
					<td style='font-weight:bold; border:1px solid #eee;'>products</td>
					<td style='font-weight:bold; border:1px solid #eee;'>tax</td>
					<td style='font-weight:bold; border:1px solid #eee;'>netPrice</td>		
					<td style='font-weight:bold; border:1px solid #eee;'>TOTAL</td>		
					<td style='font-weight:bold; border:1px solid #eee;'>PAYMENT METHOD</td>
					<td style='font-weight:bold; border:1px solid #eee;'>DATE</td>		
					</tr>");

			foreach ($sales as $row => $item){

				$customer = ControllerCustomers::ctrShowCustomers("id", $item["idCustomer"]);
				$Seller = ControllerUsers::ctrShowUsers("id", $item["idSeller"]);

			 echo ("<tr>
			 			<td style='border:1px solid #eee;'>".$item["code"]."</td> 
			 			<td style='border:1px solid #eee;'>".$customer["name"]."</td>
			 			<td style='border:1px solid #eee;'>".$Seller["name"]."</td>
			 			<td style='border:1px solid #eee;'>");

			 	$products =  json_decode($item["products"], true);

			 	foreach ($products as $key => $valueproducts) {
			 			
			 			echo ($valueproducts["quantity"]."<br>");
			 		}

			 	echo ("</td><td style='border:1px solid #eee;'>");	

		 		foreach ($products as $key => $valueproducts) {
			 			
		 			echo ($valueproducts["description"]."<br>");
		 		
		 		}

		 		echo ("</td>
					<td style='border:1px solid #eee;'>".ControllerSettings::money($item["tax"])."</td>
					<td style='border:1px solid #eee;'>".ControllerSettings::money($item["netPrice"])."</td>	
					<td style='border:1px solid #eee;'>".ControllerSettings::money($item["totalPrice"])."</td>
					<td style='border:1px solid #eee;'>".ControllerSettings::paymentLabel($item["paymentMethod"])."</td>
					<td style='border:1px solid #eee;'>".substr($item["saledate"],0,10)."</td>		
		 			</tr>");

			}


			echo "</table>";

		}

	}

	/*=============================================
	Adding TOTAL sales
	=============================================*/

	static public function ctrAddingTotalSales(){

		$table = "sales";

		$answer = ModelSales::mdlAddingTotalSales($table);

		return $answer;

	}

	/*=============================================
	DOWNLOAD XML
	=============================================*/

	static public function ctrDownloadXML(){

		if(isset($_GET["xml"])){


			$table = "sales";
			$item = "code";
			$value = $_GET["xml"];

			$sales = ModelSales::mdlShowSales($table, $item, $value);

			// PRODUCTS

			$productsList = json_decode($sales["products"], true);

			// Customer

			$tableCustomers = "customers";
			$item = "id";
			$value = $sales["idCustomer"];

			$getCustomer = ModelCustomers::mdlShowCustomers($tableCustomers, $item, $value);

			// Seller

			$tableSeller = "users";
			$item = "id";
			$value = $sales["idSeller"];

			$getSeller = UsersModel::mdlShowUsers($tableSeller, $item, $value);

			//http://php.net/manual/es/book.xmlwriter.php

			$objectXML = new XMLWriter();

			$objectXML->openURI($_GET["xml"].".xml"); //XML file creation

			$objectXML->setIndent(true); //gets a boolean value to stablish if the different XML node leves must be indented or not.

			$objectXML->setIndentString("\t"); // caracter \t, it means to tab

			$objectXML->startDocument('1.0', 'utf-8');// document start
			
			// $objectXML->startElement("mainTag");// Beginning of root node

			// $objectXML->writeAttribute("mainAttributeTag", "value main Attribute Tag"); // main Attribute Tag

			// 	$objectXML->startElement("internalTag");// Beginning of child node

			// 		$objectXML->writeAttribute("InternalTagAttribute", "value Internal tag attribute"); // Internal tag attribute

			// 		$objectXML->text("Internal text");// Beginning of child node
			
			// 	$objectXML->endElement(); // End of child node 
			
			// $objectXML->endElement(); // End of root node


			$objectXML->writeRaw('<fe:Invoice xmlns:fe="http://www.dian.gov.co/contratos/facturaelectronica/v1" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:clm54217="urn:un:unece:uncefact:codelist:specification:54217:2001" xmlns:clm66411="urn:un:unece:uncefact:codelist:specification:66411:2001" xmlns:clmIANAMIMEMediaType="urn:un:unece:uncefact:codelist:specification:IANAMIMEMediaType:2003" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2" xmlns:qdt="urn:oasis:names:specification:ubl:schema:xsd:QualifiedDatatypes-2" xmlns:sts="http://www.dian.gov.co/contratos/facturaelectronica/v1/Structures" xmlns:udt="urn:un:unece:uncefact:data:specification:UnqualifiedDataTypesSchemaModule:2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.dian.gov.co/contratos/facturaelectronica/v1 ../xsd/DIAN_UBL.xsd urn:un:unece:uncefact:data:specification:UnqualifiedDataTypesSchemaModule:2 ../../ubl2/common/UnqualifiedDataTypeSchemaModule-2.0.xsd urn:oasis:names:specification:ubl:schema:xsd:QualifiedDatatypes-2 ../../ubl2/common/UBL-QualifiedDatatypes-2.0.xsd">');

			$objectXML->writeRaw('<ext:UBLExtensions>');

			foreach ($productsList as $key => $value) {
				
				$objectXML->text($value["description"].", ");
			
			}		

			$objectXML->writeRaw('</ext:UBLExtensions>');

			$objectXML->writeRaw('</fe:Invoice>');

			$objectXML->endDocument(); // End document

			return true;	
		}

	}

}
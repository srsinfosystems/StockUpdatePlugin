<?php
namespace StockUpdatePlugin\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Templates\Twig;
use Plenty\Plugin\Log\Loggable;

/**
 * Class ContentController
 * @package StockUpdatePlugin\Controllers
 */
class ContentController  extends Controller
{
	use Loggable;
	/**
	 * @param Twig $twig
	 * @return string
	 */

	public $access_token;
	public $plentyhost;
	public $drophost;
	public $variations;
	public $NoStockVariations;
	
	public function cgi_update_stock() {
		$host = $_SERVER['HTTP_HOST'];
		$brand = isset($_GET['brand'])?$_GET['brand']:'';
		$login = $this->login($host);
		$login = json_decode($login, true);
		$this->access_token = $login['access_token'];
		$this->plentyhost = "https://".$host;
		$this->drophost = "https://www.brandsdistribution.com";

		$this->update_stock($brand);
	}
	public function cli_update_stock() {

			$host = "joiurjeuiklb.plentymarkets-cloud02.com";
			$login = $this->login($host);
			$login = json_decode($login, true);
			$this->access_token = $login['access_token'];
			$this->plentyhost = "https://".$host;
			$this->drophost = "https://www.brandsdistribution.com";
			$this->update_stock('');


	}
	public function update_stock($brand)
	{
		$print = "n";
		if(!empty($brand)) {
			$print = "y";
			$brands[] = $brand;
		}
		else {
			$brands = $this->getBrands();
		}

		foreach($brands as $brand) {
			$this->variations = array();
			if(empty($brand)) continue;
			$manufacturerId = $this->getManufacturerId($brand);
			if(empty($manufacturerId)) continue;
			$this->getManufacturerVariations($manufacturerId,1);
			if(empty($this->variations)) continue;
			$this->NoStockVariations = $this->variations;
			if($print == "y") {
				echo json_encode($this->variations);
				echo "<br>================";
				echo json_encode($this->NoStockVariations);
			}
			# get data of selected brand from dropshiper
			$variationDrop = $this->variationDropShiper($brand);
			
			if($print == "y") {
				echo json_encode($variationDrop);
			}
			$this->updateStock($variationDrop);

			//sleep(30);

		}
		//echo "Stock Updated.";
		exit;

	}

	public function getManufacturerVariations($manufacturerId, $page) {

		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => $this->plentyhost."/rest/items/variations?manufacturerId=".$manufacturerId."&isActive=true&plentyId=42296&page=".$page,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 90000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer ".$this->access_token,
		    "cache-control: no-cache",
		    "content-type: application/json",
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		  $response =json_decode($response,true); 
		  if($print == "y") {
		  echo json_encode($response); exit;
		}
		  if(isset($response['entries']) && !empty($response['entries'])) {
			  foreach($response['entries'] as $entries) {
				$number = $entries['number'];
				$this->variations[$number] = $entries['id'];
			  }
		  }

		}
		 $last_page = $response['lastPageNumber'];
		if($page != $last_page) {
			$page++;
			$this->getManufacturerVariations($manufacturerId, $page);
		}
	}
	public function getManufacturerId($brand) {

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $this->plentyhost."/rest/items/manufacturers?name=".urlencode($brand),
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 9000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer ".$this->access_token,
		    "cache-control: no-cache",
		    "content-type: application/json",
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {

		  $response =json_decode($response,true);
		  if(!empty($response) && isset($response['entries'][0]['id']))
			return $response['entries'][0]['id'];
		  else
			return "";
		}
	}

	public function variationDropShiper($brand) {
		$checktime = strtotime("-20 mins");
		$checktime = date("c", $checktime);

		$curl = curl_init();
		curl_setopt_array($curl, array(
		 // CURLOPT_URL => $this->drophost."/restful/export/api/products.xml?Accept=application%2Fxml&tag_1=".urlencode($brand)."&since=".urlencode($checktime),
		 CURLOPT_URL => $this->drophost."/restful/export/api/products.xml?Accept=application%2Fxml&tag_1=".urlencode($brand),
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 900000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Basic MTg0Y2U4Y2YtMmM5ZC00ZGU4LWI0YjEtMmZkNjcxM2RmOGNkOlN1cmZlcjc2",
		    "cache-control: no-cache",
		    "content-type: application/xml",
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return;
		}
		else {
		$xml = simplexml_load_string($response);
		$json = json_encode($xml);
		$array = json_decode($json,TRUE);

		if(empty($array['items']['item'])) return "";
		 if (is_array($array['items']['item'])) {
				$stock = array();
		        foreach ($array['items']['item'] as $items) {
					if(isset($items['models']['model'][0]['availability'])) {
					$drop_models = $items['models']['model'];
					}
					else if(!empty($items['models']['model'])) {
						$drop_models[] = $items['models']['model'];
					}
					if(empty($drop_models)) continue;
					foreach($drop_models as $model) {
						$last_updated = $model['lastUpdate'];
						$checktime = strtotime("-20 mins");
						$checktime = date("c", $checktime);
						$id = $model['id'];
						//if($last_updated <= $checktime) {
							# find relevant variation in plenty
							if(array_key_exists($id, $this->variations)) {
								unset($this->NoStockVariations[$id]);
								$plentyId = $this->variations[$id];
								$temp = array (
									'variation_id' => $plentyId,
									'drop_id' => $id,
									'availability' => $model['availability'],

								);
								$stock[] = $temp;
							}
							else if(array_key_exists($id."_1", $this->variations)) {
								unset($this->NoStockVariations[$id."_1"]);
								$plentyId = $this->variations[$id."_1"];
								$temp = array (
									'variation_id' => $plentyId,
									'drop_id' => $id,
									'availability' => $model['availability'],

								);
								$stock[] = $temp;
							}
						//}
					}

		        } #
		        return $stock;
		  }
		}
	}

	public function updateStock($variations) {

		$correcttions['corrections'] = array();
	    foreach($variations as $variation) {
			$temp = array (
			'variationId' => $variation['variation_id'],
			'reasonId' => 301,
			'quantity' => $variation['availability'],
			'storageLocationId' => 0
			);
			array_push($correcttions['corrections'],$temp);
		}
		$stock_values =  json_encode($correcttions);


		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $this->plentyhost."/rest/stockmanagement/warehouses/104/stock/correction",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 90000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "PUT",
		  CURLOPT_POSTFIELDS => "$stock_values",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer ".$this->access_token,
		    "cache-control: no-cache",
		    "content-type: application/json",
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		  //echo $response;
		  //echo "Updated successfully";
		}
	}

	public function login($host){


		if(empty($host))
			$url = "/rest/login";
		else
			$url = "https://".$host."/rest/login";

		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => $url,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 90000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "username=API-USER&password=%5BnWu%3Bx%3E8Eny%3BbSs%40",
		  CURLOPT_HTTPHEADER => array(
		    "cache-control: no-cache",
		    "content-type: application/x-www-form-urlencoded",
		    "postman-token: 49a8d541-073c-8569-b3c3-76319f67e552"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  return $response;
		}
	}

	public function getBrands() {

	  $curl = curl_init();

	  curl_setopt_array($curl, array(
	  CURLOPT_URL => "https://raw.githubusercontent.com/srsinfosystems/nunobrands/master/brands.txt?".time(),
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 9000000,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "GET",
	  CURLOPT_HTTPHEADER => array(
		"cache-control: no-cache",
		"content-type: application/json",
	  ),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
	  echo "cURL Error #:" . $err;
	} else {

	 $response = explode("\n", $response);
	 return $response;
	}

		/*
		$brands = array('Adidas','Bikkembergs','Coach','Converse','Desigual','Diadora','Diadora Heritage','Diesel','Emporio Armani','Gant','Geographical Norway','Geox','Guess','Hugo Boss','Lacoste','Love Moschino','Michael Kors','Napapijri','New Balance','Nike','Ocean Sunglasses','Puma','Ralph Lauren','Ray-Ban','Saucony','Superga','TOMS','The North Face','Timberland','Tommy Hilfiger','U.S. Polo','Vans','Versace Jeans');
		$brands = array('Adidas');
		return $brands;
		*/

	}

}

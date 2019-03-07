<?php
namespace StockUpdatePlugin\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Templates\Twig;


/**
 * Class ContentController
 * @package StockUpdatePlugin\Controllers
 */
class ContentController extends Controller
{
	/**
	 * @param Twig $twig
	 * @return string
	 */

	public $access_token;
	public $plentyhost;
	public $drophost;

	public function update_stock()
	{

		$host = $_SERVER['HTTP_HOST'];
		$login = $this->login($host);
		$login = json_decode($login, true);
		$this->access_token = $login['access_token'];
		$this->plentyhost = "https://".$host;
		$this->drophost = "https://www.brandsdistribution.com";

		$brands = array('Adidas','Bikkembergs','Converse','Desigual','Diadora','Diadora Heritage','Diesel','Elle Sport','Emporio Armani','Gant','Geographical Norway','Geox','Guess','Hugo Boss','Lacoste','Love Moschino','Moschino','Napapijri','New Balance','Nike','Ocean Sunglasses','Puma','Ralph Lauren','Ray-Ban','Saucony','Superga','TOMS','The North Face','Timberland','Tommy Hilfiger','U.S. Polo','Vans','Versace Jeans');

		foreach($brands as $brand) {
			$manufacturerId = $this->getManufacturerId($brand);
			if(empty($manufacturerId)) continue;
			$variations = $this->getManufacturerVariations($manufacturerId);
			if(empty($variations)) continue;

			# get data of selected brand from dropshiper
			$variationDrop = $this->variationDropShiper($brand, $variations);
			$this->updateStock($variationDrop);


		}
		//echo "Stock Updated.";
		exit;

	}

	public function getManufacturerVariations($manufacturerId) {

		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => $this->plentyhost."/rest/items/variations?manufacturerId=".$manufacturerId."&isActive=true",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
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
		  if(empty($response) || empty($response['entries'])) return;
		  $variations = array();
		  foreach($response['entries'] as $entries) {
			  $number = $entries['number'];
			$variations[$number] = $entries['id'];
		  }
		  return $variations;
		}
	}
	public function getManufacturerId($brand) {

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $this->plentyhost."/rest/items/manufacturers?name=".$brand,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
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

	public function variationDropShiper($brand,$plentyVariations) {

		$curl = curl_init();
		curl_setopt_array($curl, array(
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
						if($last_updated >= $checktime) {
							# find relevant variation in plenty
							if(array_key_exists($id, $plentyVariations)) {
								$plentyId = $plentyVariations[$id];
								$temp = array (
									'variation_id' => $plentyId,
									'drop_id' => $id,
									'availability' => $model['availability'],

								);
								$stock[] = $temp;
							}
						}
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
		  CURLOPT_TIMEOUT => 30,
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

		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/login",
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

}

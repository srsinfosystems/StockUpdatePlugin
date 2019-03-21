<?php
namespace StockUpdatePlugin\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Templates\Twig;
use Plenty\Plugin\Log\Loggable;

/**
 * Class ContentController
 * @package StockUpdatePlugin\Controllers
 */
class ContentController extends Controller
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

	public function cgi_update_stock() {
		$host = $_SERVER['HTTP_HOST'];

		$login = $this->login($host);
		$login = json_decode($login, true);
		$this->access_token = $login['access_token'];
		$this->plentyhost = "https://".$host;
		$this->drophost = "https://www.brandsdistribution.com";

		$this->update_stock('Adidas');
	}
	public function cli_update_stock() {
			exit;
			$host = "joiurjeuiklb.plentymarkets-cloud02.com";
			$login = $this->login($host);
			$login = json_decode($login, true);
			$this->access_token = $login['access_token'];
			$this->plentyhost = "https://".$host;
			$this->drophost = "https://www.brandsdistribution.com";
			$this->update_stock();


	}
	public function update_stock($brand = "")
	{
		if(!empty($brand)) {
			$this->variationDropShiper($brand);
		}
		else {
		$brands = $this->getBrands();
		foreach($brands as $brand) {
			
			$this->variationDropShiper($brand);

		}
	}
		exit;

	}
	public function variationDropShiper($brand) {
		$checktime = strtotime("-30 mins");
		$checktime = date("c", $checktime);

		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => $this->drophost."/restful/export/api/products.xml?Accept=application%2Fxml&acceptedlocales=en_US&tag_1=".urlencode($brand),
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
					$code = $items['code'];
					$description = html_entity_decode($items['description']);
					$plentyItems = $this->getPlentyItem($code);
					if(empty($plentyItems)) continue;
					$this->ItemDiscription($plentyItems['item_id'], $plentyItems['variationId'], $discription);
		        } #
		        
		  }
		}
	}
	
	public function getPlentyItem($name) {
		$curl = curl_init();

	  curl_setopt_array($curl, array(
	  CURLOPT_URL => $this->plentyhost."/rest/items/?name=".urlencode($name),
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
	  echo $response;
	  $response =json_decode($response,true);
	  if(!empty($response) && isset($response['entries'][0]['id'])) {
		  return array('item_id' => $response['entries'][0]['id'], 'variationId' => $response['entries'][0]['mainVariationId']);
	  }
	  else {
		  return "";
	  }
		
	}
	}
	public function ItemDiscription($itemId, $variationId, $discription){

	    $curl = curl_init();

	    curl_setopt_array($curl, array(
	      CURLOPT_URL => $this->plentyhost."/rest/items/".$itemId."/variations/".$variationId."/descriptions/en",
	      CURLOPT_RETURNTRANSFER => true,
	      CURLOPT_ENCODING => "",
	      CURLOPT_MAXREDIRS => 10,
	      CURLOPT_TIMEOUT => 900000000,
	      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	      CURLOPT_CUSTOMREQUEST => "PUT",
	      CURLOPT_POSTFIELDS => "{\"itemId\": $itemId,\"lang\": \"en\",\"description\": \"$discription\"}",
	      CURLOPT_HTTPHEADER => array(
	        "authorization: Bearer ".$this->access_token,
	        "cache-control: no-cache",
	        "content-type: application/json"
	      ),
	    ));

	    $response = curl_exec($curl);
	    $err = curl_error($curl);

	    curl_close($curl);

	    if ($err) {
	      return "cURL Error #:" . $err;
	    } else {
			echo $response;
	      return $response;
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
	  CURLOPT_TIMEOUT => 30,
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

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
	public function home(Twig $twig):string
	{	
		
		$pageNo = 1;
		$records = array();
		$response = $this->updateStock();
		$array = json_decode($response,TRUE); 
		$pageNo = $array['page'] + 1;
		$lastPageNumber = $array['lastPageNumber'];
		$isLastPage = $array['isLastPage'];
		$records = $array['entries'];
		return $twig->render('StockUpdatePlugin::content.stockManagement',array('data' => $array));
		

	}
	public function updateStock($pageNo=null){
		$login = $this->login();
		$login = json_decode($login, true);
		$access_token = $login['access_token'];
		$curl = curl_init();
		if (!empty($pageNo)) {
			$pageNoString = "page=".$pageNo."&";
		}else{
			$pageNoString = '';
		}
		$host = $_SERVER['HTTP_HOST'];
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/stockmanagement/stock?".$pageNoString."warehouseId=104",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 90000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer ".$access_token,
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
		  return $response;
		}
	}

	public function login(){
		$host = $_SERVER['HTTP_HOST'];
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
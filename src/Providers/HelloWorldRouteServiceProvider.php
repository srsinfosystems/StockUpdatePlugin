<?php
namespace StockUpdatePlugin\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;

/**
 * Class StockUpdatePluginRouteServiceProvider
 * @package StockUpdatePlugin\Providers
 */
class StockUpdatePluginRouteServiceProvider extends RouteServiceProvider
{
	/**
	 * @param Router $router
	 */
	public function map(Router $router)
	{
		$router->get('home', 'StockUpdatePlugin\Controllers\ContentController@home');

		$router->get('stockManagement', 'StockUpdatePlugin\Controllers\ContentController@stockManagement');
	}

}

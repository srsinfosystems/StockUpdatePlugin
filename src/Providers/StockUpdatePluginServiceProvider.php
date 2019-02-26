<?php
namespace StockUpdatePlugin\Providers;

use Plenty\Plugin\ServiceProvider;

/**
 * Class StockUpdatePluginServiceProvider
 * @package StockUpdatePlugin\Providers
 */
class StockUpdatePluginServiceProvider extends ServiceProvider
{

	/**
	 * Register the service provider.
	 */
	public function register()
	{
		$this->getApplication()->register(StockUpdatePluginRouteServiceProvider::class);
	}
}

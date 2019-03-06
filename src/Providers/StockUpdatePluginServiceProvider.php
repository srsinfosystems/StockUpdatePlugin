<?php
namespace StockUpdatePlugin\Providers;

use Plenty\Plugin\ServiceProvider;
use Plenty\Modules\Cron\Services\CronContainer;
use StockUpdatePlugin\Crons\StockUpdateCron;

/**
 * Class StockUpdatePluginServiceProvider
 * @package StockUpdatePlugin\Providers
 */
class StockUpdatePluginServiceProvider extends ServiceProvider
{
	public function boot(CronContainer $container) {
		$container->add(CronContainer::EVERY_FIFTEEN_MINUTES, StockUpdateCron::class);
	}

}

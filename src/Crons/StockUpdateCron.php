<?php
namespace StockUpdatePlugin\Crons;
use Plenty\Modules\Cron\Contracts\CronHandler as Cron;

use StockUpdatePlugin\Controllers\ContentController;
use Plenty\Plugin\Log\Loggable;

class StockUpdateCron extends Cron {
	use Loggable;
	public function handle(ContentController $contentController) {
		$contentController->cli_update_stock();
		//App::call('StockUpdatePlugin\Controllers\ContentController@update_stock');

	}
}

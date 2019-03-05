<?php
namespace StockUpdatePlugin\Crons;
use Plenty\Modules\Cron\Contracts\CronHandler as Cron;

use StockUpdatePlugin\Controllers\ContentController;
use Plenty\Plugin\Log\Loggable;

class StockUpdateCron extends Cron {

	private $contentController;
	public function handle(ContentController $contentController) {
		$this->contentController->update_stock();
		//App::call('StockUpdatePlugin\Controllers\ContentController@update_stock');

	}
}

<?php
namespace StockUpdatePlugin\Crons;
use Plenty\Modules\Cron\Contracts\CronHandler as Cron;

use StockUpdatePlugin\Controllers\ContentController;
use Plenty\Plugin\Log\Loggable;

class StockUpdateCron extends Cron {

	public function __construct(ContentController $contentController)
	{
		$this->contentController = $contentController;
	}
	public function handle() {
		$this->contentController->update_stock();
	}
}

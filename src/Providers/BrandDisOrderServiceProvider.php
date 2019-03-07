<?php
namespace BrandDisOrder\Providers;

use Plenty\Plugin\ServiceProvider;
use Plenty\Modules\EventProcedures\Services\EventProceduresService;
use Plenty\Modules\EventProcedures\Services\Entries\ProcedureEntry;
use BrandDisOrder\EventProcedures\Procedures;


/**
 * Class StockUpdatePluginServiceProvider
 * @package StockUpdatePlugin\Providers
 */
class BrandDisOrderServiceProvider extends ServiceProvider
{
	public function boot(EventProceduresService $eventProceduresService) {
		$eventProceduresService->registerProcedure(
            'SaveOrder',
            ProcedureEntry::EVENT_TYPE_ORDER,
            ['de' => 'SaveOrder', 'en' => 'SaveOrder'],
            'BrandDisOrder\\EventProcedures\\Procedures\\SaveOrder@saveOrder'
        );


	}


}

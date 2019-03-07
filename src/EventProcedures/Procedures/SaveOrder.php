<?php
namespace BrandDisOrder\EventProcedures\Procedures;

use Plenty\Modules\EventProcedures\Events\EventProceduresTriggered;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Models\Legacy\Order;
use BrandDisOrder\Controllers\ContentController;
use Plenty\Plugin\Log\Loggable;

class SaveOrder
{
    use Loggable;
    /**
     * @param EventProceduresTriggered $event
     * @return void
     */


    public function saveOrder(EventProceduresTriggered $event, ContentController $contentController)
    {
        $order = $event->getOrder();
        $contentController->saveOrderMap($order->id);

    }



}

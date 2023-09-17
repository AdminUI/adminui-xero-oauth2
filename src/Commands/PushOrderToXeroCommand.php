<?php

namespace AdminUI\AdminUIXero\Commands;

use Illuminate\Console\Command;
use AdminUI\AdminUI\Models\Order;
use AdminUI\AdminUIXero\Models\XeroToken;
use AdminUI\AdminUI\Events\Public\NewOrder;
use AdminUI\AdminUIXero\Facades\XeroContact;
use AdminUI\AdminUIXero\Listeners\SendOrderToXero;

class PushOrderToXeroCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adminui:xero-push-order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push an individual order to Xero';

    public function handle()
    {
        $orderId = $this->ask('Please enter the order ID to push to Xero');

        $order = Order::find($orderId);


        if (!empty($order)) {
            $event = new NewOrder($order);
            SendOrderToXero::dispatchSync($event);
            $this->info('Order has been pushed to Xero');
        } else {
            $this->error('No order found matching the ID ' . $orderId);
        }
    }
}

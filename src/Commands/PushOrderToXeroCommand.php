<?php

namespace AdminUI\AdminUIXero\Commands;

use Illuminate\Console\Command;
use AdminUI\AdminUI\Models\Order;
use AdminUI\AdminUI\Events\Public\OrderPlaced;
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
            try {
                app(SendOrderToXero::class)->handle(new OrderPlaced($order));
                if (! $order->integrations()->type('xero')->whereNotNull('processed_at')->exists()) {
                    $this->warn('Order was not pushed. Check that order syncing is enabled, Xero is connected and the order has an account');
                    return Command::FAILURE;
                }
                $this->info('Order has been pushed to Xero');
            } catch (\Throwable $e) {
                report($e);
                $this->error('Order ' . $order->id . ' failed to push to Xero: ' . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            $this->error('No order found matching the ID ' . $orderId);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

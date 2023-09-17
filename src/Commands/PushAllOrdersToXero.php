<?php

namespace AdminUI\AdminUIXero\Commands;

use Illuminate\Console\Command;
use AdminUI\AdminUI\Models\Order;
use AdminUI\AdminUI\Traits\CliTrait;
use AdminUI\AdminUI\Events\Public\NewOrder;
use AdminUI\AdminUIXero\Listeners\SendOrderToXero;
use AdminUI\AdminUIXero\Services\XeroContactService;
use AdminUI\AdminUIXero\Services\XeroInvoiceService;
use AdminUI\AdminUIXero\Services\XeroPaymentService;


class PushAllOrdersToXero extends Command
{
    use CliTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adminui:xero-push-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push unprocessed orders to Xero';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        set_time_limit(900);

        $orders = Order::whereNull('processed_at')
            ->whereNotIn('order_status_id', [9, 17])
            ->get();

        $started = microtime(true);
        $this->cliInfo('Pushing ' . $orders->count() . ' orders to XERO, Please wait...');
        $this->cliStart();

        $this->cliProgressStart($orders->count());

        foreach ($orders as $order) {
            $event = new NewOrder($order);
            if (!$order->account) {
                $this->cliInfo('No account for order:' . $order->id);
                continue;
            }

            SendOrderToXero::dispatchSync($event);
            $this->info('Order has been pushed to Xero');

            $this->cliProgress();
            sleep(1);
        }
        $this->cliFinish('All done.');
        $this->cliInfo('Finished');

        return Command::SUCCESS;
    }
}

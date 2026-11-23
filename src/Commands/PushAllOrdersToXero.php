<?php

namespace AdminUI\AdminUIXero\Commands;

use Illuminate\Console\Command;
use AdminUI\AdminUI\Models\Order;
use AdminUI\AdminUI\Traits\CliTrait;
use AdminUI\AdminUI\Events\Public\OrderPlaced;
use AdminUI\AdminUIXero\Listeners\SendOrderToXero;


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
            if (!$order->account) {
                $this->cliInfo('No account for order:' . $order->id);
                $this->cliProgress();
                continue;
            }

            try {
                app(SendOrderToXero::class)->handle(new OrderPlaced($order));
                if ($order->integrations()->type('xero')->whereNotNull('processed_at')->exists()) {
                    $this->info('Order ' . $order->id . ' has been pushed to Xero');
                } else {
                    $this->warn('Order ' . $order->id . ' was not pushed. Check that order syncing is enabled and Xero is connected');
                }
            } catch (\Throwable $e) {
                report($e);
                $this->error('Order ' . $order->id . ' failed: ' . $e->getMessage());
            }

            $this->cliProgress();
            sleep(1);
        }
        $this->cliFinish('All done.');
        $this->cliInfo('Finished');

        return Command::SUCCESS;
    }
}

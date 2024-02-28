<?php

namespace AdminUI\AdminUIXero\Controllers;

use Illuminate\Http\Request;
use AdminUI\AdminUI\Models\Order;
use AdminUI\AdminUI\Facades\Flash;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use AdminUI\AdminUI\Events\Public\OrderCreated;
use AdminUI\AdminUI\Traits\ApiResponseTrait;
use AdminUI\AdminUIXero\Listeners\SendOrderToXero;


class XeroOrdersController extends Controller
{
    use ApiResponseTrait;

    public function search(Request $request)
    {
        $validated = $request->validate([
            'date' => ['nullable', 'array'],
            'statuses' => ['nullable', 'array']
        ]);

        $results = Order::with('lines', 'account', 'user')->whereNull('processed_at')->when(!empty($validated['date']), function ($query) use ($validated) {
            $query->whereBetween('created_at', [$validated['date_range'][0], ($validated['date_range'][1] ?? date('Y-m-d'))]);
        })->when(!empty($validated['statuses']), function ($query) use ($validated) {
            $query->whereIn('order_status_id', $validated['statuses']);
        })->orderBy('created_at', 'DESC')->get();

        return $this->respondWithData($results);
    }

    public function sync(Request $request)
    {
        $validated = $request->validate([
            'orders' => ['nullable', 'array'],
            'orders.*' => ['required', 'integer', 'exists:orders,id']
        ]);

        $count = 0;
        foreach ($validated['orders'] as $orderId) {
            $order = Order::find($orderId);
            if (!$order) continue;

            $event = new OrderCreated($order);
            SendOrderToXero::dispatch($event);
            $count++;
        }

        Flash::success($count . ' orders have been queued to sync with Xero', 'Sync Instruction Received');

        return back();
    }

    public function retry(Request $request)
    {
        $validated = $request->validate([
            'selected' => ['required', 'array'],
            'selected.*' => ['required', 'string']
        ]);

        $count = 0;
        foreach ($validated['selected'] as $jobId) {
            if (!$jobId) continue;
            Artisan::call('queue:retry ' . $jobId);
            $count++;
        }

        Cache::forget('failed_order_syncs');
        Flash::success($count . ' jobs were successfully put back on the queue', 'Jobs Requeued');

        return back();
    }
}

<?php

namespace AdminUI\AdminUIXero\Controllers;

use AdminUI\AdminUI\Events\Public\OrderPlaced;
use AdminUI\AdminUI\Facades\Flash;
use AdminUI\AdminUI\Models\Order;
use AdminUI\AdminUI\Resources\Admin\OrderTableResource;
use AdminUI\AdminUI\Traits\ApiResponseTrait;
use AdminUI\AdminUIXero\Helpers\FailedJobs;
use AdminUI\AdminUIXero\Listeners\SendOrderToXero;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class XeroOrdersController extends Controller
{
    use ApiResponseTrait;

    public function search(Request $request)
    {
        $validated = $request->validate([
            'date' => ['nullable', 'array'],
            'statuses' => ['nullable', 'array'],
        ]);

        $results = Order::with(
            'lines',
            'lines.orderable',
            'account',
            'user'
        )->whereNull('processed_at')
            ->when(! empty($validated['date']), function ($query) use ($validated) {
                $query->whereBetween('created_at', [$validated['date'][0], ($validated['date'][1] ?? date('Y-m-d'))]);
            })->when(! empty($validated['statuses']), function ($query) use ($validated) {
                $query->whereIn('order_status_id', $validated['statuses']);
            })->orderBy('created_at', 'DESC')->get();

        return $this->respondWithResourceCollection(OrderTableResource::collection($results));
    }

    public function sync(Request $request)
    {
        $validated = $request->validate([
            'orders' => ['nullable', 'array'],
            'orders.*' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $count = 0;
        foreach ($validated['orders'] ?? [] as $orderId) {
            $order = Order::find($orderId);
            if (! $order) {
                continue;
            }

            SendOrderToXero::queue(new OrderPlaced($order));
            $count++;
        }

        Flash::success($count . ' orders have been queued to sync with Xero', 'Sync Instruction Received');

        return back();
    }

    public function syncSynchronous(Request $request)
    {
        $validated = $request->validate([
            'orders' => ['nullable', 'array'],
            'orders.*' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $log = collect();
        foreach ($validated['orders'] ?? [] as $orderId) {
            $order = Order::find($orderId);
            if (! $order) {
                continue;
            }

            try {
                app(SendOrderToXero::class)->handle(new OrderPlaced($order));
                $integration = $order->integrations()->type('xero')->first();
                $log->push([
                    'order_id' => $order->id,
                    'success' => ! empty($integration?->processed_at),
                    'message' => ! empty($integration?->processed_at)
                        ? 'Synced to Xero invoice ' . $integration->process_id
                        : 'Not synced, check the Xero settings and that the order has an account',
                ]);
            } catch (\Throwable $e) {
                report($e);
                $log->push([
                    'order_id' => $order->id,
                    'success' => false,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $synced = $log->where('success', true)->count();
        $failed = $log->count() - $synced;
        if ($failed > 0) {
            Flash::error($synced . ' orders synced, ' . $failed . ' failed. ' . $log->firstWhere('success', false)['message'], 'Order Sync Failed');
        } else {
            Flash::success($synced . ' orders have been synced with Xero', 'Order Synced');
        }

        return $this->respondWithData([
            'log' => $log->values(),
        ]);
    }

    public function retry(Request $request)
    {
        $validated = $request->validate([
            'selected' => ['required', 'array'],
            'selected.*' => ['required', 'string'],
        ]);

        $count = 0;
        foreach ($validated['selected'] as $jobId) {
            if (! $jobId) {
                continue;
            }
            Artisan::call('queue:retry', ['id' => [$jobId]]);
            $count++;
        }

        $cacheKey = FailedJobs::getCacheKey(SendOrderToXero::class);
        Cache::forget($cacheKey);
        Flash::success($count . ' jobs were successfully put back on the queue', 'Jobs Requeued');

        return back();
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'selected' => ['required', 'array'],
            'selected.*' => ['required', 'string'],
        ]);

        $count = 0;
        foreach ($validated['selected'] as $jobId) {
            if (! $jobId) {
                continue;
            }
            Artisan::call('queue:forget', ['id' => $jobId]);
            $count++;
        }

        $cacheKey = FailedJobs::getCacheKey(SendOrderToXero::class);
        Cache::forget($cacheKey);
        Flash::success($count . ' jobs were successfully deleted from the queue', 'Jobs Deleted');

        return back();
    }
}

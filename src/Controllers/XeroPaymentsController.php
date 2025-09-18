<?php

namespace AdminUI\AdminUIXero\Controllers;

use Illuminate\Http\Request;
use AdminUI\AdminUI\Models\Order;
use AdminUI\AdminUI\Facades\Flash;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use AdminUI\AdminUIXero\Helpers\FailedJobs;
use AdminUI\AdminUI\Traits\ApiResponseTrait;
use AdminUI\AdminUIXero\Facades\XeroContact;
use AdminUI\AdminUI\Events\Public\OrderCreated;
use AdminUI\AdminUIXero\Listeners\SendOrderToXero;
use AdminUI\AdminUIXero\Listeners\SendPaymentToXero;
use AdminUI\AdminUI\Resources\Admin\OrderTableResource;


class XeroPaymentsController extends Controller
{
    use ApiResponseTrait;

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

        $cacheKey = FailedJobs::getCacheKey(SendPaymentToXero::class);
        Cache::forget($cacheKey);
        Flash::success($count . ' jobs were successfully put back on the queue', 'Jobs Requeued');

        return back();
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'selected' => ['required', 'array'],
            'selected.*' => ['required', 'string']
        ]);

        $count = 0;
        foreach ($validated['selected'] as $jobId) {
            if (!$jobId) continue;
            Artisan::call('queue:forget ' . $jobId);
            $count++;
        }

        $cacheKey = FailedJobs::getCacheKey(SendPaymentToXero::class);
        Cache::forget($cacheKey);
        Flash::success($count . ' jobs were successfully deleted from the queue', 'Jobs Deleted');

        return back();
    }
}

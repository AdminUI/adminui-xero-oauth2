<?php

namespace AdminUI\AdminUIXero\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use AdminUI\AdminUI\Facades\Flash;
use Illuminate\Routing\Controller;
use AdminUI\AdminUI\Models\Payment;
use AdminUI\AdminUIXero\Facades\Xero;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use AdminUI\AdminUIXero\Helpers\FailedJobs;
use AdminUI\AdminUI\Models\OrderIntegration;
use AdminUI\AdminUI\Traits\ApiResponseTrait;
use AdminUI\AdminUIXero\Facades\XeroPayment;
use AdminUI\AdminUIXero\Listeners\SendPaymentToXero;

class XeroPaymentsController extends Controller
{
    use ApiResponseTrait;

    public function sync(Request $request)
    {
        $validated = $request->validate([
            'payment_id' => ['required', 'integer', 'exists:payments,id']
        ]);

        $payment = Payment::find($validated['payment_id']);
        $orderIntegration = $payment->order->integrations()->type('xero')->first();

        if (empty($orderIntegration)) {
            Flash::error("Couldn't find a synced order for this payment to be made against", "No Order Found");
            return back();
        } else if ($payment->integrations()->type('xero')->exists()) {
            Flash::error("Can't sync this payment since a payment already exists on Xero. Use re-sync instead", "Payment Already Synced");
            return back();
        }

        $paymentIntegration = $payment->integrations()->create([
            'type' => 'xero',
        ]);

        $result = XeroPayment::syncPayment($payment, $orderIntegration->process_id, $paymentIntegration->id);
        /** @var \DateTime $processedAt */
        $processedAt = $result->getDateAsDate();

        $paymentIntegration->process_id = $result->getPaymentId();
        $paymentIntegration->notes = 'Payment manually synced to Xero by ' . Admin()->full_name;
        $paymentIntegration->processed_at = Carbon::parse($processedAt);
        $paymentIntegration->save();

        Flash::success("Your payment was successfully synced to Xero", "Payment Synced");

        return back();
    }

    public function resync(Request $request)
    {
        $validated = $request->validate([
            'order_integration_id' => ['required', 'integer', 'exists:order_integrations,id']
        ]);

        $integration = OrderIntegration::find($validated['order_integration_id']);
        $payment = $integration->model;

        $order = $integration->model?->order;
        if (empty($order)) {
            return back()->withErrors([
                'order_integration_id' => 'Cannot find related order for payment'
            ]);
        }
        $orderIntegration = $order->integrations()->type('xero')->first();
        if (empty($orderIntegration)) {
            return back()->withErrors([
                'order_integration_id' => 'Cannot find order integration for payment\'s order'
            ]);
        }

        $clone = $integration->replicate();
        $integration->delete();
        $clone->save();

        XeroPayment::deletePayment($clone);
        $result = XeroPayment::syncPayment($payment, $orderIntegration->process_id, $clone->id);
        /** @var \DateTime $processedAt */
        $processedAt = $result->getDateAsDate();
        $clone->process_id = $result->getPaymentId();
        $clone->notes = 'Payment manually synced to Xero by ' . Admin()->full_name;
        $clone->processed_at = Carbon::parse($processedAt);
        $clone->save();

        Flash::success("Your payment was successfully re-synced to Xero", "Payment re-synced");

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

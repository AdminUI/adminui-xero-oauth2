<?php

namespace AdminUI\AdminUIXero\Helpers;

use AdminUI\AdminUIXero\Listeners\SendOrderToXero;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class FailedJobs
{
    public static function getFailedJobs()
    {
        return Cache::remember('failed_order_syncs', 60 * 5, function () {
            $failed = collect(app()['queue.failer']->all())->filter([__CLASS__, 'filterByName']);

            return $failed->map(function ($failed) {
                return self::parseFailedJob((array) $failed);
            })->all();
        });
    }

    public static function filterByName($item)
    {
        $payload = json_decode($item->payload, true);
        return $payload['displayName'] == 'AdminUI\AdminUIXero\Listeners\SendOrderToXero';
    }

    public static function parseFailedJob(array $failed)
    {
        $payload = json_decode($failed['payload'], true);
        $dataError = null;
        $order = null;

        try {
            $command = unserialize($payload['data']['command']);
            if ($command instanceof SendOrderToXero) {
                $order = $command->event->order;
            } else {
                $data = array_shift($command->data);
                $order = $data->order;
            }
            $order->load('account', 'user', 'lines');
        } catch (\Exception $e) {
            $dataError = "Unable to retrieve job data";
        }

        return [
            'id' => $failed['id'],
            'connection' => $failed['connection'],
            'queue' => $failed['queue'],
            'failed_at' => $failed['failed_at'],
            'job' => $payload['displayName'],
            'order' => $order,
            'order_error' => $dataError,
            'exception' => self::parseException($failed['exception'])
        ];
    }

    public static function parseException(string $exception): string
    {
        $array = explode("\n", $exception);
        return array_shift($array);
    }
}

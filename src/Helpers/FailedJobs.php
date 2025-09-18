<?php

namespace AdminUI\AdminUIXero\Helpers;

use AdminUI\AdminUIXero\Listeners\SendOrderToXero;
use AdminUI\AdminUIXero\Listeners\SendPaymentToXero;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class FailedJobs
{
    public static function getCacheKey(string $class): string
    {
        return 'xero_failed_syncs__' . basename($class);
    }

    public static function getFailedJobs($class = SendOrderToXero::class)
    {
        $cacheKey = self::getCacheKey($class);
        return Cache::remember($cacheKey, 1/* 60 * 5 */, function () use ($class) {
            $failed = collect(app()['queue.failer']->all())->filter(fn($item) => self::filterByName($item, $class));

            return $failed->map(function ($failed) use ($class) {
                return self::parseFailedJob((array) $failed, $class);
            })->values()->all();
        });
    }

    public static function filterByName($item, $class)
    {
        $payload = json_decode($item->payload, true);
        return $payload['displayName'] == $class;
    }

    public static function parseFailedJob(array $failed, string $class)
    {
        $payload = json_decode($failed['payload'], true);
        $dataError = null;
        $jobData = [];

        try {
            $command = unserialize($payload['data']['command']);
            $data = array_shift($command->data);
            if ($command->class === SendOrderToXero::class) {
                $data->order->load('account', 'user', 'lines');
                $jobData['order'] = $data->order;
            } else if ($command->class === SendPaymentToXero::class) {
                $data->payment->load('user');
                $jobData['payment'] = $data->payment;
            }
        } catch (\Exception $e) {
            $dataError = "Unable to retrieve job data";
        }

        return [
            'id' => $failed['id'],
            'connection' => $failed['connection'],
            'queue' => $failed['queue'],
            'failed_at' => $failed['failed_at'],
            'job' => $payload['displayName'],
            ...$jobData,
            'job_error' => $dataError,
            'exception' => self::parseException($failed['exception'])
        ];
    }

    public static function parseException(string $exception): string
    {
        $array = explode("\n", $exception);
        return array_shift($array);
    }
}

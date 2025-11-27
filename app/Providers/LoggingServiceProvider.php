<?php

namespace App\Providers;

use App\Support\Correlation;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;

class LoggingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /**
         * 1) Đính kèm request_id / correlation_id vào payload của MỌI Job khi dispatch
         */
        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return [
                'request_id'     => Correlation::requestId(),
                'correlation_id' => Correlation::correlationId(),
            ];
        });

        /**
         * 2) Trước khi xử lý Job: khôi phục Correlation + set log context
         */
        Queue::before(function (JobProcessing $event) {
            $p      = $event->job->payload();
            $reqId  = $p['request_id']     ?? (string) Str::uuid();
            $corrId = $p['correlation_id'] ?? $reqId;

            Correlation::set($reqId, $corrId);

            Log::withContext([
                'request_id'     => $reqId,
                'correlation_id' => $corrId,
                'queue'          => $event->connectionName . ':' . $event->job->getQueue(),
                'job_name'       => $p['displayName'] ?? get_class($event->job),
            ]);
        });

        /**
         * 3) Sau khi xử lý Job: giữ nguyên context (không cần làm gì thêm)
         */
        Queue::after(function (JobProcessed $event) {
            // noop
        });

        /**
         * 4) Outbound HTTP macro: Http::obs() -> gắn headers + mặc định throw()
         *    (dùng giá trị TẠI THỜI ĐIỂM GỌI để đảm bảo đúng request hiện hành)
         */
        Http::macro('obs', function () {
            return Http::withHeaders([
                'X-Request-Id'     => Correlation::requestId(),
                'X-Correlation-Id' => Correlation::correlationId(),
            ])->throw();
        });

        /**
         * 5) Monolog processors: đưa IDs vào extra, +WebProcessor/+UidProcessor
         */
        app('log')->getLogger()->pushProcessor(function ($record) {
            $record['extra']['request_id']     = Correlation::requestId();
            $record['extra']['correlation_id'] = Correlation::correlationId();
            return $record;
        });

        app('log')->getLogger()->pushProcessor(new WebProcessor());
        app('log')->getLogger()->pushProcessor(new UidProcessor());
    }
}

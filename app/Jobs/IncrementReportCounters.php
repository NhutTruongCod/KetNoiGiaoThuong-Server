<?php
// app/Jobs/IncrementReportCounters.php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Support\Correlation;

class IncrementReportCounters implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        public int $orderId,
        public float $amount
    ) {}

    public function handle(): void
    {
        // Correlation ID đã được restore bởi LoggingServiceProvider
        $context = [
            'correlation_id' => Correlation::correlationId(),
            'request_id' => Correlation::requestId(),
            'job_id' => $this->job->uuid(),
            'order_id' => $this->orderId,
            'amount' => $this->amount,
            'attempt' => $this->attempts(),
        ];
        
        Log::info('IncrementReportCounters Job Started', $context);
        
        try {
            // TODO: Update report_counters table
            // DB::table('report_counters')->increment('total_orders');
            // DB::table('report_counters')->increment('total_revenue', $this->amount);
            
            sleep(1); // Simulate work
            
            Log::info('IncrementReportCounters Job Completed', $context);
            
        } catch (\Exception $e) {
            Log::error('IncrementReportCounters Job Failed', array_merge(
                $context,
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            ));
            
            // Let Laravel retry automatically
            throw $e;
        }
    }
    
    public function failed(\Throwable $exception): void
    {
        Log::error('IncrementReportCounters Job Failed Permanently', [
            'correlation_id' => Correlation::correlationId(),
            'request_id' => Correlation::requestId(),
            'job_id' => $this->job->uuid(),
            'order_id' => $this->orderId,
            'error' => $exception->getMessage(),
        ]);
    }
}
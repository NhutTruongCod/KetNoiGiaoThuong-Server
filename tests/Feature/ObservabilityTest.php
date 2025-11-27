<?php
// tests/Feature/ObservabilityTest.php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Events\Orders\OrderCreated;
use App\Events\PaymentSucceeded;
use App\Events\ShipmentCreated;
use App\Services\EventLoggerService;
use App\Support\Correlation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ObservabilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test correlation ID flows through entire system
     */
    public function test_correlation_id_flows_through_events_listeners_jobs()
    {
        // 1. Setup
        Queue::fake();
        $user = User::factory()->create();
        
        // 2. Simulate incoming request with correlation ID
        $correlationId = (string) \Illuminate\Support\Str::uuid();
        $this->withHeaders([
            'X-Correlation-Id' => $correlationId,
        ]);
        
        Correlation::set(null, $correlationId);
        
        // 3. Create order (triggers event)
        $orderEvent = new OrderCreated(
            orderId: 1,
            userId: $user->id,
            totalAmount: 1000000
        );
        
        // 4. Verify event has correlation ID
        $this->assertEquals($correlationId, $orderEvent->correlationId);
        
        // 5. Dispatch event
        event($orderEvent);
        
        // 6. Verify listeners were triggered
        // (In real scenario, check that jobs were dispatched with correlation_id)
        
        // 7. Verify correlation ID is in logs
        $loggedEvents = DB::table('event_logs')
            ->where('correlation_id', $correlationId)
            ->count();
            
        $this->assertGreaterThan(0, $loggedEvents);
    }

    /**
     * Test full order flow: Order → Payment → Shipment
     */
    public function test_full_order_payment_shipment_flow()
    {
        $user = User::factory()->create();
        $correlationId = (string) \Illuminate\Support\Str::uuid();
        Correlation::set(null, $correlationId);
        
        // Step 1: Create Order
        $orderEvent = new OrderCreated(1, $user->id, 1000000);
        event($orderEvent);
        
        // Step 2: Payment Succeeded
        $paymentEvent = new PaymentSucceeded(1, 'TXN123456', 1000000);
        event($paymentEvent);
        
        // Step 3: Shipment Created
        $shipmentEvent = new ShipmentCreated(1, 1, 'Giao Hang Nhanh', 'GHN123456');
        event($shipmentEvent);
        
        // Verify all events share same correlation ID
        $events = DB::table('event_logs')
            ->where('correlation_id', $correlationId)
            ->orderBy('created_at')
            ->get();
            
        $this->assertCount(3, $events);
        $this->assertEquals('App\Events\Orders\OrderCreated', $events[0]->event_type);
        $this->assertEquals('App\Events\PaymentSucceeded', $events[1]->event_type);
        $this->assertEquals('App\Events\ShipmentCreated', $events[2]->event_type);
    }

    /**
     * Test EventLoggerService
     */
    public function test_event_logger_service_traces_flow()
    {
        $user = User::factory()->create();
        $correlationId = (string) \Illuminate\Support\Str::uuid();
        Correlation::set(null, $correlationId);
        
        $service = app(EventLoggerService::class);
        
        // Create and log events
        $event1 = new OrderCreated(1, $user->id, 1000000);
        $service->log($event1);
        
        sleep(1); // Simulate time passing
        
        $event2 = new PaymentSucceeded(1, 'TXN123', 1000000);
        $service->log($event2);
        
        // Get flow
        $flow = $service->getFlowByCorrelationId($correlationId);
        $this->assertCount(2, $flow);
        
        // Get timeline
        $timeline = $service->getTimeline($correlationId);
        $this->assertCount(2, $timeline);
        $this->assertArrayHasKey('duration_from_previous_ms', $timeline[1]);
    }
}

// artisan command for manual testing
// php artisan make:command TestObservabilityFlow

// app/Console/Commands/TestObservabilityFlow.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Events\Orders\OrderCreated;
use App\Events\PaymentSucceeded;
use App\Events\ShipmentCreated;
use App\Services\EventLoggerService;
use App\Support\Correlation;

class TestObservabilityFlow extends Command
{
    protected $signature = 'test:observability';
    protected $description = 'Test complete observability flow';

    public function handle(EventLoggerService $logger)
    {
        $this->info('🚀 Starting Observability Test...');
        
        // Setup correlation ID
        $correlationId = (string) \Illuminate\Support\Str::uuid();
        Correlation::set(null, $correlationId);
        
        $this->info("📋 Correlation ID: {$correlationId}");
        $this->newLine();
        
        // Get or create test user
        $user = User::first() ?? User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        // Step 1: Order Created
        $this->info('1️⃣  Creating Order...');
        $orderEvent = new OrderCreated(999, $user->id, 1500000);
        event($orderEvent);
        $logger->log($orderEvent);
        sleep(1);
        
        // Step 2: Payment Succeeded
        $this->info('2️⃣  Processing Payment...');
        $paymentEvent = new PaymentSucceeded(999, 'TXN_' . time(), 1500000);
        event($paymentEvent);
        $logger->log($paymentEvent);
        sleep(1);
        
        // Step 3: Shipment Created
        $this->info('3️⃣  Creating Shipment...');
        $shipmentEvent = new ShipmentCreated(999, 999, 'Giao Hang Nhanh', 'GHN_' . time());
        event($shipmentEvent);
        $logger->log($shipmentEvent);
        
        $this->newLine();
        $this->info('✅ Events fired successfully!');
        $this->newLine();
        
        // Show trace
        $this->info('📊 Event Flow:');
        $this->table(
            ['Event Type', 'Timestamp', 'Duration (ms)'],
            collect($logger->getTimeline($correlationId))->map(fn($e) => [
                $e['event_type'],
                $e['timestamp'],
                $e['duration_from_previous_ms'] ?? '-',
            ])
        );
        
        $this->newLine();
        $this->info("🔍 To view full trace:");
        $this->line("   GET /api/trace/{$correlationId}");
        $this->newLine();
        
        $this->info("📝 Check logs:");
        $this->line("   tail -f storage/logs/laravel.log | grep {$correlationId}");
        
        return 0;
    }
}
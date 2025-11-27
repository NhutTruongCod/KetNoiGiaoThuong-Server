<?php
// app/Services/EventLoggerService.php
namespace App\Services;

use App\Events\BaseEvent;
use Illuminate\Support\Facades\DB;

class EventLoggerService
{
    /**
     * Log event to database for tracing
     */
    public function log(BaseEvent $event): void
    {
        DB::table('event_logs')->insert([
            'event_id' => $event->eventId,
            'request_id' => $event->requestId,
            'correlation_id' => $event->correlationId,
            'event_type' => get_class($event),
            'payload' => json_encode($this->serializeEvent($event)),
            'metadata' => json_encode($event->metadata),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    /**
     * Serialize event properties to array
     */
    protected function serializeEvent(BaseEvent $event): array
    {
        $reflection = new \ReflectionClass($event);
        $properties = [];
        
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();
            
            // Skip base event properties
            if (!in_array($name, ['requestId', 'correlationId', 'eventId', 'metadata'])) {
                $value = $property->getValue($event);
                
                // Handle objects
                if (is_object($value) && method_exists($value, 'toArray')) {
                    $properties[$name] = $value->toArray();
                } else {
                    $properties[$name] = $value;
                }
            }
        }
        
        return $properties;
    }
    
    /**
     * Get all events for a correlation ID
     */
    public function getFlowByCorrelationId(string $correlationId): array
    {
        return DB::table('event_logs')
            ->where('correlation_id', $correlationId)
            ->orderBy('created_at')
            ->get()
            ->map(function ($row) {
                return [
                    'event_id' => $row->event_id,
                    'event_type' => $row->event_type,
                    'payload' => json_decode($row->payload, true),
                    'metadata' => json_decode($row->metadata, true),
                    'created_at' => $row->created_at,
                ];
            })
            ->toArray();
    }
    
    /**
     * Get timeline summary for correlation ID
     */
    public function getTimeline(string $correlationId): array
    {
        $events = $this->getFlowByCorrelationId($correlationId);
        $timeline = [];
        $previousTime = null;
        
        foreach ($events as $event) {
            $currentTime = strtotime($event['created_at']);
            
            $timeline[] = [
                'event_type' => class_basename($event['event_type']),
                'timestamp' => $event['created_at'],
                'duration_from_previous_ms' => $previousTime 
                    ? ($currentTime - $previousTime) * 1000 
                    : null,
            ];
            
            $previousTime = $currentTime;
        }
        
        return $timeline;
    }
}

// app/Http/Controllers/Api/TraceController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EventLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TraceController extends Controller
{
    public function __construct(
        private EventLoggerService $eventLogger
    ) {}
    
    /**
     * Get full trace for a correlation ID
     * 
     * GET /api/trace/{correlationId}
     */
    public function show(Request $request, string $correlationId)
    {
        $events = $this->eventLogger->getFlowByCorrelationId($correlationId);
        $timeline = $this->eventLogger->getTimeline($correlationId);
        
        // Get related records
        $relatedData = $this->getRelatedData($correlationId);
        
        return response()->json([
            'correlation_id' => $correlationId,
            'total_events' => count($events),
            'events' => $events,
            'timeline' => $timeline,
            'related_data' => $relatedData,
        ]);
    }
    
    /**
     * Get related database records for correlation ID
     */
    private function getRelatedData(string $correlationId): array
    {
        $data = [];
        
        // Orders
        if (DB::getSchemaBuilder()->hasTable('orders')) {
            $data['orders'] = DB::table('orders')
                ->where('correlation_id', $correlationId)
                ->get();
        }
        
        // Payments
        if (DB::getSchemaBuilder()->hasTable('payments')) {
            $data['payments'] = DB::table('payments')
                ->where('correlation_id', $correlationId)
                ->get();
        }
        
        // Shipments
        if (DB::getSchemaBuilder()->hasTable('shipments')) {
            $data['shipments'] = DB::table('shipments')
                ->where('correlation_id', $correlationId)
                ->get();
        }
        
        // Notifications
        if (DB::getSchemaBuilder()->hasTable('notifications')) {
            $data['notifications'] = DB::table('notifications')
                ->whereRaw("JSON_EXTRACT(data, '$.correlation_id') = ?", [$correlationId])
                ->get();
        }
        
        return $data;
    }
}

// routes/api.php - ADD THIS ROUTE
// Route::get('/trace/{correlationId}', [App\Http\Controllers\Api\TraceController::class, 'show']);
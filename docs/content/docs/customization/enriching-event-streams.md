---
title: "Enriching Event Streams"
weight: 2
---

Event stream enrichment allows applications to inject metadata into events as they flow through the system. This
metadata provides contextual information like the current tenant, authenticated user, or correlation keys.

Enrichment occurs after the model records events but before they are persisted to EventStore or published to EventBus.

## Understanding stream enrichment

An application should have a single implementation of `StreamEnricherInterface`:

```php
interface StreamEnricherInterface
{
    public function enrich(RecordedEventStream $stream): RecordedEventStream;
}
```

Using a single enricher avoids metadata conflicts, ensures predictable execution order, and simplifies configuration.
The enricher's role is to inject metadata into events. This implementation can receive services as dependencies when
contextual information is needed. Common metadata includes:

- Current tenant in multi-tenant applications
- Authenticated user information
- Correlation IDs for request tracing
- Execution context (batch job, API request, CLI command)

## Creating a stream enricher

Here's an example enricher that adds tenant and user information:

```php
use Backslash\StreamEnricher\StreamEnricherInterface;
use Backslash\Event\RecordedEventStream;
use Backslash\Event\RecordedEvent;

class StreamEnricher implements StreamEnricherInterface
{
    public function __construct(
        private TenantService $tenantService,
        private AuthService $authService,
        private CorrelationIdService $correlationService,
    ) {
    }

    public function enrich(RecordedEventStream $stream): RecordedEventStream
    {
        $enrichedStream = new RecordedEventStream();
        
        foreach ($stream->getRecordedEvents() as $recordedEvent) {
            $metadata = $recordedEvent->getMetadata();
            
            // Add tenant information
            if ($this->tenantService->hasTenant()) {
                $metadata = $metadata->with('tenant_id', $this->tenantService->getCurrentTenantId());
            }
            
            // Add authenticated user
            if ($this->authService->isAuthenticated()) {
                $metadata = $metadata->with('user_id', $this->authService->getCurrentUserId());
            }
            
            // Add correlation ID for tracing
            $metadata = $metadata->with('correlation_id', $this->correlationService->get());
            
            $enrichedStream = $enrichedStream->withRecordedEvents(
                $recordedEvent->withMetadata($metadata)
            );
        }
        
        return $enrichedStream;
    }
}
```

This enricher receives services as dependencies and uses them to inject relevant metadata into every event.

## Registering the enricher

Backslash provides two middleware for stream enrichment, as introduced in section 14:

- **StreamEnricherEventStoreMiddleware**: Enriches events before persisting to EventStore
- **StreamEnricherEventBusMiddleware**: Enriches events before publishing to EventBus

Register both middleware during application bootstrap to ensure consistent metadata in storage and when published:

```php
use Backslash\StreamEnricher\StreamEnricherEventStoreMiddleware;
use Backslash\StreamEnricher\StreamEnricherEventBusMiddleware;

$enricher = new StreamEnricher($tenantService, $authService, $correlationService);

// Enrich before persisting
$eventStore->addMiddleware(new StreamEnricherEventStoreMiddleware($enricher));

// Enrich before publishing
$eventBus->addMiddleware(new StreamEnricherEventBusMiddleware($enricher));
```

## Using enriched metadata

Enriched metadata becomes available throughout the application. Event handlers can access this metadata to make
decisions or add context:

```php
class NotificationHandler implements EventHandlerInterface
{
    use EventHandlerTrait;

    private function handleStudentSubscribedToCourseEvent(
        StudentSubscribedToCourseEvent $event,
        RecordedEvent $recordedEvent,
    ): void {
        $metadata = $recordedEvent->getMetadata();
        
        // Access enriched tenant information
        $tenantId = $metadata->get('tenant_id');
        
        // Access correlation ID for tracing
        $correlationId = $metadata->get('correlation_id');
        
        $this->logger->info('Student subscribed', [
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
            'student_id' => $event->studentId,
        ]);
        
        // Send notification with tenant context
        $this->notifier->send($event->studentId, $tenantId);
    }
}
```

Queries can also filter events based on enriched metadata:

```php
use Backslash\EventStore\Query\Metadata;

// Load only events for specific tenant
$query = EventClass::is(CourseDefinedEvent::class)
    ->and(Metadata::is('tenant_id', $currentTenantId));
```

## Best practices

**Use one enricher per application.** Create a single `StreamEnricherInterface` implementation that handles all metadata
injection; avoid multiple enrichers with overlapping concerns.

**Inject services as dependencies when needed.** If the enricher requires contextual information, pass services like
authentication, tenant management, or correlation tracking as constructor dependencies rather than accessing global
state.

**Register both middleware.** Always register `StreamEnricherEventStoreMiddleware` and
`StreamEnricherEventBusMiddleware` to ensure metadata consistency between persisted events and published events.

**Enrich metadata, not payloads.** Event payloads should contain domain information; metadata is for technical concerns
like tenant context, user information, or correlation IDs.

**Avoid sensitive data.** Metadata is stored and potentially logged; avoid adding passwords, tokens, or other sensitive
information unless absolutely required.

**Keep enrichment fast.** Enrichment runs for every event; avoid expensive operations like database queries or external
API calls.

---

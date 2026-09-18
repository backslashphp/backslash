---
title: "Architecture Overview"
weight: 2
---

Backslash is designed around the core principles of event sourcing and CQRS. Its modular, lightweight, and
framework-agnostic architecture allows for gradual adoption or integration into existing systems.

**Core components** are provided by Backslash and handle the infrastructure behind event sourcing and CQRS.

**Application components** are written by the developer to implement use cases and domain behavior.

| Component           | Core | Application | Role                                                                                   |
|---------------------|:----:|:-----------:|----------------------------------------------------------------------------------------|
| Events              |      |      ✓      | Represent facts that have occurred within the system.                                  |
| Models              |      |      ✓      | Replay events to hold current state and make decisions that record new events.         |
| Queries             |      |      ✓      | Define how to retrieve past events relevant to a model.                                |
| `Repository`        |  ✓   |             | Persists model changes to the `EventStore` and publishes new events on the `EventBus`. |
| `EventNameResolver` |  ✓   |             | Converts event FQCNs to short names and vice versa.                                    |
| `EventStore`        |  ✓   |             | Stores events as an append-only, queryable log.                                        |
| `EventBus`          |  ✓   |             | Delivers published events to event handlers.                                           |
| Event handlers      |      |      ✓      | Respond to events to update projections or trigger side effects.                       |
| Projections         |      |      ✓      | Read-optimized views of the system's current state.                                    |
| `ProjectionStore`   |  ✓   |             | Persists and retrieves projections.                                                    |
| Commands            |      |      ✓      | Express an intention or request to perform an operation on the system.                 |
| Command handlers    |      |      ✓      | Contain the logic to process commands and update the system accordingly.               |
| `Dispatcher`        |  ✓   |             | Routes commands to their respective handlers.                                          |
| `Scenario`          |  ✓   |             | Provides helpers for writing BDD-style tests using PHPUnit.                            |
| Testing scenarios   |      |      ✓      | Define given/when/then test flows using the `Scenario` test helpers.                   |
| `PdoProxy`          |  ✓   |             | Delays the creation of a PDO connection until it's needed.                             |
| Storage adapters    |  ✓   |             | Provide PDO-based persistence for `EventStore` and `ProjectionStore`.                  |
| `Serializer`        |  ✓   |             | Handles the serialization and deserialization of events and projections.               |
| Middlewares         |  ✓   |      ✓      | Add custom logic around core components operations.                                    |
| Stream enrichers    |      |      ✓      | Modify or enhance events going through `EventStore` and `EventBus`.                    |

## Understanding how it works

Backslash separates write operations (commands) from read operations (queries) using the CQRS pattern. The write side
and read side operate independently, connected only through events.

### Write side flow

When you execute a command, here's what happens:

1. **Command dispatch**: The `Dispatcher` receives a command and routes it to the registered command handler
2. **Query building**: The handler builds a query to determine which events are needed by the model
3. **Event loading**: The `Repository` fetches matching events from the `EventStore`
4. **State reconstruction**: Events are replayed into the model to rebuild its current state
5. **Business decision**: The model executes business logic and records new events
6. **Event persistence**: The `Repository` appends new events to the `EventStore`
7. **Event publishing**: New events are published to the `EventBus`

### Read side flow

When events are published, the read side reacts:

1. **Event notification**: The `EventBus` delivers events to subscribed event handlers
2. **Projection update**: Event handlers load projections from the `ProjectionStore`
3. **State modification**: Projections are updated based on event data
4. **Projection persistence**: Updated projections are stored back to the `ProjectionStore`

This separation ensures that complex business rules don't complicate queries, and query optimization doesn't constrain
domain logic.

## Extensibility

Backslash provides two mechanisms for extending functionality:

**Middlewares** wrap core operations (command dispatch, event storage, projection updates) to add cross-cutting concerns
like logging, validation, or transactions.

**Stream enrichers** modify events as they flow through the system, adding metadata such as correlation IDs, user
context, or timestamps.

## Handling concurrency

Backslash uses optimistic concurrency control to prevent race conditions when writing events to the EventStore. The
`PdoEventStoreAdapter` enforces this using a conditional append in SQL that checks the expected number of events in a
stream before appending new ones. If another process has modified the stream in the meantime, a concurrency exception is
thrown, allowing you to retry the operation with the latest state.

## What's not included

Backslash focuses exclusively on event sourcing and CQRS infrastructure. It intentionally does not provide:

- HTTP routing or web framework components
- User interface frameworks or templating
- Process managers or sagas for long-running workflows
- Message queue integration (though you can add this via middleware)
- Authentication or authorization mechanisms
- Database migration tools

These concerns are best handled by your chosen framework or dedicated libraries, allowing Backslash to remain
framework-agnostic and focused on its core purpose.

---

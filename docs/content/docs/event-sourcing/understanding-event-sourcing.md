---
title: "Understanding Event Sourcing"
weight: 3
---

Event sourcing is a pattern where you store the state of your application as a sequence of events rather than just the
current state. Instead of updating records in place, you append new events describing what happened.

## Events as source of truth

In traditional CRUD applications, you store the current state:

```
UPDATE students SET name = 'Alice Johnson' WHERE id = 123;
```

With event sourcing, you store what happened:

```
StudentRegistered(id: 123, name: 'Alice Johnson')
StudentNameChanged(id: 123, from: 'Alice Johnson', to: 'Alice Cooper')
```

The current state is derived by replaying these events in order.

## Replaying events to reconstruct state

Systems rebuild state by processing events sequentially:

```
state = EmptyState
for each event in events:
    state = apply(state, event)
```

This replay mechanism enables:

- **Audit trail**: Complete history of all changes
- **Debugging**: Replay events to understand how the system reached its current state
- **Time travel**: Query past states by replaying events up to a specific point

## Benefits of event sourcing

**Complete audit trail.** Every change is recorded as an immutable event, providing a full history for compliance,
debugging, and analysis.

**Debugging and troubleshooting.** Replay events to reproduce bugs or understand how the system reached a problematic
state.

**Flexible projections.** Build multiple read models from the same events, optimized for different queries.

**Business insights.** Analyze event history to understand user behavior, identify trends, and make data-driven
decisions.

---

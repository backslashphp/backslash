---
title: "Understanding CQRS"
weight: 1
---

Command Query Responsibility Segregation (CQRS) is a pattern that separates read and write operations into distinct
models. Instead of using the same model for both updates and queries, CQRS uses different models optimized for their
specific purposes.

## Separating write and read sides

**Write side (Commands):**

- Processes commands that express intent to change state
- Uses models that enforce business rules
- Records events when operations succeed

**Read side (Queries):**

- Provides optimized views of current state
- Uses projections denormalized for specific queries
- Updated by reacting to events from the write side

In Backslash:

- Commands → Handlers → Models → Events
- Events → Event Handlers → Projections

## Benefits of CQRS

**Independent optimization.** Write and read models can be optimized separately; complex business rules don't complicate
queries, and query optimization doesn't constrain domain logic.

**Denormalized views.** Projections can be structured however queries need them, with precomputed values and joined data
for fast reads.

**Clear separation of concerns.** Business logic lives in models; query logic lives in projections. Each has a single,
focused responsibility.

---

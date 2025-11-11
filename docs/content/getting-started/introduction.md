---
title: "Introduction"
weight: 1
bookToc: true
---
# Introduction

Backslash is a modern and opinionated PHP library for building event-sourced applications with CQRS. It is fully
compliant with the [Dynamic Consistency Boundary specification](https://dcb.events/specification/).

Unlike most event sourcing libraries built around aggregates with rigid boundaries, Backslash allows you to define
consistency boundaries on the fly based on the decision you're making, not based on predetermined aggregate structures.

> **Important note:** Backslash has evolved in production for many years at the [FNQLHSSC](https://cssspnql.com/en/);
> however, it was originally designed for the company's specific use cases. Nevertheless, Backslash adopts a generic
> approach to event sourcing and does not distort its fundamental principles.

## Core capabilities

**Dynamic consistency boundaries.** Define which events matter for each decision. No fixed aggregates; just queries that
fetch exactly what you need.

**Built-in persistence.** PDO-compatible adapters for storing events and projections are included. Supports MySQL and
SQLite out of the box.

**Synchronous projections.** Projections are updated immediately within the same transaction as command execution,
ensuring read-your-writes consistency.

**Framework agnostic.** Works with any PHP framework or runs standalone. Use it with Laravel, Symfony, Slim, or your own
custom setup.

**Event replay and projection rebuilding.** Reconstruct projections from scratch by replaying historical events,
enabling schema changes and bug fixes.

**Optimistic concurrency control.** Prevent race conditions with built-in version checking that detects concurrent
modifications.

**Extensible through middleware.** Add logging, validation, transactions, or any cross-cutting concern by wrapping core
operations with middleware.

**Stream enrichment.** Enhance events with contextual metadata like correlation IDs, user information, or tenant context
as they flow through the system.

**BDD-style testing.** Write expressive tests using the Scenario component with given-when-then syntax that reads like
specifications.

## Prerequisites

While not strictly required, familiarity with event sourcing and CQRS fundamentals is strongly recommended. If these
concepts are new to you, start with these resources:

- [Martin Fowler on Event Sourcing](https://martinfowler.com/eaaDev/EventSourcing.html)
- [Event Store – What Is Event Sourcing?](https://eventstore.com/docs/event-sourcing-basics/)
- [Greg Young's Original CQRS Introduction (2007)](https://web.archive.org/web/20230324113014/https://cqrs.files.wordpress.com/2010/11/cqrs_documents.pdf)
- [Microsoft Docs: CQRS Pattern](https://learn.microsoft.com/en-us/azure/architecture/patterns/cqrs)

### Installing Backslash

Install Backslash using [Composer](https://getcomposer.org/):

```shell
composer require backslashphp/backslash
```

## Exploring the demo application

The examples in this documentation come from the [demo application](https://github.com/backslashphp/demo), a simple
course enrollment system where students can subscribe to courses with capacity limits. The demo demonstrates how to
define events, build decision-making models, react with event handlers, and maintain projections.

The domain chosen for the demo application is inspired by Sara Pellegrini's blog
series ["Kill Aggregate"](https://sara.event-thinking.io/2023/04/kill-aggregate-chapter-1-I-am-here-to-kill-the-aggregate.html).

The demo is intentionally simple to keep focus on Backslash concepts rather than domain complexity. Real applications
will have more sophisticated domain logic, but the patterns remain the same.

## Developing a Backslash system

Building an event-sourced application with Backslash involves several key steps:

1. **Define events** that represent facts about what happened in your system
2. **Build models** that replay events to understand current state and make decisions
3. **Write queries** to select which events are relevant for each decision
4. **Create command handlers** that load models, execute business logic, and persist changes
5. **Set up event handlers** that react to events by updating projections or triggering side effects

Backslash handles command routing, event persistence, concurrency control, and event publishing; you focus on expressing
your domain logic clearly.

---

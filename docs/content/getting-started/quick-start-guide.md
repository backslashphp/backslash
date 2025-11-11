---
title: "Quick Start Guide"
weight: 3
bookToc: true
---

# Quick Start Guide

This guide walks you through setting up and running the Backslash demo application, helping you understand the basic
workflow before building your own application.

## Installing the demo

Clone the demo repository and install dependencies:

```shell
git clone https://github.com/backslashphp/demo.git
cd demo
composer install
```

## Running the demo

Execute the demo script:

```shell
cd bin
php demo.php
```

The demo will automatically create the SQLite database and execute a series of commands demonstrating:

- Defining a course with capacity limits
- Registering students
- Subscribing students to courses
- Handling capacity constraints
- Updating projections in real-time

## Understanding the demo structure

The demo follows a vertical slice architecture (VSA):

```
src/
├── Feature/
│   ├── {Feature name}/
│   │   ├── Command/        # Commands and handler
│   │   ├── Event/          # Domain events
│   │   ├── Exception/      # Business rule violations
│   │   ├── Model/          # Decision-making models
│   │   └── Test/           # Test scenarios
│   └── Shared/
│       └── Projection/     # Shared projections
└── Infrastructure/
    └── Container.php       # Dependency injection
```

This structure is a recommended approach that works well for event sourcing applications. However, Backslash does not
impose any specific code structure; you're free to organize your application in whatever way suits your needs.

## Exploring the code

As you read through this documentation, explore the demo application's source code. Each concept explained in the
following sections has a concrete implementation in the demo that you can study and experiment with.

---

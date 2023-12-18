# Backslash

[![Latest Version](https://img.shields.io/github/release/backslashphp/backslash.svg)](https://github.com/backslashphp/backslash/releases)
[![Composer](https://img.shields.io/badge/composer-backslashphp/backslash-lightgray)](https://packagist.org/packages/backslashphp/backslash)
![PHP](https://img.shields.io/packagist/php-v/backslashphp/backslash)
[![Software License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Modern and opinionated PHP library designed to facilitate the integration of DDD, CQRS, and Event Sourcing patterns in
your application.

**Domain-centric** — Implement complex domain logic using aggregates and events.

**Event-driven** — Efficiently compute query models and initiate actions based on domain events.

**Command-oriented** — Decouple the application logic from the UI by dispatching commands to designated handlers.

**Test-friendly** — Validate expected code behavior with Given-When-Then scenarios.

**Control-focused** — Tailor custom middleware and storage adapters to suit your specific requirements.

**Framework-agnostic** — Integrate with your preferred framework or use it independently.

## Documentation

Documentation can be found [here](https://github.com/backslashphp/docs).

A [demo application](https://github.com/backslashphp/demo) is also available for learning purposes.

## Installation

Add Backslash to your project with [Composer](https://getcomposer.org/):

```bash
composer require backslashphp/backslash
```

## Requirements

- PHP version 8.1 or higher
- `json` and `pdo` extensions enabled

## Testing

```bash
vendor/bin/phpunit
```

## Credits

Backslash was crafted in Canada by [Maxime Gosselin](https://github.com/maximegosselin).

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

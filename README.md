# Backslash

[![Latest Version](https://img.shields.io/github/release/backslashphp/backslash.svg)](https://github.com/backslashphp/backslash/releases)
[![Composer](https://img.shields.io/badge/composer-backslashphp/backslash-lightgray)](https://packagist.org/packages/backslashphp/backslash)
![PHP](https://img.shields.io/packagist/php-v/backslashphp/backslash)
[![Software License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Modern and opinionated PHP library designed to facilitate the integration of CQRS and Event Sourcing patterns in your
application.

A [demo application](https://github.com/backslashphp/demo) is available to see Backslash in action.

> Version 2 of Backslash has been completely rewritten to align with Dynamic Consistency Boundary principles, as
> outlined by Sara Pellegrini in her
> [Kill Aggregate](https://sara.event-thinking.io/2023/04/kill-aggregate-chapter-1-I-am-here-to-kill-the-aggregate.html)
> blog series. Nevertheless, the traditional DDD approach of implementing Event Sourcing with aggregates is still
> possible.

## Disclaimer

While Backslash has been used in production for many years at [FNQLHSSC](https://cssspnql.com/), it was tailored to
meet the needs of a specific environment, and it may lack the robustness, flexibility, or polish expected in a
widely-adopted, general-purpose library. This library is provided as-is, with no guarantees, warranties, or support.

If you are exploring CQRS or Event Sourcing, you may use Backslash as a learning resource to understand these concepts in
practice.

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

Backslash was crafted by [Maxime Gosselin](https://github.com/maximegosselin) in Québec, Canada.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

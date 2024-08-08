<?php

declare(strict_types=1);

namespace Backslash\Domain;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class IdentifiersTest extends TestCase
{
    /** @test */
    public function key_and_value_must_have_valid_type(): void
    {
        $fixtures = [
            [['foo' => 'bar'], null],
            [['foo' => 'bar', 'baz' => ['foo', 'bar']], null],
            [['foo' => []], null],
            [['foo' => 'bar', 'baz' => ['foo' => '1', 'bar' => '2']], 'Value must be a string or an array of strings.'],
            [['foo' => 1], 'Value must be a string or an array of strings.'],
            [['foo' => 1.1], 'Value must be a string or an array of strings.'],
            [['foo' => true], 'Value must be a string or an array of strings.'],
            [['foo' => null], 'Value must be a string or an array of strings.'],
        ];

        foreach ($fixtures as $fixture) {
            try {
                new Identifiers($fixture[0]);
                if ($fixture[1]) {
                    $this->fail('Exception was not thrown.');
                }
            } catch (InvalidArgumentException $e) {
                $this->assertEquals($fixture[1], $e->getMessage());
            }
        }
    }
}

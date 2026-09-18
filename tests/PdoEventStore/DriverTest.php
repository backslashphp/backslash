<?php

declare(strict_types=1);

namespace Backslash\PdoEventStore;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DriverTest extends TestCase
{
    #[Test]
    public function it_resolves_sequences_from_the_first_id_for_mysql(): void
    {
        $this->assertSame([10, 11, 12], Driver::MYSQL->resolveInsertedSequences(10, 3));
    }

    #[Test]
    public function it_resolves_sequences_from_the_last_id_for_sqlite(): void
    {
        $this->assertSame([10, 11, 12], Driver::SQLITE->resolveInsertedSequences(12, 3));
    }

    #[Test]
    public function it_returns_no_statements_for_empty_rows(): void
    {
        $this->assertSame([], Driver::SQLITE->buildChildInsertStatementsFromSequences('event_store_identifiers', [], []));
    }

    #[Test]
    public function it_builds_a_single_statement_when_under_the_variable_cap(): void
    {
        $rows = [
            [0, 'uid-a', 'studentId', '1'],
            [1, 'uid-b', 'studentId', '2'],
        ];
        $sequences = [0 => 5, 1 => 6];

        $statements = Driver::SQLITE->buildChildInsertStatementsFromSequences('event_store_identifiers', $rows, $sequences);

        $this->assertCount(1, $statements);
        [$sql, $values] = $statements[0];
        $this->assertSame(
            'INSERT INTO `event_store_identifiers` (`sequence`, `name`, `value`) VALUES (?, ?, ?), (?, ?, ?)',
            $sql,
        );
        $this->assertSame([5, 'studentId', '1', 6, 'studentId', '2'], $values);
    }

    #[Test]
    public function it_chunks_statements_when_exceeding_the_variable_cap(): void
    {
        $rowsPerChunk = intdiv(32000, 3);
        $rowCount = $rowsPerChunk + 1;

        $rows = [];
        $sequences = [];
        for ($i = 0; $i < $rowCount; $i++) {
            $rows[] = [$i, 'uid-' . $i, 'tag', (string) $i];
            $sequences[$i] = 100 + $i;
        }

        $statements = Driver::SQLITE->buildChildInsertStatementsFromSequences('event_store_identifiers', $rows, $sequences);

        $this->assertCount(2, $statements);
        [, $firstValues] = $statements[0];
        [, $secondValues] = $statements[1];
        $this->assertCount($rowsPerChunk * 3, $firstValues);
        $this->assertCount(3, $secondValues);
        $this->assertSame([100 + $rowCount - 1, 'tag', (string) ($rowCount - 1)], $secondValues);
    }
}

<?php

declare(strict_types=1);

namespace Backslash\PdoProxy;

use Backslash\Pdo\PdoProxy;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class PdoProxyTest extends TestCase
{
    /** @test */
    public function it_does_not_connect_on_instantiation(): void
    {
        new PdoProxy(
            fn (): PDO => new PDO('fake-dsn', 'user', 'pass', []),
        );
        $this->assertTrue(true);
    }

    /** @test */
    public function it_throws_exception_if_connection_error_on_first_use(): void
    {
        $this->expectException(PDOException::class);
        $pdo = new PdoProxy(
            fn (): PDO => new PDO('fake-dsn', 'user', 'pass', []),
        );
        $pdo->rollback();
    }

    /** @test */
    public function it_returns_real_pdo(): void
    {
        $pdo = new PdoProxy(
            fn (): PDO => new PDO('sqlite::memory:'),
        );
        $this->assertInstanceOf(PDO::class, $pdo->getPdo());
    }

    /** @test */
    public function it_forwards_call_to_proxied_pdo(): void
    {
        $pdo = new PdoProxy(
            fn (): PDO => new PDO('sqlite::memory:'),
        );

        $this->assertTrue($pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC));

        $this->assertEquals(0, $pdo->exec('CREATE TABLE foo (id INT, message TEXT)'));
        $this->assertTrue($pdo->beginTransaction());

        $query = $pdo->prepare('INSERT INTO foo (id, message) VALUES (:id, :message)');
        $this->assertInstanceOf(PDOStatement::class, $query);

        $success = $query->execute(
            [
                'id' => 1,
                'message' => 'hello world',
            ],
        );
        $this->assertTrue($success);

        $this->assertTrue($pdo->commit());

        $rows = $pdo->query('SELECT id, message FROM foo')->fetchAll();
        $this->assertCount(1, $rows);
        $this->assertSame(
            [
                0 => [
                    'id' => 1,
                    'message' => 'hello world',
                ],
            ],
            $rows,
        );

        $this->assertFalse($pdo->query('/**/'));

        $this->assertEquals(1, $pdo->exec('DELETE FROM foo'));

        $rows = $pdo->query('SELECT * FROM foo')->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(0, $rows);
    }
}

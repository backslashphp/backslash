<?php

declare(strict_types=1);

namespace Backslash\CommandDispatcher;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class CommandHandlerTraitTest extends TestCase
{
    /** @test */
    public function it_handles_a_command_without_exception(): void
    {
        $bus = new Dispatcher();
        $bus->registerHandler(TestCommand::class, new SuccessfulCommandHandler());
        $bus->dispatch(new TestCommand());
        $this->assertTrue(true); // We are not expecting any exception to this point.
    }

    /** @test */
    public function it_throws_an_exception_if_no_handler_method_implemented(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Function "handleTestCommand" must be implemented in class ' . FailingCommandHandler::class,
        );
        $bus = new Dispatcher();
        $bus->registerHandler(TestCommand::class, new FailingCommandHandler());
        $bus->dispatch(new TestCommand());
    }
}

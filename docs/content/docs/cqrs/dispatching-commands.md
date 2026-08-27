---
title: "Dispatching Commands"
weight: 2
---

Commands represent intentions to change the system's state. Unlike events that describe what happened, commands express
what should happen. They carry the information needed to make a business decision and are the entry points for all write
operations.

Commands are dispatched through a `Dispatcher` that routes them to their corresponding handlers. Command handlers
coordinate whatever operations are necessary to fulfill the command's intent.

## Defining commands

Commands are simple data transfer objects with no behavior. They don't need to implement any interface; they're just
plain PHP classes that carry data:

```php
readonly class RegisterStudentCommand
{
    public function __construct(
        public string $studentId,
        public string $name,
    ) {
    }
}
```

Mark commands as `readonly` to emphasize their immutability. Commands should never change once created.

## Naming commands

Command names should clearly express intent using imperative verbs:

```php
// Good: clear intent
RegisterStudentCommand
ChangeCourseCapacityCommand
SubscribeStudentToCourseCommand
UnsubscribeStudentFromCourseCommand

// Avoid: unclear or passive names
StudentCommand
CourseCapacityCommand
StudentCourseCommand
```

## Creating command handlers

Command handlers implement `HandlerInterface`:

```php
interface HandlerInterface
{
    public function handle(object $command): void;
}
```

Use `HandleCommandTrait` to automatically route commands to methods based on class names:

```php
use Backslash\CommandDispatcher\HandlerInterface;
use Backslash\CommandDispatcher\HandleCommandTrait;

class StudentCommandHandler implements HandlerInterface
{
    use HandleCommandTrait;

    public function __construct(
        private RepositoryInterface $repository,
    ) {
    }

    protected function handleRegisterStudentCommand(
        RegisterStudentCommand $command,
    ): void {
        $query = StudentRegistrationModel::buildQuery($command->studentId);
        
        $model = $this->repository->loadModel(
            StudentRegistrationModel::class,
            $query
        );
        
        $model->register($command->studentId, $command->name);
        
        $this->repository->storeChanges($model);
    }
}
```

The trait looks for methods named by prefixing `handle` to the command's short class name.

## The command handler workflow

A common pattern for command handlers working with models follows these steps:

1. **Build the query** to load relevant events
2. **Load the model** from the Repository
3. **Execute business logic** by calling decision methods
4. **Persist changes** back to the Repository

```php
protected function handleChangeCourseCapacityCommand(
    ChangeCourseCapacityCommand $command,
): void {
    // 1. Build query
    $query = CourseCapacityModel::buildQuery($command->courseId);
    
    // 2. Load model
    $model = $this->repository->loadModel(
        CourseCapacityModel::class,
        $query
    );
    
    // 3. Execute business logic
    $model->change($command->capacity);
    
    // 4. Persist changes
    $this->repository->storeChanges($model);
}
```

The Repository handles event replay, concurrency checks, event persistence, and event publishing. Command handlers
coordinate operations.

However, command handlers are not limited to working with models. They can perform any operation needed to fulfill the
command's intent. For example, a handler might call external services, send notifications, or perform system operations:

```php
class SystemCommandHandler implements HandlerInterface
{
    use HandleCommandTrait;

    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {
    }

    protected function handleSendWelcomeEmailCommand(
        SendWelcomeEmailCommand $command,
    ): void {
        $this->mailer->send(
            to: $command->email,
            subject: 'Welcome!',
            body: $this->buildWelcomeMessage($command->name)
        );
        
        $this->logger->info('Welcome email sent', [
            'email' => $command->email,
        ]);
    }

    private function buildWelcomeMessage(string $name): string
    {
        return "Hello {$name}, welcome to our platform!";
    }
}
```

The key is that handlers encapsulate the logic needed to execute a command, whether that involves models, external
services, or any other operations.

## Registering handlers

Register command handlers with the Dispatcher during application bootstrap:

```php
$dispatcher->registerHandler(
    RegisterStudentCommand::class,
    new HandlerProxy(fn() => $container->get(StudentCommandHandler::class))
);
```

## Adding middleware

Middleware wraps command dispatch to add cross-cutting concerns like logging, validation or security:

```php
$dispatcher = new Dispatcher();

$dispatcher->addMiddleware(
    new MyCommandDispatcherMiddleware()
);
```

Middleware executes in LIFO order. The last registered middleware wraps all previous ones.

## Best practices

**Keep commands simple.** Commands should be data containers with no logic. All validation and business rules belong in
models.

**Use descriptive command names.** Command names should clearly express intent using action verbs.

**Group related commands.** Commands that share dependencies or operate on related concepts can share a handler.

**Handle each command once.** Each command class should map to exactly one handler method.

**Let exceptions propagate.** Domain exceptions should propagate to the dispatcher where middleware can handle them
consistently.

**Keep handlers focused.** Command handlers should coordinate operations, not implement business logic. Business rules
belong in models.

**Make commands serializable.** Commands should only contain scalar types and arrays so they can be serialized for
queuing or logging.

---

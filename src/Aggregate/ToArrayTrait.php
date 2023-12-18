<?php

declare(strict_types=1);

namespace Backslash\Aggregate;

use ReflectionClass;
use RuntimeException;

trait ToArrayTrait
{
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public static function fromArray(array $data): EventInterface
    {
        $class = new ReflectionClass(__CLASS__);

        $args = [];
        $constructor = $class->getConstructor();
        if ($constructor) {
            foreach ($constructor->getParameters() as $parameter) {
                $paramName = $parameter->getName();
                $optional = $parameter->isOptional();
                if (!array_key_exists($paramName, $data) && !$optional) {
                    throw new RuntimeException(
                        sprintf(
                            "No payload value for the constructor argument named '%s'.",
                            $paramName,
                        ),
                    );
                }
                if (array_key_exists($paramName, $data)) {
                    $args[] = $data[$paramName];
                }
            }
        }

        /** @var EventInterface $object */
        $object = $class->newInstanceArgs($args);
        return $object;
    }
}

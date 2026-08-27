<?php

declare(strict_types=1);

namespace Backslash\PdoProjectionStore;

use Backslash\Serializer\SerializerInterface;
use Backslash\Pdo\PdoInterface;
use Backslash\Projection\ProjectionInterface;
use Backslash\ProjectionStore\AdapterInterface;
use Backslash\ProjectionStore\ProjectionNotFoundException;
use Backslash\ProjectionStore\UnitOfWork;
use Generator;
use PDO;
use RuntimeException;

final class PdoProjectionStoreAdapter implements AdapterInterface
{
    private PdoInterface $pdo;

    private SerializerInterface $serializer;

    public function __construct(PdoInterface $pdo, SerializerInterface $serializer)
    {
        $this->pdo = $pdo;
        $this->serializer = $serializer;
    }

    public function find(string $id, string $class): ProjectionInterface
    {
        $sql = 'select projection_payload from projection_store '
            . 'where projection_id = :projectionId and projection_class = :projectionClass';
        $query = $this->pdo->prepare($sql);
        $success = $query->execute(
            [
                ':projectionId' => $id,
                ':projectionClass' => $class,
            ],
        );
        if (!$success) {
            throw new RuntimeException();
        }
        $payload = $this;
        $query->bindColumn(1, $payload, PDO::PARAM_LOB);
        $rows = $query->fetch(PDO::FETCH_BOUND);
        if (!$rows || $payload === null) {
            throw ProjectionNotFoundException::forProjection($id, $class);
        }
        /** @var ProjectionInterface $projection */
        $projection = $this->serializer->deserialize(is_resource($payload) ? stream_get_contents($payload) : $payload);

        return $projection;
    }

    public function findBy(string $class): Generator
    {
        $sql = 'select projection_payload from projection_store where projection_class = :projectionClass';
        $query = $this->pdo->prepare($sql);
        $success = $query->execute(
            [
                ':projectionClass' => $class,
            ],
        );
        if (!$success) {
            throw new RuntimeException();
        }

        $projectionPayload = $this;
        $query->bindColumn(1, $projectionPayload, PDO::PARAM_LOB);

        while ($query->fetch(PDO::FETCH_BOUND)) {
            $projectionPayload = stream_get_contents($projectionPayload);
            $projection = $this->serializer->deserialize($projectionPayload);
            yield $projection;
        }
    }

    public function has(string $id, string $class): bool
    {
        $sql = 'select projection_id, projection_class from projection_store '
            . 'where projection_id = :projectionId and projection_class = :projectionClass';
        $query = $this->pdo->prepare($sql);
        $success = $query->execute(
            [
                ':projectionId' => $id,
                ':projectionClass' => $class,
            ],
        );
        if (!$success) {
            throw new RuntimeException();
        }
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);

        return count($rows) > 0;
    }

    public function commit(UnitOfWork $unit): void
    {
        foreach ($unit->getStored() as $projection) {
            $this->store($projection);
        }
        foreach ($unit->getRemoved() as $descriptor) {
            $this->remove($descriptor->getId(), $descriptor->getClass());
        }
    }

    public function purge(): void
    {
        $this->pdo->exec('delete from projection_store where 1=1');
    }

    private function store(ProjectionInterface $projection): void
    {
        $id = $projection->getId();
        $class = $projection::class;
        $payload = $this->serializer->serialize($projection);
        $this->remove($id, $class);

        $sql = 'insert into projection_store (projection_id,projection_class,projection_payload) '
            . 'values (:projectionId, :projectionClass, :projectionPayload)';
        $query = $this->pdo->prepare($sql);
        $success = $query->execute(
            [
                ':projectionId' => $id,
                ':projectionClass' => $class,
                ':projectionPayload' => $payload,
            ],
        );
        if (!$success) {
            throw new RuntimeException();
        }
    }

    private function remove(string $id, string $class): void
    {
        $sql = 'delete from projection_store where projection_id = :projectionId and projection_class = :projectionClass';
        $query = $this->pdo->prepare($sql);
        $success = $query->execute(
            [
                ':projectionId' => $id,
                ':projectionClass' => $class,
            ],
        );
        if (!$success) {
            throw new RuntimeException();
        }
    }
}

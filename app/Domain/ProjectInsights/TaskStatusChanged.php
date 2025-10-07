<?php

declare(strict_types=1);

namespace App\Domain\ProjectInsights;

use DateTimeImmutable;

/**
 * Domain event emitted whenever a task transitions across workflow states.
 */
final class TaskStatusChanged implements DomainEvent
{
    public function __construct(
        private readonly string $taskId,
        private readonly string $previous,
        private readonly string $next,
        private readonly DateTimeImmutable $occurredAt = new DateTimeImmutable('now')
    ) {
        WorkflowStatus::assertValid($previous);
        WorkflowStatus::assertValid($next);
    }

    public function taskId(): string
    {
        return $this->taskId;
    }

    public function previous(): string
    {
        return $this->previous;
    }

    public function next(): string
    {
        return $this->next;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}

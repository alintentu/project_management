<?php

declare(strict_types=1);

namespace App\Domain\ProjectInsights;

use DateTimeImmutable;

/**
 * Lightweight aggregate optimised for analytics derived from persistent tasks.
 */
final class InsightTask
{
    private string $id;
    private string $title;
    private string $status;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $dueDate;
    private ?DateTimeImmutable $completedAt;
    private array $assignees = [];
    private array $tags = [];
    private ?string $reviewNotes = null;

    public function __construct(
        string $id,
        string $title,
        string $status,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $dueDate = null,
        ?DateTimeImmutable $completedAt = null
    ) {
        WorkflowStatus::assertValid($status);

        $this->id = $id;
        $this->title = $title;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->dueDate = $dueDate;
        $this->completedAt = $completedAt;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function dueDate(): ?DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function reviewersNotes(): ?string
    {
        return $this->reviewNotes;
    }

    /**
     * @return array<int, string>
     */
    public function assignees(): array
    {
        return $this->assignees;
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return $this->tags;
    }

    public function setReviewNotes(?string $notes): void
    {
        $this->reviewNotes = $notes;
    }

    public function assign(string $userId): void
    {
        if (!in_array($userId, $this->assignees, true)) {
            $this->assignees[] = $userId;
        }
    }

    public function tag(string $tag): void
    {
        if (!in_array($tag, $this->tags, true)) {
            $this->tags[] = strtolower($tag);
        }
    }

    public function moveTo(string $status, ?DateTimeImmutable $occurredAt = null): void
    {
        WorkflowStatus::assertValid($status);
        $this->status = $status;

        if ($status === WorkflowStatus::DONE && $this->completedAt === null) {
            $this->completedAt = $occurredAt ?? new DateTimeImmutable('now');
        }
    }

    /**
     * Returns a value >1 when overdue, ~1 when exactly on plan, <1 when ahead.
     */
    public function estimateTrend(): float
    {
        if ($this->dueDate === null) {
            return 1.0;
        }

        $total = max($this->dueDate->getTimestamp() - $this->createdAt->getTimestamp(), 1);
        $elapsed = max((new DateTimeImmutable('now'))->getTimestamp() - $this->createdAt->getTimestamp(), 0);

        return $elapsed / $total;
    }
}

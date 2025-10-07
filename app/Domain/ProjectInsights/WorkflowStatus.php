<?php

declare(strict_types=1);

namespace App\Domain\ProjectInsights;

use App\Enums\TaskStatus as TaskStatusEnum;
use InvalidArgumentException;

/**
 * Pure workflow status helper decoupled from the Eloquent-backed enum.
 */
final class WorkflowStatus
{
    public const BACKLOG = 'backlog';
    public const IN_PROGRESS = 'in_progress';
    public const IN_REVIEW = 'in_review';
    public const DONE = 'done';

    private function __construct()
    {
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::BACKLOG,
            self::IN_PROGRESS,
            self::IN_REVIEW,
            self::DONE,
        ];
    }

    public static function assertValid(string $status): void
    {
        if (!in_array($status, self::all(), true)) {
            throw new InvalidArgumentException("Unknown workflow status: {$status}");
        }
    }

    public static function progressOrder(string $status): int
    {
        self::assertValid($status);

        return match ($status) {
            self::BACKLOG => 0,
            self::IN_PROGRESS => 1,
            self::IN_REVIEW => 2,
            self::DONE => 3,
        };
    }

    public static function fromEnum(TaskStatusEnum $enum): string
    {
        return $enum->value;
    }

    public static function toEnum(string $status): TaskStatusEnum
    {
        self::assertValid($status);

        return TaskStatusEnum::from($status);
    }
}

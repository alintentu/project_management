<?php

namespace App\Observers;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TaskStatusTransition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class TaskObserver
{
    public function created(Task $task): void
    {
        if (! Schema::hasTable('task_status_transitions')) {
            return;
        }

        $currentStatus = self::statusValue($task->status);

        if ($currentStatus === null) {
            return;
        }

        $this->storeTransition(
            $task,
            null,
            $currentStatus,
            ['event' => 'created']
        );
    }

    public function updated(Task $task): void
    {
        if (! Schema::hasTable('task_status_transitions')) {
            return;
        }

        if (! $task->wasChanged('status')) {
            return;
        }

        $previousStatus = self::statusString($task->getOriginal('status'));
        $currentStatus = self::statusValue($task->status);

        if ($previousStatus === $currentStatus) {
            return;
        }

        $this->storeTransition(
            $task,
            $previousStatus,
            $currentStatus,
            ['event' => 'status_changed']
        );
    }

    protected function metadata(array $extra = []): ?array
    {
        $userId = Auth::id();
        $data = $extra;

        if ($userId !== null) {
            $data['user_id'] = $userId;
        }

        return $data === [] ? null : $data;
    }

    protected static function statusValue(mixed $value): ?string
    {
        if ($value instanceof TaskStatus) {
            return $value->value;
        }

        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return self::statusString($value);
    }

    protected static function statusString(mixed $value): ?string
    {
        if ($value instanceof TaskStatus) {
            return $value->value;
        }

        if (is_string($value)) {
            return $value;
        }

        if ($value === null) {
            return null;
        }

        // When the original attribute is stored as an enum backed value.
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return (string) $value;
    }

    private function storeTransition(Task $task, ?string $from, string $to, array $extraMeta): void
    {
        try {
            TaskStatusTransition::create([
                'task_id' => $task->id,
                'project_id' => $task->project_id,
                'from_status' => $from,
                'to_status' => $to,
                'occurred_at' => now(),
                'metadata' => $this->metadata($extraMeta),
            ]);
        } catch (\Throwable $exception) {
            if (str_contains($exception->getMessage(), 'no such table')) {
                return;
            }

            throw $exception;
        }
    }
}

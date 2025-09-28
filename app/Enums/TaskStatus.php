<?php

namespace App\Enums;

enum TaskStatus: string
{
    case BACKLOG = 'backlog';
    case IN_PROGRESS = 'in_progress';
    case IN_REVIEW = 'in_review';
    case DONE = 'done';

    public function label(): string
    {
        return match ($this) {
            self::BACKLOG => 'Backlog',
            self::IN_PROGRESS => 'In lucru',
            self::IN_REVIEW => 'In verificare',
            self::DONE => 'Terminat',
        };
    }
}

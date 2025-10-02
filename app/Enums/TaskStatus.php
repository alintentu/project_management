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

    public function theme(): array
    {
        return match ($this) {
            self::BACKLOG => [
                'icon' => '🗂️',
                'accent' => 'border-l-4 border-slate-300',
                'card' => 'bg-slate-50',
                'chip' => 'bg-slate-200 text-slate-700',
                'badge' => 'bg-slate-100 text-slate-600',
                'dot' => 'bg-slate-400',
                'ring' => 'ring-slate-300',
                'text' => 'text-slate-700',
                'header' => 'bg-slate-50',
            ],
            self::IN_PROGRESS => [
                'icon' => '⚙️',
                'accent' => 'border-l-4 border-blue-500',
                'card' => 'bg-blue-50',
                'chip' => 'bg-blue-100 text-blue-700',
                'badge' => 'bg-blue-100 text-blue-600',
                'dot' => 'bg-blue-500',
                'ring' => 'ring-blue-300',
                'text' => 'text-blue-700',
                'header' => 'bg-blue-50',
            ],
            self::IN_REVIEW => [
                'icon' => '🧐',
                'accent' => 'border-l-4 border-amber-400',
                'card' => 'bg-amber-50',
                'chip' => 'bg-amber-100 text-amber-700',
                'badge' => 'bg-amber-100 text-amber-700',
                'dot' => 'bg-amber-500',
                'ring' => 'ring-amber-300',
                'text' => 'text-amber-700',
                'header' => 'bg-amber-50',
            ],
            self::DONE => [
                'icon' => '✅',
                'accent' => 'border-l-4 border-emerald-500',
                'card' => 'bg-emerald-50',
                'chip' => 'bg-emerald-100 text-emerald-700',
                'badge' => 'bg-emerald-100 text-emerald-700',
                'dot' => 'bg-emerald-500',
                'ring' => 'ring-emerald-300',
                'text' => 'text-emerald-700',
                'header' => 'bg-emerald-50',
            ],
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\ProjectInsights;

/**
 * Extremely light in-memory event dispatcher powering dashboards and listeners.
 */
final class DomainEvents
{
    /** @var array<class-string<DomainEvent>, callable[]> */
    private array $listeners = [];

    public function listen(string $eventClass, callable $handler): void
    {
        $this->listeners[$eventClass][] = $handler;
    }

    public function dispatch(DomainEvent $event): void
    {
        foreach ($this->listeners[$event::class] ?? [] as $handler) {
            $handler($event);
        }
    }
}

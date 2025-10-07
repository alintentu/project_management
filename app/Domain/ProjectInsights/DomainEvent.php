<?php

declare(strict_types=1);

namespace App\Domain\ProjectInsights;

use DateTimeImmutable;

interface DomainEvent
{
    public function occurredAt(): DateTimeImmutable;
}

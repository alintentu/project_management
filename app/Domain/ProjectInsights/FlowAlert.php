<?php

declare(strict_types=1);

namespace App\Domain\ProjectInsights;

/**
 * Lightweight value object describing actionable flow alerts.
 */
final class FlowAlert
{
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_CRITICAL = 'critical';

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private readonly string $type,
        private readonly string $message,
        private readonly string $severity = self::SEVERITY_INFO,
        private readonly array $context = []
    ) {
        if (!in_array($severity, [self::SEVERITY_INFO, self::SEVERITY_WARNING, self::SEVERITY_CRITICAL], true)) {
            throw new \InvalidArgumentException("Unknown flow alert severity: {$severity}");
        }
    }

    public function type(): string
    {
        return $this->type;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function severity(): string
    {
        return $this->severity;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'message' => $this->message(),
            'severity' => $this->severity(),
            'context' => $this->context(),
        ];
    }
}

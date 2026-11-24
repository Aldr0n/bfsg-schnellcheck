<?php

namespace App\Enums;

enum AuditStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Done = 'done';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In der Warteschlange',
            self::Running => 'Läuft',
            self::Done => 'Fertig',
            self::Failed => 'Fehlgeschlagen',
            self::Cancelled => 'Abgebrochen',
        };
    }

    /**
     * The statuses an audit never leaves again.
     *
     * @return array<int, self>
     */
    public static function finishedCases(): array
    {
        return [self::Done, self::Failed, self::Cancelled];
    }

    public function isFinished(): bool
    {
        return in_array($this, self::finishedCases(), true);
    }
}

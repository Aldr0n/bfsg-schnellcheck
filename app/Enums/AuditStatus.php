<?php

namespace App\Enums;

enum AuditStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Done = 'done';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In der Warteschlange',
            self::Running => 'Läuft',
            self::Done => 'Fertig',
            self::Failed => 'Fehlgeschlagen',
        };
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Done, self::Failed], true);
    }
}

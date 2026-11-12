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
            self::Pending => 'Wartet',
            self::Running => 'Läuft',
            self::Done => 'Fertig',
            self::Failed => 'Fehlgeschlagen',
        };
    }
}

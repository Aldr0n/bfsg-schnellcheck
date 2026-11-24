<?php

namespace App\Repositories;

use App\Enums\AuditStatus;
use App\Models\Audit;
use Illuminate\Contracts\Database\Eloquent\Builder;

class AuditRepository
{
    /**
     * Whether the audit is cancelled in the database. Reads the row again, because the job holds a copy from when it was queued.
     */
    public function hasBeenCancelled(Audit $audit): bool
    {
        return Audit::query()->whereKey($audit->getKey())->where('status', AuditStatus::Cancelled)->exists();
    }

    /**
     * The audit with its findings in the order they were stored.
     */
    public function auditWithFindings(Audit $audit): Audit
    {
        return $audit->load([
            'findings' => function (Builder $query) {
                $query->orderBy('id');
            },
        ]);
    }
}

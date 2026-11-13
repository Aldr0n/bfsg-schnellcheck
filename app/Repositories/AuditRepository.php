<?php

namespace App\Repositories;

use App\Models\Audit;
use Illuminate\Contracts\Database\Eloquent\Builder;

class AuditRepository
{
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

<?php

namespace App\Repositories;

use App\Models\Audit;
use Illuminate\Contracts\Database\Eloquent\Builder;

class AuditRepository
{
    /**
     * The audit with its findings ordered by rule.
     */
    public function auditWithFindingsByRule(Audit $audit): Audit
    {
        return $audit->load([
            'findings' => function (Builder $query) {
                $query->orderBy('accessibility_rule')->orderBy('id');
            },
        ]);
    }
}

<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RunAudit implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 90;

    public bool $failOnTimeout = true;

    public function __construct(public readonly Audit $audit) {}

    public function handle(AuditService $auditService): void
    {
        $auditService->run($this->audit);
    }

    public function failed(Throwable $exception): void
    {
        app(AuditService::class)->markFailed($this->audit, 'Die Prüfung ist fehlgeschlagen.');
    }
}

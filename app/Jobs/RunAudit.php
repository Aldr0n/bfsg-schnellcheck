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

    // Must stay below the worker's 60 s timeout and the queue's retry_after, or failed() never runs.
    public int $timeout = 50;

    public bool $failOnTimeout = true;

    public function __construct(public readonly Audit $audit) {}

    public function handle(AuditService $auditService): void
    {
        $auditService->run($this->audit);
    }

    public function failed(Throwable $exception): void
    {
        app(AuditService::class)->markFailed($this->audit, 'Beim Prüfen ist ein unerwarteter Fehler aufgetreten. Versuch es später noch einmal.');
    }
}

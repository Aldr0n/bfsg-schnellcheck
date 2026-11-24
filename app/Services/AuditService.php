<?php

namespace App\Services;

use App\Enums\AuditStatus;
use App\Exceptions\UrlFetchException;
use App\Jobs\RunAudit;
use App\Models\Audit;
use App\Repositories\AuditRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Sleep;

class AuditService
{
    // Together with the 10 s fetch timeout this stays below the job timeout of 50 s.
    private const START_DELAY_SECONDS = 30;

    public function __construct(private readonly UrlFetchService $urlFetchService, private readonly AccessibilityCheckService $accessibilityCheckService, private readonly AuditRepository $auditRepository) {}

    /**
     * Stores a pending audit and queues its run.
     */
    public function start(string $url): Audit
    {
        $audit = Audit::create(['url' => $url, 'status' => AuditStatus::Pending]);

        RunAudit::dispatch($audit);

        return $audit;
    }

    /**
     * Fetches the page, stores the findings and ends as done, or as failed with the reason of the failed fetch.
     * Stops without a result as soon as the audit is cancelled, before or during the run.
     */
    public function run(Audit $audit): void
    {
        // Only a pending audit starts, so a cancel that came in while the job was queued is never overwritten.
        if (Audit::query()->whereKey($audit->getKey())->where('status', AuditStatus::Pending)->update(['status' => AuditStatus::Running]) === 0) {
            return;
        }

        if (! $this->waitsOutStartDelay($audit)) {
            return;
        }

        try {
            $html = $this->urlFetchService->fetchHtml($audit->url);
        } catch (UrlFetchException $exception) {
            $this->markFailed($audit, $exception->getMessage());

            return;
        }

        $result = $this->accessibilityCheckService->checkHtml($html);

        DB::transaction(function () use ($audit, $result) {
            // The user may have cancelled while the page was fetched and checked.
            if ($this->auditRepository->hasBeenCancelled($audit)) {
                return;
            }

            $audit->findings()->saveMany($result['findings']);
            $audit->update([
                'status' => AuditStatus::Done,
                'checked_counts' => $result['checkedCounts'],
                'finding_counts' => $result['findingCounts'],
            ]);
        });
    }

    /**
     * Ends an audit with the reason, so nobody waits for it forever. A cancelled audit stays cancelled.
     */
    public function markFailed(Audit $audit, string $errorMessage): void
    {
        Audit::unfinished()->whereKey($audit->getKey())->update(['status' => AuditStatus::Failed, 'error_message' => $errorMessage]);
    }

    /**
     * Cancels an audit that is still pending or running. A finished audit stays as it is.
     */
    public function cancel(Audit $audit): void
    {
        Audit::unfinished()->whereKey($audit->getKey())->update(['status' => AuditStatus::Cancelled]);
    }

    /**
     * Waits one second at a time and returns false as soon as the audit is cancelled. The wait keeps the running
     * state visible long enough to cancel.
     */
    private function waitsOutStartDelay(Audit $audit): bool
    {
        for ($second = 0; $second < self::START_DELAY_SECONDS; $second++) {
            Sleep::for(1)->second();

            if ($this->auditRepository->hasBeenCancelled($audit)) {
                return false;
            }
        }

        return true;
    }
}

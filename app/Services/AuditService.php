<?php

namespace App\Services;

use App\Enums\AuditStatus;
use App\Exceptions\UrlFetchException;
use App\Jobs\RunAudit;
use App\Models\Audit;
use Illuminate\Support\Facades\DB;

class AuditService
{
    public function __construct(private readonly UrlFetchService $urlFetchService, private readonly AccessibilityCheckService $accessibilityCheckService) {}

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
     */
    public function run(Audit $audit): void
    {
        $audit->update(['status' => AuditStatus::Running]);

        try {
            $html = $this->urlFetchService->fetchHtml($audit->url);
        } catch (UrlFetchException $exception) {
            $this->markFailed($audit, $exception->getMessage());

            return;
        }

        $result = $this->accessibilityCheckService->checkHtml($html);

        DB::transaction(function () use ($audit, $result) {
            $audit->findings()->saveMany($result['findings']);
            $audit->update([
                'status' => AuditStatus::Done,
                'checked_counts' => $result['checkedCounts'],
                'finding_counts' => $result['findingCounts'],
            ]);
        });
    }

    /**
     * Ends an audit with the reason, so nobody waits for it forever.
     */
    public function markFailed(Audit $audit, string $errorMessage): void
    {
        $audit->update(['status' => AuditStatus::Failed, 'error_message' => $errorMessage]);
    }
}

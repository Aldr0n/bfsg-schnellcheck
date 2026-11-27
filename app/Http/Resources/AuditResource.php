<?php

namespace App\Http\Resources;

use App\Enums\AccessibilityRule;
use App\Enums\AccessibilityRuleResult;
use App\Enums\AuditStatus;
use App\Models\Audit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Audit
 */
class AuditResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'statusLabel' => $this->status->label(),
            'errorMessage' => $this->error_message,
            'isFinished' => $this->status->isFinished(),
            'isCancelled' => $this->status === AuditStatus::Cancelled,
            'findingGroups' => $this->whenLoaded('findings', fn () => $this->findingGroups()),
            'ruleSummaries' => $this->whenLoaded('findings', fn () => $this->ruleSummaries()),
        ];
    }

    /**
     * One summary per rule in case order, or null for audits without stored counts.
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function ruleSummaries(): ?array
    {
        if ($this->checked_counts === null) {
            return null;
        }

        return collect(AccessibilityRule::cases())
            ->map(fn (AccessibilityRule $rule) => $this->ruleSummary($rule))
            ->all();
    }

    /**
     * The real number of findings of the rule. Audits stored before the counts existed only have their stored findings.
     */
    private function findingCount(AccessibilityRule $rule): int
    {
        return $this->finding_counts[$rule->value]
            ?? $this->findings->where('accessibility_rule', $rule)->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function ruleSummary(AccessibilityRule $rule): array
    {
        $checkedCount = $this->checked_counts[$rule->value] ?? 0;
        $findingCount = $this->findingCount($rule);

        return [
            'accessibilityRule' => $rule->value,
            'label' => $rule->label(),
            'checkedCount' => $checkedCount,
            'findingCount' => $findingCount,
            'result' => AccessibilityRuleResult::fromCounts($checkedCount, $findingCount)->value,
            'summarySentence' => $rule->summarySentence($checkedCount, $findingCount),
        ];
    }

    /**
     * One group per rule with findings, in case order like the summaries.
     *
     * @return array<int, array<string, mixed>>
     */
    private function findingGroups(): array
    {
        return collect(AccessibilityRule::cases())
            ->filter(fn (AccessibilityRule $rule) => $this->findings->contains('accessibility_rule', $rule))
            ->map(fn (AccessibilityRule $rule) => $this->findingGroup($rule))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function findingGroup(AccessibilityRule $rule): array
    {
        return [
            'accessibilityRule' => $rule->value,
            'label' => $rule->label(),
            'wcagCriteria' => $rule->wcagCriteria(),
            'explanation' => $rule->explanation(),
            'findingCount' => $this->findingCount($rule),
            'snippets' => $this->findings->where('accessibility_rule', $rule)->pluck('snippet')->all(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Enums\AccessibilityRule;
use App\Enums\AccessibilityRuleResult;
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
            'url' => $this->url,
            'statusLabel' => $this->status->label(),
            'errorMessage' => $this->error_message,
            'isFinished' => $this->status->isFinished(),
            'findingGroups' => $this->whenLoaded('findings', fn () => $this->findingGroups()),
            'ruleSummaries' => $this->whenLoaded('findings', fn () => $this->ruleSummaries()),
        ];
    }

    /**
     * One summary per rule in case order, or null for audits without stored counts.
     *
     * @return list<array<string, mixed>>|null
     */
    private function ruleSummaries(): ?array
    {
        if ($this->checked_counts === null) {
            return null;
        }

        return array_map(fn (AccessibilityRule $rule) => $this->ruleSummary($rule, $this->checked_counts[$rule->value] ?? 0, $this->findingCountOf($rule)), AccessibilityRule::cases());
    }

    /**
     * The real number of findings of the rule. Audits stored before the counts existed only have their stored findings.
     */
    private function findingCountOf(AccessibilityRule $rule): int
    {
        return $this->finding_counts[$rule->value]
            ?? $this->findings->where('accessibility_rule', $rule)->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function ruleSummary(AccessibilityRule $rule, int $checkedCount, int $findingCount): array
    {
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
     * @return list<array<string, mixed>>
     */
    private function findingGroups(): array
    {
        $groups = [];

        foreach ($this->findings->groupBy('accessibility_rule') as $ruleFindings) {
            $groups[] = $this->findingGroup($ruleFindings->first()->accessibility_rule, $ruleFindings->pluck('snippet')->all());
        }

        return $groups;
    }

    /**
     * @param  array<mixed>  $snippets
     * @return array<string, mixed>
     */
    private function findingGroup(AccessibilityRule $rule, array $snippets): array
    {
        return [
            'accessibilityRule' => $rule->value,
            'label' => $rule->label(),
            'wcagCriteria' => $rule->wcagCriteria(),
            'explanation' => $rule->explanation(),
            'findingCount' => $this->findingCountOf($rule),
            'snippets' => $snippets,
        ];
    }
}

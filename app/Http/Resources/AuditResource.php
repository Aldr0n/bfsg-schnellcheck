<?php

namespace App\Http\Resources;

use App\Enums\AccessibilityRule;
use App\Enums\AccessibilityRuleResult;
use App\Models\Audit;
use App\Models\Finding;
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

        $findingCounts = $this->findings->countBy(fn (Finding $finding) => $finding->accessibility_rule->value);
        $summaries = [];

        foreach (AccessibilityRule::cases() as $rule) {
            $summaries[] = $this->ruleSummary($rule, $this->checked_counts[$rule->value] ?? 0, $findingCounts->get($rule->value, 0));
        }

        return $summaries;
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
            'snippets' => $snippets,
        ];
    }
}

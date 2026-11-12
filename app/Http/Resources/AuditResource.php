<?php

namespace App\Http\Resources;

use App\Enums\AccessibilityRule;
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

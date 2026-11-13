<?php

namespace App\Enums;

enum AccessibilityRuleResult: string
{
    case Passed = 'passed';
    case Partial = 'partial';
    case Failed = 'failed';
    case NotApplicable = 'not_applicable';

    /**
     * A rule that checks a single element, like the page language, is either fine or failed, never partial.
     */
    public static function fromCounts(int $checkedCount, int $findingCount): self
    {
        if ($checkedCount === 0) {
            return self::NotApplicable;
        }

        if ($findingCount === 0) {
            return self::Passed;
        }

        if ($checkedCount > 1 && $findingCount * 2 <= $checkedCount) {
            return self::Partial;
        }

        return self::Failed;
    }
}

import { CheckIcon, MinusIcon, XIcon } from 'lucide-react';
import type { RuleSummary } from '@/types/audit';

export function RuleRingCenter({ ruleSummary }: { ruleSummary: RuleSummary }) {
    const { checkedCount, findingCount, result } = ruleSummary;

    if (result === 'not_applicable') {
        return <MinusIcon className="size-6" aria-hidden="true" />;
    }

    if (checkedCount === 1) {
        return findingCount === 0 ? (
            <CheckIcon className="size-7" aria-hidden="true" />
        ) : (
            <XIcon className="size-7" aria-hidden="true" />
        );
    }

    // Rounded down, so a ring with findings never reads 100 %.
    const passedPercent = Math.floor(
        ((checkedCount - findingCount) / checkedCount) * 100,
    );

    return (
        <span className="text-lg font-semibold">{passedPercent}&#8239;%</span>
    );
}

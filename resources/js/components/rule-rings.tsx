import { RuleRing } from '@/components/rule-ring';
import type { RuleSummary } from '@/types/audit';

type RuleRingsProps = {
    ruleSummaries: RuleSummary[];
    onShowFindings: (accessibilityRule: string) => void;
};

export function RuleRings({ ruleSummaries, onShowFindings }: RuleRingsProps) {
    return (
        <section
            aria-labelledby="overview-heading"
            className="flex flex-col gap-4"
        >
            <h2 id="overview-heading" className="text-xl font-semibold">
                Übersicht
            </h2>
            <ul className="grid grid-cols-1 gap-3 sm:grid-cols-5">
                {ruleSummaries.map((ruleSummary) => (
                    <RuleRing
                        key={ruleSummary.accessibilityRule}
                        ruleSummary={ruleSummary}
                        onShowFindings={onShowFindings}
                    />
                ))}
            </ul>
        </section>
    );
}

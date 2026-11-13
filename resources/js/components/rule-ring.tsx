import { cva } from 'class-variance-authority';
import { RuleRingCenter } from '@/components/rule-ring-center';
import { Button } from '@/components/ui/button';
import type { RuleSummary } from '@/types/audit';

const RADIUS = 40;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;

const ringColor = cva('', {
    variants: {
        result: {
            passed: 'text-success',
            partial: 'text-warning',
            failed: 'text-destructive',
            not_applicable: 'text-muted-foreground',
        },
    },
});

type RuleRingProps = {
    ruleSummary: RuleSummary;
    onShowFindings: (accessibilityRule: string) => void;
};

export function RuleRing({ ruleSummary, onShowFindings }: RuleRingProps) {
    const { checkedCount, findingCount, result } = ruleSummary;
    const dashOffset = CIRCUMFERENCE * (findingCount / checkedCount);

    return (
        <li className="flex items-center gap-4 rounded-lg border p-4 sm:flex-col sm:text-center">
            <div
                className={ringColor({
                    result,
                    className:
                        'relative flex size-24 shrink-0 items-center justify-center',
                })}
            >
                <svg
                    viewBox="0 0 100 100"
                    className="absolute inset-0 size-full -rotate-90"
                    aria-hidden="true"
                >
                    <circle
                        cx="50"
                        cy="50"
                        r={RADIUS}
                        fill="none"
                        strokeWidth="10"
                        className="stroke-muted"
                    />
                    {checkedCount > 0 && (
                        <circle
                            cx="50"
                            cy="50"
                            r={RADIUS}
                            fill="none"
                            strokeWidth="10"
                            strokeLinecap="butt"
                            strokeDasharray={CIRCUMFERENCE}
                            strokeDashoffset={dashOffset}
                            className="stroke-current"
                        />
                    )}
                </svg>
                <RuleRingCenter ruleSummary={ruleSummary} />
            </div>
            <div className="flex flex-col items-start gap-1 sm:items-center">
                <h3 className="font-medium">{ruleSummary.label}</h3>
                <p className="text-sm text-muted-foreground">
                    {ruleSummary.summarySentence}
                </p>
                {findingCount > 0 && (
                    <Button
                        type="button"
                        variant="link"
                        size="sm"
                        className="h-auto px-0"
                        aria-label={`Funde ansehen: ${ruleSummary.label}`}
                        onClick={() =>
                            onShowFindings(ruleSummary.accessibilityRule)
                        }
                    >
                        Funde ansehen
                    </Button>
                )}
            </div>
        </li>
    );
}

export type FindingGroup = {
    accessibilityRule: string;
    label: string;
    wcagCriteria: string[];
    explanation: string;
    findingCount: number;
    snippets: string[];
};

export type AccessibilityRuleResult =
    | 'passed'
    | 'partial'
    | 'failed'
    | 'not_applicable';

export type RuleSummary = {
    accessibilityRule: string;
    label: string;
    checkedCount: number;
    findingCount: number;
    result: AccessibilityRuleResult;
    summarySentence: string;
};

export type Audit = {
    url: string;
    statusLabel: string;
    errorMessage: string | null;
    isFinished: boolean;
    findingGroups: FindingGroup[];
    ruleSummaries: RuleSummary[] | null;
};

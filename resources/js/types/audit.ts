export type FindingGroup = {
    accessibilityRule: string;
    label: string;
    wcagCriteria: string[];
    explanation: string;
    snippets: string[];
};

export type Audit = {
    url: string;
    statusLabel: string;
    errorMessage: string | null;
    isFinished: boolean;
    findingGroups: FindingGroup[];
};

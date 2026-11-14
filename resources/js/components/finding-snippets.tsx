import { useId, useState } from 'react';
import { Button } from '@/components/ui/button';

const COLLAPSED_SNIPPET_COUNT = 5;

type FindingSnippetsProps = {
    snippets: string[];
    findingCount: number;
};

export function FindingSnippets({
    snippets,
    findingCount,
}: FindingSnippetsProps) {
    const listId = useId();
    const [isExpanded, setIsExpanded] = useState(false);

    const hasMoreSnippets = snippets.length > COLLAPSED_SNIPPET_COUNT;
    const visibleSnippets = isExpanded
        ? snippets
        : snippets.slice(0, COLLAPSED_SNIPPET_COUNT);
    const hasUnstoredFindings = findingCount > snippets.length;

    return (
        <div className="flex flex-col gap-2">
            <ul id={listId} className="flex flex-col gap-2">
                {visibleSnippets.map((snippet, index) => (
                    <li key={index}>
                        <pre
                            tabIndex={0}
                            className="overflow-x-auto rounded-md bg-muted p-3 text-xs text-foreground"
                        >
                            <code>{snippet}</code>
                        </pre>
                    </li>
                ))}
            </ul>
            {hasUnstoredFindings && (
                <p className="text-sm text-muted-foreground">
                    Der Bericht zeigt die ersten {snippets.length} von{' '}
                    {findingCount} Funden.
                </p>
            )}
            {hasMoreSnippets && (
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="self-start"
                    aria-expanded={isExpanded}
                    aria-controls={listId}
                    onClick={() => setIsExpanded(!isExpanded)}
                >
                    {isExpanded
                        ? 'Weniger anzeigen'
                        : `Alle ${snippets.length} anzeigen`}
                </Button>
            )}
        </div>
    );
}

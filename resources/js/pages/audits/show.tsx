import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { AuditPoller } from '@/components/audit-poller';
import { FindingSnippets } from '@/components/finding-snippets';
import { RuleRings } from '@/components/rule-rings';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import type { Audit } from '@/types/audit';

type Props = {
    audit: Audit;
};

function statusText(audit: Audit): string {
    if (!audit.isFinished) {
        return 'Die Seite wird geprüft …';
    }

    // A failed audit is announced by the alert, which has role="alert" itself.
    return audit.errorMessage ? '' : 'Die Prüfung ist fertig.';
}

function findingGroupId(accessibilityRule: string): string {
    return `findings-${accessibilityRule}`;
}

export default function Show({ audit }: Props) {
    const [openRules, setOpenRules] = useState<string[]>([]);

    function showFindings(accessibilityRule: string) {
        setOpenRules((current) =>
            current.includes(accessibilityRule)
                ? current
                : [...current, accessibilityRule],
        );
        document
            .getElementById(findingGroupId(accessibilityRule))
            ?.scrollIntoView();
    }

    return (
        <>
            <Head title={`Bericht für ${audit.url}`} />
            {!audit.isFinished && <AuditPoller />}
            <main className="mx-auto flex min-h-screen w-full max-w-3xl flex-col gap-6 bg-background p-6 text-foreground">
                <header className="flex flex-col gap-2">
                    <h1 className="text-2xl font-semibold">Bericht</h1>
                    <p className="break-all text-muted-foreground">
                        {audit.url}
                    </p>
                    <div>
                        <Badge variant="secondary">{audit.statusLabel}</Badge>
                    </div>
                </header>

                <Alert role="note">
                    <AlertTitle>
                        Was dieser Bericht nicht leisten kann
                    </AlertTitle>
                    <AlertDescription>
                        Automatische Checks finden nur einen Teil der Probleme.
                        Geprüft wird nur das HTML, das der Server für diese eine
                        Seite ausliefert. Der Bericht ist keine rechtliche
                        Prüfung.
                    </AlertDescription>
                </Alert>

                <p role="status" className={cn(audit.isFinished && 'sr-only')}>
                    {statusText(audit)}
                </p>

                {audit.errorMessage && (
                    <Alert variant="destructive">
                        <AlertTitle>Die Prüfung ist fehlgeschlagen</AlertTitle>
                        <AlertDescription>
                            {audit.errorMessage}
                        </AlertDescription>
                    </Alert>
                )}

                {audit.isFinished && !audit.errorMessage && (
                    <>
                        {audit.ruleSummaries !== null && (
                            <RuleRings
                                ruleSummaries={audit.ruleSummaries}
                                onShowFindings={showFindings}
                            />
                        )}

                        <section
                            aria-labelledby="findings-heading"
                            className="flex flex-col gap-4"
                        >
                            <h2
                                id="findings-heading"
                                className="text-xl font-semibold"
                            >
                                Funde
                            </h2>
                            {audit.findingGroups.length === 0 ? (
                                <p>Die fünf Regeln haben nichts gefunden.</p>
                            ) : (
                                <Accordion
                                    type="multiple"
                                    value={openRules}
                                    onValueChange={setOpenRules}
                                >
                                    {audit.findingGroups.map((group) => (
                                        <AccordionItem
                                            key={group.accessibilityRule}
                                            id={findingGroupId(
                                                group.accessibilityRule,
                                            )}
                                            value={group.accessibilityRule}
                                        >
                                            <AccordionTrigger>
                                                <span className="flex items-center gap-2">
                                                    {group.label}
                                                    <Badge variant="outline">
                                                        {group.findingCount ===
                                                        1
                                                            ? '1 Fund'
                                                            : `${group.findingCount} Funde`}
                                                    </Badge>
                                                </span>
                                            </AccordionTrigger>
                                            <AccordionContent className="flex flex-col gap-3">
                                                <p className="text-muted-foreground">
                                                    WCAG{' '}
                                                    {group.wcagCriteria.join(
                                                        ', ',
                                                    )}
                                                </p>
                                                <p>{group.explanation}</p>
                                                <FindingSnippets
                                                    snippets={group.snippets}
                                                    findingCount={
                                                        group.findingCount
                                                    }
                                                />
                                            </AccordionContent>
                                        </AccordionItem>
                                    ))}
                                </Accordion>
                            )}
                        </section>
                    </>
                )}

                <div>
                    <Button asChild variant="outline">
                        <Link href={home()}>Neue Prüfung</Link>
                    </Button>
                </div>
            </main>
        </>
    );
}

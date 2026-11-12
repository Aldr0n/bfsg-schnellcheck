import { Head, Link } from '@inertiajs/react';
import { AuditPoller } from '@/components/audit-poller';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import type { Audit } from '@/types/audit';

type Props = {
    audit: Audit;
};

function statusText(audit: Audit): string {
    if (!audit.isFinished) {
        return 'Die Seite wird geprüft …';
    }

    return audit.errorMessage
        ? 'Die Prüfung ist fehlgeschlagen.'
        : 'Die Prüfung ist fertig.';
}

export default function Show({ audit }: Props) {
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

                <p role="status">{statusText(audit)}</p>

                {audit.errorMessage && (
                    <Alert variant="destructive">
                        <AlertTitle>Die Prüfung ist fehlgeschlagen</AlertTitle>
                        <AlertDescription>
                            {audit.errorMessage}
                        </AlertDescription>
                    </Alert>
                )}

                {audit.isFinished && !audit.errorMessage && (
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
                            <Accordion type="multiple">
                                {audit.findingGroups.map((group) => (
                                    <AccordionItem
                                        key={group.accessibilityRule}
                                        value={group.accessibilityRule}
                                    >
                                        <AccordionTrigger>
                                            <span className="flex items-center gap-2">
                                                {group.label}
                                                <Badge variant="outline">
                                                    {group.snippets.length === 1
                                                        ? '1 Fund'
                                                        : `${group.snippets.length} Funde`}
                                                </Badge>
                                            </span>
                                        </AccordionTrigger>
                                        <AccordionContent className="flex flex-col gap-3">
                                            <p className="text-muted-foreground">
                                                WCAG{' '}
                                                {group.wcagCriteria.join(', ')}
                                            </p>
                                            <p>{group.explanation}</p>
                                            <ul className="flex flex-col gap-2">
                                                {group.snippets.map(
                                                    (snippet, index) => (
                                                        <li key={index}>
                                                            <pre
                                                                tabIndex={0}
                                                                className="overflow-x-auto rounded-md bg-muted p-3 text-xs text-foreground"
                                                            >
                                                                <code>
                                                                    {snippet}
                                                                </code>
                                                            </pre>
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        </AccordionContent>
                                    </AccordionItem>
                                ))}
                            </Accordion>
                        )}
                    </section>
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

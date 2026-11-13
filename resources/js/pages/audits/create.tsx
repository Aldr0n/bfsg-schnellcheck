import { Form, Head } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/AuditController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function Create() {
    return (
        <>
            <Head title="Prüfung starten" />
            <main className="mx-auto flex min-h-screen w-full max-w-xl flex-col justify-center gap-6 bg-background p-6 text-foreground">
                <div className="flex flex-col gap-2">
                    <h1 className="text-3xl font-semibold">
                        BFSG-Schnellcheck
                    </h1>
                    <p className="text-muted-foreground">
                        Prüft eine Webseite auf Barrierefreiheitsfehler, die
                        sich automatisch finden lassen.
                    </p>
                </div>
                <Form
                    {...store.form()}
                    noValidate
                    className="flex flex-col gap-4"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="url">Adresse der Seite</Label>
                                <Input
                                    id="url"
                                    name="url"
                                    type="url"
                                    required
                                    placeholder="https://example.com"
                                    aria-invalid={Boolean(errors.url)}
                                    aria-describedby={
                                        errors.url ? 'url-error' : undefined
                                    }
                                />
                                {errors.url && (
                                    <p
                                        id="url-error"
                                        role="alert"
                                        className="text-sm text-destructive"
                                    >
                                        {errors.url}
                                    </p>
                                )}
                            </div>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Wird gesendet …' : 'Prüfen'}
                            </Button>
                        </>
                    )}
                </Form>
            </main>
        </>
    );
}

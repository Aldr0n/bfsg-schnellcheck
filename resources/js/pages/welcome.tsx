export default function Welcome() {
    return (
        <main className="flex min-h-screen flex-col items-center justify-center gap-4 bg-background p-6 text-center text-foreground">
            <h1 className="text-3xl font-semibold">BFSG-Schnellcheck</h1>
            <p className="max-w-prose text-muted-foreground">
                Prüft eine Webseite auf Barrierefreiheitsfehler, die sich
                automatisch finden lassen.
            </p>
        </main>
    );
}

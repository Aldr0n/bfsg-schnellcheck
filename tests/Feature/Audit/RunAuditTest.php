<?php

use App\Enums\AccessibilityRule;
use App\Enums\AuditStatus;
use App\Jobs\RunAudit;
use App\Models\Audit;
use App\Support\DnsResolver;
use Illuminate\Support\Facades\Http;

dataset('html with one violation', [
    'image without alt' => [
        AccessibilityRule::ImageAlt,
        '<!doctype html><html lang="de"><head><title>Start</title></head><body><img src="logo.png"></body></html>',
        '<img src="logo.png">',
    ],
    'document without lang' => [
        AccessibilityRule::DocumentLanguage,
        '<!doctype html><html><head><title>Start</title></head><body><p>Hallo</p></body></html>',
        '<html>',
    ],
    'document with empty title' => [
        AccessibilityRule::DocumentTitle,
        '<!doctype html><html lang="de"><head><title> </title></head><body><p>Hallo</p></body></html>',
        '<head>',
    ],
    'document without title' => [
        AccessibilityRule::DocumentTitle,
        '<!doctype html><html lang="de"><head></head><body><p>Hallo</p></body></html>',
        '<head>',
    ],
    'input without label' => [
        AccessibilityRule::FormLabel,
        '<!doctype html><html lang="de"><head><title>Start</title></head><body><label for="other">Name</label><input type="text" id="name"></body></html>',
        '<input type="text" id="name">',
    ],
    'image input without alt' => [
        AccessibilityRule::FormLabel,
        '<!doctype html><html lang="de"><head><title>Start</title></head><body><input type="image" src="go.png"></body></html>',
        '<input type="image" src="go.png">',
    ],
    'select without label' => [
        AccessibilityRule::FormLabel,
        '<!doctype html><html lang="de"><head><title>Start</title></head><body><select name="city"><option>Berlin</option><option>Hamburg</option></select></body></html>',
        '<select name="city">',
    ],
    'link with only an empty image' => [
        AccessibilityRule::LinkText,
        '<!doctype html><html lang="de"><head><title>Start</title></head><body><a href="/kontakt"><img src="mail.png" alt=""></a></body></html>',
        '<a href="/kontakt"><img src="mail.png" alt=""></a>',
    ],
    'link spread over several lines' => [
        AccessibilityRule::LinkText,
        "<!doctype html><html lang=\"de\"><head><title>Start</title></head><body><a href=\"/kontakt\">\n    <img src=\"mail.png\" alt=\"\">\n</a></body></html>",
        '<a href="/kontakt"> <img src="mail.png" alt=""> </a>',
    ],
]);

dataset('html without violations', [
    'decorative image' => [
        '<!doctype html><html lang="de"><head><title>Start</title></head><body><img src="deko.png" alt=""></body></html>',
    ],
    'hidden image without alt' => [
        '<!doctype html><html lang="de"><head><title>Start</title></head><body><img src="logo.svg" aria-hidden="true"><img src="a.png" role="presentation"><img src="b.png" role="none"></body></html>',
    ],
    'image with alt' => [
        '<!doctype html><html lang="de"><head><title>Start</title></head><body><img src="logo.png" alt="Logo"></body></html>',
    ],
    'document with lang' => [
        '<!doctype html><html lang="en"><head><title>Home</title></head><body><p>Hello</p></body></html>',
    ],
    'document with title' => [
        '<!doctype html><html lang="de"><head><title>Kontakt</title></head><body><p>Hallo</p></body></html>',
    ],
    'fields with every kind of label' => [
        '<!doctype html><html lang="de"><head><title>Start</title></head><body>'
        .'<label for="a">A</label><input id="a">'
        .'<label>B <select name="b"></select></label>'
        .'<textarea aria-label="C"></textarea>'
        .'<span id="d">D</span><input aria-labelledby="d">'
        .'<input type="image" src="go.png" alt="Suchen">'
        .'<input type="hidden" name="token"><input type="submit"><input type="reset"><input type="button">'
        .'</body></html>',
    ],
    'links with every kind of name' => [
        '<!doctype html><html lang="de"><head><title>Start</title></head><body>'
        .'<a href="/a">Start</a>'
        .'<a href="/b" aria-label="Kontakt"></a>'
        .'<a href="/c" title="Hilfe"></a>'
        .'<a href="/d"><img src="m.png" alt="Mail"></a>'
        .'<a href="/e"><svg aria-label="Suche"></svg></a>'
        .'<a name="anchor"></a>'
        .'</body></html>',
    ],
]);

beforeEach(function () {
    $this->mock(DnsResolver::class)->shouldReceive('resolveIpAddresses')->andReturn(['93.184.215.14']);
});

it('stores the finding of a violation', function (AccessibilityRule $rule, string $html, string $snippet) {
    Http::fake(['example.com/*' => Http::response($html, 200, ['Content-Type' => 'text/html'])]);
    $audit = Audit::factory()->create(['url' => 'https://example.com/']);

    RunAudit::dispatchSync($audit);

    $audit->refresh();
    expect($audit->status)->toBe(AuditStatus::Done)
        ->and($audit->findings)->toHaveCount(1)
        ->and($audit->findings->first()->accessibility_rule)->toBe($rule)
        ->and($audit->findings->first()->snippet)->toBe($snippet);
})->with('html with one violation');

it('ends html without violations as done without findings', function (string $html) {
    Http::fake(['example.com/*' => Http::response($html, 200, ['Content-Type' => 'text/html'])]);
    $audit = Audit::factory()->create(['url' => 'https://example.com/']);

    RunAudit::dispatchSync($audit);

    $audit->refresh();
    expect($audit->status)->toBe(AuditStatus::Done)
        ->and($audit->findings)->toBeEmpty();
})->with('html without violations');

it('stores how many elements each rule checked', function () {
    $html = '<!doctype html><html lang="de"><head><title>Start</title></head><body>'
        .'<img src="a.png" alt="A"><img src="b.png"><img src="c.png" aria-hidden="true">'
        .'<label for="name">Name</label><input id="name"><input type="text" id="other"><input type="hidden" name="token">'
        .'<a href="/a">A</a><a href="/b">B</a><a href="/c"></a><a name="anchor"></a>'
        .'</body></html>';
    Http::fake(['example.com/*' => Http::response($html, 200, ['Content-Type' => 'text/html'])]);
    $audit = Audit::factory()->create(['url' => 'https://example.com/']);

    RunAudit::dispatchSync($audit);

    expect($audit->refresh()->checked_counts)->toBe([
        'image_alt' => 2,
        'document_language' => 1,
        'document_title' => 1,
        'form_label' => 2,
        'link_text' => 3,
    ]);
});

it('ends as failed with the message of the failed fetch', function () {
    $audit = Audit::factory()->create(['url' => 'http://10.0.0.5/']);

    RunAudit::dispatchSync($audit);

    $audit->refresh();
    expect($audit->status)->toBe(AuditStatus::Failed)
        ->and($audit->error_message)->toBe('Diese Adresse zeigt auf ein internes Netz.');
});

it('ends as failed with a message to try again later after an unexpected error', function () {
    Http::fake(['example.com/*' => fn () => throw new RuntimeException('boom')]);
    $audit = Audit::factory()->create(['url' => 'https://example.com/']);

    expect(fn () => RunAudit::dispatchSync($audit))->toThrow(RuntimeException::class, 'boom');

    $audit->refresh();
    expect($audit->status)->toBe(AuditStatus::Failed)
        ->and($audit->error_message)->toBe('Beim Prüfen ist ein unerwarteter Fehler aufgetreten. Versuch es später noch einmal.');
});

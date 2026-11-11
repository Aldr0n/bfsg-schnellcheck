<?php

use App\Exceptions\UrlFetchException;
use App\Services\UrlFetchService;
use App\Support\DnsResolver;
use Illuminate\Support\Facades\Http;

it('returns the html of a public page', function () {
    $this->mock(DnsResolver::class)->shouldReceive('resolveIpAddresses')->andReturn(['93.184.215.14']);
    Http::fake(['example.com/*' => Http::response('<html>Hallo</html>', 200, ['Content-Type' => 'text/html; charset=utf-8'])]);

    expect(app(UrlFetchService::class)->fetchHtml('https://example.com/'))->toBe('<html>Hallo</html>');
});

it('rejects a host when only one of its addresses is private', function () {
    $this->mock(DnsResolver::class)->shouldReceive('resolveIpAddresses')->andReturn(['93.184.215.14', '10.0.0.5']);
    Http::fake();

    expect(fn () => app(UrlFetchService::class)->fetchHtml('https://example.com/'))
        ->toThrow(UrlFetchException::class, 'Diese Adresse zeigt auf ein internes Netz.');
    Http::assertNothingSent();
});

it('rejects an ip address in the url without asking dns', function () {
    $this->mock(DnsResolver::class)->shouldNotReceive('resolveIpAddresses');
    Http::fake();

    expect(fn () => app(UrlFetchService::class)->fetchHtml('http://127.0.0.1/'))
        ->toThrow(UrlFetchException::class, 'Diese Adresse zeigt auf ein internes Netz.');
    Http::assertNothingSent();
});

it('rejects a response that is not html', function () {
    $this->mock(DnsResolver::class)->shouldReceive('resolveIpAddresses')->andReturn(['93.184.215.14']);
    Http::fake(['example.com/*' => Http::response('%PDF-1.7', 200, ['Content-Type' => 'application/pdf'])]);

    expect(fn () => app(UrlFetchService::class)->fetchHtml('https://example.com/report.pdf'))
        ->toThrow(UrlFetchException::class, 'Unter dieser Adresse liegt keine HTML-Seite.');
});

it('rejects schemes other than http and https', function () {
    Http::fake();

    expect(fn () => app(UrlFetchService::class)->fetchHtml('file:///etc/passwd'))
        ->toThrow(UrlFetchException::class, 'Nur http- und https-Adressen sind erlaubt.');
    Http::assertNothingSent();
});

it('rejects an unparseable address', function () {
    Http::fake();

    expect(fn () => app(UrlFetchService::class)->fetchHtml('http:///example.com:abc'))
        ->toThrow(UrlFetchException::class, 'Die Adresse ist ungültig.');
    Http::assertNothingSent();
});

it('rejects a host that resolves to an internal network', function (string $url, string $ipAddress) {
    $this->mock(DnsResolver::class)->shouldReceive('resolveIpAddresses')->andReturn([$ipAddress]);
    Http::fake();

    expect(fn () => app(UrlFetchService::class)->fetchHtml($url))
        ->toThrow(UrlFetchException::class, 'Diese Adresse zeigt auf ein internes Netz.');
    Http::assertNothingSent();
})->with([
    'localhost' => ['http://localhost/', '127.0.0.1'],
    'private network' => ['https://intranet.example.com/', '10.0.0.5'],
]);

it('rejects a redirect to a private ip', function () {
    $this->mock(DnsResolver::class)->shouldReceive('resolveIpAddresses')->andReturnUsing(fn (string $host) => $host === 'intranet.example.net' ? ['10.0.0.5'] : ['93.184.215.14']);
    Http::fake(['example.com/*' => Http::response('', 302, ['Location' => 'http://intranet.example.net/admin'])]);

    expect(fn () => app(UrlFetchService::class)->fetchHtml('https://example.com/'))
        ->toThrow(UrlFetchException::class, 'Diese Adresse zeigt auf ein internes Netz.');
    Http::assertSentCount(1);
});

it('follows a relative redirect to the final page', function () {
    $this->mock(DnsResolver::class)->shouldReceive('resolveIpAddresses')->andReturn(['93.184.215.14']);
    Http::fake([
        'example.com/old' => Http::response('', 301, ['Location' => '/new']),
        'example.com/new' => Http::response('<html>Neu</html>', 200, ['Content-Type' => 'text/html']),
    ]);

    expect(app(UrlFetchService::class)->fetchHtml('https://example.com/old'))->toBe('<html>Neu</html>');
});

it('gives up after more than five redirects', function () {
    $this->mock(DnsResolver::class)->shouldReceive('resolveIpAddresses')->andReturn(['93.184.215.14']);
    Http::fake(['example.com/*' => Http::response('', 302, ['Location' => '/again'])]);

    expect(fn () => app(UrlFetchService::class)->fetchHtml('https://example.com/'))
        ->toThrow(UrlFetchException::class, 'Die Adresse leitet zu oft weiter.');
});

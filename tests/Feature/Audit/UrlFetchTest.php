<?php

use App\Exceptions\UrlFetchException;
use App\Services\UrlFetchService;
use App\Support\DnsResolver;
use Illuminate\Support\Facades\Http;

it('returns the body of a public page', function () {
    $this->mock(DnsResolver::class)->shouldReceive('resolveIpAddresses')->andReturn(['93.184.215.14']);
    Http::fake(['example.com/*' => Http::response('<html>Hallo</html>', 200)]);

    expect(app(UrlFetchService::class)->fetchHtml('https://example.com/'))->toBe('<html>Hallo</html>');
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

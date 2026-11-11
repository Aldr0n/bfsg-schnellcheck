<?php

namespace App\Services;

use App\Exceptions\UrlFetchException;
use App\Support\DnsResolver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;

class UrlFetchService
{
    public function __construct(private readonly DnsResolver $dnsResolver) {}

    /**
     * The body of the page at the URL. Only public http(s) addresses are fetched.
     *
     * @throws UrlFetchException
     */
    public function fetchHtml(string $url): string
    {
        try {
            $response = Http::withoutRedirecting()
                ->withMiddleware($this->pinPublicIpAddressMiddleware())
                ->get($url);
        } catch (ConnectionException) {
            throw new UrlFetchException('Die Seite ist nicht erreichbar.');
        } catch (InvalidArgumentException) {
            // Guzzle rejects an unparseable URL before any middleware runs.
            throw new UrlFetchException('Die Adresse ist ungültig.');
        }

        if (! $response->successful()) {
            throw new UrlFetchException("Die Seite antwortet mit Status {$response->status()}.");
        }

        return $response->body();
    }

    /**
     * A Guzzle middleware that rejects a request unless its host resolves only to public addresses,
     * and pins the connection to the first of them. This keeps the DNS answer from changing between
     * the check and the request.
     */
    private function pinPublicIpAddressMiddleware(): callable
    {
        return fn (callable $handler) => function (RequestInterface $request, array $options) use ($handler) {
            $uri = $request->getUri();
            $scheme = $uri->getScheme();

            if (! in_array($scheme, ['http', 'https'], true)) {
                throw new UrlFetchException('Nur http- und https-Adressen sind erlaubt.');
            }

            $host = trim($uri->getHost(), '[]');

            if ($host === '') {
                throw new UrlFetchException('Die Adresse enthält keinen Host.');
            }

            $port = $uri->getPort() ?? ($scheme === 'https' ? 443 : 80);
            $ipAddress = $this->resolvePublicIpAddress($host);
            $address = str_contains($ipAddress, ':') ? "[{$ipAddress}]" : $ipAddress;

            $options['curl'][CURLOPT_RESOLVE] = ["{$host}:{$port}:{$address}"];

            return $handler($request, $options);
        };
    }

    /**
     * The first address of the host, after checking that all of its addresses are public.
     */
    private function resolvePublicIpAddress(string $host): string
    {
        $ipAddresses = filter_var($host, FILTER_VALIDATE_IP)
            ? [$host]
            : $this->dnsResolver->resolveIpAddresses($host);

        if ($ipAddresses === []) {
            throw new UrlFetchException('Diese Adresse konnte nicht aufgelöst werden.');
        }

        foreach ($ipAddresses as $ipAddress) {
            if (! filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE)) {
                throw new UrlFetchException('Diese Adresse zeigt auf ein internes Netz.');
            }
        }

        return $ipAddresses[0];
    }
}

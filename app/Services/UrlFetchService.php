<?php

namespace App\Services;

use App\Exceptions\UrlFetchException;
use App\Support\DnsResolver;
use GuzzleHttp\Exception\TooManyRedirectsException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Throwable;

class UrlFetchService
{
    private const MAX_REDIRECTS = 5;

    private const TIMEOUT_SECONDS = 10;

    private const MAX_BODY_BYTES = 5 * 1024 * 1024;

    public function __construct(private readonly DnsResolver $dnsResolver) {}

    /**
     * The body of the page at the URL. Only public http(s) addresses are fetched,
     * and every redirect target is checked like the first URL. The request is
     * aborted after a timeout or once the body exceeds the size limit.
     *
     * @throws UrlFetchException
     */
    public function fetchHtml(string $url): string
    {
        try {
            // The middleware sits below Guzzle's redirect handling, so it runs for every hop.
            $response = Http::withMiddleware($this->pinPublicIpAddressMiddleware())
                ->timeout(self::TIMEOUT_SECONDS)
                ->withOptions([
                    'allow_redirects' => ['max' => self::MAX_REDIRECTS],
                    // Guzzle does not allow CURLOPT_MAXFILESIZE_LARGE, so the limit is enforced here.
                    'progress' => function (int $downloadTotal, int $downloadedBytes) {
                        if ($downloadTotal > self::MAX_BODY_BYTES || $downloadedBytes > self::MAX_BODY_BYTES) {
                            throw new UrlFetchException('Die Seite ist größer als 5 MB.');
                        }
                    },
                ])
                ->get($url);
        } catch (ConnectionException $exception) {
            throw $this->urlFetchExceptionFor($exception);
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
     * The exception to throw for a failed request: the one Guzzle wrapped somewhere in the chain,
     * otherwise a generic one.
     */
    private function urlFetchExceptionFor(Throwable $exception): UrlFetchException
    {
        if ($exception instanceof UrlFetchException) {
            return $exception;
        }

        if ($exception instanceof TooManyRedirectsException) {
            return new UrlFetchException('Die Adresse leitet zu oft weiter.');
        }

        $previous = $exception->getPrevious();

        return $previous === null
            ? new UrlFetchException('Die Seite ist nicht erreichbar.')
            : $this->urlFetchExceptionFor($previous);
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

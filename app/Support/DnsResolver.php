<?php

namespace App\Support;

class DnsResolver
{
    /**
     * The IPv4 and IPv6 addresses of the host, empty if it does not resolve.
     *
     * @return list<string>
     */
    public function resolveIpAddresses(string $host): array
    {
        $ipAddresses = [];

        foreach (dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $record) {
            $ipAddresses[] = $record['ip'] ?? $record['ipv6'];
        }

        return $ipAddresses;
    }
}

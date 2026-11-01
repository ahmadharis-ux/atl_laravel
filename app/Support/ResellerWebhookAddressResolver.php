<?php

namespace App\Support;

class ResellerWebhookAddressResolver
{
    /**
     * Resolve a webhook host and reject the entire result if any address is
     * private or reserved, so the caller can pin the accepted DNS answers.
     *
     * @return array<int, string>|null
     */
    public function resolvePublicAddresses(string $host): ?array
    {
        $host = strtolower(trim($host, '[]'));

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return $this->isPublicAddress($host) ? [$host] : null;
        }

        $records = $this->lookupDnsRecords($host);

        if ($records === false) {
            return null;
        }

        $addresses = [];

        foreach ($records as $record) {
            $address = match ($record['type'] ?? null) {
                'A' => $record['ip'] ?? null,
                'AAAA' => $record['ipv6'] ?? null,
                default => null,
            };

            if (! is_string($address)) {
                continue;
            }

            if (! $this->isPublicAddress($address)) {
                return null;
            }

            $addresses[] = $address;
        }

        $addresses = array_values(array_unique($addresses));

        return $addresses === [] ? null : $addresses;
    }

    /**
     * @param  array<int, string>  $addresses
     * @return array<int, string>
     */
    public function curlResolveEntries(string $host, int $port, array $addresses): array
    {
        return array_map(
            static fn (string $address): string => $host.':'.$port.':'.(
                str_contains($address, ':') ? '['.$address.']' : $address
            ),
            $addresses,
        );
    }

    private function isPublicAddress(string $address): bool
    {
        return filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }

    /**
     * @return array<int, array<string, mixed>>|false
     */
    protected function lookupDnsRecords(string $host): array|false
    {
        return dns_get_record($host, DNS_A | DNS_AAAA);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class TrustCloudflareProxies extends TrustProxies
{
    public function handle(Request $request, Closure $next): Response
    {
        $remoteAddress = $request->server->get('REMOTE_ADDR');
        $cloudflareProxies = config('cloudflare.proxies', []);

        if (is_string($remoteAddress) && IpUtils::checkIp($remoteAddress, $cloudflareProxies)) {
            $clientAddress = $request->headers->get('CF-Connecting-IP');

            if (is_string($clientAddress) && filter_var($clientAddress, FILTER_VALIDATE_IP)) {
                $request->headers->set('X-Forwarded-For', $clientAddress);
            } else {
                $request->headers->remove('X-Forwarded-For');
            }
        }

        return parent::handle($request, $next);
    }

    protected function proxies(): array|string|null
    {
        return config('cloudflare.proxies', []);
    }
}

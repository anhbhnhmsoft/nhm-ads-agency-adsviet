<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTelegramIp
{
    /**
     * Danh sách IP của Telegram chính thức.
     * Nguồn: https://core.telegram.org/bots/webhooks#the-short-version
     */
    protected array $allowedIps = [
        '149.154.160.0/20',
        '91.108.4.0/22',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        if (!$this->isIpAllowed($ip)) {
            abort(403, 'Access denied');
        }
        return $next($request);
    }
    private function isIpAllowed(string $ip): bool
    {
        foreach ($this->allowedIps as $subnet) {
            if ($this->ipInRange($ip, $subnet)) {
                return true;
            }
        }
        return false;
    }
    private function ipInRange(string $ip, string $subnet): bool
    {
        [$subnet, $mask] = explode('/', $subnet);
        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet);
    }
}

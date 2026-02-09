<?php

namespace App\Http\Middleware;

use App\Models\InstanceSettings;
use App\Models\Server;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Spatie\Url\Url;
use Symfony\Component\HttpFoundation\Response;

/**
 * Automatically redirects IP-based requests to the configured FQDN domain.
 *
 * Safety mechanisms:
 * - DNS is validated and cached (5-min TTL). If DNS fails, redirect is disabled.
 * - API, webhook, terminal, and health check routes are never redirected.
 * - Emergency bypass: append ?no-redirect to any URL to skip redirect for the entire session.
 *   Use ?redirect to re-enable redirects.
 * - No redirect during initial setup (no FQDN configured).
 */
class RedirectToFqdn
{
    /**
     * Routes that should never be redirected (they must work via IP).
     */
    private const SKIP_PATTERNS = [
        'api/*',
        'webhooks/*',
        'terminal/auth',
        'terminal/auth/ips',
    ];

    /**
     * Cookie name for the emergency bypass.
     * When set, redirects are skipped for the entire browser session.
     */
    private const BYPASS_COOKIE = 'coolify_no_redirect';

    public function handle(Request $request, Closure $next): Response
    {
        // Skip for specific route patterns (API, webhooks, terminal)
        if ($request->is(...self::SKIP_PATTERNS)) {
            return $next($request);
        }

        // Emergency bypass: ?no-redirect sets a session cookie to disable redirect
        // for all subsequent requests. Use ?redirect to re-enable.
        if ($request->has('no-redirect')) {
            $response = $next($request);
            $response->headers->setCookie(cookie(self::BYPASS_COOKIE, '1', 0, '/', null, false, false));

            return $response;
        }

        // Clear the bypass cookie when ?redirect is used
        if ($request->has('redirect')) {
            $response = $next($request);
            $response->headers->setCookie(cookie()->forget(self::BYPASS_COOKIE));

            return $response;
        }

        // Skip if bypass cookie is active
        if ($request->cookie(self::BYPASS_COOKIE)) {
            return $next($request);
        }

        try {
            $settings = InstanceSettings::get();
        } catch (\Exception $e) {
            // Instance settings table may not exist yet (during installation)
            return $next($request);
        }

        // Auto-redirect must be explicitly enabled in Settings > General
        if (! $settings->is_auto_redirect_enabled) {
            return $next($request);
        }

        $fqdn = $settings->fqdn;

        // No FQDN configured -- nothing to redirect to
        if (empty($fqdn)) {
            return $next($request);
        }

        $fqdnUrl = Url::fromString($fqdn);
        $fqdnHost = $fqdnUrl->getHost();

        // Already accessing via the configured FQDN -- no redirect needed
        if (strtolower($request->getHost()) === strtolower($fqdnHost)) {
            return $next($request);
        }

        // Check if DNS is valid (cached for 5 minutes)
        $dnsValid = Cache::remember('fqdn_redirect_dns_valid', 300, function () use ($fqdn, $settings) {
            return $this->isDnsValid($fqdn, $settings);
        });

        if (! $dnsValid) {
            return $next($request);
        }

        // Build the redirect URL: FQDN base + original path + query string
        $redirectUrl = $fqdn.$request->getRequestUri();

        return redirect()->away($redirectUrl, 301);
    }

    /**
     * Validate that the FQDN DNS A-record points to this server's IP.
     *
     * Uses the existing validateDNSEntry() helper when DNS validation is enabled.
     * Falls back to a simple DNS resolution check when DNS validation is disabled.
     */
    private function isDnsValid(string $fqdn, InstanceSettings $settings): bool
    {
        try {
            // If the existing DNS validation system is enabled, use it
            $server = Server::find(0);
            if ($server && $settings->is_dns_validation_enabled) {
                return validateDNSEntry($fqdn, $server);
            }

            // Fallback: simple check that the domain resolves at all
            $url = Url::fromString($fqdn);
            $host = $url->getHost();
            $resolved = gethostbyname($host);

            // gethostbyname returns the hostname unchanged if resolution fails
            return $resolved !== $host;
        } catch (\Exception $e) {
            return false;
        }
    }
}

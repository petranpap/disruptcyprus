<?php

namespace App\Support;

/**
 * The two public origins, never hardcoded: the main site (landing, share pages, admin) and the app (PWA + API).
 *
 * Each comes from .env (APP_URL / FRONTEND_URL) when that value is real. A localhost value seen on a request to a
 * public host (an .env copied from .env.example) is ignored, and the origin is derived from the request instead:
 * the app lives on "app.<site host>". Console code (mails, queued notifications) uses .env; deploy.sh refuses
 * localhost values in production.
 */
final class Urls
{
    public static function site(): string
    {
        $configured = self::configured('app.url');
        if ($configured !== null) {
            return $configured;
        }

        $request = request();
        $host = preg_replace('/^app\./', '', $request->getHost()) ?? $request->getHost();

        return $request->getScheme().'://'.$host.self::port();
    }

    public static function app(): string
    {
        $configured = self::configured('app.frontend_url');
        if ($configured !== null) {
            return $configured;
        }

        $site = parse_url(self::site());
        $host = (string) ($site['host'] ?? 'localhost');

        return ($site['scheme'] ?? 'https').'://app.'.$host.(isset($site['port']) ? ':'.$site['port'] : '');
    }

    /**
     * Cookie domain shared by the site and app hosts (".example.com"); null for localhost and IP addresses.
     */
    public static function sharedCookieDomain(): ?string
    {
        $host = (string) parse_url(self::site(), PHP_URL_HOST);

        return self::isLocal($host) || filter_var($host, FILTER_VALIDATE_IP) ? null : '.'.$host;
    }

    public static function isLocal(string $host): bool
    {
        return in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true) || str_ends_with($host, '.localhost');
    }

    private static function configured(string $key): ?string
    {
        $value = rtrim((string) config($key), '/');
        if ($value === '') {
            return null;
        }

        // A localhost value is only trusted when the request is local too (console/queue requests are "localhost").
        $configuredHost = (string) parse_url($value, PHP_URL_HOST);
        if (self::isLocal($configuredHost) && ! self::isLocal(request()->getHost())) {
            return null;
        }

        return $value;
    }

    private static function port(): string
    {
        $port = request()->getPort();

        return in_array($port, [80, 443, null], true) ? '' : ':'.$port;
    }
}

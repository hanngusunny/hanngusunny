<?php

namespace App\Http\Middleware;

use App\Models\SiteVisitLog;
use App\Support\VisitAnalytics;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackSiteVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $visitorId = '';
        $sessionId = '';
        $isNew = 0;
        $willTrack = $request->isMethod('GET')
            && Schema::hasTable('site_visit_log')
            && ! VisitAnalytics::shouldSkipPath(ltrim($request->path(), '/'));

        if ($willTrack) {
            $visitorId = (string) $request->cookie('sunny_vid', '');
            if ($visitorId === '' || ! preg_match('/^[a-f0-9]{32}$/i', $visitorId)) {
                $visitorId = bin2hex(random_bytes(16));
                $isNew = 1;
            }
            cookie()->queue(cookie('sunny_vid', $visitorId, 60 * 24 * 365, '/', null, false, true, false, 'lax'));
            $sessionId = (string) $request->cookie('sunny_sid', '');
            if ($sessionId === '' || ! preg_match('/^[a-f0-9]{32}$/i', $sessionId)) {
                $sessionId = bin2hex(random_bytes(16));
            }
            cookie()->queue(cookie('sunny_sid', $sessionId, 30, '/', null, false, true, false, 'lax'));
        }

        $response = $next($request);

        if (! $willTrack || $response->getStatusCode() !== 200) {
            return $response;
        }
        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $ua = (string) $request->userAgent();
        $path = ltrim($request->path(), '/');
        $referrer = (string) $request->headers->get('referer', '');
        $refInfo = VisitAnalytics::classifyReferrer($referrer, (string) $request->getHost());
        $uaInfo = VisitAnalytics::parseUserAgent($ua);
        if (in_array($request->query('utm_medium'), ['cpc', 'ppc'], true)) {
            $refInfo['type'] = 'paid';
        }

        try {
            SiteVisitLog::query()->create([
                'visitor_id' => $visitorId,
                'session_id' => $sessionId,
                'is_new_visitor' => $isNew,
                'is_bot' => VisitAnalytics::isBotUserAgent($ua) ? 1 : 0,
                'request_path' => Str::limit('/'.$path, 500, ''),
                'query_string' => Str::limit((string) $request->getQueryString(), 500, ''),
                'page_url' => Str::limit($request->fullUrl(), 1000, ''),
                'http_method' => 'GET',
                'status_code' => 200,
                'referrer' => Str::limit($referrer, 1000, ''),
                'referrer_host' => Str::limit($refInfo['host'], 255, ''),
                'source_type' => $refInfo['type'],
                'search_keyword' => VisitAnalytics::extractSearchKeyword($referrer),
                'utm_source' => Str::limit((string) $request->query('utm_source', ''), 120, ''),
                'utm_medium' => Str::limit((string) $request->query('utm_medium', ''), 120, ''),
                'utm_campaign' => Str::limit((string) $request->query('utm_campaign', ''), 120, ''),
                'utm_term' => Str::limit((string) $request->query('utm_term', ''), 120, ''),
                'utm_content' => Str::limit((string) $request->query('utm_content', ''), 120, ''),
                'client_ip' => (string) $request->ip(),
                'user_agent' => Str::limit($ua, 512, ''),
                'device_type' => $uaInfo['device'],
                'browser' => $uaInfo['browser'],
                'os' => $uaInfo['os'],
                'accept_language' => Str::limit((string) $request->header('Accept-Language', ''), 120, ''),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
        }

        return $response;
    }
}

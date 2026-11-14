<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteVisitLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to, $includeBots] = $this->range($request);
        $q = SiteVisitLog::query()->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);
        if (! $includeBots) {
            $q->where('is_bot', 0);
        }
        $total = (clone $q)->count();
        $visitors = (int) (clone $q)->selectRaw('COUNT(DISTINCT visitor_id) as c')->value('c');
        $pages = (clone $q)->select('request_path', DB::raw('COUNT(*) as c'))->groupBy('request_path')->orderByDesc('c')->limit(30)->get();
        $sources = (clone $q)->select('source_type', DB::raw('COUNT(*) as c'))->groupBy('source_type')->orderByDesc('c')->get();
        $devices = (clone $q)->select('device_type', DB::raw('COUNT(*) as c'))->groupBy('device_type')->orderByDesc('c')->get();
        $log = (clone $q)->orderByDesc('id')->paginate(40)->withQueryString();

        return view('admin.analytics.index', [
            'pageTitle' => 'Báo cáo truy cập',
            'adminNavActive' => 'analytics_reports',
            'preset' => (string) $request->query('preset', '30d'),
            'from' => $from,
            'to' => $to,
            'includeBots' => $includeBots,
            'total' => $total,
            'visitors' => $visitors,
            'pages' => $pages,
            'sources' => $sources,
            'devices' => $devices,
            'log' => $log,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to, $includeBots] = $this->range($request);
        $q = SiteVisitLog::query()->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);
        if (! $includeBots) {
            $q->where('is_bot', 0);
        }

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['created_at', 'request_path', 'source_type', 'search_keyword', 'utm_source', 'device_type', 'client_ip']);
            $q->orderBy('id')->each(function ($row) use ($out) {
                fputcsv($out, [
                    (string) $row->created_at,
                    $row->request_path,
                    $row->source_type,
                    $row->search_keyword,
                    $row->utm_source,
                    $row->device_type,
                    $row->client_ip,
                ]);
            });
            fclose($out);
        }, 'visit-log-'.$from.'_'.$to.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function detail(Request $request, string $section): View
    {
        $allowed = ['trends', 'sources', 'google', 'pages', 'referrers', 'devices', 'visitors', 'utm', 'log'];
        abort_unless(in_array($section, $allowed, true), 404);
        [$from, $to, $includeBots] = $this->range($request);
        $q = SiteVisitLog::query()->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);
        if (! $includeBots) {
            $q->where('is_bot', 0);
        }
        $rows = collect();
        $log = null;
        if ($section === 'trends') {
            $rows = (clone $q)->selectRaw('DATE(created_at) as label, COUNT(*) as c')->groupBy('label')->orderBy('label')->get();
        } elseif ($section === 'sources' || $section === 'google') {
            $src = clone $q;
            if ($section === 'google') {
                $src->where(function ($w) {
                    $w->where('source_type', 'like', '%google%')->orWhere('referrer_host', 'like', '%google%');
                });
            }
            $rows = $src->select('source_type as label', DB::raw('COUNT(*) as c'))->groupBy('source_type')->orderByDesc('c')->get();
        } elseif ($section === 'pages') {
            $rows = (clone $q)->select('request_path as label', DB::raw('COUNT(*) as c'))->groupBy('request_path')->orderByDesc('c')->limit(100)->get();
        } elseif ($section === 'referrers') {
            $rows = (clone $q)->select('referrer_host as label', DB::raw('COUNT(*) as c'))->groupBy('referrer_host')->orderByDesc('c')->limit(100)->get();
        } elseif ($section === 'devices') {
            $rows = (clone $q)->select('device_type as label', DB::raw('COUNT(*) as c'))->groupBy('device_type')->orderByDesc('c')->get();
        } elseif ($section === 'visitors') {
            $rows = (clone $q)->select('visitor_id as label', DB::raw('COUNT(*) as c'))->groupBy('visitor_id')->orderByDesc('c')->limit(100)->get();
        } elseif ($section === 'utm') {
            $rows = (clone $q)->select('utm_source as label', DB::raw('COUNT(*) as c'))->groupBy('utm_source')->orderByDesc('c')->get();
        } else {
            $log = (clone $q)->orderByDesc('id')->paginate(40)->withQueryString();
        }

        return view('admin.analytics.detail', [
            'pageTitle' => 'Báo cáo — '.$section,
            'adminNavActive' => 'analytics_reports',
            'section' => $section,
            'preset' => (string) $request->query('preset', '30d'),
            'from' => $from,
            'to' => $to,
            'includeBots' => $includeBots,
            'rows' => $rows,
            'log' => $log,
        ]);
    }

    /**
     * @return array{0: string, 1: string, 2: bool}
     */
    private function range(Request $request): array
    {
        $preset = (string) $request->query('preset', '30d');
        $today = now()->toDateString();
        if ($preset === 'today') {
            return [$today, $today, $request->query('bots') === '1'];
        }
        if ($preset === '7d') {
            return [now()->subDays(6)->toDateString(), $today, $request->query('bots') === '1'];
        }
        if ($preset === '90d') {
            return [now()->subDays(89)->toDateString(), $today, $request->query('bots') === '1'];
        }
        $from = (string) $request->query('from', now()->subDays(29)->toDateString());
        $to = (string) $request->query('to', $today);

        return [$from, $to, $request->query('bots') === '1'];
    }
}

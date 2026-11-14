<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultLead;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConsultLeadController extends Controller
{
    public function index(\Illuminate\Http\Request $request): View
    {
        $ym = trim((string) $request->query('ym', now()->format('Y-m')));
        if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
            $ym = now()->format('Y-m');
        }
        $q = ConsultLead::query()->orderByDesc('id');
        if ($request->filled('ym')) {
            $q->where('created_at', 'like', $ym.'%');
        }
        $leads = $q->paginate(30)->withQueryString();

        return view('admin.leads.index', [
            'pageTitle' => 'Liên hệ / tư vấn',
            'adminNavActive' => 'consult_leads',
            'leads' => $leads,
            'ym' => $request->filled('ym') ? $ym : '',
        ]);
    }

    public function show(ConsultLead $lead): View
    {
        if ($lead->read_at === null) {
            $lead->read_at = now();
            $lead->save();
        }

        return view('admin.leads.show', [
            'pageTitle' => 'Chi tiết liên hệ',
            'adminNavActive' => 'consult_leads',
            'lead' => $lead,
        ]);
    }

    public function destroy(ConsultLead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()->route('admin.consult-leads.index')->with('content_success', 'Đã xóa.');
    }

    public function export(\Illuminate\Http\Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $ym = trim((string) $request->query('ym', now()->format('Y-m')));
        if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
            $ym = now()->format('Y-m');
        }
        $q = ConsultLead::query()->where('created_at', 'like', $ym.'%')->orderBy('id');

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['id', 'created_at', 'form_origin', 'source_page', 'topic', 'full_name', 'phone', 'province', 'message', 'ip']);
            $q->each(function (ConsultLead $row) use ($out) {
                fputcsv($out, [
                    $row->id,
                    (string) $row->created_at,
                    $row->form_origin,
                    $row->source_page,
                    $row->topic_label,
                    $row->full_name,
                    $row->phone,
                    $row->province,
                    $row->message,
                    $row->client_ip,
                ]);
            });
            fclose($out);
        }, 'consult-leads-'.$ym.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

<?php

namespace App\Services\Leads;

use App\Models\ConsultLead;
use Illuminate\Http\Request;

class ConsultLeadService
{
    public function createFromRequest(Request $request): ConsultLead
    {
        $topic = (string) $request->input('chuDe');

        return ConsultLead::query()->create([
            'topic_key' => $topic,
            'topic_label' => ConsultLead::TOPICS[$topic] ?? $topic,
            'full_name' => trim((string) $request->input('hoTen')),
            'phone' => trim((string) $request->input('sdt')),
            'province' => trim((string) $request->input('tinhThanh')),
            'message' => trim((string) $request->input('cauHoi')),
            'client_ip' => (string) $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'source_page' => $this->sanitizeSource(
                trim((string) $request->input('consult_source')),
                (string) $request->headers->get('referer', '')
            ),
            'form_origin' => in_array($request->input('consult_form_origin'), ['footer', 'modal', 'event', 'landing'], true)
                ? (string) $request->input('consult_form_origin')
                : '',
            'created_at' => now(),
        ]);
    }

    public function createFromLanding(Request $request): ConsultLead
    {
        $program = trim((string) $request->input('program'));
        $topic = match ($program) {
            'D4-1' => 'tieng',
            'D2-1', 'D2-2' => 'dai-hoc',
            'D2-3' => 'sau-dh',
            default => 'khac',
        };
        $score = trim((string) $request->input('academic_score'));
        $cta = trim((string) $request->input('cta_id'));
        $message = 'Landing du-hoc-han-quoc-2026';
        $message .= ' | Chương trình: '.($program !== '' ? $program : 'chưa chọn');
        if ($score !== '') {
            $message .= ' | Điểm: '.$score;
        }
        if ($cta !== '') {
            $message .= ' | CTA: '.$cta;
        }

        return ConsultLead::query()->create([
            'topic_key' => $topic,
            'topic_label' => ConsultLead::TOPICS[$topic] ?? $topic,
            'full_name' => trim((string) $request->input('name')),
            'phone' => trim((string) $request->input('phone')),
            'province' => 'Landing 2026',
            'message' => $message,
            'client_ip' => (string) $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'source_page' => $this->sanitizeSource(
                trim((string) $request->input('landing_page')),
                (string) $request->headers->get('referer', '')
            ) ?: '/du-hoc-han-quoc-2026',
            'form_origin' => 'landing',
            'created_at' => now(),
        ]);
    }

    private function sanitizeSource(string $fromPost, string $referer): string
    {
        $raw = $fromPost !== '' ? $fromPost : $referer;
        $raw = str_replace(["\0", "\r", "\n"], '', trim($raw));
        if ($raw === '') {
            return '';
        }
        if (str_starts_with($raw, '/') && ! str_starts_with($raw, '//')) {
            return mb_substr($raw, 0, 500, 'UTF-8');
        }
        $parts = parse_url($raw);
        if ($parts === false || empty($parts['host'])) {
            return '';
        }
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if ($appHost && strcasecmp((string) $parts['host'], (string) $appHost) !== 0) {
            return '';
        }
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';

        return mb_substr($path.$query, 0, 500, 'UTF-8');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\HotlineClick;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;

class HotlineController extends Controller
{
    public function click(Request $request): Response
    {
        $raw = str_replace(["\0", "\r", "\n"], '', trim((string) $request->input('source_page', '')));
        if (mb_strlen($raw, 'UTF-8') > 500) {
            $raw = mb_substr($raw, 0, 500, 'UTF-8');
        }
        if (! Schema::hasTable('hotline_click')) {
            return response()->noContent();
        }
        HotlineClick::query()->create([
            'source_page' => str_starts_with($raw, '/') && ! str_starts_with($raw, '//') ? $raw : '',
            'client_ip' => (string) $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'created_at' => now(),
        ]);

        return response()->noContent();
    }
}

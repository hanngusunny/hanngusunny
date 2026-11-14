<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class DocsController extends Controller
{
    public function index(): View
    {
        return view('api.docs-hub', $this->urls());
    }

    public function news(): View
    {
        $u = $this->urls();

        return view('api.docs-news', array_merge($u, [
            'sampleLatest' => $u['newsLatest'].'?category_slug=tin-tuc&limit=20',
        ]));
    }

    public function schools(): View
    {
        $u = $this->urls();

        return view('api.docs-schools', array_merge($u, [
            'hub' => $u['schoolsList'].'?category_slug=truong-dai-hoc&limit=8000',
            'hubMap' => $u['schoolsList'].'?category_slug=truong-dai-hoc&has_map=1&limit=8000',
            'postsTree' => $u['schoolsPosts'].'?category_slug=truong-dai-hoc&include_children=yes&limit=8000',
        ]));
    }

    /**
     * @return array<string, string>
     */
    private function urls(): array
    {
        return [
            'docs' => url('/api/docs'),
            'docsNews' => url('/api/docs/tin-tuc'),
            'docsSchools' => url('/api/docs/schools'),
            'newsLatest' => url('/api/news/latest'),
            'schoolsList' => url('/api/schools/list'),
            'schoolsPosts' => url('/api/schools/posts'),
        ];
    }
}

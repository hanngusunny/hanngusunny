<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $scope = (string) $request->query('scope', 'post');
        $pageType = (string) $request->query('page_type', 'category');

        return view('admin.templates.index', [
            'pageTitle' => 'Giao diện nội dung',
            'adminNavActive' => 'content_templates',
            'scope' => $scope,
            'pageType' => $pageType,
            'rows' => ContentTemplate::query()->where('scope', $scope)->where('page_type', $pageType)->orderBy('slug')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'scope' => ['required', 'in:post,page,events,school,major'],
            'page_type' => ['required', 'in:category,article,tag'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:64'],
            'content' => ['nullable', 'string'],
        ]);
        ContentTemplate::query()->create([
            'scope' => $data['scope'],
            'page_type' => $data['page_type'],
            'slug' => $data['slug'],
            'name' => $data['name'],
            'rel_path' => $data['scope'].'/'.$data['page_type'].'/'.$data['slug'].'.php',
            'content' => $data['content'] ?? '',
            'is_active' => 0,
            'created_at' => now(),
        ]);

        return redirect()->route('admin.templates.index', ['scope' => $data['scope'], 'page_type' => $data['page_type']])
            ->with('ok', 'Đã tạo (Laravel render Blade mặc định; file PHP CI4 không được thực thi).');
    }

    public function edit(ContentTemplate $content_template): View
    {
        return view('admin.templates.form', [
            'pageTitle' => 'Sửa giao diện',
            'adminNavActive' => 'content_templates',
            'row' => $content_template,
        ]);
    }

    public function update(Request $request, ContentTemplate $content_template): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);
        $content_template->update($data);

        return back()->with('ok', 'Đã lưu metadata template.');
    }

    public function setActive(ContentTemplate $content_template): RedirectResponse
    {
        ContentTemplate::query()->where('scope', $content_template->scope)->where('page_type', $content_template->page_type)->update(['is_active' => 0]);
        $content_template->update(['is_active' => 1]);

        return back()->with('ok', 'Đã đặt template mặc định.');
    }
}
